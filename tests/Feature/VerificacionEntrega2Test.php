<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\DeudaCuota;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verificación cruzada de la Entrega 2 (A17, A34, A3), que implementó Gemini.
 *
 * No repite lo que ya prueba el autor: mira los bordes donde el cobro adelantado se cruza
 * con reglas que ya existían —la deuda vieja primero— y con el contador de deudores que
 * se arregló el mismo día (A54), que la búsqueda nueva del selector podía haber roto.
 */
class VerificacionEntrega2Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private Alumno $alumno;
    private GrupoPlan $plan;
    private TipoCaja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-05 10:00:00');

        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->caja = TipoCaja::first() ?? TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);

        $deporte = Deporte::first() ?? Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 48000,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'apellido' => 'Adelanta',
            'nombre' => 'Marta',
            'dni' => '33444555',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => '2026-09-01',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => '2026-09-01',
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Cobrar adelantado a quien debe meses viejos no puede pasar en silencio: el sistema
     * ya avisaba de la deuda anterior y ese aviso tiene que seguir apareciendo.
     */
    public function test_cobrar_adelantado_a_quien_debe_meses_viejos_pide_confirmacion(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 48000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $respuesta = $this->actingAs($this->operativo)
            ->postJson(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->caja->id,
                'periodos' => ['2026-11'],
                'montos_cuota' => ['2026-11' => 48000],
                'fecha_pago' => '2026-10-05',
            ]);

        $respuesta->assertStatus(409);
        $this->assertSame(
            0,
            DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->count(),
            'No se puede cobrar adelantado sin avisar que el alumno debe meses anteriores.'
        );
    }

    /** Confirmado el aviso, el cobro adelantado entra y la deuda vieja queda intacta. */
    public function test_confirmado_el_aviso_el_cobro_adelantado_entra_sin_tocar_la_deuda_vieja(): void
    {
        $vieja = DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-09',
            'monto_original' => 48000,
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->operativo)
            ->post(route('web.caja.pagar', $this->alumno->id), [
                'tipo_caja_id' => $this->caja->id,
                'periodos' => ['2026-11'],
                'montos_cuota' => ['2026-11' => 48000],
                'fecha_pago' => '2026-10-05',
                'confirmar_deuda_anterior' => 1,
                // Saltear la deuda vieja exige motivo: queda escrito quién decidió y por qué.
                'motivo' => 'La madre paga noviembre adelantado y arregla septiembre aparte',
            ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $adelantada = DeudaCuota::where('alumno_id', $this->alumno->id)->where('periodo', '2026-11')->sole();
        $this->assertSame(DeudaCuota::ESTADO_PAGADA, $adelantada->estado);
        $this->assertEquals(48000, $adelantada->monto_pagado);

        $vieja->refresh();
        $this->assertSame(DeudaCuota::ESTADO_PENDIENTE, $vieja->estado);
        $this->assertEquals(0, $vieja->monto_pagado);
    }

    /**
     * A54 otra vez: la busqueda nueva muestra a cualquier alumno activo, incluso al que no
     * debe nada. El contador del encabezado tiene que seguir contando solo a los que deben.
     */
    public function test_buscar_a_un_alumno_al_dia_no_lo_cuenta_como_deudor(): void
    {
        DeudaCuota::create([
            'alumno_id' => $this->alumno->id,
            'periodo' => '2026-10',
            'monto_original' => 48000,
            'monto_pagado' => 48000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);

        $this->actingAs($this->operativo)
            ->get(route('web.caja.cobrar-cuota', ['search' => 'Adelanta']))
            ->assertOk()
            ->assertSee('Adelanta, Marta')
            ->assertSee('Sin deudas pendientes');
    }
}
