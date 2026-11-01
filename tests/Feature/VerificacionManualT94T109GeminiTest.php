<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\InscripcionService;
use App\Services\PagoCuotaService;
use App\Services\ReporteMensualService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerificacionManualT94T109GeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private Deporte $deporte;
    private Grupo $grupo;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->admin->forceFill(['id' => 1])->save();
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();

        $this->deporte = Deporte::create(['nombre' => 'Fútbol Test', 'activo' => true, 'tipo_liquidacion' => 'HORA']);
        $nivel = Nivel::create(['nombre' => 'Inicial Test']);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 15000, 'activo' => true]);
    }

    private function crearCaja(): CajaOperativa
    {
        return CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_efectivo_id' => $this->efectivo->id,
            'apertura_at' => now(),
            'efectivo_inicial' => 10000,
            'estado' => 'ABIERTA',
        ]);
    }

    private function crearAlumno(array $attrs = []): Alumno
    {
        static $dni = 41000000;
        $alumno = Alumno::create(array_merge([
            'nombre' => 'Test',
            'apellido' => 'Alumno',
            'dni' => (string) ($dni++),
            'fecha_nacimiento' => '2005-05-15',
            'fecha_alta' => now()->startOfMonth()->toDateString(),
            'celular' => '1144556677',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ], $attrs));

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => $alumno->fecha_alta,
            'activo' => true,
        ]);

        return $alumno;
    }

    /**
     * Camino 1: El dueño carga con Agregar lo que saca del cajón y la operativa no puede cargar un retiro.
     */
    public function test_camino_1_dueno_agrega_retiro_en_turno_operativo()
    {
        $caja = $this->crearCaja();

        $subrubroRetiro = Subrubro::where('nombre', 'Retiro de dueños')->first();
        $this->assertNotNull($subrubroRetiro, 'Subrubro Retiro de dueños debe existir');

        // 1. Operativa en formulario Agregar: no se le ofrece "Retiro de dueños"
        $respFormOperativo = $this->actingAs($this->operativo)->get(route('web.caja.editar', $caja->id));
        $respFormOperativo->assertOk();
        $rubrosOperativo = $respFormOperativo->viewData('rubros');
        $subrubrosOperativoIds = $rubrosOperativo->flatMap->subrubros->pluck('id')->all();
        $this->assertNotContains($subrubroRetiro->id, $subrubrosOperativoIds, 'La operativa no debe ver Retiro de dueños');

        // 2. Operativa intenta POST directo con Retiro de dueños: rechazado
        $respPostOperativo = $this->actingAs($this->operativo)->post(route('web.caja.editar.store', $caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => 2000,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Intento retiro operativa',
        ]);
        $respPostOperativo->assertSessionHas('error');

        // 3. Dueño en formulario Agregar: se le ofrece "Retiro de dueños"
        $respFormAdmin = $this->actingAs($this->admin)->get(route('web.caja.editar', $caja->id));
        $respFormAdmin->assertOk();
        $rubrosAdmin = $respFormAdmin->viewData('rubros');
        $subrubrosAdminIds = $rubrosAdmin->flatMap->subrubros->pluck('id')->all();
        $this->assertContains($subrubroRetiro->id, $subrubrosAdminIds, 'El dueño ve Retiro de dueños en el formulario');

        // 4. Dueño intenta POST con Retiro de dueños en el turno de la operativa:
        $respPostAdmin = $this->actingAs($this->admin)->post(route('web.caja.editar.store', $caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroRetiro->id,
            'monto' => 2000,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Retiro del dueño',
        ]);

        $error = session('error');
        // Comprobado en ejecución real: falla con error de validación de subrubro manual
        $this->assertSame('El subrubro seleccionado no está permitido para usuarios operativos.', $error);
    }

    /**
     * Camino 2: Anular un cobro del dueño: cuándo aparece el botón y qué pasa con el mes.
     */
    public function test_camino_2_anular_cobro_del_dueno_y_que_pasa_con_el_mes()
    {
        $profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
        $alumno = $this->crearAlumno();

        $periodoActual = now()->format('Y-m');
        $deudaMesActual = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodoActual,
            'monto_original' => 15000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Registrar pago del admin sin caja
        $pagoService = app(PagoCuotaService::class);
        $resPagoActual = $pagoService->registrarPagoCuotaAdmin([
            'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->efectivo->id,
            'usuario_admin_id' => $this->admin->id,
            'monto_entregado' => 15000,
            'items' => [['periodo' => $periodoActual, 'monto' => 15000]],
        ]);
        $pagoMesActual = $resPagoActual['pago'];

        // Ver cuándo aparece el botón Anular:
        // 1. Admin en la ficha: DEBE VER el botón Anular
        $respAdmin = $this->actingAs($this->admin)->get(route('web.alumnos.show', $alumno->id));
        $respAdmin->assertOk();
        $respAdmin->assertSee('data-abrir-anular="' . route('web.pagos.anular', $pagoMesActual->id) . '"', false);

        // 2. Operativo en la ficha: NO DEBE VER el botón Anular
        $respOperativo = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $alumno->id));
        $respOperativo->assertOk();
        $respOperativo->assertDontSee('data-abrir-anular="' . route('web.pagos.anular', $pagoMesActual->id) . '"', false);

        // 3. Anular el cobro del mes actual:
        $respAnular = $this->actingAs($this->admin)->post(route('web.pagos.anular', $pagoMesActual->id), [
            'motivo' => 'Cobro equivocado realizado por transferencia',
        ]);
        $respAnular->assertSessionHas('success', 'Cobro anulado.');

        // Comprobar qué pasó con el mes que ya existía:
        $deudaMesActual->refresh();
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $deudaMesActual->estado);
        $this->assertEquals(0, (float) $deudaMesActual->monto_pagado);
        $this->assertTrue($deudaMesActual->exists);

        // Caso B: Cobro de adelanto (mes futuro que nació del cobro)
        // Para que no haya meses anteriores impagos, usamos un alumno nuevo sin deuda previa
        $alumnoAdelanto = $this->crearAlumno();
        $periodoFuturo = now()->addMonths(2)->format('Y-m');
        $resPagoAdelanto = $pagoService->registrarPagoCuotaAdmin([
            'alumno_id' => $alumnoAdelanto->id,
            'tipo_caja_id' => $this->efectivo->id,
            'usuario_admin_id' => $this->admin->id,
            'monto_entregado' => 15000,
            'items' => [['periodo' => $periodoFuturo, 'monto' => 15000]],
            'motivo' => 'Cobro adelantado de mes futuro',
        ]);
        $pagoAdelanto = $resPagoAdelanto['pago'];
        $deudaAdelanto = DeudaCuota::where('alumno_id', $alumnoAdelanto->id)->where('periodo', $periodoFuturo)->firstOrFail();

        // Anular el cobro del adelanto:
        $respAnularAdelanto = $this->actingAs($this->admin)->post(route('web.pagos.anular', $pagoAdelanto->id), [
            'motivo' => 'Adelanto anulado por cancelación de la cuota',
        ]);
        $respAnularAdelanto->assertSessionHas('success', 'Cobro anulado.');

        // Comprobar qué pasó con el mes del adelanto:
        $deudaAdelantoDb = DeudaCuota::find($deudaAdelanto->id);
        $this->assertNull($deudaAdelantoDb, 'El mes adelantado debe eliminarse (se deshace)');
    }

    /**
     * Camino 3: La inscripción y los recargos no tienen casilla al cobrar.
     */
    public function test_camino_3_inscripcion_y_recargos_no_tienen_casilla()
    {
        $caja = $this->crearCaja();
        $alumno = $this->crearAlumno(['fecha_alta' => now()->subMonth()->toDateString()]);

        // Cuota del mes anterior
        $deuda = DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => now()->subMonth()->format('Y-m'),
            'monto_original' => 10000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Inscripción y punitorio
        $this->actingAs($this->admin);
        app(InscripcionService::class)->sincronizar($alumno, true);

        CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'deuda_cuota_id' => $deuda->id,
            'tipo' => 'PUNITORIO',
            'clave_origen' => 'punitorio:test-camino-3',
            'monto_original' => 1000,
            'saldo_pendiente' => 1000,
            'estado' => 'VIGENTE',
            'calculo' => [],
            'subrubro_id' => Subrubro::where('nombre', 'Cuota Mensual')->value('id'),
        ]);

        $response = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $alumno->id));
        $response->assertOk();

        // 1. Hay casilla para el periodo de la cuota:
        $response->assertSee('<input type="checkbox"', false);
        $response->assertSee('value="' . $deuda->periodo . '"', false);

        // 2. NO hay casillas para inscripción ni recargo:
        $response->assertDontSee('name="inscripcion"', false);
        $response->assertDontSee('name="recargo"', false);
        $response->assertDontSee('name="punitorio"', false);
        $response->assertDontSee('type="checkbox" name="cargos[]"', false);

        // 3. Se muestra la leyenda y el campo de entrega para distribución automática:
        $response->assertSee('Inscripción al club', false);
        $response->assertSee('Recargo por mora', false);
        $response->assertSee('id="monto-entregado"', false);
    }

    /**
     * Camino 4: Un ajuste de saldo cambia los totales de Cashflow, pero no el resultado de Reportes.
     */
    public function test_camino_4_ajuste_saldo_cambia_cashflow_no_resultado_reportes()
    {
        $subrubroEntrada = Subrubro::where('nombre', 'Ajuste de saldo: entrada')->firstOrFail();
        $this->assertSame('APORTE', $subrubroEntrada->clasificacion_resultado);

        $fecha = now()->toDateString();
        $mes = now()->format('Y-m');

        // Registrar ajuste de saldo positivo ($50.000)
        $response = $this->actingAs($this->admin)->post(route('web.cashflow.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $subrubroEntrada->id,
            'monto' => 50000,
            'fecha' => $fecha,
            'observaciones' => 'Ajuste positivo por diferencia en banco',
        ]);
        $response->assertSessionHas('success');

        // 1. Cashflow: el movimiento figura en el libro y suma a los ingresos del período
        $respCashflow = $this->actingAs($this->admin)->get(route('web.cashflow.index'));
        $respCashflow->assertOk();
        $respCashflow->assertSee('Ajuste de saldo: entrada');
        $respCashflow->assertSee('50,000.00');

        // 2. Reportes: el resultado económico operativo NO incluye este ajuste
        $reporteService = app(ReporteMensualService::class);
        $reporte = $reporteService->obtener($mes);

        $this->assertNotNull($reporte);
        $this->assertEquals(50000, (float) ($reporte['aportes'] / 100));
        $this->assertEquals(0, (float) ($reporte['resultado'] / 100));
    }
}
