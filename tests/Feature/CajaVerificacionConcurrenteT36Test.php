<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('lento')]
class CajaVerificacionConcurrenteT36Test extends TestCase
{
    private string $principal;
    private User $admin;
    private CajaOperativa $caja;
    private MovimientoOperativo $movimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->principal = config('database.default');
        $this->artisan('migrate:fresh')->assertExitCode(0);
        RefreshDatabaseState::$migrated = true;
        config(['database.connections.t36_otra' => config('database.connections.'.$this->principal)]);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $medio = TipoCaja::create(['nombre' => 'Efectivo T36 concurrente', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingresos T36', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Manual T36', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true]);
        app(CajaService::class)->configurarMostrador($medio->id, $this->admin->id);
        $this->caja = CajaOperativa::create([
            'usuario_operativo_id' => $operativa->id, 'estado' => 'CERRADA',
            'apertura_at' => now()->subHour(), 'cierre_at' => now(),
            'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 100,
            'efectivo_esperado' => 130, 'efectivo_contado' => 130,
            'cambio_retenido' => 100, 'efectivo_retirado' => 30, 'diferencia_efectivo' => 0,
        ]);
        $this->movimiento = MovimientoOperativo::create([
            'caja_operativa_id' => $this->caja->id, 'fecha' => today(),
            'tipo_caja_id' => $medio->id, 'subrubro_id' => $subrubro->id,
            'usuario_id' => $operativa->id, 'monto' => 30, 'estado' => 'ACTIVO',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->principal)) {
            DB::setDefaultConnection($this->principal);
            while (DB::connection('t36_otra')->transactionLevel()) DB::connection('t36_otra')->rollBack();
            DB::purge('t36_otra');
            $this->artisan('migrate:fresh')->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        parent::tearDown();
    }

    private function marcar(bool $valor): void
    {
        app(CajaService::class)->verificarMovimiento($this->caja->id, $this->movimiento->id,
            $this->admin->id, $valor, $this->movimiento->huellaVerificacion());
    }

    private function otra(callable $accion): void
    {
        DB::connection('t36_otra')->beginTransaction();
        DB::setDefaultConnection('t36_otra');
        try { $accion(); } finally { DB::setDefaultConnection($this->principal); }
    }

    private function bloqueadaSinEscribir(callable $accion): void
    {
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $accion();
            $this->fail('La segunda conexión avanzó sin esperar.');
        } catch (\Illuminate\Database\DeadlockException|QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
        }
        foreach ($queries as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
    }

    public function test_validar_espera_la_verificacion_y_refleja_una_sola_vez(): void
    {
        $this->otra(fn () => $this->marcar(true));
        $this->bloqueadaSinEscribir(fn () => app(CajaService::class)->validarCaja($this->caja->id, $this->admin->id));
        DB::connection('t36_otra')->commit();
        app(CajaService::class)->validarCaja($this->caja->id, $this->admin->id);
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
        $this->assertTrue($this->movimiento->fresh()->estaVerificado());
        $this->assertDatabaseCount('cashflow_movimientos', 1);
    }

    public function test_destildar_espera_validacion_y_no_altera_una_caja_validada(): void
    {
        $this->marcar(true);
        $this->otra(fn () => app(CajaService::class)->validarCaja($this->caja->id, $this->admin->id));
        $this->bloqueadaSinEscribir(fn () => $this->marcar(false));
        DB::connection('t36_otra')->commit();
        try {
            $this->marcar(false);
            $this->fail('Se destildó una caja ya validada.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $this->assertTrue($this->movimiento->fresh()->estaVerificado());
        $this->assertDatabaseCount('cashflow_movimientos', 1);
    }
}
