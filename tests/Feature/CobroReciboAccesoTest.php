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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ENT-05: Acceso directo al recibo de cuota en dos lugares clave:
 * 1. Despues de cobrar, en la confirmacion del cobro (banner flash con link al recibo).
 * 2. En la ficha del alumno, en cada fila del historial de pagos.
 */
class CobroReciboAccesoTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $profesor;
    private TipoCaja $tipoCaja;
    private Alumno $alumno;
    private GrupoPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-10 10:00:00');
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
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
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

    public function test_despues_de_cobrar_la_confirmacion_incluye_enlace_al_recibo(): void
    {
        $deuda = DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'saldo_pendiente' => 35000,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $response = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $this->alumno->id), [
            'tipo_caja_id' => $this->tipoCaja->id,
            'periodos' => ['2026-09'],
            'montos_cuota' => ['2026-09' => 35000],
            'fecha_pago' => '2026-09-10',
        ]);

        $response->assertRedirect(route('web.caja.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('recibo_pago_id');

        $pago = Pago::where('alumno_id', $this->alumno->id)->firstOrFail();
        $this->assertSame($pago->id, session('recibo_pago_id'));

        // Seguimos la redireccion para comprobar que el banner de exito renderiza el boton Recibo
        $cajaIndexResponse = $this->actingAs($this->operativo)->get(route('web.caja.index'));
        $cajaIndexResponse->assertOk();
        $cajaIndexResponse->assertSee('Pago registrado para Stoessel, Martina.');
        $cajaIndexResponse->assertSee(route('web.recibos.cuota', $pago->id) . '?inline=1');
        $cajaIndexResponse->assertSee('Recibo');
        $cajaIndexResponse->assertSee('target="_blank"', false);
    }

    public function test_ficha_del_alumno_muestra_enlace_al_recibo_en_cada_fila_del_historial(): void
    {
        $pago1 = Pago::create([
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

        $pago2 = Pago::create([
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

        // Verifica que ambos recibos estan presentes en el historial
        $recibo1Url = route('web.recibos.cuota', $pago1->id) . '?inline=1';
        $recibo2Url = route('web.recibos.cuota', $pago2->id) . '?inline=1';

        $response->assertSee($recibo1Url);
        $response->assertSee($recibo2Url);
        $response->assertSee('Anulado');
        $response->assertSee('Historial de pagos');

        // Ambos enlaces tienen target="_blank" y clase ds-btn-row--sec
        $this->assertStringContainsString('class="ds-btn-row ds-btn-row--sec"', $response->getContent());
        $this->assertStringContainsString('target="_blank"', $response->getContent());
    }

    public function test_profesor_no_puede_acceder_al_recibo(): void
    {
        $pago = Pago::create([
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

        $this->actingAs($this->profesor)
            ->get(route('web.recibos.cuota', $pago->id))
            ->assertStatus(403);
    }
}
