<?php

namespace Tests\Feature;

use App\Models\{Alumno, Deporte, DeudaCuota, Grupo, Nivel, Rubro, Subrubro, TipoCaja, User};
use App\Services\PagoCuotaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class CobrarCondonarConcurrenteTest extends TestCase
{
    private array $creados = [];
    private Alumno $alumno;
    private DeudaCuota $deuda;
    private User $admin;
    private User $operativo;
    private TipoCaja $caja;
    private string $principal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->principal = config('database.default');
        $this->assertStringContainsString('testing', DB::connection()->getDatabaseName());
        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh')->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        Storage::fake();
        config(['database.connections.fin07_otra' => config('database.connections.'.$this->principal)]);
        $this->admin = $this->guardar(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
        $this->operativo = $this->guardar(User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]));
        $deporte = $this->guardar(Deporte::create(['nombre' => 'FIN07', 'tipo_liquidacion' => 'HORA', 'activo' => true]));
        $nivel = $this->guardar(Nivel::create(['nombre' => 'FIN07']));
        $grupo = $this->guardar(Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]));
        $this->alumno = $this->guardar(Alumno::create([
            'nombre' => 'Prueba', 'apellido' => 'FIN07', 'dni' => '30988776',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
            'fecha_alta' => '2025-01-01', 'activo' => true,
        ]));
        $this->caja = $this->guardar(TipoCaja::create(['nombre' => 'FIN07', 'activo' => true]));
        if (!Subrubro::where('nombre', 'Cuota Mensual')->exists()) {
            $rubro = $this->guardar(Rubro::create(['nombre' => 'Cuotas FIN07', 'tipo' => 'INGRESO']));
            $this->guardar(Subrubro::create(['nombre' => 'Cuota Mensual', 'rubro_id' => $rubro->id,
                'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true]));
        }
        $this->deuda = $this->guardar(DeudaCuota::create([
            'alumno_id' => $this->alumno->id, 'periodo' => '2026-08',
            'monto_original' => 60000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE',
        ]));
    }

    private function guardar($modelo)
    {
        $this->creados[] = [$modelo->getTable(), $modelo->id];
        return $modelo;
    }

    protected function tearDown(): void
    {
        if (isset($this->principal)) {
            DB::setDefaultConnection($this->principal);
            $otra = DB::connection('fin07_otra');
            while ($otra->transactionLevel() > 0) {
                $otra->rollBack();
            }
            DB::purge('fin07_otra');
        }
        if (isset($this->alumno)) {
            $pagos = DB::table('pagos')->where('alumno_id', $this->alumno->id)->pluck('id');
            DB::table('pago_deuda_cuota')->whereIn('pago_id', $pagos)->delete();
            DB::table('movimientos_operativos')->where('alumno_id', $this->alumno->id)->delete();
            DB::table('pagos')->whereIn('id', $pagos)->delete();
            DB::table('cajas_operativas')->where('usuario_operativo_id', $this->operativo->id)->delete();
        }
        foreach (array_reverse($this->creados) as [$tabla, $id]) {
            DB::table($tabla)->where('id', $id)->delete();
        }
        parent::tearDown();
    }

    private function cobrar(float $monto): void
    {
        app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $this->alumno->id, 'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_id' => $this->caja->id, 'items' => [['periodo' => '2026-08', 'monto' => $monto]],
        ]);
    }

    private function condonar(): void
    {
        app(PagoCuotaService::class)->condonarDeuda($this->deuda->id, 'Excepcion autorizada FIN07', $this->admin->id);
    }

    // Ejecuta el primer servicio real en otra conexión y deja pendiente su commit.
    private function iniciarOtra(callable $accion): void
    {
        DB::connection('fin07_otra')->beginTransaction();
        DB::setDefaultConnection('fin07_otra');
        try {
            $accion();
        } finally {
            DB::setDefaultConnection($this->principal);
        }
    }

    private function comprobarEspera(callable $accion): void
    {
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $accion();
            $this->fail('La segunda operación avanzó sin esperar el bloqueo.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
            // Sin el arreglo también bloquea el UPDATE: eso no prueba lectura actual.
            // Exigimos que el SQL REAL que espera sea SELECT FOR UPDATE, no guardar tarde.
            $this->assertStringContainsString('for update', strtolower($e->getSql()));
            $this->assertStringStartsWith('select', strtolower($e->getSql()));
        } finally {
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
        }
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
        }
        $this->assertSame(0, DB::connection()->transactionLevel());
    }

    public function test_parcial_en_curso_bloquea_condonacion_y_solo_se_perdona_saldo_actual(): void
    {
        $this->iniciarOtra(fn () => $this->cobrar(10000));
        $this->comprobarEspera(fn () => $this->condonar());
        $this->assertSame('PENDIENTE', $this->deuda->fresh()->estado);
        DB::connection('fin07_otra')->commit();
        $antes = DB::table('pago_deuda_cuota')->where('deuda_cuota_id', $this->deuda->id)->get()->toJson();
        $this->actingAs($this->admin)->post(route('web.deudas.condonar', $this->deuda->id),
            ['motivo' => 'Excepcion autorizada FIN07'])->assertSessionHas('success');
        $deuda = $this->deuda->fresh();
        $this->assertSame('CONDONADA', $deuda->estado);
        $this->assertSame('60000.00', $deuda->monto_original);
        $this->assertSame('10000.00', $deuda->monto_pagado);
        $this->assertStringContainsString('Saldo condonado: 50000.00', $deuda->observaciones);
        $this->assertSame($antes, DB::table('pago_deuda_cuota')->where('deuda_cuota_id', $deuda->id)->get()->toJson());
        $this->assertSame(10000.0, (float) DB::table('movimientos_operativos')->where('alumno_id', $this->alumno->id)->sum('monto'));
        $this->assertSame('COMPLETADO', DB::table('pagos')->where('alumno_id', $this->alumno->id)->value('estado'));
    }

    public function test_condonacion_en_curso_bloquea_cobro_y_despues_lo_rechaza_sin_escrituras(): void
    {
        $this->iniciarOtra(fn () => $this->condonar());
        $this->comprobarEspera(fn () => $this->cobrar(10000));
        DB::connection('fin07_otra')->commit();
        try {
            $this->cobrar(10000);
            $this->fail('Una deuda condonada no admite cobro.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('fue condonada, no admite pagos', $e->getMessage());
        }
        $this->assertSame('CONDONADA', $this->deuda->fresh()->estado);
        $this->assertSame('0.00', $this->deuda->fresh()->monto_pagado);
        $this->assertSame(0, DB::table('pagos')->where('alumno_id', $this->alumno->id)->count());
        $this->assertSame(0, DB::table('pago_deuda_cuota')->where('deuda_cuota_id', $this->deuda->id)->count());
        $this->assertSame(0, DB::table('movimientos_operativos')->where('alumno_id', $this->alumno->id)->count());
        $this->assertSame(0, DB::table('cajas_operativas')->where('usuario_operativo_id', $this->operativo->id)->count());
    }

    public function test_pago_completo_en_curso_impide_condonar_una_vez_confirmado(): void
    {
        $this->iniciarOtra(fn () => $this->cobrar(60000));
        $this->comprobarEspera(fn () => $this->condonar());
        DB::connection('fin07_otra')->commit();
        $this->actingAs($this->admin)->post(route('web.deudas.condonar', $this->deuda->id),
            ['motivo' => 'Excepcion autorizada FIN07'])->assertSessionHas('error');
        $this->assertSame('PAGADA', $this->deuda->fresh()->estado);
        $this->assertSame('60000.00', $this->deuda->fresh()->monto_pagado);
        $this->assertSame(60000.0, (float) DB::table('pago_deuda_cuota')->where('deuda_cuota_id', $this->deuda->id)->sum('monto_aplicado'));
    }
}
