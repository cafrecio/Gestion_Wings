<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Rubro, Subrubro};
use App\Services\{CashflowCorreccionService, CashflowService};
use Carbon\Carbon;
use Illuminate\Database\{DeadlockException, QueryException};
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\{DB, Notification, Storage};
use Illuminate\Support\Str;
use Tests\Support\ImportesT24;
use Tests\TestCase;

/** Dos conexiones MariaDB reales: timeout sin escribir y reintento tras el commit. */
#[\PHPUnit\Framework\Attributes\Group('lento')]
class CashflowCorreccionesConcurrentesT27Test extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        // La prueba necesita filas confirmadas, no una transacción de RefreshDatabase.
        // Evita depender del rollback global de migraciones antiguas; la base es descartable.
        $this->beforeApplicationDestroyed(function () {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        });
    }

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T27 solo permite bases descartables de pruebas.');
        }
    }

    private function foto(): array
    {
        return collect(['cashflow_movimientos', 'cashflow_correcciones'])
            ->mapWithKeys(fn ($tabla) => [$tabla => DB::table($tabla)->orderBy('id')->get()->toJson()])->all();
    }

    private function cruce(string $accion): void
    {
        Carbon::setTestNow('2026-10-12 12:00:00'); Notification::fake(); Storage::fake();
        $principal = config('database.default');
        config(['database.connections.t27_otra' => config('database.connections.'.$principal)]);
        $f = new ImportesT24('apertura');
        $rubro = Rubro::create(['nombre' => 'T27 concurrente', 'tipo' => 'EGRESO']);
        $sub = Subrubro::create(['nombre' => 'T27 concurrente', 'rubro_id' => $rubro->id,
            'permitido_para' => 'ADMIN', 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
        $mov = app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $f->admin->id,
            'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $sub->id, 'monto' => '48.50', 'observaciones' => 'Original concurrente']);
        $datos = ['clave' => (string) Str::uuid(), 'version' => 0];
        if ($accion === 'editar') $datos += ['monto' => '75.25', 'fecha' => '2026-10-12',
            'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $sub->id, 'observaciones' => 'Cambio concurrente'];
        $this->assertNotSame(DB::selectOne('SELECT CONNECTION_ID() AS id')->id,
            DB::connection('t27_otra')->selectOne('SELECT CONNECTION_ID() AS id')->id);
        $antes = $this->foto();
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        try {
            DB::connection('t27_otra')->beginTransaction(); DB::setDefaultConnection('t27_otra');
            app(CashflowCorreccionService::class)->$accion($mov->id, $f->admin->id, $datos);
            DB::setDefaultConnection($principal);
            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
            DB::flushQueryLog(); DB::enableQueryLog();
            try {
                app(CashflowCorreccionService::class)->$accion($mov->id, $f->admin->id, $datos);
                $this->fail('La segunda conexión corrigió antes del commit de la primera.');
            } catch (DeadlockException|QueryException $e) {
                $sql = $e instanceof QueryException ? $e : $e->getPrevious();
                $this->assertInstanceOf(QueryException::class, $sql);
                $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
                $this->assertStringContainsString('for update', strtolower($sql->getSql()));
            } finally {
                $consultas = DB::getQueryLog(); DB::disableQueryLog();
                DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
            }
            $this->assertSame([], array_values(array_filter($consultas,
                fn ($q) => preg_match('/^\s*(insert|update|delete)\b/i', $q['query']))));
            $this->assertSame(0, DB::connection()->transactionLevel());
            $this->assertSame($antes, $this->foto(), 'La segunda conexión escribió sin obtener el bloqueo.');
            DB::connection('t27_otra')->commit();
            $confirmado = $this->foto();
            app(CashflowCorreccionService::class)->$accion($mov->id, $f->admin->id, $datos);
            $this->assertSame($confirmado, $this->foto(), 'El reintento duplicó la corrección.');
            $this->assertSame(1, DB::table('cashflow_correcciones')->count());
            $this->assertSame(1, $mov->fresh()->version_correccion);
            $this->assertEquals($accion === 'editar' ? -75.25 : 0, CashflowMovimiento::sum('monto'));
            $this->assertSame($accion === 'eliminar' ? 1 : 0, CashflowMovimiento::where('contra_origen_id', $mov->id)->count());
        } finally {
            DB::disableQueryLog(); DB::setDefaultConnection($principal);
            while (DB::connection('t27_otra')->transactionLevel() > 0) DB::connection('t27_otra')->rollBack();
            DB::purge('t27_otra'); Carbon::setTestNow();
        }
    }

    public function test_dos_conexiones_no_duplican_una_edicion(): void { $this->cruce('editar'); }
    public function test_dos_conexiones_no_duplican_un_contraasiento(): void { $this->cruce('eliminar'); }
}
