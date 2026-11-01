<?php

namespace Tests\Feature;

use App\Services\AnulacionTransferenciaCashflowService;
use Illuminate\Database\{DeadlockException, QueryException};
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\TransferenciasT97;
use Tests\TestCase;

/** Transacciones solapadas en dos conexiones MariaDB, como los cruces financieros T27. */
#[\PHPUnit\Framework\Attributes\Group('lento')]
class AnulacionTransferenciasConcurrentesT97Test extends TestCase
{
    use DatabaseMigrations, TransferenciasT97 {
        TransferenciasT97::beforeRefreshingDatabase insteadof DatabaseMigrations;
    }

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->beforeApplicationDestroyed(function () {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        });
    }

    public function test_dos_conexiones_anulando_el_mismo_par_no_duplican_ni_escriben_sin_candado(): void
    {
        $patas = $this->transferir(); $antes = $this->fotoT97();
        $principal = DB::getDefaultConnection();
        config(['database.connections.t97_otra' => config('database.connections.'.$principal)]);
        $conexionA = DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
        $conexionB = DB::connection('t97_otra')->selectOne('SELECT CONNECTION_ID() AS id')->id;
        $this->assertNotSame($conexionA, $conexionB);
        $timeout = DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS valor')->valor;
        $consultas = [];
        try {
            DB::connection('t97_otra')->beginTransaction();
            DB::setDefaultConnection('t97_otra');
            app(AnulacionTransferenciaCashflowService::class)->anular($patas[0]->id, $this->admin->id, ['motivo' => 'Primera conexión']);
            DB::setDefaultConnection($principal);
            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
            DB::flushQueryLog(); DB::enableQueryLog();
            try {
                app(AnulacionTransferenciaCashflowService::class)->anular($patas[1]->id, $this->admin->id, ['motivo' => 'Segunda conexión']);
                $this->fail('La segunda anuló sin esperar el commit de la primera.');
            } catch (DeadlockException|QueryException $e) {
                $sql = $e instanceof QueryException ? $e : $e->getPrevious();
                $this->assertInstanceOf(QueryException::class, $sql);
                $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
                $this->assertStringContainsString('for update', strtolower($sql->getSql()));
            } finally {
                $consultas = DB::getQueryLog(); DB::disableQueryLog();
                DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
            }
            $escrituras = array_values(array_filter($consultas, fn ($q) => preg_match('/^\s*(insert|update|delete)\b/i', $q['query'])));
            $this->assertSame([], $escrituras);
            $this->assertSame(0, DB::connection()->transactionLevel());
            $this->assertSame($antes, $this->fotoT97()); $this->assertCuentasT97(30000000, 40000000);
            DB::connection('t97_otra')->commit();
            $confirmada = $this->fotoT97();
            try {
                app(AnulacionTransferenciaCashflowService::class)->anular($patas[1]->id, $this->admin->id, ['motivo' => 'Segunda conexión']);
                $this->fail('El reintento debió reconocer la anulación confirmada.');
            } catch (ValidationException $e) { $this->assertArrayHasKey('motivo', $e->errors()); }
            $this->assertSame($confirmada, $this->fotoT97());
            $this->assertDatabaseCount('cashflow_movimientos', 4);
            $this->assertSame('Primera conexión', DB::table('cashflow_transferencias')->value('motivo_anulacion'));
            $this->assertSame(2, DB::table('cashflow_movimientos')->whereNotNull('contra_origen_id')->count());
            $this->assertCuentasT97();
            if (getenv('WINGS_EVIDENCIA_T97') === '1') {
                file_put_contents(base_path('docs/06-pruebas/PRU-04/evidencia-t97/concurrencia.json'), json_encode([
                    'base' => config('database.connections.'.$principal.'.database'), 'conexiones' => [$conexionA, $conexionB],
                    'segundo_timeout_antes_de_escribir' => true, 'escrituras_segundo' => $escrituras,
                    'originales' => 2, 'inversos' => 2, 'salida_centavos' => 60000000, 'destino_centavos' => 10000000,
                    'reintento' => 'rechazado: ya anulada, auditoría y movimientos idénticos',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        } finally {
            DB::disableQueryLog(); DB::setDefaultConnection($principal);
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.(int) $timeout);
            while (DB::connection('t97_otra')->transactionLevel() > 0) DB::connection('t97_otra')->rollBack();
            DB::purge('t97_otra');
        }
    }
}
