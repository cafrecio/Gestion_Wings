<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Deporte, DeudaCuota, Grupo, Nivel, TipoCaja, User};
use App\Services\{InscripcionService, PagoCuotaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;
use Symfony\Component\Process\Process;

#[\PHPUnit\Framework\Attributes\Group('lento')]
class PerdonConcurrenteT33Test extends TestCase
{
    private string $principal;
    private Alumno $alumno;
    private User $admin;
    private CargoAlumno $cargo;
    private DeudaCuota $cuota;
    private ?Process $segundoProceso = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->principal = config('database.default');
        if (!in_array(DB::connection()->getDatabaseName(), ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T33 requiere una base descartable de pruebas.');
        }
        $this->artisan('migrate:fresh')->assertExitCode(0);
        RefreshDatabaseState::$migrated = true;
        $this->seed(CatalogosSeeder::class);
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
        \App\Models\ReglaPrimerPago::query()->delete();
        Carbon::setTestNow('2026-10-13 12:00:00');
        Storage::fake();
        config(['database.connections.t33_otra' => config('database.connections.'.$this->principal)]);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Prueba', 'apellido' => 'Concurrente T33', 'dni' => '33999002',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => '2026-09-01',
            'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'activo' => true]);
        app(InscripcionService::class)->sincronizar($this->alumno, $this->admin->id);
        $this->cargo = app(InscripcionService::class)->cargo($this->alumno->dni);
        $this->cuota = DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10',
            'monto_original' => 48000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }

    protected function tearDown(): void
    {
        if ($this->segundoProceso?->isRunning()) $this->segundoProceso->stop();
        if (isset($this->principal)) {
            DB::setDefaultConnection($this->principal);
            while (DB::connection('t33_otra')->transactionLevel()) DB::connection('t33_otra')->rollBack();
            DB::purge('t33_otra');
            $this->artisan('migrate:fresh');
            RefreshDatabaseState::$migrated = true;
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function perdonar(bool $inscripcion): void
    {
        if ($inscripcion) app(InscripcionService::class)->condonar($this->cargo->id, 'Perdón concurrente autorizado', $this->admin->id);
        else app(PagoCuotaService::class)->condonarDeuda($this->cuota->id, 'Perdón concurrente autorizado', $this->admin->id);
    }

    private function cobrar(float $importe, bool $inscripcion): void
    {
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
            'usuario_admin_id' => $this->admin->id, 'tipo_caja_id' => TipoCaja::first()->id,
            'items' => $inscripcion ? [] : [['periodo' => '2026-10', 'monto' => $importe]],
            'monto_entregado' => $importe, 'fecha_pago' => '2026-10-13']);
    }

    private function cruzar(callable $primero, callable $segundo): void
    {
        DB::connection('t33_otra')->beginTransaction();
        DB::setDefaultConnection('t33_otra');
        try { $primero(); } finally { DB::setDefaultConnection($this->principal); }
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::flushQueryLog(); DB::enableQueryLog();
        try {
            $segundo();
            $this->fail('El segundo pedido no esperó al primero.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
            $this->assertStringContainsString('for update', strtolower($e->getSql()));
            $this->assertStringContainsString('inscripcion_personas', $e->getSql());
        } finally {
            $consultas = DB::getQueryLog(); DB::disableQueryLog();
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
        }
        foreach ($consultas as $consulta) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $consulta['query']);
        $this->assertSame(0, DB::connection()->transactionLevel());
        DB::connection('t33_otra')->commit();
    }

    private function rechazo(callable $accion, string $mensaje): void
    {
        $antes = collect(['pagos', 'pago_deuda_cuota', 'pago_cargo_alumno', 'cashflow_movimientos', 'cargo_alumno_eventos'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->get()->toJson()])->all();
        try { $accion(); $this->fail('El segundo pedido fue aceptado.'); }
        catch (\Exception $e) { $this->assertStringContainsString($mensaje, $e->getMessage()); }
        foreach ($antes as $t => $json) $this->assertSame($json, DB::table($t)->get()->toJson());
    }

    private function continuarPedidoReal(callable $primero, bool $inscripcion): void
    {
        DB::connection('t33_otra')->beginTransaction();
        DB::setDefaultConnection('t33_otra');
        try { $primero(); } finally { DB::setDefaultConnection($this->principal); }
        $datos = ['accion' => 'condonar', 'inscripcion' => $inscripcion, 'cargo' => $this->cargo->id,
            'cuota' => $this->cuota->id, 'alumno' => $this->alumno->id, 'admin' => $this->admin->id,
            'tipo_caja' => TipoCaja::first()->id];
        $this->segundoProceso = new Process([PHP_BINARY, base_path('tests/Support/perdon-t33-worker.php'), json_encode($datos)],
            base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(),
                'DB_HOST' => config('database.connections.'.$this->principal.'.host'),
                'DB_PORT' => (string) config('database.connections.'.$this->principal.'.port'),
                'DB_USERNAME' => config('database.connections.'.$this->principal.'.username'),
                'DB_PASSWORD' => config('database.connections.'.$this->principal.'.password')], timeout: 20);
        $this->segundoProceso->start();
        $inicio = microtime(true);
        $bloqueado = false;
        do {
            if (preg_match('/READY:(\d+)/', $this->segundoProceso->getOutput(), $m)) {
                // PROCESSLIST observa la conexión real. En la suite, MariaDB mantiene
                // este SELECT esperando en Statistics sin publicarlo en INNODB_LOCK_WAITS.
                $espera = DB::selectOne('SELECT TIME, STATE, INFO FROM information_schema.PROCESSLIST WHERE ID = ?', [$m[1]]);
                $bloqueado = $espera && $espera->TIME >= 1
                    && str_contains(strtolower($espera->INFO ?? ''), 'inscripcion_personas')
                    && str_contains(strtolower($espera->INFO ?? ''), 'for update');
            }
            if (!$bloqueado) usleep(20000);
        } while (!$bloqueado && $this->segundoProceso->isRunning() && microtime(true)-$inicio < 10);
        if (!$bloqueado) {
            $estado = isset($m[1]) ? DB::select('SELECT ID, STATE, INFO FROM information_schema.PROCESSLIST WHERE ID = ?', [$m[1]]) : [];
            DB::connection('t33_otra')->rollBack();
            $this->segundoProceso->wait();
            $this->fail('El segundo proceso no aparece esperando en MariaDB: '.json_encode($estado).' '.$this->segundoProceso->getOutput());
        }
        $this->assertTrue($bloqueado);
        $this->assertStringContainsString('SQL:select * from `inscripcion_personas`', $this->segundoProceso->getOutput());
        $this->assertStringNotContainsString('RESULT:', $this->segundoProceso->getOutput());
        DB::connection('t33_otra')->commit();
        $this->segundoProceso->wait();
        $this->assertSame(0, $this->segundoProceso->getExitCode(), $this->segundoProceso->getErrorOutput());
        $this->assertStringContainsString('RESULT:OK', $this->segundoProceso->getOutput());
    }

    public function test_pedido_inscripcion_espera_commit_y_continua_sin_reintento_ni_timeout(): void
    {
        $this->continuarPedidoReal(fn () => $this->cobrar(2000, true), true);
        $this->assertSame('3000.00', $this->cargo->fresh()->monto_condonado);
        $this->assertSame(0.0, $this->cargo->fresh()->saldo_pendiente);
        $this->assertSame(1, DB::table('cargo_alumno_eventos')->where('accion', 'CONDONAR')->count());
    }

    public function test_pedido_cuota_espera_commit_y_continua_sin_reintento_ni_timeout(): void
    {
        $this->cargo->update(['estado' => 'ANULADO']);
        $this->continuarPedidoReal(fn () => $this->cobrar(10000, false), false);
        $this->assertSame('10000.00', $this->cuota->fresh()->monto_pagado);
        $this->assertSame('CONDONADA', $this->cuota->fresh()->estado);
        $this->assertStringContainsString('Saldo condonado: 38000.00', $this->cuota->fresh()->observaciones);
    }

    public function test_cobro_parcial_inscripcion_primero_perdona_solo_tres_mil(): void
    {
        $this->cruzar(fn () => $this->cobrar(2000, true), fn () => $this->perdonar(true));
        $this->perdonar(true);
        $this->assertSame('3000.00', $this->cargo->fresh()->monto_condonado);
        $this->assertSame(2000.0, $this->cargo->fresh()->monto_cobrado);
        $this->assertSame(2000.0, (float) DB::table('cashflow_movimientos')->sum('monto'));
    }

    public function test_cobro_completo_inscripcion_primero_rechaza_perdon(): void
    {
        $this->cruzar(fn () => $this->cobrar(5000, true), fn () => $this->perdonar(true));
        $this->rechazo(fn () => $this->perdonar(true), 'no tiene saldo pendiente');
        $this->assertSame('0.00', $this->cargo->fresh()->monto_condonado);
    }

    public function test_perdon_inscripcion_primero_rechaza_cobro_sin_escribir(): void
    {
        $this->cruzar(fn () => $this->perdonar(true), fn () => $this->cobrar(2000, true));
        $this->rechazo(fn () => $this->cobrar(2000, true), 'importe entregado');
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_dos_perdones_de_inscripcion_se_serializan_y_segundo_rechaza(): void
    {
        $this->cruzar(fn () => $this->perdonar(true), fn () => $this->perdonar(true));
        $this->rechazo(fn () => $this->perdonar(true), 'ya tiene un perdón');
        $this->assertSame(1, DB::table('cargo_alumno_eventos')->where('accion', 'CONDONAR')->count());
    }

    public function test_cobro_parcial_cuota_primero_perdona_saldo_conservando_imputacion(): void
    {
        $this->cargo->update(['estado' => 'ANULADO']);
        $this->cruzar(fn () => $this->cobrar(10000, false), fn () => $this->perdonar(false));
        $this->perdonar(false);
        $this->assertSame('10000.00', $this->cuota->fresh()->monto_pagado);
        $this->assertStringContainsString('Saldo condonado: 38000.00', $this->cuota->fresh()->observaciones);
        $this->assertSame(10000.0, (float) DB::table('pago_deuda_cuota')->sum('monto_aplicado'));
    }

    public function test_cobro_completo_cuota_primero_rechaza_perdon(): void
    {
        $this->cargo->update(['estado' => 'ANULADO']);
        $this->cruzar(fn () => $this->cobrar(48000, false), fn () => $this->perdonar(false));
        $this->rechazo(fn () => $this->perdonar(false), 'Estado actual: PAGADA');
    }

    public function test_perdon_cuota_primero_rechaza_cobro_sin_escribir(): void
    {
        $this->cargo->update(['estado' => 'ANULADO']);
        $this->cruzar(fn () => $this->perdonar(false), fn () => $this->cobrar(10000, false));
        $this->rechazo(fn () => $this->cobrar(10000, false), 'fue condonada');
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_dos_perdones_de_cuota_se_serializan_y_segundo_rechaza(): void
    {
        $this->cruzar(fn () => $this->perdonar(false), fn () => $this->perdonar(false));
        $this->rechazo(fn () => $this->perdonar(false), 'Estado actual: CONDONADA');
        $this->assertSame(1, substr_count($this->cuota->fresh()->observaciones, 'CONDONADA por'));
    }
}
