<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Dos conexiones MariaDB, con datos confirmados y una lectura vieja deliberada. */
class T88EfectivoConcurrenteTest extends TestCase
{
    public function test_el_segundo_gasto_no_usa_una_foto_vieja_del_efectivo(): void
    {
        $principal = config('database.default');
        $this->assertMatchesRegularExpression('/^wings_testing(?:_(?:codex|claude|gemini))?$/', DB::connection()->getDatabaseName());
        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh')->assertExitCode(0);
            RefreshDatabaseState::$migrated = true;
        }
        config(['database.connections.t88_otra' => config('database.connections.'.$principal)]);
        $u = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $medio = TipoCaja::create(['nombre' => 'Efectivo concurrente T88', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Gasto concurrente T88', 'tipo' => 'EGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Gasto T88', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'es_reservado_sistema' => false, 'activo' => true]);
        $caja = \Tests\Support\CajaDeclarada::crear($u->id, $medio->id);
        $caja->update(['efectivo_inicial' => 100]);
        $datos = ['usuario_operativo_id' => $u->id, 'subrubro_id' => $sub->id, 'tipo_caja_id' => $medio->id,
            'monto' => 60, 'observaciones' => 'Gasto concurrente', 'fecha' => today()->toDateString()];
        $otra = DB::connection('t88_otra');
        try {
            $this->assertNotSame(DB::selectOne('select connection_id() id')->id, $otra->selectOne('select connection_id() id')->id);
            $otra->beginTransaction();
            $this->assertSame(0, $otra->table('movimientos_operativos')->where('caja_operativa_id', $caja->id)->count());
            app(CajaService::class)->registrarMovimientoOperativo($datos);
            DB::setDefaultConnection('t88_otra');
            try {
                app(CajaService::class)->registrarMovimientoOperativo($datos);
                $this->fail('Aceptó otro gasto de60 sobre efectivo restante40 con una foto vieja.');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('aporte', $e->getMessage());
            }
            $otra->rollBack();
            DB::setDefaultConnection($principal);
            $this->assertSame(1, MovimientoOperativo::where('caja_operativa_id', $caja->id)->count());
            $this->assertSame('40.00', app(CajaService::class)->arqueoCaja($caja->id)['efectivo_esperado']);

            // También comprobar la espera real: una escritura retiene la caja;
            // el otro intento agota el timeout antes de emitir INSERT/UPDATE/DELETE.
            DB::beginTransaction();
            CajaOperativa::whereKey($caja->id)->lockForUpdate()->firstOrFail();
            DB::setDefaultConnection('t88_otra');
            $otra->statement('SET SESSION innodb_lock_wait_timeout = 1');
            $otra->flushQueryLog(); $otra->enableQueryLog();
            try {
                app(CajaService::class)->registrarMovimientoOperativo($datos);
                $this->fail('El segundo intento no esperó el bloqueo de caja.');
            } catch (\Illuminate\Database\DeadlockException|QueryException $e) {
                $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
                $sql = $e instanceof QueryException ? $e : $e->getPrevious();
                $this->assertInstanceOf(QueryException::class, $sql);
                $this->assertStringContainsString('for update', strtolower($sql->getSql()));
            } finally {
                $queries = $otra->getQueryLog(); $otra->disableQueryLog();
            }
            $this->assertSame([], array_values(array_filter($queries, fn ($q) => preg_match('/^\s*(insert|update|delete)\b/i', $q['query']))));
            DB::setDefaultConnection($principal); DB::rollBack();
            $this->assertSame(1, MovimientoOperativo::where('caja_operativa_id', $caja->id)->count());
        } finally {
            DB::setDefaultConnection($principal);
            while ($otra->transactionLevel() > 0) $otra->rollBack();
            while (DB::connection()->transactionLevel() > 0) DB::rollBack();
            DB::purge('t88_otra');
            MovimientoOperativo::where('caja_operativa_id', $caja->id)->delete();
            $caja->delete(); $sub->delete(); $rubro->delete(); $medio->delete(); $u->delete();
        }
    }
}
