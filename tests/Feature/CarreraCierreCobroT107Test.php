<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\PagoCuotaService;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * Control C1 sobre T107: Concurrencia real con dos conexiones/procesos PHP independientes.
 * Un cobro en efectivo y el cierre de esa caja a la vez, en los dos órdenes:
 * - Orden A: Cierre toma el bloqueo primero; el cobro concurrente se bloquea y es rechazado sin dejar faltante.
 * - Orden B: Cobro toma el bloqueo primero; el cierre con esperado visto viejo se bloquea y al entrar detecta
 *   el cambio de esperado y es rechazado sin dejar faltante.
 */
#[Group('lento')]
class CarreraCierreCobroT107Test extends TestCase
{
    private User $admin;
    private User $operativa;
    private TipoCaja $efectivo;
    private Subrubro $subrubroCuota;
    private Alumno $alumno;
    private GrupoPlan $plan;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();
        // Sin transacción en el test principal: la otra conexión externa debe ver los datos.
        $this->artisan('migrate:fresh');
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->efectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);
        app(CajaService::class)->configurarMostrador($this->efectivo->id, $this->admin->id);

        $rubro = Rubro::firstOrCreate(['nombre' => 'Ingresos'], ['tipo' => 'INGRESO']);
        $this->subrubroCuota = Subrubro::firstOrCreate(['nombre' => 'Cobro de cuota'], [
            'rubro_id' => $rubro->id,
            'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true,
            'clasificacion_resultado' => 'NEGOCIO',
            'activo' => true,
        ]);

        $deporte = Deporte::create(['nombre' => 'Fútbol Carrera', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial Carrera']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 20000, 'activo' => true]);

        $this->alumno = Alumno::create([
            'nombre' => 'Alumno', 'apellido' => 'Carrera', 'dni' => '40111222',
            'fecha_nacimiento' => '2005-01-01', 'fecha_alta' => '2026-01-10', 'celular' => '1144445555',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-01-10', 'activo' => true,
        ]);
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id, 'periodo' => '2026-11', 'monto_original' => 20000,
            'monto_pagado' => 0, 'estado' => 'PENDIENTE',
        ]);

        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00']);
    }

    protected function tearDown(): void
    {
        // Dejar la base limpia al terminar
        $this->artisan('migrate:fresh');
        RefreshDatabaseState::$migrated = true;
        parent::tearDown();
    }

    private function esperar(mixed $proceso): void
    {
        while (proc_get_status($proceso)['running']) {
            usleep(100000);
        }
        proc_close($proceso);
    }

    /**
     * Orden A: El cierre de caja inicia primero y mantiene el bloqueo.
     * El cobro en efectivo concurrente se bloquea, y al terminar el cierre
     * es rechazado porque la caja ya no está abierta.
     * Resultado: El cobro es rechazado, nunca queda como faltante en la caja cerrada.
     */
    public function test_orden_a_cierre_bloquea_y_cobro_concurrente_es_rechazado_sin_faltante(): void
    {
        $c = config('database.connections.mysql');
        $db = $c['database'];

        // Proceso 1: Inicia cierre bajo bloqueo del mostrador y de la caja, duerme 3s y confirma cierre.
        $codigo = sprintf(
            '$p=new PDO("mysql:host=%s;port=%s;dbname=%s",%s,%s);$p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);'
            .'$p->beginTransaction();'
            .'$p->query("SELECT id FROM caja_mostrador WHERE id=1 FOR UPDATE")->fetchAll();'
            .'$p->query("SELECT id FROM cajas_operativas WHERE id=%d FOR UPDATE")->fetchAll();'
            .'sleep(3);'
            .'$p->exec("UPDATE cajas_operativas SET estado=\'CERRADA\', efectivo_esperado=10000.00, efectivo_contado=10000.00, cambio_retenido=5000.00, efectivo_retirado=5000.00, diferencia_efectivo=0.00, cierre_at=NOW(), usuario_cierre_id=%d WHERE id=%d");'
            .'$p->commit();',
            $c['host'], $c['port'], $db, var_export($c['username'], true), var_export((string) $c['password'], true),
            $this->caja->id, $this->operativa->id, $this->caja->id
        );

        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [], $pipes);
        usleep(800000); // 800ms para asegurar que el proceso 1 tomó los locks

        // Proceso 2 (proceso principal): intenta registrar cobro en efectivo.
        // Debe bloquearse esperando a que el proceso 1 libere caja_mostrador, y luego ser rechazado.
        $rechazado = false;
        $mensaje = '';
        try {
            app(PagoCuotaService::class)->registrarPagoCuotaOperativo([
                'alumno_id' => $this->alumno->id,
                'tipo_caja_id' => $this->efectivo->id,
                'usuario_operativo_id' => $this->operativa->id,
                'items' => [['periodo' => '2026-11', 'monto' => 20000]],
                'fecha_pago' => '2026-11-01',
            ]);
        } catch (\Throwable $e) {
            $rechazado = true;
            $mensaje = $e->getMessage();
        }

        $this->esperar($proceso);

        $this->assertTrue($rechazado, 'El cobro debió ser rechazado al cerrarse la caja durante la espera.');
        $this->assertStringContainsString('Debés abrir la caja y confirmar el efectivo antes de operar', $mensaje);

        // Verificación en base:
        $caja = CajaOperativa::find($this->caja->id);
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertEquals(0.00, (float) $caja->diferencia_efectivo);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    /**
     * Orden B: El cobro inicia primero y mantiene el bloqueo del mostrador y la caja.
     * El intento de cierre con el esperado_visto viejo ($10.000) se bloquea.
     * Al confirmar el cobro ($20.000), el cierre se despierta, detecta que el esperado
     * ahora es $30.000 y rechaza el cierre.
     * Resultado: El cierre es rechazado, la caja sigue abierta, no se registra ningún
     * faltante falso de -$20.000. Al reintentar con el esperado al día, cierra perfecto.
     */
    public function test_orden_b_cobro_bloquea_y_cierre_con_esperado_viejo_es_rechazado_sin_faltante(): void
    {
        $c = config('database.connections.mysql');
        $db = $c['database'];

        // Proceso 1: Simula el cobro que toma el bloqueo del mostrador y de la caja,
        // asienta el movimiento en la caja, duerme 3s y hace commit.
        $codigo = sprintf(
            '$p=new PDO("mysql:host=%s;port=%s;dbname=%s",%s,%s);$p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);'
            .'$p->beginTransaction();'
            .'$p->query("SELECT id FROM caja_mostrador WHERE id=1 FOR UPDATE")->fetchAll();'
            .'$p->query("SELECT id FROM cajas_operativas WHERE id=%d FOR UPDATE")->fetchAll();'
            .'$p->exec("INSERT INTO movimientos_operativos (caja_operativa_id, fecha, tipo_caja_id, subrubro_id, monto, usuario_id, estado, created_at, updated_at) VALUES (%d, CURDATE(), %d, %d, 20000.00, %d, \'ACTIVO\', NOW(), NOW())");'
            .'sleep(3);'
            .'$p->commit();',
            $c['host'], $c['port'], $db, var_export($c['username'], true), var_export((string) $c['password'], true),
            $this->caja->id, $this->caja->id, $this->efectivo->id, $this->subrubroCuota->id, $this->operativa->id
        );

        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [], $pipes);
        usleep(800000); // 800ms para asegurar que el proceso 1 tomó los locks

        // Proceso 2 (proceso principal): intenta cerrar la caja con la pantalla vieja ($10.000 visto).
        // Se bloquea esperando el commit de proceso 1. Al liberarse, detecta el cobro y rechaza.
        $rechazado = false;
        $errorMsg = '';
        try {
            app(CajaService::class)->cerrarCajaOperativa($this->caja->id, $this->operativa->id, false, [
                'efectivo_contado' => 10000,
                'cambio_retenido' => 5000,
                'esperado_visto' => 10000,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $rechazado = true;
            $errorMsg = $e->errors()['efectivo_contado'][0] ?? '';
        }

        $this->esperar($proceso);

        $this->assertTrue($rechazado, 'El cierre debió ser rechazado por cambio en el efectivo esperado.');
        $this->assertStringContainsString('Cambió el efectivo esperado: ahora es ' . \App\Support\FormatoImporte::pesos(30000), $errorMsg);

        // Verificación en base: la caja permanece ABIERTA, no tiene diferencia_efectivo
        $caja = CajaOperativa::find($this->caja->id);
        $this->assertSame('ABIERTA', $caja->estado);
        $this->assertNull($caja->diferencia_efectivo);

        // Al cerrar ahora con la pantalla al día ($30.000 visto y contado), cierra sin faltante
        app(CajaService::class)->cerrarCajaOperativa($this->caja->id, $this->operativa->id, false, [
            'efectivo_contado' => 30000,
            'cambio_retenido' => 5000,
            'esperado_visto' => 30000,
        ]);

        $caja->refresh();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertEquals(30000.00, (float) $caja->efectivo_esperado);
        $this->assertEquals(30000.00, (float) $caja->efectivo_contado);
        $this->assertEquals(0.00, (float) $caja->diferencia_efectivo);
    }
}
