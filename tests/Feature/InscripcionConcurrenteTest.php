<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class InscripcionConcurrenteTest extends TestCase
{
    private string $principal;
    private array $datos;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->principal = config('database.default');
        $this->assertStringContainsString('testing', DB::connection()->getDatabaseName());
        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh')->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        $this->seed(CatalogosSeeder::class);
        \App\Models\ReglaPrimerPago::query()->delete();
        Carbon::setTestNow('2026-09-24 12:00:00');
        Storage::fake();
        config(['database.connections.ent01_otra' => config('database.connections.'.$this->principal)]);
        $this->usuario = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($this->usuario)->withoutExceptionHandling();
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->datos = ['nombre' => 'Concurrente', 'apellido' => 'Inscripcion', 'dni' => '41999111', 'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111', 'fecha_alta' => '2026-09-23', 'grupo_id' => $grupo->id, 'deporte_id' => $grupo->deporte_id, 'plan_id' => $plan->id];
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->principal);
        if (DB::connection('ent01_otra')->transactionLevel()) DB::connection('ent01_otra')->rollBack();
        DB::purge('ent01_otra');
        Carbon::setTestNow();
        $this->artisan('migrate:fresh');
        RefreshDatabaseState::$migrated = true;
        parent::tearDown();
    }

    private function iniciar(callable $accion): void
    {
        DB::connection('ent01_otra')->beginTransaction();
        DB::setDefaultConnection('ent01_otra');
        try { $accion(); } finally { DB::setDefaultConnection($this->principal); }
    }

    private function esperaSinEscribir(callable $accion): void
    {
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $accion();
            $this->fail('La segunda conexión avanzó sin esperar');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
            $this->assertStringContainsString('for update', strtolower($e->getSql()));
        } finally {
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
        }
        foreach ($queries as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
        DB::connection('ent01_otra')->commit();
    }

    public function test_dos_altas_del_mismo_dni_en_deportes_distintos_se_serializan(): void
    {
        $otro = Grupo::create(['deporte_id' => Deporte::whereKeyNot($this->datos['deporte_id'])->first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $otro->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $datosOtro = array_replace($this->datos, ['deporte_id' => $otro->deporte_id, 'grupo_id' => $otro->id, 'plan_id' => $plan->id]);
        $this->iniciar(fn () => $this->post('/alumnos', $this->datos)->assertRedirect());
        $this->esperaSinEscribir(fn () => $this->post('/alumnos', $datosOtro));
        $this->post('/alumnos', $datosOtro)->assertRedirect();
        $this->assertDatabaseCount('alumnos', 2);
        $this->assertDatabaseCount('cargos_alumno', 1);
    }

    public function test_segundo_cobro_relee_saldo_despues_de_esperar(): void
    {
        $this->post('/alumnos', $this->datos)->assertRedirect();
        $alumno = Alumno::firstOrFail();
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-09', 'monto_original' => 30000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $data = ['alumno_id' => $alumno->id, 'usuario_operativo_id' => $this->usuario->id, 'tipo_caja_id' => TipoCaja::first()->id,
            'fecha_pago' => '2026-09-24', 'items' => [['periodo' => '2026-09', 'monto' => 30000]]];
        $this->iniciar(fn () => app(PagoCuotaService::class)->registrarPagoCuotaOperativo($data + ['monto_entregado' => 3000]));
        $this->esperaSinEscribir(fn () => app(PagoCuotaService::class)->registrarPagoCuotaOperativo($data + ['monto_entregado' => 7000]));
        app(PagoCuotaService::class)->registrarPagoCuotaOperativo($data + ['monto_entregado' => 7000]);
        $this->assertEquals(5000, CargoAlumno::first()->monto_cobrado);
        $this->assertEquals(5000, DeudaCuota::first()->monto_pagado);
        $this->assertEquals(10000, DB::table('movimientos_operativos')->sum('monto'));
    }
}
