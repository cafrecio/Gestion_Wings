<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CambioPlanCobroTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private TipoCaja $tipoCaja;
    private Alumno $alumno;
    private AlumnoPlan $planAlumnoActual;
    private GrupoPlan $planBajo;
    private GrupoPlan $planAlto;
    private GrupoPlan $planSuperior;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-15 10:00:00');

        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Caja General', 'activo' => true]);
        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Hockey',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Cambio de plan']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->planBajo = $this->crearPlan(2, 20000);
        $this->planAlto = $this->crearPlan(4, 40000);
        $this->planSuperior = $this->crearPlan(6, 60000);

        $this->alumno = Alumno::create([
            'nombre' => 'Plan',
            'apellido' => 'Prueba',
            'dni' => '30111222',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);
        $this->planAlumnoActual = AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->planAlto->id,
            'fecha_desde' => '2026-01-01',
            'activo' => true,
        ]);
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => 40000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_baja_con_asistencia_aplica_desde_el_mes_siguiente(): void
    {
        $this->registrarAsistenciaEsteMes();

        $this->cobrarConPlan($this->planBajo, 40000);

        $this->assertDatabaseHas('alumno_planes', [
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->planBajo->id,
            'fecha_desde' => '2026-09-01 00:00:00',
        ]);
        $this->assertDatabaseHas('alumno_planes', [
            'id' => $this->planAlumnoActual->id,
            'fecha_hasta' => '2026-08-31 00:00:00',
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => '40000.00',
        ]);
    }

    public function test_baja_sin_asistencia_aplica_al_mes_en_curso(): void
    {
        $this->cobrarConPlan($this->planBajo, 20000);

        $this->assertDatabaseHas('alumno_planes', [
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->planBajo->id,
            'fecha_desde' => '2026-08-15 00:00:00',
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => '20000.00',
        ]);
    }

    public function test_subida_con_asistencia_aplica_al_mes_en_curso(): void
    {
        $this->registrarAsistenciaEsteMes();

        $this->cobrarConPlan($this->planSuperior, 60000);

        $this->assertDatabaseHas('alumno_planes', [
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->planSuperior->id,
            'fecha_desde' => '2026-08-15 00:00:00',
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => '60000.00',
        ]);
    }

    public function test_fallo_del_pago_revierte_cambio_de_plan_y_deuda(): void
    {
        $this->mock(\App\Services\PagoCuotaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registrarPagoCuotaOperativo')
                ->once()
                ->andThrow(new \RuntimeException('Fallo simulado del pago'));
        });

        $cantidadPlanesAntes = AlumnoPlan::where('alumno_id', $this->alumno->id)->count();

        $this->actingAs($this->operativo)
            ->from(route('web.caja.cobrar', $this->alumno->id))
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => 20000],
                'nuevo_plan_id' => $this->planBajo->id,
                'fecha_pago' => '2026-08-15',
            ])
            ->assertRedirect(route('web.caja.cobrar', $this->alumno->id))
            ->assertSessionHas('error', 'Fallo simulado del pago');

        $this->assertSame(
            $cantidadPlanesAntes,
            AlumnoPlan::where('alumno_id', $this->alumno->id)->count()
        );
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-08',
            'monto_original' => '40000.00',
        ]);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    private function crearPlan(int $clasesPorSemana, float $precio): GrupoPlan
    {
        return GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => $clasesPorSemana,
            'precio_mensual' => $precio,
            'activo' => true,
        ]);
    }

    private function registrarAsistenciaEsteMes(): void
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => '2026-08-10',
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $this->alumno->id,
            'presente' => true,
        ]);
    }

    private function cobrarConPlan(GrupoPlan $plan, float $monto): void
    {
        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->tipoCaja->id,
                'periodos' => ['2026-08'],
                'montos_cuota' => ['2026-08' => $monto],
                'nuevo_plan_id' => $plan->id,
                'fecha_pago' => '2026-08-15',
            ])
            ->assertRedirect(route('web.caja.index'));
    }
}
