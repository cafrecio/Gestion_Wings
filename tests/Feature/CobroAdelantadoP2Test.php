<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobroAdelantadoP2Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private Alumno $alumno;
    private GrupoPlan $plan;
    private TipoCaja $tipoCaja;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);

        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'apertura_at' => now(),
            'estado' => 'ABIERTA',
        ]);

        $deporte = Deporte::firstOrCreate(['nombre' => 'Patín'], ['activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 42000,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Federico',
            'apellido' => 'Navarro',
            'dni' => '38444555',
            'fecha_nacimiento' => '2000-01-01',
            'fecha_alta' => '2026-08-01',
            'celular' => '1199887766',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-08-01',
            'activo' => true,
        ]);

        $this->tipoCaja = TipoCaja::first();
    }

    public function test_pantalla_cobro_ofrece_periodo_adelantado_cuando_no_hay_deudas_pendientes(): void
    {
        // El alumno NO tiene deudas pendientes (mes actual ya saldado o no generado)
        $this->assertDatabaseMissing('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $res = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $this->alumno->id));
        $res->assertOk();

        // Debe ofrecer el período adelantado (ej. mes actual o mes siguiente)
        $proximoPeriodo = now()->addMonth()->format('Y-m');
        $res->assertSee($proximoPeriodo);
        $res->assertSee('42.000');
    }

    public function test_operativo_puede_cobrar_periodo_adelantado_sin_deuda_previa(): void
    {
        $periodoAdelantado = now()->addMonth()->format('Y-m');

        $datos = [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => [$periodoAdelantado],
            'montos_cuota' => [
                $periodoAdelantado => '42000',
            ],
            'fecha_pago' => now()->toDateString(),
            'observaciones' => 'Cobro por adelantado del mes que viene',
        ];

        $res = $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), $datos);

        $res->assertRedirect();

        // Se debe haber creado la DeudaCuota para ese período adelantado en estado PAGADA
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $this->alumno->id,
            'periodo' => $periodoAdelantado,
            'monto_original' => 42000,
            'monto_pagado' => 42000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        // Se debe haber registrado el Pago
        $this->assertDatabaseHas('pagos', [
            'alumno_id' => $this->alumno->id,
            'monto_final' => 42000,
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
    }
}
