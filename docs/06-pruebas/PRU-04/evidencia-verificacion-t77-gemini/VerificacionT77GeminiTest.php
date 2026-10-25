<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\AlumnoRevisionCobranza;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T77 — Verificación independiente de Gemini (Antigravity).
 *
 * Verifica exhaustivamente la lógica implementada por Codex:
 * 1. Sin deuda inventada: CajaWebController::cobrar no agrega deuda en memoria;
 *    lista solo cuotas impagas en DB y "Total pendiente" suma solo esas.
 * 2. Cobro de mes sin deuda: selector ofrece mes actual y futuros al precio del plan;
 *    al pagar se crea cuota PAGADA y pago sin duplicaciones.
 * 3. Rechazo del servidor: enviar mes sin deuda sin selección manual es rechazado sin escribir;
 *    cobrar mes ya pagado es rechazado.
 * 4. Integridad: deudas viejas reales, pago parcial, confirmación de adelanto con deuda vieja,
 *    diferenciación operativo (caja) vs admin (cashflow), y consistencia con las demás pantallas.
 */
class VerificacionT77GeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $admin;
    private Alumno $alumno;
    private GrupoPlan $plan;
    private TipoCaja $tipoEfectivo;
    private CajaOperativa $cajaOperativa;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-25 15:00:00');
        $this->seed(CatalogosSeeder::class);

        $this->tipoEfectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->cajaOperativa = CajaDeclarada::crear($this->operativo->id, $this->tipoEfectivo->id);

        $deporte = Deporte::where('nombre', 'Patín')->firstOrFail();
        $nivel = Nivel::where('nombre', 'Principiantes')->firstOrFail();
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 30000,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Valentina',
            'apellido' => 'Sosa',
            'dni' => '45123456',
            'fecha_nacimiento' => '2012-04-10',
            'celular' => '11-4444-5555',
            'fecha_alta' => '2026-03-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-03-01',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function snapshotTablas(): array
    {
        $tablas = [
            'deuda_cuotas',
            'pagos',
            'pago_deuda_cuota',
            'movimientos_operativos',
            'cashflow_movimientos',
            'alumnos_revision_cobranza',
        ];

        $snap = [];
        foreach ($tablas as $tabla) {
            $snap[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        return $snap;
    }

    // ── 1. Sin deuda inventada ──────────────────────────────────────────────

    public function test_01_sin_deuda_inventada_en_pantalla_cobrar_y_total_pendiente_cero(): void
    {
        $antes = $this->snapshotTablas();

        $response = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alumno->id));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('alumno')->deudaCuotas);
        $response->assertSee('Sin cuotas pendientes.');
        $response->assertSee('$0.00');

        // Comprobar que no existen checkboxes para cuota corriente ni futuras
        $this->assertStringNotContainsString('name="periodos[]"', $response->getContent());

        // La visita no genera registros en base de datos
        $this->assertSame($antes, $this->snapshotTablas());
    }

    // ── 2. Selector de meses adelantables y mes actual ──────────────────────

    public function test_02_selector_ofrece_mes_actual_al_precio_del_plan_y_12_futuros(): void
    {
        $response = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alumno->id));

        $response->assertOk();
        $meses = $response->viewData('mesesAdelantables');

        // 1 mes corriente + 12 meses futuros = 13
        $this->assertCount(13, $meses);

        $primero = $meses->first();
        $this->assertSame('2026-10', $primero['periodo']);
        $this->assertSame('Octubre 2026', $primero['etiqueta']);
        $this->assertEquals(30000.0, $primero['precio']);

        $ultimo = $meses->last();
        $this->assertSame('2027-10', $ultimo['periodo']);
        $this->assertSame('Octubre 2027', $ultimo['etiqueta']);
    }

    public function test_03_selector_excluye_meses_con_cuotas_existentes_en_cualquier_estado(): void
    {
        // Si octubre ya existe en DB (ej. PAGADA o CONDONADA), no debe aparecer en el selector
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 30000,
            'monto_pagado' => 30000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alumno->id));

        $response->assertOk();
        $meses = $response->viewData('mesesAdelantables');

        // Solo 12 futuros, octubre 2026 ya no está
        $this->assertCount(12, $meses);
        $this->assertFalse($meses->contains('periodo', '2026-10'));
        $this->assertSame('2026-11', $meses->first()['periodo']);
    }

    // ── 3. Cobro manual del mes sin deuda ───────────────────────────────────

    public function test_04_cobro_manual_del_mes_sin_deuda_crea_cuota_pagada_y_pago_sin_duplicaciones(): void
    {
        $payload = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-10'],
            'periodos_manuales' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 30000],
        ];

        $response = $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), $payload);

        $response->assertSessionHas('success');

        // Cuota creada en estado PAGADA
        $cuota = DeudaCuota::where('alumno_id', $this->alumno->id)
            ->where('periodo', '2026-10')
            ->first();
        $this->assertNotNull($cuota);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $cuota->estado);
        $this->assertEquals(30000, $cuota->monto_original);
        $this->assertEquals(30000, $cuota->monto_pagado);

        // Pago e imputación únicos
        $pago = Pago::where('alumno_id', $this->alumno->id)->first();
        $this->assertNotNull($pago);
        $this->assertEquals(30000, $pago->monto_final);
        $this->assertSame(1, DB::table('pago_deuda_cuota')->where('pago_id', $pago->id)->count());

        // Movimiento operativo en la caja abierta
        $mov = MovimientoOperativo::where('pago_id', $pago->id)->first();
        $this->assertNotNull($mov);
        $this->assertEquals(30000, $mov->monto);

        // Al volver a la pantalla Cobrar, el mes ya no se ofrece
        $recarga = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alumno->id));
        $this->assertFalse($recarga->viewData('mesesAdelantables')->contains('periodo', '2026-10'));
    }

    // ── 4. Rechazos del servidor ────────────────────────────────────────────

    public function test_05_servidor_rechaza_pagar_como_deuda_el_mes_sin_deuda_sin_seleccion_manual(): void
    {
        $antes = $this->snapshotTablas();

        // Enviar periodos=['2026-10'] pero omitiendo periodos_manuales
        $payloadSinManual = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 30000],
        ];

        $response = $this->actingAs($this->operativo)
            ->from(route('web.caja.cobrar', $this->alumno->id))
            ->post(route('web.caja.pagar', $this->alumno->id), $payloadSinManual);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'Ese mes no tiene una deuda guardada',
            session('error')
        );

        // Cero escrituras
        $this->assertSame($antes, $this->snapshotTablas());
    }

    public function test_06_servidor_rechaza_cobrar_mes_ya_pagado_incluso_con_payload_manual(): void
    {
        // Deuda ya pagada
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 30000,
            'monto_pagado' => 30000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $antes = $this->snapshotTablas();

        $payload = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-10'],
            'periodos_manuales' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 30000],
        ];

        $response = $this->actingAs($this->operativo)
            ->from(route('web.caja.cobrar', $this->alumno->id))
            ->post(route('web.caja.pagar', $this->alumno->id), $payload);

        $response->assertSessionHas('error');
        $this->assertSame($antes, $this->snapshotTablas());
    }

    // ── 5. Deudas reales y pagos parciales ───────────────────────────────────

    public function test_07_deudas_reales_siguen_apareciendo_y_cobrandose_normalmente(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 30000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar', $this->alumno->id));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('alumno')->deudaCuotas);
        $response->assertSee('Septiembre 2026');
        $response->assertSee('value="2026-09"', false);
        $response->assertSee('$30,000.00');

        // Se cobra como deuda normal (sin periodos_manuales)
        $payload = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 30000],
        ];

        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), $payload)
            ->assertSessionHas('success');

        $cuota = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->first();
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $cuota->estado);
    }

    public function test_08_pago_parcial_de_deuda_real_deja_saldo_pendiente(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 30000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $payload = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 12000],
        ];

        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), $payload)
            ->assertSessionHas('success');

        $cuota = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-09')->first();
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $cuota->estado);
        $this->assertEquals(12000, $cuota->monto_pagado);
        $this->assertEquals(18000, $cuota->saldo_pendiente);
    }

    // ── 6. Adelantado con deuda vieja exige confirmación y motivo ───────────

    public function test_09_adelantado_con_deuda_vieja_exige_confirmacion_y_motivo(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 30000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Intentar pagar mes futuro 2026-11 sin confirmar deuda anterior
        $payloadSinConfirmar = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => 30000],
            'confirmar_pago_adelantado' => true,
        ];

        $response = $this->actingAs($this->operativo)
            ->postJson(route('web.caja.pagar', $this->alumno->id), $payloadSinConfirmar);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'requiere_confirmacion' => true,
            ]);

        // Con confirmación y motivo se procesa
        $payloadConfirmado = $payloadSinConfirmar + [
            'confirmar_deuda_anterior' => true,
            'motivo' => 'Tutor pidió adelantar noviembre por viaje',
        ];

        $responseOk = $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), $payloadConfirmado);

        $responseOk->assertSessionHas('success');
    }

    // ── 7. Roles: Operativo (caja) vs Admin (cashflow) ─────────────────────

    public function test_10_admin_cobra_mes_manual_directo_a_cashflow_sin_abrir_caja(): void
    {
        $payload = [
            'tipo_caja_id' => $this->tipoEfectivo->id,
            'periodos' => ['2026-10'],
            'periodos_manuales' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 30000],
        ];

        $this->actingAs($this->admin)
            ->post(route('web.caja.pagar', $this->alumno->id), $payload)
            ->assertSessionHas('success');

        // Cashflow recibe el movimiento, cero movimientos operativos
        $this->assertEquals(30000, DB::table('cashflow_movimientos')->sum('monto'));
        $this->assertSame(0, MovimientoOperativo::where('alumno_id', $this->alumno->id)->count());
    }

    // ── 8. Coherencia con las demás pantallas del sistema ───────────────────

    public function test_11_cobranza_ficha_e_inicio_no_muestran_deuda_inventada(): void
    {
        $cobranzaService = app(CobranzaEstadoService::class);

        // Saldo total debe ser $0
        $saldo = $cobranzaService->saldoDeAlumnos(collect([$this->alumno]));
        $this->assertEquals(0.0, (float) $saldo[$this->alumno->id]['total']);
        $this->assertSame(CobranzaEstadoService::ESTADO_AL_DIA, $cobranzaService->estadoAlumno($this->alumno->id)['estado']);

        // Resumen dashboard
        $this->assertEquals(0.0, (float) $cobranzaService->resumenDashboard()['total_adeudado']);

        // Ficha del alumno
        $ficha = $this->actingAs($this->operativo)
            ->get(route('web.alumnos.show', $this->alumno->id));
        $ficha->assertOk();
        $this->assertSame(CobranzaEstadoService::ESTADO_AL_DIA, $ficha->viewData('estadoCobranza')['estado']);
        $this->assertCount(0, $ficha->viewData('alumno')->deudaCuotas);

        // Pantalla Cobranza index
        $cobranzaIndex = $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index'));
        $cobranzaIndex->assertOk();
        $this->assertEquals(0.0, (float) $cobranzaIndex->viewData('resumen')['total_adeudado']);

        // Inicio operativo y admin
        $inicioAdmin = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(0, $inicioAdmin->viewData('reporte')['deuda']['total']);

        $inicioOperativo = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();
        $this->assertSame(0, $inicioOperativo->viewData('vencidos'));
        $this->assertSame(0, $inicioOperativo->viewData('enPlazo'));
    }
}
