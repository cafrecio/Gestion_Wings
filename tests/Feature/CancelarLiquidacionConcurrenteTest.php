<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\LiquidacionPagoService;
use App\Services\LiquidacionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FIN-12: Carrera entre pago y cancelación en dos conexiones MariaDB reales.
 *
 * Criterio contractual:
 * - Dos conexiones MariaDB reales: pago contra cancelación en ambos órdenes;
 *   evidencia de espera sin escritura del segundo proceso y estado final coherente.
 * - Si el pago ganó, la cancelación se rechaza; si ganó la cancelación, impedir
 *   que se pague el documento cancelado y ningún egreso se escribe en cashflow.
 */
class CancelarLiquidacionConcurrenteTest extends TestCase
{
    private User $admin;
    private TipoCaja $tipoCaja;
    private Rubro $rubro;
    private Subrubro $subrubro;
    private Deporte $deporte;
    private Profesor $profesor;
    private Liquidacion $liquidacion;
    private LiquidacionPagoService $pagoService;
    private LiquidacionService $liquidacionService;

    protected function setUp(): void
    {
        parent::setUp();

        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh');
            RefreshDatabaseState::$migrated = true;
        }

        Storage::fake();

        $this->pagoService = app(LiquidacionPagoService::class);
        $this->liquidacionService = app(LiquidacionService::class);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Caja Concurrencia FIN12', 'activo' => true]);

        $this->rubro = Rubro::create(['nombre' => 'Sueldos Concurrencia FIN12', 'tipo' => 'EGRESO', 'observacion' => '']);
        $this->subrubro = Subrubro::create([
            'rubro_id' => $this->rubro->id,
            'nombre' => 'Sueldo - Bielsa, Concurrente',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => false,
            'activo' => true,
        ]);

        $this->deporte = Deporte::create([
            'nombre' => 'Tenis Concurrencia FIN12',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $this->deporte->id,
            'nombre' => 'Marcelo',
            'apellido' => 'Bielsa',
            'dni' => '24999888',
            'fecha_nacimiento' => '1980-01-01',
            'direccion' => 'Calle 1',
            'localidad' => 'Rosario',
            'valor_hora' => 15000,
            'porcentaje_comision' => 0,
            'subrubro_id' => $this->subrubro->id,
            'activo' => true,
        ]);

        $this->liquidacion = Liquidacion::create([
            'profesor_id' => $this->profesor->id,
            'mes' => 8,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 150000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->liquidacion)) {
            DB::table('cashflow_movimientos')
                ->where('referencia_tipo', CashflowMovimiento::REF_LIQUIDACION)
                ->where('referencia_id', $this->liquidacion->id)
                ->delete();
            DB::table('liquidaciones')->where('id', $this->liquidacion->id)->delete();
        }
        foreach ([
            'profesores' => $this->profesor ?? null,
            'subrubros' => $this->subrubro ?? null,
            'rubros' => $this->rubro ?? null,
            'deportes' => $this->deporte ?? null,
            'tipos_caja' => $this->tipoCaja ?? null,
            'users' => $this->admin ?? null,
        ] as $tabla => $modelo) {
            if ($modelo) {
                DB::table($tabla)->where('id', $modelo->id)->delete();
            }
        }

