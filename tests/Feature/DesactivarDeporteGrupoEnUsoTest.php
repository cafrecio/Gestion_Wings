<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesactivarDeporteGrupoEnUsoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $deporte;
    private Nivel $nivel;
    private Grupo $grupo;
    private int $dniCounter = 40000000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-18 10:00:00'));

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->deporte = Deporte::create([
            'nombre' => 'Tenis Test',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);

        $this->nivel = Nivel::create([
            'nombre' => 'Avanzado Test',
            'activo' => true,
        ]);

        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $this->nivel->id,
            'activo' => true,
        ]);
    }

    private function crearAlumno(array $overrides = []): Alumno
    {
        return Alumno::create(array_merge([
            'nombre' => 'Alumno',
            'apellido' => 'Test',
            'dni' => (string) (++$this->dniCounter),
            'fecha_nacimiento' => '2005-01-01',
            'fecha_alta' => today()->toDateString(),
            'celular' => '1122334455',
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'activo' => true,
        ], $overrides));
    }

    public function test_admin_no_puede_desactivar_deporte_con_alumnos_activos_web(): void
    {
        $this->crearAlumno(['activo' => true]);

        $response = $this->actingAs($this->admin)
            ->patch(route('web.deportes.toggle-activo', $this->deporte->id));

        $response->assertSessionHas('error', 'No se puede desactivar el deporte porque tiene 1 alumno activo.');
        $this->assertTrue($this->deporte->fresh()->activo);
    }

    public function test_admin_no_puede_desactivar_deporte_con_alumnos_activos_json(): void
    {
        $this->crearAlumno(['activo' => true]);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('web.deportes.toggle-activo', $this->deporte->id));

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede desactivar el deporte porque tiene 1 alumno activo.',
            ]);
        $this->assertTrue($this->deporte->fresh()->activo);
    }

    public function test_admin_no_puede_desactivar_deporte_con_clases_futuras(): void
    {
        Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => today()->addDay()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('web.deportes.toggle-activo', $this->deporte->id));

        $response->assertSessionHas('error', 'No se puede desactivar el deporte porque tiene 1 clase futura.');
        $this->assertTrue($this->deporte->fresh()->activo);
    }

    public function test_admin_no_puede_desactivar_deporte_con_alumnos_activos_y_clases_futuras_plural(): void
    {
        $this->crearAlumno(['activo' => true]);
        $this->crearAlumno(['activo' => true]);

        Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => false,
        ]);

        Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => today()->addDays(2)->toDateString(),
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
            'cancelada' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('web.deportes.toggle-activo', $this->deporte->id));

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede desactivar el deporte porque tiene 2 alumnos activos y 2 clases futuras.',
            ]);
        $this->assertTrue($this->deporte->fresh()->activo);
    }

    public function test_admin_puede_desactivar_deporte_con_alumnos_inactivos_y_clases_canceladas_o_pasadas(): void
    {
        $this->crearAlumno(['activo' => false]);

        // Clase cancelada
        Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => today()->addDay()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => true,
        ]);

        // Clase pasada
        Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => today()->subDays(3)->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('web.deportes.toggle-activo', $this->deporte->id));

        $response->assertSessionHas('success', 'Deporte desactivado correctamente.');
        $this->assertFalse($this->deporte->fresh()->activo);
    }

    public function test_admin_puede_reactivar_deporte_inactivo_sin_restricciones(): void
    {
        $this->deporte->update(['activo' => false]);

        $response = $this->actingAs($this->admin)
            ->patch(route('web.deportes.toggle-activo', $this->deporte->id));

        $response->assertSessionHas('success', 'Deporte activado correctamente.');
        $this->assertTrue($this->deporte->fresh()->activo);
    }

    public function test_admin_no_puede_desactivar_grupo_con_alumnos_activos(): void
    {
        $this->crearAlumno(['activo' => true]);

        $response = $this->actingAs($this->admin)
            ->patch(route('web.grupos.toggle-activo', $this->grupo->id));

        $response->assertSessionHas('error', 'No se puede desactivar el grupo porque tiene 1 alumno activo.');
        $this->assertTrue($this->grupo->fresh()->activo);
    }

    public function test_admin_no_puede_desactivar_grupo_con_clases_futuras_json(): void
    {
        Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => today()->addDays(5)->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cancelada' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('web.grupos.toggle-activo', $this->grupo->id));

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede desactivar el grupo porque tiene 1 clase futura.',
            ]);
        $this->assertTrue($this->grupo->fresh()->activo);
    }

    public function test_admin_puede_desactivar_grupo_sin_alumnos_activos_ni_clases_futuras(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('web.grupos.toggle-activo', $this->grupo->id));

        $response->assertSessionHas('success', 'Grupo desactivado correctamente.');
        $this->assertFalse($this->grupo->fresh()->activo);
    }

    public function test_admin_puede_reactivar_grupo_inactivo_sin_restricciones(): void
    {
        $this->grupo->update(['activo' => false]);

        $response = $this->actingAs($this->admin)
            ->patch(route('web.grupos.toggle-activo', $this->grupo->id));

        $response->assertSessionHas('success', 'Grupo activado correctamente.');
        $this->assertTrue($this->grupo->fresh()->activo);
    }
}
