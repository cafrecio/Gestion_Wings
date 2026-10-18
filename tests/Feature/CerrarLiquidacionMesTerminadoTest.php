<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T41 (DU-37, LIQ-005): No se puede cerrar una liquidación antes de que termine el mes.
 *
 * Decisión de Carlos:
 * - «Impedir cerrar antes de que termine el mes».
 * - «A partir del 1ro de cada mes» (en la hora de Wings, America/Argentina/Buenos_Aires).
 */
class CerrarLiquidacionMesTerminadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Profesor $profesor;
    private Grupo $grupo;
    private LiquidacionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LiquidacionService::class);

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $deporte = Deporte::create([
            'nombre' => 'Tenis',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);

        $nivel = Nivel::create(['nombre' => 'Inicial']);

        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Laura',
            'apellido' => 'Gomez',
            'dni' => '25444333',
            'fecha_nacimiento' => '1988-03-12',
            'direccion' => 'Calle 10',
            'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora' => 10000,
            'activo' => true,
        ]);
    }

    private function crearLiquidacionConClase(int $mes, int $anio, string $fechaClase): Liquidacion
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fechaClase,
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'validada_para_liquidacion' => true,
            'cancelada' => false,
        ]);
        $clase->profesores()->attach($this->profesor->id);

        return $this->service->generarLiquidacionMensual($this->profesor->id, $mes, $anio);
    }

    public function test_cerrar_octubre_el_31_de_octubre_a_las_23_59_no_cierra(): void
    {
        $this->travelTo( '2026-10-31 23:59:00');

        $liquidacion = $this->crearLiquidacionConClase(10, 2026, '2026-10-15');

        $response = $this->actingAs($this->admin)
            ->from(route('web.liquidaciones.show', $liquidacion->id))
            ->post(route('web.liquidaciones.cerrar', $liquidacion->id));

        $response->assertRedirect(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertSessionHas(
            'error',
            'No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.'
        );

        $this->assertTrue($liquidacion->fresh()->estaAbierta());
        $this->assertFalse($liquidacion->fresh()->estaCerrada());
    }

    public function test_cerrar_octubre_el_1_de_noviembre_a_las_00_00_cierra(): void
    {
        $this->travelTo('2026-11-01 00:00:00');

        $liquidacion = $this->crearLiquidacionConClase(10, 2026, '2026-10-15');

        $response = $this->actingAs($this->admin)
            ->post(route('web.liquidaciones.cerrar', $liquidacion->id));

        $response->assertRedirect(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertSessionHas('success', 'Liquidación cerrada correctamente.');

        $this->assertTrue($liquidacion->fresh()->estaCerrada());
    }

    public function test_cerrar_un_mes_anterior_cierra(): void
    {
        $this->travelTo('2026-10-18 14:30:00');

        $liquidacion = $this->crearLiquidacionConClase(9, 2026, '2026-09-10');

        $response = $this->actingAs($this->admin)
            ->post(route('web.liquidaciones.cerrar', $liquidacion->id));

        $response->assertRedirect(route('web.liquidaciones.show', $liquidacion->id));
        $response->assertSessionHas('success', 'Liquidación cerrada correctamente.');

        $this->assertTrue($liquidacion->fresh()->estaCerrada());
    }

    public function test_servicio_cerrar_liquidacion_llamado_directo_respeta_el_limite(): void
    {
        $this->travelTo('2026-10-31 23:59:59');

        $liquidacion = $this->crearLiquidacionConClase(10, 2026, '2026-10-15');

        try {
            $this->service->cerrarLiquidacion($liquidacion->id);
            $this->fail('Debió rechazar el cierre antes de que termine el mes.');
        } catch (\Exception $e) {
            $this->assertSame(
                'No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.',
                $e->getMessage()
            );
        }

        $this->assertTrue($liquidacion->fresh()->estaAbierta());

        // Al pasar a 00:00 del 01/11, el mismo llamado directo cierra la liquidación
        $this->travelTo('2026-11-01 00:00:00');
        $cerrada = $this->service->cerrarLiquidacion($liquidacion->id);

        $this->assertTrue($cerrada->estaCerrada());
    }

    public function test_pantalla_muestra_texto_en_vez_del_boton_cerrar_si_el_mes_no_termino(): void
    {
        $this->travelTo('2026-10-31 23:59:00');

        $liquidacion = $this->crearLiquidacionConClase(10, 2026, '2026-10-15');

        $response = $this->actingAs($this->admin)
            ->get(route('web.liquidaciones.show', $liquidacion->id));

        $response->assertOk();
        $response->assertSee('Se puede cerrar desde el 01/11/2026');
        $response->assertDontSee(route('web.liquidaciones.cerrar', $liquidacion->id), false);
        // El resto de las acciones de liquidación abierta siguen disponibles
        $response->assertSee(route('web.liquidaciones.recalcular', $liquidacion->id), false);
        $response->assertSee(route('web.liquidaciones.eliminar', $liquidacion->id), false);

        // A partir del 01/11 00:00 aparece el botón Cerrar y desaparece el texto
        $this->travelTo('2026-11-01 00:00:00');

        $responseDespues = $this->actingAs($this->admin)
            ->get(route('web.liquidaciones.show', $liquidacion->id));

        $responseDespues->assertOk();
        $responseDespues->assertDontSee('Se puede cerrar desde el 01/11/2026');
        $responseDespues->assertSee(route('web.liquidaciones.cerrar', $liquidacion->id), false);
    }
}