        parent::tearDown();
    }

    /**
     * ORDEN 1: El pago tiene el lock de la liquidación.
     * La cancelación espera sin modificar el registro.
     * Al confirmar el pago, la cancelación se rechaza porque ya está pagada.
     */
    public function test_pago_en_curso_bloquea_cancelacion_y_tras_pagar_la_cancelacion_se_rechaza(): void
    {
        $otra = $this->otraConexion();
        $otra->beginTransaction();
        $otra->query('SELECT id FROM liquidaciones WHERE id = ' . $this->liquidacion->id . ' FOR UPDATE');

        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::enableQueryLog();

        try {
            $this->liquidacionService->cancelarLiquidacion($this->liquidacion->id, 'Intento concurrente', $this->admin->id);
            $this->fail('La cancelación avanzó con la liquidación tomada por el pago en la otra conexión.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
        }

        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();

        // Evidencia: no se escribió ninguna modificación en liquidaciones
        $modifico = $queries->contains(fn (array $q) => preg_match('/^\s*(update|insert|delete)\b/i', $q['query']));
        $this->assertFalse($modifico, 'La cancelación modificó datos antes de obtener el bloqueo.');

        $this->assertSame(Liquidacion::ESTADO_CERRADA, $this->liquidacion->fresh()->estado);
        $this->assertNull($this->liquidacion->fresh()->cancelada_at);

        // La otra conexión confirma el pago
        $ahora = now()->toDateTimeString();
        $otra->query("UPDATE liquidaciones SET estado_pago = 'PAGADA', pagada_at = '{$ahora}', pagada_por_admin_id = {$this->admin->id}, pagada_fecha = '2026-09-05', pagada_tipo_caja_id = {$this->tipoCaja->id}, pagada_subrubro_id = {$this->subrubro->id} WHERE id = {$this->liquidacion->id}");
        $otra->commit();

        // Reintento de cancelación ahora que terminó el pago: se rechaza limpiamente
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se puede cancelar una liquidación pagada.');
        $this->liquidacionService->cancelarLiquidacion($this->liquidacion->id, 'Reintento tras pago', $this->admin->id);
    }

    /**
     * ORDEN 2: La cancelación tiene el lock de la liquidación.
     * El pago espera sin registrar egreso en cashflow.
     * Al confirmar la cancelación, el pago se rechaza porque la liquidación quedó CANCELADA.
     */
    public function test_cancelacion_en_curso_bloquea_pago_y_tras_cancelar_el_pago_se_rechaza(): void
    {
        $otra = $this->otraConexion();
        $otra->beginTransaction();
        $otra->query('SELECT id FROM liquidaciones WHERE id = ' . $this->liquidacion->id . ' FOR UPDATE');

        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::enableQueryLog();

        try {
            $this->pagoService->marcarComoPagada($this->liquidacion->id, [
                'fecha_pago' => '2026-09-05',
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubro->id,
                'admin_id' => $this->admin->id,
            ]);
            $this->fail('El pago avanzó con la liquidación tomada por la cancelación en la otra conexión.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
        }

        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();

        // Evidencia: ningún egreso registrado en cashflow
        $insertoEgreso = $queries->contains(fn (array $q) => str_contains($q['query'], 'insert into `cashflow_movimientos`'));
        $this->assertFalse($insertoEgreso, 'El pago registró un egreso antes de obtener el bloqueo de la liquidación.');
        $this->assertSame(0, $this->egresosDeLaLiquidacion());

        // La otra conexión confirma la cancelación
        $ahora = now()->toDateTimeString();
        $otra->query("UPDATE liquidaciones SET estado = 'CANCELADA', cancelada_at = '{$ahora}', usuario_cancelacion_id = {$this->admin->id}, motivo_cancelacion = 'Cancelación concurrente ganadora' WHERE id = {$this->liquidacion->id}");
        $otra->commit();

        $this->assertSame(Liquidacion::ESTADO_CANCELADA, $this->liquidacion->fresh()->estado);

        // Reintento de pago ahora que se canceló: se rechaza y no crea egreso
        try {
            $this->pagoService->marcarComoPagada($this->liquidacion->id, [
                'fecha_pago' => '2026-09-05',
                'tipo_caja_id' => $this->tipoCaja->id,
                'subrubro_id' => $this->subrubro->id,
                'admin_id' => $this->admin->id,
            ]);
            $this->fail('Permitió pagar una liquidación CANCELADA.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('debe estar CERRADA para poder pagarla. Estado actual: CANCELADA', $e->getMessage());
        }

        $this->assertSame(0, $this->egresosDeLaLiquidacion(), 'Ningún egreso debe crearse para una liquidación cancelada.');
    }

    private function egresosDeLaLiquidacion(): int
    {
        return CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_LIQUIDACION)
            ->where('referencia_id', $this->liquidacion->id)
            ->count();
    }

    private function otraConexion(): \PDO
    {
        $c = config('database.connections.' . config('database.default'));
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4";

        return new \PDO($dsn, $c['username'], $c['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }
}
