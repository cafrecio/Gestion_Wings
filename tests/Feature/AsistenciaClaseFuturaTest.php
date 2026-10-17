<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\User;
use App\Services\ClaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * T35 (DU-35): la asistencia se toma desde el día de la clase, no antes.
 * Un presente cargado de antemano cuenta como clase dictada, para la
 * comisión y para la cuota del mes siguiente.
 */
class AsistenciaClaseFuturaTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE = 'La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.';

    private User $admin;
    private User $operativo;
    private Grupo $grupo;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-17 10:00:00'));

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Iniciación']);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->alumno = Alumno::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'dni' => '50000001',
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-09-01',
            'activo' => true,
        ]);
    }

    public function test_no_se_toma_asistencia_de_una_clase_de_manana(): void
    {
        $clase = $this->clase('2026-10-18');

        foreach ([$this->admin, $this->operativo] as $usuario) {
            $this->actingAs($usuario)
                ->postJson(route('web.clases.asistencias', $clase->id), $this->presente())
                ->assertUnprocessable()
                ->assertJsonPath('message', self::MENSAJE);
        }

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_sin_javascript_vuelve_con_el_aviso_y_no_guarda(): void
    {
        $clase = $this->clase('2026-10-18');

        $this->actingAs($this->admin)
            ->from(route('web.clases.show', $clase->id))
            ->post(route('web.clases.asistencias', $clase->id), $this->presente())
            ->assertRedirect(route('web.clases.show', $clase->id))
            ->assertSessionHas('error', self::MENSAJE);

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_el_dia_de_la_clase_si_se_toma(): void
    {
        $clase = $this->clase('2026-10-17');

        $this->actingAs($this->operativo)
            ->postJson(route('web.clases.asistencias', $clase->id), $this->presente())
            ->assertOk();

        $this->assertDatabaseHas('asistencias', ['clase_id' => $clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
    }

    public function test_una_clase_pasada_sin_lista_se_carga_sin_motivo(): void
    {
        $clase = $this->clase('2026-10-16');

        $this->actingAs($this->operativo)
            ->postJson(route('web.clases.asistencias', $clase->id), $this->presente())
            ->assertOk();

        $this->assertDatabaseHas('asistencias', ['clase_id' => $clase->id, 'presente' => true]);
    }

    public function test_el_limite_es_la_medianoche_de_wings(): void
    {
        $clase = $this->clase('2026-10-18');

        $this->travelTo(Carbon::parse('2026-10-17 23:59:00'));
        $this->actingAs($this->admin)
            ->postJson(route('web.clases.asistencias', $clase->id), $this->presente())
            ->assertUnprocessable();

        $this->travelTo(Carbon::parse('2026-10-18 00:00:00'));
        $this->actingAs($this->admin)
            ->postJson(route('web.clases.asistencias', $clase->id), $this->presente())
            ->assertOk();

        $this->assertDatabaseHas('asistencias', ['clase_id' => $clase->id, 'presente' => true]);
    }

    public function test_el_servicio_tambien_lo_rechaza(): void
    {
        $clase = $this->clase('2026-10-18');

        try {
            app(ClaseService::class)->registrarAsistencia($clase->id, $this->alumno->id, true);
            $this->fail('Debía rechazar la asistencia de una clase futura.');
        } catch (\Exception $e) {
            $this->assertSame(self::MENSAJE, $e->getMessage());
        }

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_la_pantalla_de_una_clase_futura_avisa_y_no_ofrece_guardar(): void
    {
        $clase = $this->clase('2026-10-18');

        $respuesta = $this->actingAs($this->operativo)
            ->get(route('web.clases.show', $clase->id))
            ->assertOk()
            ->assertSee('Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.')
            ->assertDontSee('id="btn-guardar-asistencias"', false);

        $this->assertMatchesRegularExpression(
            '/<input[^>]*id="presente-' . $this->alumno->id . '"[^>]*disabled[^>]*>/s',
            $respuesta->getContent()
        );
    }

    public function test_la_pantalla_de_una_clase_de_hoy_no_cambia(): void
    {
        $clase = $this->clase('2026-10-17');

        $respuesta = $this->actingAs($this->operativo)
            ->get(route('web.clases.show', $clase->id))
            ->assertOk()
            ->assertDontSee('todavía no ocurrió')
            ->assertSee('id="btn-guardar-asistencias"', false);

        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*id="presente-' . $this->alumno->id . '"[^>]*disabled[^>]*>/s',
            $respuesta->getContent()
        );
    }

    private function clase(string $fecha): Clase
    {
        return Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
    }

    private function presente(): array
    {
        return ['items' => [['alumno_id' => $this->alumno->id, 'presente' => true]]];
    }
}
