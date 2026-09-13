<?php

namespace Tests\Feature;

use App\Models\{Alumno, Deporte, DeudaCuota, Grupo, Nivel, Rubro, Subrubro, TipoCaja, User};
use App\Services\PagoCuotaService;
use App\Services\CajaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

/**
 * FIN-11: servicios reales en dos conexiones MariaDB, tres cruces en ambos órdenes.
 * Sin RefreshDatabase: los datos iniciales deben estar confirmados para la otra conexión.
 * Se fuerza timeout sin escrituras; después del commit se reintenta y verifica dinero,
 * estados e idempotencia. No equivale a una solicitud HTTP que se reanuda automáticamente.
 */
class CobrarCancelarValidarConcurrenteTest extends TestCase
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
        config(['database.connections.fin11_otra' => config('database.connections.'.$this->principal)]);
        $this->admin = $this->guardar(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
        $this->operativo = $this->guardar(User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]));
        $deporte = $this->guardar(Deporte::create(['nombre' => 'FIN11', 'tipo_liquidacion' => 'HORA', 'activo' => true]));
        $nivel = $this->guardar(Nivel::create(['nombre' => 'FIN11']));
        $grupo = $this->guardar(Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]));
        $this->alumno = $this->guardar(Alumno::create([
            'nombre' => 'Prueba', 'apellido' => 'FIN11', 'dni' => '30988776',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
            'fecha_alta' => '2025-01-01', 'activo' => true,
        ]));
        $this->caja = $this->guardar(TipoCaja::create(['nombre' => 'FIN11', 'activo' => true]));
        if (!Subrubro::where('nombre', 'Cuota Mensual')->exists()) {
            $rubro = $this->guardar(Rubro::create(['nombre' => 'Cuotas FIN11', 'tipo' => 'INGRESO']));
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
            $otra = DB::connection('fin11_otra');
            while ($otra->transactionLevel() > 0) {
                $otra->rollBack();
            }
            DB::purge('fin11_otra');
        }
        if (isset($this->alumno)) {
            $pagos = DB::table('pagos')->where('alumno_id', $this->alumno->id)->pluck('id');
            DB::table('pago_deuda_cuota')->whereIn('pago_id', $pagos)->delete();
            DB::table('movimientos_operativos')->where('alumno_id', $this->alumno->id)->delete();
            DB::table('pagos')->whereIn('id', $pagos)->delete();
            DB::table('cashflow_movimientos')->where('tipo_caja_id', $this->caja->id)->delete();
            DB::table('cajas_operativas')->where('usuario_operativo_id', $this->operativo->id)->delete();
        }
        foreach (array_reverse($this->creados) as [$tabla, $id]) {
            DB::table($tabla)->where('id', $id)->delete();
        }
        parent::tearDown();
    }

    private function cobrar(): array
    {
        return app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
            'alumno_id' => $this->alumno->id, 'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_id' => $this->caja->id, 'items' => [['periodo' => '2026-08', 'monto' => 10000]],
        ]);
    }

    private function cancelar(int $id): void
    {
        app(PagoCuotaService::class)->cancelarCobroOperativo($id, 'Prueba concurrente FIN11', $this->operativo->id);
    }

    private function validar(int $id): void
    {
        app(CajaService::class)->validarCaja($id, $this->admin->id);
    }

    private function iniciarOtra(callable $accion): void
    {
        DB::connection('fin11_otra')->beginTransaction();
        DB::setDefaultConnection('fin11_otra');
        try {
            $accion();
        } finally {
            DB::setDefaultConnection($this->principal);
        }
    }

    private function foto(): array
    {
        $foto = [];
        foreach (['deuda_cuotas', 'pagos', 'pago_deuda_cuota', 'movimientos_operativos', 'cajas_operativas', 'cashflow_movimientos'] as $tabla) {
            $foto[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        return $foto;
    }

    private function esperaSinEscribir(callable $accion): void
    {
        $antes = $this->foto();
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $accion();
            DB::connection('fin11_otra')->commit();
            $this->fail('El segundo servicio avanzo sin esperar al primero. Foto final: '.json_encode([
                'deuda_pagado' => $this->deuda->fresh()->monto_pagado,
                'pago' => DB::table('pagos')->where('alumno_id', $this->alumno->id)->value('estado'),
                'movimiento' => DB::table('movimientos_operativos')->where('alumno_id', $this->alumno->id)->value('estado'),
                'caja' => DB::table('cajas_operativas')->where('usuario_operativo_id', $this->operativo->id)->value('estado'),
                'cashflow' => DB::table('cashflow_movimientos')->where('tipo_caja_id', $this->caja->id)->sum('monto'),
            ]));
        } catch (\Illuminate\Database\DeadlockException|QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
            // Laravel envuelve el timeout cuando ocurre dentro de una transacción anidada.
            $sqlError = $e instanceof QueryException ? $e : $e->getPrevious();
            $this->assertInstanceOf(QueryException::class, $sqlError);
            $this->assertStringStartsWith('select', strtolower($sqlError->getSql()));
            $this->assertStringContainsString('for update', strtolower($sqlError->getSql()));
        } finally {
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
        }
        $this->assertSame([], array_values(array_filter($queries,
            fn ($q) => preg_match('/^\s*(insert|update|delete)\b/i', $q['query']))),
            'El segundo escribio antes de obtener el bloqueo compartido.');
        $this->assertSame(0, DB::connection()->transactionLevel());
        $this->assertSame($antes, $this->foto(), 'El intento fallido dejo cambios persistidos.');
    }

    private function cruce(string $primero, string $segundo): void
    {
        $base = $this->cobrar();
        $movId = $base['movimiento']->id;
        $cajaId = $base['movimiento']->caja_operativa_id;
        $acciones = [
            'cobrar' => fn () => $this->cobrar(),
            'cancelar' => fn () => $this->cancelar($movId),
            'validar' => fn () => $this->validar($cajaId),
        ];
        $this->iniciarOtra($acciones[$primero]);
        $this->esperaSinEscribir($acciones[$segundo]);
        DB::connection('fin11_otra')->commit();

        if ($primero === 'validar' && $segundo === 'cancelar') {
            $antes = $this->foto();
            try {
                $acciones[$segundo]();
                $this->fail('Cancelo un cobro de caja ya validada.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('caja esté abierta o rechazada', $e->getMessage());
            }
            $this->assertSame($antes, $this->foto());
        } else {
            $acciones[$segundo]();
        }

        $cancelado = in_array('cancelar', [$primero, $segundo]) && $primero !== 'validar';
        $cobrado = in_array('cobrar', [$primero, $segundo]);
        $monto = 10000 + ($cobrado ? 10000 : 0) - ($cancelado ? 10000 : 0);
        $this->assertSame((float) $monto, (float) $this->deuda->fresh()->monto_pagado);
        $this->assertSame('PENDIENTE', $this->deuda->fresh()->estado);
        $this->assertSame((float) $monto, (float) DB::table('pago_deuda_cuota')->where('deuda_cuota_id', $this->deuda->id)->sum('monto_aplicado'));
        $this->assertSame($cancelado ? 'ANULADO' : 'COMPLETADO', $base['pago']->fresh()->estado);
        $this->assertSame($cancelado ? 'CANCELADO' : 'ACTIVO', $base['movimiento']->fresh()->estado);
        if ($cancelado) {
            $this->assertSame('2026-08', $base['pago']->fresh()->detalle_anulacion['periodos'][0]['periodo']);
        }

        $validada = in_array('validar', [$primero, $segundo]);
        $this->assertSame($validada ? 'VALIDADA' : 'ABIERTA', DB::table('cajas_operativas')->where('id', $cajaId)->value('estado'));
        $cashflow = $validada ? ($primero === 'validar' ? 10000 : $monto) : 0;
        $this->assertSame((float) $cashflow, (float) DB::table('cashflow_movimientos')->where('tipo_caja_id', $this->caja->id)->sum('monto'));
        if ($primero === 'validar' && $segundo === 'cobrar') {
            $nueva = DB::table('cajas_operativas')->where('usuario_operativo_id', $this->operativo->id)->where('estado', 'ABIERTA')->sole();
            $this->assertNotEquals($cajaId, $nueva->id);
            $this->assertSame(1, DB::table('movimientos_operativos')->where('caja_operativa_id', $cajaId)->count());
            $this->assertSame(1, DB::table('movimientos_operativos')->where('caja_operativa_id', $nueva->id)->count());
        }
        if ($validada) {
            $antes = $this->foto();
            $this->validar($cajaId);
            $this->assertSame($antes, $this->foto(), 'Revalidar duplico o altero registros.');
        }
    }

    public function test_cobrar_primero_cancelar_despues(): void { $this->cruce('cobrar', 'cancelar'); }
    public function test_cancelar_primero_cobrar_despues(): void { $this->cruce('cancelar', 'cobrar'); }
    public function test_cobrar_primero_validar_despues(): void { $this->cruce('cobrar', 'validar'); }
    public function test_validar_primero_cobrar_despues(): void { $this->cruce('validar', 'cobrar'); }
    public function test_cancelar_primero_validar_despues(): void { $this->cruce('cancelar', 'validar'); }
    public function test_validar_primero_cancelar_despues(): void { $this->cruce('validar', 'cancelar'); }
}
