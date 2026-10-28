<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, DeudaCuota, Deporte, Grupo, GrupoPlan, Nivel, Pago, TipoCaja, User};
use App\Services\PagoCuotaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T25 (Carlos, 11/10 y 28/10): cobrarle a un alumno inactivo pregunta si pasa a activo,
 * con Sí, No y Cancelar. «No» cobra la deuda vieja y lo deja inactivo, sin cuota del mes.
 * Si el cobro incluye el mes en curso o uno futuro, no se ofrece «No»: pagar el mes es volver.
 */
class CobroInactivoT25Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private GrupoPlan $plan;
    private Grupo $grupo;
    private TipoCaja $efectivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $this->withoutVite();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $deporte = Deporte::create(['nombre' => 'Patín T25', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T25']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    }

    public function test_cobrar_deuda_vieja_a_un_inactivo_pregunta_y_ofrece_no(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();
        $pagosAntes = Pago::count();

        $this->cobrar($alumno, ['2026-09'])
            ->assertStatus(409)
            ->assertJson(['requiere_reactivacion' => true, 'permite_no' => true])
            ->assertJsonFragment(['message' => 'Inactivo, Ana está inactivo. ¿Pasa a activo? Si vuelve, se le genera la cuota de octubre.']);

        $this->assertSame($pagosAntes, Pago::count());
        $this->assertFalse($alumno->fresh()->activo);
    }

    public function test_no_cobra_la_deuda_vieja_y_lo_deja_inactivo_sin_cuota_del_mes(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        $this->cobrar($alumno, ['2026-09'], ['reactivar' => 0])->assertRedirect(route('web.caja.index'));

        $this->assertFalse($alumno->fresh()->activo);
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-09')->sole()->estado);
        $this->assertSame(0, DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->count());
    }

    public function test_si_cobra_y_reactiva_como_hasta_ahora(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        $this->cobrar($alumno, ['2026-09'], ['reactivar' => 1])->assertRedirect(route('web.caja.index'));

        $this->assertTrue($alumno->fresh()->activo);
        $octubre = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', '2026-10')->sole();
        $this->assertSame('2026-10-20', $octubre->fecha_reingreso->toDateString());
    }

    public function test_si_el_cobro_incluye_el_mes_no_se_ofrece_no(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();
        $this->cuota($alumno, '2026-10', 10000);

        $this->cobrar($alumno, ['2026-09', '2026-10'])
            ->assertStatus(409)
            ->assertJson(['requiere_reactivacion' => true, 'permite_no' => false]);
    }

    public function test_no_forzado_con_el_mes_se_rechaza_sin_cobrar(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();
        $this->cuota($alumno, '2026-10', 10000);
        $pagosAntes = Pago::count();

        $this->cobrar($alumno, ['2026-09', '2026-10'], ['reactivar' => 0])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.']);

        $this->assertSame($pagosAntes, Pago::count());
        $this->assertFalse($alumno->fresh()->activo);
    }

    public function test_el_servicio_tambien_rechaza_no_con_el_mes(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();
        $this->cuota($alumno, '2026-10', 10000);

        $this->expectExceptionMessage('para cobrarlo, el alumno tiene que volver a activo');
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $alumno->id, 'tipo_caja_id' => $this->efectivo->id,
            'items' => [['periodo' => '2026-10', 'monto' => 10000]], 'usuario_admin_id' => $this->admin->id,
            'monto_entregado' => 10000, 'reactivar' => false]);
    }

    public function test_a_un_activo_no_se_le_pregunta(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();
        $alumno->update(['activo' => true]);

        $this->cobrar($alumno, ['2026-09'])->assertRedirect(route('web.caja.index'));
    }

    public function test_sin_plan_para_el_mes_no_se_pregunta_porque_no_se_reactivaria(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();
        AlumnoPlan::where('alumno_id', $alumno->id)->update(['fecha_hasta' => '2026-09-30', 'activo' => false]);

        $this->cobrar($alumno, ['2026-09'])->assertRedirect(route('web.caja.index'));
        $this->assertFalse($alumno->fresh()->activo);
    }

    public function test_la_pantalla_tiene_el_panel_con_si_no_y_cancelar(): void
    {
        $alumno = $this->inactivoQueDebeSeptiembre();

        $this->actingAs($this->admin)->get(route('web.caja.cobrar', $alumno->id))->assertOk()
            ->assertSee('id="modal-reactivar"', false)
            ->assertSeeInOrder(['id="reactivar-cancelar"', 'Cancelar', 'id="reactivar-no"', 'No', 'id="reactivar-si"', 'Sí'], false);
    }

    /** Pagó agosto, debe septiembre y se dio de baja. */
    private function inactivoQueDebeSeptiembre(): Alumno
    {
        $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Inactivo', 'dni' => '25000001',
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-03-01',
            'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
        $this->cuota($alumno, '2026-08', 10000);
        $this->cuota($alumno, '2026-09', 10000);
        app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $alumno->id, 'tipo_caja_id' => $this->efectivo->id,
            'items' => [['periodo' => '2026-08', 'monto' => 10000]], 'usuario_admin_id' => $this->admin->id, 'monto_entregado' => 10000]);
        $alumno->update(['activo' => false]);
        return $alumno->fresh();
    }

    private function cuota(Alumno $alumno, string $periodo, float $monto): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo,
            'monto_original' => $monto, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function cobrar(Alumno $alumno, array $periodos, array $extra = [])
    {
        $deudas = DeudaCuota::where('alumno_id', $alumno->id)->whereIn('periodo', $periodos)->get();
        return $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $alumno->id), $extra + [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => $periodos,
            'montos_cuota' => $deudas->mapWithKeys(fn ($d) => [$d->periodo => (float) $d->saldo_pendiente])->all(),
        ]);
    }
}
