<?php

namespace Tests\Feature;

use App\Models\{Clase, Deporte, Grupo, Liquidacion, Nivel, Profesor, User};
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Controles propios de Claude al verificar T41 (ee65b57), 18/10/2026.
 * Se corre desde esta carpeta, no desde tests/, para no cambiar el conteo de la carpeta compartida.
 */
class VerificacionT41ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Profesor $profesor;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Tenis', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Laura', 'apellido' => 'Gomez',
            'dni' => '25444333', 'fecha_nacimiento' => '1988-03-12', 'direccion' => 'Calle 10', 'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'valor_hora' => 10000, 'activo' => true]);
    }

    private function liquidacion(int $mes, int $anio, string $fechaClase): Liquidacion
    {
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fechaClase, 'hora_inicio' => '10:00',
            'hora_fin' => '11:00', 'validada_para_liquidacion' => true, 'cancelada' => false]);
        $clase->profesores()->attach($this->profesor->id);
        return app(LiquidacionService::class)->generarLiquidacionMensual($this->profesor->id, $mes, $anio);
    }

    public function test_diciembre_se_cierra_desde_el_1_de_enero(): void
    {
        $this->travelTo(Carbon::parse('2026-12-20 10:00:00'));
        $liq = $this->liquidacion(12, 2026, '2026-12-10');
        $this->assertSame('01/01/2027', $liq->fechaHabilitacionCierre()->format('d/m/Y'));

        $this->travelTo(Carbon::parse('2026-12-31 23:59:00'));
        $this->assertFalse($liq->fresh()->puedeCerrarse());
        $this->travelTo(Carbon::parse('2027-01-01 00:00:00'));
        $this->assertTrue($liq->fresh()->puedeCerrarse());
    }

    public function test_el_corte_es_la_medianoche_de_wings_y_no_la_del_servidor_en_utc(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $liq = $this->liquidacion(10, 2026, '2026-10-10');

        // 02:30 UTC del 1/11 son las 23:30 del 31/10 en Buenos Aires: todavía no.
        $this->travelTo(Carbon::parse('2026-11-01 02:30:00', 'UTC'));
        $this->actingAs($this->admin)->post(route('web.liquidaciones.cerrar', $liq->id));
        $this->assertTrue($liq->fresh()->estaAbierta());

        // 03:00 UTC son las 00:00 del 1/11 en Buenos Aires.
        $this->travelTo(Carbon::parse('2026-11-01 03:00:00', 'UTC'));
        $this->actingAs($this->admin)->post(route('web.liquidaciones.cerrar', $liq->id));
        $this->assertTrue($liq->fresh()->estaCerrada());
    }

    public function test_una_liquidacion_cerrada_no_muestra_el_aviso(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 10:00:00'));
        $liq = $this->liquidacion(10, 2026, '2026-10-10');
        app(LiquidacionService::class)->cerrarLiquidacion($liq->id);

        $this->actingAs($this->admin)->get(route('web.liquidaciones.show', $liq->id))
            ->assertOk()->assertDontSee('Se puede cerrar desde');
    }
}
