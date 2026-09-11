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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FIN-05: dos pagos de la misma liquidacion no pueden registrar dos egresos.
 *
 * El pago leia "¿ya esta paga?" y "¿ya hay egreso?" sin tomar la fila. Dos pedidos a la
 * vez —la liquidacion abierta en dos pestañas, o dos personas— pasaban los dos chequeos
 * antes de que el otro guardara, y cada uno escribia su egreso: el sueldo se descontaba
 * dos veces del cashflow. El doble clic no llega aca: lo frena el anti doble envio de
 * `ds-app.js`. Lo que la pantalla no puede frenar lo tiene que frenar el servidor.
 *
 * No usa RefreshDatabase: la segunda conexion tiene que ver los datos, y dentro de la
 * transaccion de la prueba no los veria. Tampoco DatabaseTruncation: trunca al empezar y
 * no al terminar, asi que dejaba filas que rompian a las pruebas siguientes, y vacia la
 * tabla de configuraciones que cargan las migraciones. Esta prueba guarda sus datos y al
 * terminar borra exactamente lo que creo.
 */
class PagoLiquidacionConcurrenteTest extends TestCase
{
    private User $admin;
    private TipoCaja $tipoCaja;
    private Rubro $rubro;
    private Subrubro $subrubro;
    private Deporte $deporte;
    private Profesor $profesor;
    private Liquidacion $liquidacion;

    protected function setUp(): void
    {
        parent::setUp();

        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh');
            RefreshDatabaseState::$migrated = true;
        }

        // El pago dispara el PDF del recibo al confirmar; que no escriba en el disco real.
        Storage::fake();

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Banco prueba concurrencia', 'activo' => true]);

        $this->rubro = Rubro::create(['nombre' => 'Sueldos prueba concurrencia', 'tipo' => 'EGRESO', 'observacion' => '']);
        $this->subrubro = Subrubro::create([
            'rubro_id' => $this->rubro->id,
            'nombre' => 'Sueldo - Mitre, Jorge',
            'permitido_para' => 'ADMIN',
            'afecta_caja' => false,
            'activo' => true,
        ]);

        $this->deporte = Deporte::create([
            'nombre' => 'Patin prueba concurrencia',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $this->profesor = Profesor::create([
            'deporte_id' => $this->deporte->id,
            'nombre' => 'Jorge',
            'apellido' => 'Mitre',
            'dni' => '25111222',
            'fecha_nacimiento' => '1980-01-01',
            'direccion' => 'Calle 1',
            'localidad' => 'Rosario',
            'valor_hora' => 10000,
            'porcentaje_comision' => 0,
            'subrubro_id' => $this->subrubro->id,
            'activo' => true,
        ]);

        $this->liquidacion = Liquidacion::create([
            'profesor_id' => $this->profesor->id,
            'mes' => 8,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 170000,
            'estado' => Liquidacion::ESTADO_CERRADA,
        ]);
    }

    protected function tearDown(): void
    {
        // Lo que esta prueba guardo, en orden inverso a las claves foraneas. Directo en la
        // base: el modelo impide borrar una liquidacion cerrada, que es justo la regla que
        // se quiere en produccion. Tolera un setUp que se corto a mitad de camino.
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
     * Otra conexion tiene la liquidacion tomada, como la tendria un pago en curso en la
     * otra pestaña. Este pago no puede escribir un egreso hasta tenerla el: si lo escribe
     * antes, cuando el otro termine los dos quedan con su egreso.
     */
    public function test_no_registra_un_egreso_mientras_otro_pago_tiene_la_liquidacion(): void
    {
        $otra = $this->otraConexion();
        $otra->beginTransaction();
        $otra->query('SELECT id FROM liquidaciones WHERE id = '.$this->liquidacion->id.' FOR UPDATE');

        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::enableQueryLog();

        try {
            $this->pagar();
            $this->fail('El pago avanzo con la liquidacion tomada por otra conexion.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            $otra->rollBack();
            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
        }

        $insertoEgreso = collect(DB::getQueryLog())
            ->contains(fn (array $q) => str_contains($q['query'], 'insert into `cashflow_movimientos`'));
        DB::disableQueryLog();

        $this->assertFalse(
            $insertoEgreso,
            'Registro el egreso antes de tener la liquidacion: con dos pedidos a la vez, cada uno deja el suyo.'
        );
        $this->assertSame(0, $this->egresosDeLaLiquidacion());
    }

    public function test_cuando_el_otro_pago_termina_este_no_vuelve_a_pagar(): void
    {
        $primero = $this->pagar();
        $segundo = $this->pagar();

        $this->assertFalse($primero['ya_pagada']);
        $this->assertTrue($segundo['ya_pagada'], 'El segundo pedido tiene que reconocer que ya estaba paga.');
        $this->assertSame(1, $this->egresosDeLaLiquidacion(), 'Un solo egreso por liquidacion.');
        $this->assertSame(
            -170000.0,
            (float) CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_LIQUIDACION)
                ->where('referencia_id', $this->liquidacion->id)
                ->sum('monto')
        );
    }

    private function pagar(): array
    {
        return app(LiquidacionPagoService::class)->marcarComoPagada($this->liquidacion->id, [
            'fecha_pago' => '2026-09-05',
            'tipo_caja_id' => $this->tipoCaja->id,
            'subrubro_id' => $this->subrubro->id,
            'admin_id' => $this->admin->id,
        ]);
    }

    private function egresosDeLaLiquidacion(): int
    {
        return CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_LIQUIDACION)
            ->where('referencia_id', $this->liquidacion->id)
            ->count();
    }

    private function otraConexion(): \PDO
    {
        $c = config('database.connections.'.config('database.default'));
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4";

        return new \PDO($dsn, $c['username'], $c['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }
}
