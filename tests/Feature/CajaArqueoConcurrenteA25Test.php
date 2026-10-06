<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Dos conexiones reales: timeout sin escritura, commit y reintento con estado actual. */
class CajaArqueoConcurrenteA25Test extends TestCase
{
    private string $principal;
    private User $admin;
    private User $operativa;
    private User $otraOperativa;
    private TipoCaja $medio;
    private Subrubro $ingreso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->principal = config('database.default');
        $this->artisan('migrate:fresh')->assertExitCode(0);
        RefreshDatabaseState::$migrated = true;
        config(['database.connections.a25_otra' => config('database.connections.'.$this->principal)]);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->otraOperativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->medio = TipoCaja::create(['nombre' => 'Efectivo de prueba A25', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingreso A25', 'tipo' => 'INGRESO']);
        $this->ingreso = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Manual A25', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true]);
        app(CajaService::class)->configurarMostrador($this->medio->id, $this->admin->id);
    }

    protected function tearDown(): void
    {
        if (isset($this->principal)) {
            DB::setDefaultConnection($this->principal);
            while (DB::connection('a25_otra')->transactionLevel()) DB::connection('a25_otra')->rollBack();
            DB::purge('a25_otra');
            $this->artisan('migrate:fresh')->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        parent::tearDown();
    }

    private function iniciarOtra(callable $accion): void
    {
        DB::connection('a25_otra')->beginTransaction();
        DB::setDefaultConnection('a25_otra');
        try { $accion(); } finally { DB::setDefaultConnection($this->principal); }
    }

    private function esperarSinEscribir(callable $accion): void
    {
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $accion();
            $this->fail('La segunda operación avanzó antes del bloqueo.');
        } catch (\Illuminate\Database\DeadlockException|QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
        }
        foreach ($queries as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
    }

    private function abrir(User $persona): CajaOperativa
    {
        return app(CajaService::class)->abrirCajaOperativa($persona->id, ['efectivo_inicial' => 10000, 'confirmacion' => true]);
    }

    private function cerrar(CajaOperativa $caja, int $contado = 10000): CajaOperativa
    {
        return app(CajaService::class)->cerrarCajaOperativa($caja->id, $this->operativa->id, false, ['efectivo_contado' => $contado, 'cambio_retenido' => 10000]);
    }

    private function movimiento(CajaOperativa $caja): MovimientoOperativo
    {
        return app(CajaService::class)->registrarMovimientoEnCaja($caja->id, [
            'tipo_caja_id' => $this->medio->id, 'subrubro_id' => $this->ingreso->id, 'monto' => 25000,
        ]);
    }

    public function test_dos_aperturas_no_crean_dos_turnos(): void
    {
        $this->iniciarOtra(fn () => $this->abrir($this->operativa));
        $this->esperarSinEscribir(fn () => $this->abrir($this->otraOperativa));
        DB::connection('a25_otra')->commit();
        $this->assertDatabaseCount('cajas_operativas', 1);
        $this->expectException(ValidationException::class);
        $this->abrir($this->otraOperativa);
    }

    public function test_cerrar_primero_rechaza_movimiento_tardio(): void
    {
        $caja = $this->abrir($this->operativa);
        $this->iniciarOtra(fn () => $this->cerrar($caja));
        $this->esperarSinEscribir(fn () => $this->movimiento($caja));
        DB::connection('a25_otra')->commit();
        $this->assertSame('CERRADA', $caja->fresh()->estado);
        $this->assertDatabaseCount('movimientos_operativos', 0);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('editable');
        $this->movimiento($caja);
    }

    public function test_movimiento_primero_se_incluye_en_el_arqueo(): void
    {
        $caja = $this->abrir($this->operativa);
        $this->iniciarOtra(fn () => $this->movimiento($caja));
        $this->esperarSinEscribir(fn () => $this->cerrar($caja, 35000));
        DB::connection('a25_otra')->commit();
        $cerrada = $this->cerrar($caja, 35000);
        $this->assertSame('35000.00', $cerrada->efectivo_esperado);
        $this->assertSame('0.00', $cerrada->diferencia_efectivo);
        $this->assertDatabaseCount('movimientos_operativos', 1);
    }

    public function test_dos_cierres_no_reescriben_el_conteo(): void
    {
        $caja = $this->abrir($this->operativa);
        $this->iniciarOtra(fn () => $this->cerrar($caja));
        $this->esperarSinEscribir(fn () => $this->cerrar($caja, 20000));
        DB::connection('a25_otra')->commit();
        $this->assertSame('10000.00', $caja->fresh()->efectivo_contado);
        $this->expectException(\Exception::class);
        $this->cerrar($caja, 20000);
    }
}
