<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * P2 Entrega 2: Tres defectos de la ficha del alumno y el cobro.
 * - A17: Botón Cobrar en la ficha del alumno (cabecera y filas de deuda).
 * - A34: Historial y acceso a recibos en la ficha (incluyendo pagos anulados).
 * - A3: Cobro adelantado de períodos futuros al precio vigente del plan, sin duplicación en cobranza:generar-deudas.
 */
class P2Entrega2FichaCobroAdelantadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesor;
    private TipoCaja $tipoCaja;
    private Alumno $alumno;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 10:00:00');
        Storage::fake();

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        $this->tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->tipoCaja->id);
        $this->profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);

        $deporte = Deporte::create([
            'nombre' => 'Patin Artistico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 35000.0,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Martina',
            'apellido' => 'Stoessel',
            'dni' => '40111222',
            'fecha_nacimiento' => '2012-05-10',
            'celular' => '1144445555',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-10',
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-01-10',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── A17: Botón Cobrar en la ficha del alumno ─────────────────────────────

    public function test_a17_admin_y_operativo_ven_boton_cobrar_en_cabecera_de_ficha(): void
    {
        $urlCobrar = route('web.caja.cobrar', $this->alumno->id);

        $responseAdmin = $this->actingAs($this->admin)->get(route('web.alumnos.show', $this->alumno->id));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee($urlCobrar);
        $responseAdmin->assertSee('Cobrar');

        $responseOp = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $this->alumno->id));
        $responseOp->assertOk();
        $responseOp->assertSee($urlCobrar);
        $responseOp->assertSee('Cobrar');

        // El admin no tiene caja abierta: igual tiene que poder cobrar desde Caja,
        // no solo entrando a la ficha (Carlos, 10/10/2026).
        $this->actingAs($this->admin)->get(route('web.caja.index'))
            ->assertOk()->assertSee(route('web.caja.cobrar-cuota'))->assertSee('sin abrir caja');
        $this->actingAs($this->admin)->get(route('web.caja.cobrar-cuota'))->assertOk()->assertSee('Stoessel');
    }

    public function test_a17_profesor_no_puede_acceder_a_ficha_ni_a_cobrar(): void
    {
        $this->actingAs($this->profesor)
            ->get(route('web.alumnos.show', $this->alumno->id))
            ->assertStatus(403);

        $this->actingAs($this->profesor)
            ->get(route('web.caja.cobrar', $this->alumno->id))
            ->assertStatus(403);
    }

    public function test_a17_fila_de_deuda_en_ficha_muestra_boton_cobrar(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $this->alumno->id));
        $response->assertOk();

        $urlCobrar = route('web.caja.cobrar', $this->alumno->id);
        $response->assertSee($urlCobrar);
        $response->assertSee('Sep 2026');
        $response->assertSee('35.000');
    }

    // ── A34: Historial y acceso a recibos ────────────────────────────────────

    public function test_a34_ficha_permite_acceder_a_recibo_de_pago_completado_y_anulado(): void
    {
        $pagoCompletado = Pago::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'mes' => 8,
            'anio' => 2026,
            'monto_base' => 35000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 35000,
            'fecha_pago' => '2026-08-10',
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);

        $pagoAnulado = Pago::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'mes' => 9,
            'anio' => 2026,
            'monto_base' => 35000,
            'porcentaje_aplicado' => 100,
            'monto_final' => 35000,
            'fecha_pago' => '2026-09-10',
            'estado' => Pago::ESTADO_ANULADO,
        ]);

        $response = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $this->alumno->id));
        $response->assertOk();

        $recibo1Url = route('web.recibos.cuota', $pagoCompletado->id) . '?inline=1';
        $recibo2Url = route('web.recibos.cuota', $pagoAnulado->id) . '?inline=1';

        $response->assertSee($recibo1Url);
        $response->assertSee($recibo2Url);
        $response->assertSee('Recibo');
        $response->assertSee('Anulado');

        // Comprobar que el endpoint genera y entrega el PDF con status 200
        $responsePdf = $this->actingAs($this->operativo)->get(route('web.recibos.cuota', $pagoCompletado->id) . '?inline=1');
        $responsePdf->assertOk();
        $this->assertEquals('application/pdf', $responsePdf->headers->get('Content-Type'));

        // También para el anulado entrega el PDF
        $responsePdfAnulado = $this->actingAs($this->operativo)->get(route('web.recibos.cuota', $pagoAnulado->id) . '?inline=1');
        $responsePdfAnulado->assertOk();
        $this->assertEquals('application/pdf', $responsePdfAnulado->headers->get('Content-Type'));
    }

    // ── A3: Cobro adelantado ─────────────────────────────────────────────────

    public function test_a3_alumno_al_dia_ofrece_periodos_futuros_para_cobro_adelantado(): void
    {
        // El alumno ya pagó octubre 2026 (mes corriente)
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 35000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $response = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $response->assertOk();

        // No debe quedar en "Sin deudas pendientes" bloqueante sin opciones
        // T18: los meses futuros se ofrecen aparte, en un selector, con el precio de hoy
        // como sugerencia. Ya no se dibujan como deuda.
        $response->assertSee('Cobro adelantado');
        $response->assertSee('value="2026-11" data-precio="35000"', false);
        $response->assertSee('Noviembre 2026');
        $response->assertDontSee('name="montos_cuota[2026-11]"', false);
    }

    public function test_a3_cobro_adelantado_registra_pago_y_deuda_al_precio_del_plan(): void
    {
        // Octubre ya pagado
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 35000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        // Operativo cobra Noviembre 2026 por adelantado
        $postData = [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-11'],
            'montos_cuota' => ['2026-11' => '35.000'],
            'fecha_pago' => '2026-10-05',
            'confirmar_pago_adelantado' => 1,
        ];

        $response = $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), $postData);

        $response->assertRedirect();

        // Verificar que se creó la DeudaCuota para 2026-11 con estado PAGADO y precio del plan
        $deudaNov = DeudaCuota::where('alumno_id', $this->alumno->id)
            ->where('periodo', '2026-11')
            ->first();

        $this->assertNotNull($deudaNov);
        $this->assertEquals(35000.0, (float)$deudaNov->monto_original);
        $this->assertEquals(35000.0, (float)$deudaNov->monto_pagado);
        $this->assertEquals(DeudaCuota::ESTADO_PAGADA, $deudaNov->estado);

        // Verificar que el pago existe
        $pago = Pago::where('alumno_id', $this->alumno->id)
            ->where('estado', Pago::ESTADO_COMPLETADO)
            ->latest('id')
            ->first();
        $this->assertNotNull($pago);
        $this->assertEquals(35000.0, (float)$pago->monto_final);
    }

    // ── T18: el mes futuro no es deuda; se elige aparte, con importe y aviso ───────

    private function octubrePago(): void
    {
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => 35000,
            'monto_pagado' => 35000, 'estado' => DeudaCuota::ESTADO_PAGADA]);
    }

    public function test_t18_total_pendiente_no_suma_meses_que_no_empezaron(): void
    {
        // Debe solo octubre. Antes el cartel sumaba noviembre y diciembre: 105.000.
        $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id))
            ->assertOk()->assertSee('$35.000,00')->assertDontSee('105.000');
    }

    public function test_t18_sin_confirmar_no_se_cobra_un_mes_futuro(): void
    {
        $this->octubrePago();

        $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id, 'periodos' => ['2026-11'], 'montos_cuota' => ['2026-11' => 35000],
        ])->assertStatus(409)->assertJson(['requiere_confirmacion_adelantado' => true])
            ->assertJsonFragment(['message' => 'Estás cobrando por adelantado noviembre 2026, que todavía no empezó. El importe queda fijo aunque después cambie la cuota.']);

        $this->assertSame(0, DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->count());
        $this->assertSame(0, Pago::count());
    }

    public function test_t18_el_importe_adelantado_se_puede_cambiar_y_queda_fijo(): void
    {
        $this->octubrePago();

        // Se anunció un 10% de aumento para noviembre: se cobra 38.500 en vez de 35.000.
        foreach ([[$this->operativo, '2026-11'], [$this->admin, '2026-12']] as [$quien, $periodo]) {
            $this->actingAs($quien)->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id, 'periodos' => [$periodo], 'montos_cuota' => [$periodo => '38.500'],
                'confirmar_pago_adelantado' => 1,
            ])->assertSessionHas('success');

            $deuda = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', $periodo)->sole();
            $this->assertEquals(38500.0, (float) $deuda->monto_original);
            $this->assertEquals(38500.0, (float) $deuda->monto_pagado);
            $this->assertSame(DeudaCuota::ESTADO_PAGADA, $deuda->estado);
        }

        // El precio queda congelado: aunque el plan suba, el mes pago no vuelve a deber.
        $this->plan->update(['precio_mensual' => 45000]);
        Carbon::setTestNow('2026-11-01 06:00:00');
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $noviembre = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertEquals(38500.0, (float) $noviembre->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $noviembre->estado);
    }

    public function test_t18_no_se_cobra_mas_alla_de_doce_meses_ni_sin_importe(): void
    {
        $this->octubrePago();
        $base = ['tipo_caja_id' => $this->tipoCaja->id, 'confirmar_pago_adelantado' => 1];

        $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id),
            $base + ['periodos' => ['2027-11'], 'montos_cuota' => ['2027-11' => 35000]])->assertStatus(422);
        $this->actingAs($this->operativo)->postJson(route('web.caja.pagar', $this->alumno->id),
            $base + ['periodos' => ['2026-11']])->assertStatus(422);

        $this->assertSame(1, DeudaCuota::where('alumno_id', $this->alumno->id)->count());
        $this->assertSame(0, Pago::count());
    }

    public function test_a3_generacion_mensual_en_dia_1_no_duplica_el_mes_cobrado_adelantado(): void
    {
        // Supongamos que se cobró Noviembre 2026 por adelantado
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 35000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-11',
            'monto_original' => 35000,
            'monto_pagado' => 35000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        // Llega el 1 de noviembre de 2026 y corre el comando de generación mensual
        Carbon::setTestNow('2026-11-01 04:00:00');

        $exitCode = Artisan::call('cobranza:generar-deudas');
        $this->assertEquals(0, $exitCode);

        // Debe seguir habiendo exactamente 1 registro para 2026-11, con estado PAGADO
        $cuotasNov = DeudaCuota::where('alumno_id', $this->alumno->id)
            ->where('periodo', '2026-11')
            ->get();

        $this->assertCount(1, $cuotasNov);
        $this->assertEquals(DeudaCuota::ESTADO_PAGADA, $cuotasNov->first()->estado);
        $this->assertEquals(35000.0, (float)$cuotasNov->first()->monto_pagado);
    }

    public function test_a3_busqueda_en_caja_cobrar_encuentra_alumno_al_dia_para_cobro_adelantado(): void
    {
        // Martina está al día (octubre pagado)
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 35000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        // Al buscar "Stoessel" en /caja/cobrar, debe aparecer para permitir cobrarle adelantado
        $response = $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota', ['search' => 'Stoessel']));

        $response->assertOk();
        $response->assertSee('Stoessel, Martina');
        $response->assertSee(route('web.caja.cobrar', $this->alumno->id));
    }
}
