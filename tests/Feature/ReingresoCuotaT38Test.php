<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\{CobranzaEstadoService, PagoCuotaService, PunitorioService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T38 (DU-12, Carlos 13/10): al reactivar a un alumno se le genera la cuota del mes en
 * curso con el porcentaje de reingreso del día, salvo que ya la tenga; la deuda que tenía
 * se mantiene. Esa cuota tiene el mismo plazo que una de alta (Carlos, 17/10).
 *
 * Reglas de CatalogosSeeder: días 1-15 al 100 %, 16-23 al 70 %, 24-31 al 40 %.
 * Gracia general 10 días; plazo de alta 7 días; día de mora 10.
 */
class ReingresoCuotaT38Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private TipoCaja $efectivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $deporte = Deporte::create(['nombre' => 'Patín T38', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T38']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    }

    // ── Reactivar por la ficha y por el listado ───────────────────────────────

    public function test_reactivar_genera_la_cuota_del_mes_con_el_porcentaje_del_dia_y_conserva_la_deuda_vieja(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $septiembre = $this->cuota($alumno, '2026-09', 10000);

        $this->actingAs($this->operativo)
            ->from(route('web.alumnos.show', $alumno->id))
            ->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertRedirect(route('web.alumnos.show', $alumno->id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Se generó la cuota de octubre') && str_contains($m, '70 % por reingreso'));

        $this->assertTrue($alumno->fresh()->activo);
        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame('7000.00', $octubre->monto_original);
        $this->assertSame('70.00', $octubre->porcentaje_alta);
        $this->assertSame('2026-10-20', $octubre->fecha_reingreso->toDateString());
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $octubre->estado);
        // La deuda que tenía queda como estaba.
        $this->assertSame('10000.00', $septiembre->fresh()->monto_original);
        $this->assertSame('0.00', $septiembre->fresh()->monto_pagado);
        $this->assertNull($septiembre->fresh()->fecha_reingreso);
    }

    public function test_lo_que_ve_la_operativa_despues_de_reactivar(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');

        $this->actingAs($this->operativo)
            ->from(route('web.alumnos.show', $alumno->id))
            ->followingRedirects()
            ->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertOk()
            ->assertSee('Se generó la cuota de octubre')
            ->assertSee('70 % por reingreso');

        $this->actingAs($this->operativo)
            ->get(route('web.cobranza.index', ['estado' => 'EN_PLAZO']))
            ->assertOk()
            ->assertSee('Reingreso');
    }

    public function test_el_interruptor_del_listado_tambien_la_genera(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');

        $this->actingAs($this->admin)
            ->patchJson(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertOk()
            ->assertJson(['activo' => true]);

        $this->assertSame('7000.00', DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole()->monto_original);
    }

    public function test_si_ya_tiene_la_cuota_del_mes_no_se_genera_otra_ni_se_toca(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $octubre = $this->cuota($alumno, '2026-10', 10000);

        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertSessionHas('success', 'Alumno activado correctamente.');

        $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->count());
        $this->assertSame('10000.00', $octubre->fresh()->monto_original);
        $this->assertNull($octubre->fresh()->fecha_reingreso);
        $this->assertNull($octubre->fresh()->porcentaje_alta);
    }

    public function test_dar_de_baja_y_volver_a_reactivar_en_el_mismo_mes_no_duplica(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id));
        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id));
        $this->assertFalse($alumno->fresh()->activo);

        $this->travelTo(Carbon::parse('2026-10-28 10:00:00'));
        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id));

        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame('7000.00', $octubre->monto_original);
        $this->assertSame('2026-10-20', $octubre->fecha_reingreso->toDateString());
    }

    public function test_dar_de_baja_no_genera_nada(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $alumno->update(['activo' => true]);

        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertSessionHas('success', 'Alumno desactivado correctamente.');

        $this->assertFalse($alumno->fresh()->activo);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->count());
    }

    public function test_sin_plan_para_el_mes_no_se_reactiva_y_avisa(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01', conPlan: false);
        $mensaje = 'El alumno no tiene un plan para este mes. Asigná un plan antes de reactivarlo.';

        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertSessionHas('error', $mensaje);
        $this->actingAs($this->admin)->patchJson(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertStatus(422)->assertJson(['error' => $mensaje]);

        $this->assertFalse($alumno->fresh()->activo);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->count());
    }

    public function test_si_todavia_no_empezo_reactivar_no_genera_la_cuota_de_este_mes(): void
    {
        $alumno = $this->alumnoInactivo('2026-11-05');

        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertSessionHas('success', 'Alumno activado correctamente.');

        $this->assertTrue($alumno->fresh()->activo);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->count());
    }

    public function test_sin_una_unica_regla_para_el_dia_va_al_100(): void
    {
        DB::table('reglas_primer_pago')->where('dia_desde', 16)->delete();
        $alumno = $this->alumnoInactivo('2026-03-01');

        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Se generó la cuota de octubre') && !str_contains($m, 'reingreso'));

        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame('10000.00', $octubre->monto_original);
        $this->assertSame('100.00', $octubre->porcentaje_alta);
    }

    public function test_reactivar_responde_la_revision_pendiente_del_mes(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $revision = AlumnoRevisionCobranza::create(['alumno_id' => $alumno->id, 'periodo_objetivo' => '2026-10',
            'motivo' => 'SIN_ASISTENCIAS_NI_ALTA_PAGO_RECIENTES', 'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE]);

        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id));

        $this->assertSame(AlumnoRevisionCobranza::ESTADO_RESUELTO, $revision->fresh()->estado_revision);
        $this->assertSame(AlumnoRevisionCobranza::RESOLUCION_REACTIVADO_AUTO, $revision->fresh()->resolucion);
    }

    // ── Plazo: el mismo que una cuota de alta ─────────────────────────────────

    public function test_la_cuota_del_reingreso_esta_en_plazo_siete_dias_y_despues_vence(): void
    {
        $alumno = $this->reactivar($this->alumnoInactivo('2026-03-01'));
        $servicio = app(CobranzaEstadoService::class);

        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $servicio->estadoAlumno($alumno->id, Carbon::parse('2026-10-20'))['estado']);
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $servicio->estadoAlumno($alumno->id, Carbon::parse('2026-10-26'))['estado']);
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $servicio->estadoAlumno($alumno->id, Carbon::parse('2026-10-27'))['estado']);
        // El listado usa el mismo cálculo.
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO,
            $servicio->estadosParaAlumnos(collect([$alumno->fresh()]), Carbon::parse('2026-10-22'))[$alumno->id]);
    }

    public function test_reactivado_a_fin_de_mes_el_plazo_cruza_al_mes_siguiente(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 10:00:00'));
        $alumno = $this->reactivar($this->alumnoInactivo('2026-03-01'));
        $this->assertSame('4000.00', DeudaCuota::where('alumno_id', $alumno->id)->sole()->monto_original);
        $servicio = app(CobranzaEstadoService::class);

        $this->travelTo(Carbon::parse('2026-11-03 10:00:00'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $servicio->estadoAlumno($alumno->id)['estado']);
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $servicio->estadoAlumno($alumno->id)['estado']);
    }

    public function test_las_demas_cuotas_del_alumno_siguen_con_su_vencimiento(): void
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $this->cuota($alumno, '2026-09', 10000);
        $this->reactivar($alumno);

        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, app(CobranzaEstadoService::class)->estadoAlumno($alumno->id)['estado']);
    }

    public function test_el_recargo_por_mora_espera_el_plazo_del_reingreso(): void
    {
        Configuracion::set('mora_porcentaje', '5');
        $control = $this->alumnoInactivo('2026-03-01');
        $control->update(['activo' => true]);
        $cuotaControl = $this->cuota($control, '2026-10', 10000);
        $alumno = $this->reactivar($this->alumnoInactivo('2026-03-01', dni: '38000002'));
        $cuota = DeudaCuota::where('alumno_id', $alumno->id)->sole();

        $this->travelTo(Carbon::parse('2026-10-21 06:15:00'));
        app(PunitorioService::class)->aplicar();
        $this->assertTrue(CargoAlumno::where('deuda_cuota_id', $cuotaControl->id)->exists());
        $this->assertFalse(CargoAlumno::where('deuda_cuota_id', $cuota->id)->exists());

        $this->travelTo(Carbon::parse('2026-10-26 06:15:00'));
        app(PunitorioService::class)->aplicar();
        $this->assertFalse(CargoAlumno::where('deuda_cuota_id', $cuota->id)->exists());

        $this->travelTo(Carbon::parse('2026-10-27 06:15:00'));
        app(PunitorioService::class)->aplicar();
        // 5 % sobre lo que vale la cuota del reingreso.
        $this->assertSame('350.00', CargoAlumno::where('deuda_cuota_id', $cuota->id)->sole()->monto_original);
    }

    // ── Cobrarle a un inactivo también lo reactiva ────────────────────────────

    public function test_cobrar_solo_deuda_vieja_a_un_inactivo_genera_la_cuota_del_mes(): void
    {
        $alumno = $this->alumnoInactivoConPagos();

        $this->cobrarAdmin($alumno, [['periodo' => '2026-09', 'monto' => 10000]]);

        $this->assertTrue($alumno->fresh()->activo);
        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame('7000.00', $octubre->monto_original);
        $this->assertSame('0.00', $octubre->monto_pagado);
        $this->assertSame('2026-10-20', $octubre->fecha_reingreso->toDateString());
    }

    public function test_cobrar_parte_del_mes_a_un_inactivo_deja_el_saldo_con_plazo(): void
    {
        $alumno = $this->alumnoInactivoConPagos();

        $this->cobrarOperativo($alumno, [['periodo' => '2026-09', 'monto' => 10000], ['periodo' => '2026-10', 'monto' => 3000]]);

        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame('7000.00', $octubre->monto_original);
        $this->assertSame('3000.00', $octubre->monto_pagado);
        $this->assertSame('70.00', $octubre->porcentaje_alta);
        $this->assertSame('2026-10-20', $octubre->fecha_reingreso->toDateString());
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO,
            app(CobranzaEstadoService::class)->estadoAlumno($alumno->id, Carbon::parse('2026-10-25'))['estado']);
    }

    public function test_cobrar_a_un_activo_no_cambia(): void
    {
        $alumno = $this->alumnoInactivoConPagos();
        $alumno->update(['activo' => true]);

        $this->cobrarAdmin($alumno, [['periodo' => '2026-09', 'monto' => 10000]]);

        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->count());
    }

    public function test_el_cobro_no_recalcula_la_cuota_del_reingreso_en_el_mes_del_alta(): void
    {
        // Alta el 17 (70 %), baja, y vuelve el 24 (40 %): vale lo del reingreso.
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $alumno = $this->reactivar($this->alumnoInactivo('2026-10-17'));
        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->sole();
        $this->assertSame('4000.00', $octubre->monto_original);

        $this->cobrarAdmin($alumno, [['periodo' => '2026-10', 'monto' => 4000]]);

        $this->assertSame('4000.00', $octubre->fresh()->monto_original);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $octubre->fresh()->estado);
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────

    private function alumnoInactivo(string $fechaAlta, bool $conPlan = true, string $dni = '38000001'): Alumno
    {
        $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Reingreso', 'dni' => $dni,
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => $fechaAlta,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        if ($conPlan) {
            AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => $fechaAlta, 'activo' => true]);
        }
        $alumno->update(['activo' => false]);
        return $alumno;
    }

    /** Pagó agosto y debe septiembre: «ya conoce el lugar». */
    private function alumnoInactivoConPagos(): Alumno
    {
        $alumno = $this->alumnoInactivo('2026-03-01');
        $alumno->update(['activo' => true]);
        $this->cuota($alumno, '2026-08', 10000);
        $this->cuota($alumno, '2026-09', 10000);
        $this->cobrarAdmin($alumno, [['periodo' => '2026-08', 'monto' => 10000]]);
        $alumno->update(['activo' => false]);
        return $alumno;
    }

    private function reactivar(Alumno $alumno): Alumno
    {
        $this->actingAs($this->operativo)->patch(route('web.alumnos.toggle-activo', $alumno->id))->assertSessionHasNoErrors();
        $this->assertTrue($alumno->fresh()->activo);
        return $alumno->fresh();
    }

    private function cuota(Alumno $alumno, string $periodo, float $monto): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo,
            'monto_original' => $monto, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function cobrarAdmin(Alumno $alumno, array $items): array
    {
        return app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['motivo' => 'Parcial autorizado para la prueba de regresion', 'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->efectivo->id, 'items' => $items, 'usuario_admin_id' => $this->admin->id,
            'monto_entregado' => array_sum(array_column($items, 'monto'))]);
    }

    private function cobrarOperativo(Alumno $alumno, array $items): array
    {
        $caja = app(\App\Services\CajaService::class);
        $caja->configurarMostrador($this->efectivo->id, $this->admin->id);
        $caja->abrirCajaOperativa($this->operativo->id, ['efectivo_inicial' => 0, 'confirmacion' => 1, 'caja_origen_id' => null]);
        return app(PagoCuotaService::class)->registrarPagoCuotaOperativo(['motivo' => 'Parcial autorizado para la prueba de regresion', 'alumno_id' => $alumno->id,
            'tipo_caja_id' => $this->efectivo->id, 'items' => $items, 'usuario_operativo_id' => $this->operativo->id,
            'monto_entregado' => array_sum(array_column($items, 'monto'))]);
    }
}
