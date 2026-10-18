<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CashflowMovimiento, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use App\Services\{PagoCuotaService, ReporteMensualService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T39 (DU-21, Carlos 13/10): al anular un cobro del dueño, la resta va con la fecha del cobro
 * original, como al eliminar un movimiento directo en Cashflow. La deuda vuelve en ese mismo mes.
 */
class AnulacionFechaOriginalT39Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Alumno $alumno;
    private TipoCaja $medio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        $this->travelTo(Carbon::parse('2026-09-25 10:00:00'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->medio = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $deporte = Deporte::create(['nombre' => 'Patín T39', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T39']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Anulada', 'dni' => '39000001',
            'fecha_nacimiento' => '2010-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-03-01',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-09',
            'monto_original' => 10000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }

    private function cobrar(?string $fechaPago = null): \App\Models\Pago
    {
        return app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
            'tipo_caja_id' => $this->medio->id, 'usuario_admin_id' => $this->admin->id, 'monto_entregado' => 10000,
            'items' => [['periodo' => '2026-09', 'monto' => 10000]]] + ($fechaPago ? ['fecha_pago' => $fechaPago] : []))['pago'];
    }

    private function contra(\App\Models\Pago $pago): CashflowMovimiento
    {
        return CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)
            ->where('referencia_id', $pago->id)->where('monto', '<', 0)->sole();
    }

    public function test_anular_en_octubre_un_cobro_de_septiembre_resta_en_septiembre(): void
    {
        $pago = $this->cobrar();
        $original = CashflowMovimiento::where('referencia_id', $pago->id)->where('monto', '>', 0)->sole();

        $this->travelTo(Carbon::parse('2026-10-10 10:00:00'));
        $this->actingAs($this->admin)
            ->post(route('web.pagos.anular', $pago->id), ['motivo' => 'Se cargó al alumno equivocado'])
            ->assertRedirect(route('web.alumnos.show', $this->alumno->id));

        $contra = $this->contra($pago);
        $this->assertSame('2026-09-25', $contra->fecha->toDateString());
        $this->assertSame(-1 * (float) $original->monto, (float) $contra->monto);
        $this->assertSame($original->tipo_caja_id, $contra->tipo_caja_id);
        $this->assertSame($original->subrubro_id, $contra->subrubro_id);
        // El original no se toca.
        $this->assertSame('2026-09-25', $original->fresh()->fecha->toDateString());
    }

    public function test_un_cobro_cargado_con_fecha_anterior_se_anula_con_esa_fecha(): void
    {
        $this->travelTo(Carbon::parse('2026-10-02 10:00:00'));
        $pago = $this->cobrar('2026-09-28');

        $this->travelTo(Carbon::parse('2026-10-15 10:00:00'));
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Fecha mal cargada', $this->admin->id);

        $this->assertSame('2026-09-28', $this->contra($pago)->fecha->toDateString());
    }

    public function test_la_deuda_vuelve_en_el_mismo_mes_que_la_plata(): void
    {
        $pago = $this->cobrar();
        $this->travelTo(Carbon::parse('2026-10-10 10:00:00'));
        $reportes = app(ReporteMensualService::class);
        $septiembre = $reportes->obtener('2026-09');
        $octubre = $reportes->obtener('2026-10');

        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Cobro duplicado', $this->admin->id);

        $despues = $reportes->obtener('2026-09');
        $this->assertSame($septiembre['resultado_global'] - 1000000, $despues['resultado_global']);
        $this->assertSame($septiembre['deuda']['total'] + 1000000, $despues['deuda']['total']);
        $this->assertSame($octubre['resultado_global'], $reportes->obtener('2026-10')['resultado_global']);
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, DeudaCuota::where('periodo', '2026-09')->sole()->estado);
    }

    public function test_la_inscripcion_cobrada_vuelve_a_deberse_en_el_mes_del_cobro(): void
    {
        app(\App\Services\InscripcionService::class)->sincronizar($this->alumno, $this->admin->id);
        $cargo = app(\App\Services\InscripcionService::class)->cargo($this->alumno->dni);
        $inscripcion = (float) $cargo->monto_original;
        $pago = app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
            'tipo_caja_id' => $this->medio->id, 'usuario_admin_id' => $this->admin->id,
            'monto_entregado' => 10000 + $inscripcion, 'items' => [['periodo' => '2026-09', 'monto' => 10000]]])['pago'];
        $this->assertSame(0.0, $cargo->fresh()->saldo_pendiente);

        $this->travelTo(Carbon::parse('2026-10-10 10:00:00'));
        $reportes = app(ReporteMensualService::class);
        $antes = $reportes->obtener('2026-09')['deuda']['inscripciones'];
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Cobro duplicado', $this->admin->id);

        $this->assertSame($antes + (int) round($inscripcion * 100), $reportes->obtener('2026-09')['deuda']['inscripciones']);
        $this->assertTrue(CashflowMovimiento::where('referencia_id', $pago->id)->where('monto', '<', 0)->get()
            ->every(fn ($m) => $m->fecha->toDateString() === '2026-09-25'));
    }

    public function test_el_saldo_del_medio_de_pago_queda_igual_que_antes_del_cobro(): void
    {
        $pago = $this->cobrar();
        $this->travelTo(Carbon::parse('2026-10-10 10:00:00'));
        app(PagoCuotaService::class)->anularCobroAdmin($pago->id, 'Cobro duplicado', $this->admin->id);

        $this->assertEquals(0, CashflowMovimiento::where('referencia_id', $pago->id)
            ->where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)->sum('monto'));
    }
}
