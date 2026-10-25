<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, Asistencia, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\{CambioPlanService, PagoCuotaService, RevisionCobranzaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrecioPlanVigenciaT44Test extends TestCase
{
    use RefreshDatabase;

    private GrupoPlan $plan;
    private Grupo $grupo;
    private User $admin;
    private PagoCuotaService $cuotas;
    private int $dni = 44000000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00'));
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        Storage::fake();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín T44', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial T44']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 40000, 'activo' => true]);
        $this->cuotas = app(PagoCuotaService::class);
    }

    private function alumno(string $alta = '2026-09-01', ?GrupoPlan $plan = null): Alumno
    {
        $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Vigencia', 'dni' => (string) ++$this->dni,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1144000000', 'fecha_alta' => $alta,
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => ($plan ?? $this->plan)->id, 'fecha_desde' => $alta, 'activo' => true]);
        return $alumno;
    }

    private function cambiar(string $dia = '2026-10-20', float $precio = 45000): void
    {
        $this->travelTo(Carbon::parse($dia.' 10:00:00'));
        $this->plan->update(['precio_mensual' => $precio]);
    }

    public function test_precio_anterior_en_el_mes_del_aumento_y_nuevo_al_mes_siguiente(): void
    {
        $alumno = $this->alumno();
        $this->assertSame(40000.0, $this->plan->precioParaPeriodo('2026-10', $alumno->fecha_alta));
        $this->cambiar();
        $this->assertSame(40000.0, $this->plan->precioParaPeriodo('2026-10', $alumno->fecha_alta));
        $this->assertSame(45000.0, $this->plan->precioParaPeriodo('2026-11', $alumno->fecha_alta));
        $this->assertSame(40000.0, $this->plan->precioParaPeriodo('2020-01', null));
    }

    public function test_alta_y_vista_previa_antes_y_despues_del_aumento_coinciden(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 10:00:00'));
        $antes = $this->alumno('2026-10-19');
        $this->actingAs($this->admin)->getJson(route('web.alumnos.cuota-alta-preview', [
            'fecha_alta' => '2026-10-19', 'plan_id' => $this->plan->id,
        ]))->assertOk()->assertJsonPath('importe', fn ($importe) => (float) $importe === 28000.0);
        $this->assertSame(28000.0, $this->cuotas->previsualizarCuotaAlta('2026-10-19', $this->plan->id)['importe']);
        $this->assertSame('28000.00', $this->cuotas->crearCuotaAlta($antes, $this->plan->id)->monto_original);
        $this->cambiar();
        $nuevo = $this->alumno('2026-10-22');
        $this->actingAs($this->admin)->getJson(route('web.alumnos.cuota-alta-preview', [
            'fecha_alta' => '2026-10-22', 'plan_id' => $this->plan->id,
        ]))->assertOk()->assertJsonPath('importe', fn ($importe) => (float) $importe === 31500.0);
        $this->assertSame(31500.0, $this->cuotas->previsualizarCuotaAlta('2026-10-22', $this->plan->id)['importe']);
        $this->assertSame('31500.00', $this->cuotas->crearCuotaAlta($nuevo, $this->plan->id)->monto_original);
        $this->assertSame(45000.0, $this->plan->precioParaPeriodo('2026-10', '2026-10-20'));
    }

    public function test_reingreso_conserva_fecha_de_alta_original_y_tarifa_anterior(): void
    {
        $alumno = $this->alumno();
        $alumno->update(['activo' => false]);
        $this->cambiar();
        $cuota = $this->cuotas->crearCuotaReingreso($alumno);
        $this->assertSame('28000.00', $cuota->monto_original);
        $this->assertSame('2026-10-20', $cuota->fecha_reingreso->toDateString());
        $this->assertSame('2026-09-01', $alumno->fresh()->fecha_alta->toDateString());
    }

    public function test_cuota_pasada_y_su_oferta_usan_el_precio_historico_del_mes(): void
    {
        $alumno = $this->alumno();
        $this->cambiar();
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        $meses = collect($this->cuotas->mesesPasadosSinCuota($alumno))->keyBy('periodo');
        $this->assertSame(40000.0, $meses['2026-10']['precio']);
        $this->assertSame('40000.00', $this->cuotas->crearCuotaMesPasado($alumno->id, '2026-10', $this->admin->id)->monto_original);
    }

    public function test_revision_continua_usa_precio_y_plan_del_periodo_objetivo(): void
    {
        $alumno = $this->alumno();
        $revision = AlumnoRevisionCobranza::create(['alumno_id' => $alumno->id, 'periodo_objetivo' => '2026-10',
            'motivo' => 'SIN_ASISTENCIAS_NI_ALTA_PAGO_RECIENTES', 'estado_revision' => 'PENDIENTE']);
        $this->cambiar();
        app(RevisionCobranzaService::class)->resolver($revision->id, 'CONTINUA', 'Confirmado', $this->admin->id);
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 40000]);
    }

    public function test_generador_del_dia_uno_toma_ultimo_precio_del_mes_anterior(): void
    {
        $alumno = $this->alumno();
        $this->cambiar();
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => '2026-10-21', 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);
        $this->travelTo(Carbon::parse('2026-11-01 09:00:00'));
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-11', 'monto_original' => 45000]);
        $this->plan->update(['precio_mensual' => 50000]);
        $this->assertSame(45000.0, $this->plan->precioParaPeriodo('2026-11', $alumno->fecha_alta));
        $this->assertSame(50000.0, $this->plan->precioParaPeriodo('2026-12', $alumno->fecha_alta));
    }

    public function test_cobrar_ofrece_actual_anterior_y_adelantados_nuevos(): void
    {
        $alumno = $this->alumno();
        $this->cambiar();
        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $alumno->id))->assertOk()
            // T77: el precio corriente se sugiere en el selector manual, sin inventar deuda.
            ->assertViewHas('alumno', fn ($a) => $a->deudaCuotas->isEmpty())
            ->assertViewHas('mesesAdelantables', fn ($meses) => $meses->firstWhere('periodo', '2026-10')['precio'] === 40000.0)
            ->assertViewHas('mesesAdelantables', fn ($meses) => $meses->firstWhere('periodo', '2026-11')['precio'] === 45000.0);
        $this->assertDatabaseMissing('deuda_cuotas', ['alumno_id' => $alumno->id]);
    }

    public function test_cobro_tardio_del_mes_del_cambio_no_usa_la_tarifa_nueva(): void
    {
        $alumno = $this->alumno();
        $this->cambiar();
        $resultado = $this->cuotas->registrarPagoCuotaAdmin(['alumno_id' => $alumno->id, 'tipo_caja_id' => TipoCaja::where('nombre', 'Efectivo')->firstOrFail()->id,
            'usuario_admin_id' => $this->admin->id,
            'items' => [['periodo' => '2026-10', 'monto' => 40000]], 'monto_entregado' => 40000, 'fecha_pago' => '2026-10-20']);
        $this->assertSame(40000.0, (float) $resultado['pago']->monto_cuota);
        $this->assertDatabaseHas('deuda_cuotas', ['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 40000, 'estado' => 'PAGADA']);
    }

    public function test_dos_aumentos_en_el_mes_segun_decision_de_carlos(): void
    {
        $viejo = $this->alumno();
        $this->cambiar();
        $alta22 = $this->alumno('2026-10-22');
        $this->cambiar('2026-10-25', 50000);
        $this->assertSame(40000.0, $this->plan->precioParaPeriodo('2026-10', $viejo->fecha_alta));
        $this->assertSame(45000.0, $this->plan->precioParaPeriodo('2026-10', $alta22->fecha_alta));
        $this->assertSame(50000.0, $this->plan->precioParaPeriodo('2026-10', '2026-10-26'));
        $this->assertSame(50000.0, $this->plan->precioParaPeriodo('2026-11', $viejo->fecha_alta));
        $this->assertSame(50000.0, $this->plan->precioParaPeriodo('2026-11', $alta22->fecha_alta));
    }

    public function test_editar_por_pantalla_registra_historial_y_no_recalcula_cuotas_generadas(): void
    {
        $alumno = $this->alumno();
        $cuota = $this->cuotas->crearDeudaSiNoExiste($alumno->id, '2026-11', 39000.50);
        $anterior = $cuota->fresh()->getRawOriginal();
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $this->actingAs($this->admin)->put(route('web.grupos.update', $this->grupo->id), [
            'planes' => [['id' => $this->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => 45000]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('web.grupos.index'));
        $this->assertDatabaseHas('grupo_plan_precios', ['grupo_plan_id' => $this->plan->id, 'vigente_desde' => '2026-10-20', 'precio_mensual' => 45000]);
        $this->assertSame($anterior, $cuota->fresh()->getRawOriginal());
        $this->assertSame('39000.50', $this->cuotas->crearDeudaSiNoExiste($alumno->id, '2026-11', 45000)->monto_original);
    }

    public function test_guardar_sin_cambio_de_precio_no_agrega_vigencia(): void
    {
        $this->plan->update(['precio_mensual' => '40000.00']);
        $this->assertSame(1, DB::table('grupo_plan_precios')->where('grupo_plan_id', $this->plan->id)->count());
    }

    public function test_transaccion_fallida_no_deja_precio_ni_vigencia_a_medias(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        try {
            DB::transaction(function () {
                $this->plan->update(['precio_mensual' => 45000]);
                throw new \RuntimeException('Revertir');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Revertir', $e->getMessage());
        }
        $this->assertSame('40000.00', $this->plan->fresh()->precio_mensual);
        $this->assertSame(1, DB::table('grupo_plan_precios')->where('grupo_plan_id', $this->plan->id)->count());
    }

    public function test_cobro_adelantado_t18_congela_importe_aunque_despues_aumente_el_plan(): void
    {
        $alumno = $this->alumno();
        $this->cuotas->crearDeudaSiNoExiste($alumno->id, '2026-10', 40000)
            ->update(['monto_pagado' => 40000, 'estado' => DeudaCuota::ESTADO_PAGADA]);
        $respuesta = $this->actingAs($this->admin)->post(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => TipoCaja::where('nombre', 'Efectivo')->firstOrFail()->id,
            'periodos' => ['2026-11'], 'montos_cuota' => ['2026-11' => 42000.50],
            'confirmar_pago_adelantado' => 1, 'fecha_pago' => '2026-10-01',
        ]);
        $this->assertSame(302, $respuesta->status(), $respuesta->getContent());
        $respuesta->assertSessionHasNoErrors()->assertSessionMissing('error')->assertSessionHas('success');
        $this->cambiar();
        $cuota = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertSame('42000.50', $cuota->monto_original);
        $this->assertSame('42000.50', $cuota->monto_pagado);
        $this->travelTo(Carbon::parse('2026-11-01 09:00:00'));
        $this->artisan('cobranza:generar-deudas')->assertSuccessful();
        $this->assertSame('42000.50', $cuota->fresh()->monto_original);
        $this->assertSame(1, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-11')->count());
    }

    public function test_cambios_del_mismo_dia_y_centavos_se_conservan(): void
    {
        $this->cambiar('2026-10-20', 45000.50);
        $this->cambiar('2026-10-20', 50000.75);
        $this->assertSame(50000.75, $this->plan->precioParaPeriodo('2026-10', '2026-10-20'));
        $this->assertSame(50000.75, $this->plan->precioParaPeriodo('2026-11', '2026-09-01'));
        $this->assertSame(3, DB::table('grupo_plan_precios')->where('grupo_plan_id', $this->plan->id)->count());
    }

    public function test_bajada_sin_asistencias_se_difiere_y_conserva_plan_y_cuota_del_mes(): void
    {
        $alumno = $this->alumno();
        $cuota = $this->cuotas->crearDeudaSiNoExiste($alumno->id, '2026-10', 40000);
        $bajo = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
        $this->cambiar();
        $bajo->update(['precio_mensual' => 25000]);
        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $alumno->id))->assertOk()
            ->assertViewHas('planesDisponibles', fn ($planes) => $planes->firstWhere('id', $bajo->id)->precio_ofrecido === 25000.0
                && $planes->firstWhere('id', $bajo->id)->precio_mes === 40000.0);
        $this->actingAs($this->admin)->get(route('web.alumnos.edit', $alumno->id))->assertOk()
            ->assertViewHas('grupoPlanesJson', fn ($grupos) => $grupos->get($this->grupo->id)->firstWhere('id', $bajo->id)['precio'] === 25000.0);
        $programado = app(CambioPlanService::class)->cambiar($alumno, $bajo);
        $this->assertSame('2026-11-01', $programado->fecha_desde->toDateString());
        $this->assertSame($this->plan->id, $alumno->fresh()->planActivo->plan_id);
        $this->assertSame('40000.00', $cuota->fresh()->monto_original);
        $this->assertSame($this->plan->id, $this->cuotas->obtenerPlanParaPeriodo($alumno->id, '2026-10')->plan_id);
        $this->assertSame($bajo->id, $this->cuotas->obtenerPlanParaPeriodo($alumno->id, '2026-11')->plan_id);
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $this->assertSame($bajo->id, $alumno->fresh()->planActivo->plan_id);
    }

    public function test_subida_con_pago_parcial_ajusta_saldo_del_mes_y_cobra_lo_anunciado(): void
    {
        $alumno = $this->alumno();
        $cuota = $this->cuotas->crearDeudaSiNoExiste($alumno->id, '2026-10', 40000);
        $cuota->update(['monto_pagado' => 10000]);
        $alto = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 3, 'precio_mensual' => 60000, 'activo' => true]);
        $this->cambiar();
        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $alumno->id))->assertOk()
            ->assertViewHas('planesDisponibles', fn ($planes) => $planes->firstWhere('id', $alto->id)->precio_mes === 60000.0);
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => TipoCaja::where('nombre', 'Efectivo')->firstOrFail()->id,
            'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 50000], 'nuevo_plan_id' => $alto->id,
            'fecha_pago' => '2026-10-20',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('60000.00', $cuota->fresh()->monto_original);
        $this->assertSame('60000.00', $cuota->fresh()->monto_pagado);
        $this->assertSame($alto->id, $alumno->fresh()->planActivo->plan_id);
        $this->assertSame('2026-10-20', $alumno->fresh()->planActivo->fecha_desde->toDateString());
    }

    public function test_subida_con_mes_saldado_se_programa_sin_modificar_la_cuota(): void
    {
        $alumno = $this->alumno();
        $cuota = $this->cuotas->crearDeudaSiNoExiste($alumno->id, '2026-10', 40000);
        $cuota->update(['monto_pagado' => 40000, 'estado' => DeudaCuota::ESTADO_PAGADA]);
        $alto = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 3, 'precio_mensual' => 60000, 'activo' => true]);
        $this->cambiar();
        $asignacion = app(CambioPlanService::class)->cambiar($alumno, $alto);
        $this->assertSame('2026-11-01', $asignacion->fecha_desde->toDateString());
        $this->assertSame('40000.00', $cuota->fresh()->monto_original);
        $this->assertSame($this->plan->id, $alumno->fresh()->planActivo->plan_id);
    }

    public function test_cambio_desde_ficha_usa_la_misma_regla_de_bajada_futura(): void
    {
        $alumno = $this->alumno();
        $bajo = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 20000, 'activo' => true]);
        $this->cambiar();
        $datos = $alumno->only(['nombre', 'apellido', 'dni', 'celular', 'deporte_id', 'grupo_id']);
        $this->actingAs($this->admin)->put(route('web.alumnos.update', $alumno->id), $datos + [
            'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => '2026-09-01', 'plan_id' => $bajo->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($this->plan->id, $alumno->fresh()->planActivo->plan_id);
        $this->assertSame($bajo->id, $alumno->fresh()->planProgramado->plan_id);
    }

    public function test_subida_de_cuota_de_alta_con_pago_parcial_conserva_el_porcentaje_original(): void
    {
        $this->cambiar();
        $this->travelTo(Carbon::parse('2026-10-22 10:00:00'));
        $alumno = $this->alumno('2026-10-22');
        $cuota = $this->cuotas->crearCuotaAlta($alumno, $this->plan->id);
        $this->assertSame('31500.00', $cuota->monto_original);
        $cuota->update(['monto_pagado' => 10000]);
        $alto = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 3, 'precio_mensual' => 60000, 'activo' => true]);
        // El porcentaje del día 24 sería 40%; la cuota ya nació al 70% el día 22.
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $alumno->id))->assertOk()
            ->assertViewHas('planesDisponibles', fn ($planes) => $planes->firstWhere('id', $alto->id)->precio_mes === 42000.0);
        $this->actingAs($this->admin)->post(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => TipoCaja::where('nombre', 'Efectivo')->firstOrFail()->id,
            'periodos' => ['2026-10'], 'montos_cuota' => ['2026-10' => 32000], 'nuevo_plan_id' => $alto->id,
            'fecha_pago' => '2026-10-24',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('42000.00', $cuota->fresh()->monto_original);
        $this->assertSame('42000.00', $cuota->fresh()->monto_pagado);
        $this->assertSame(70.0, (float) $cuota->fresh()->porcentaje_alta);
    }
}
