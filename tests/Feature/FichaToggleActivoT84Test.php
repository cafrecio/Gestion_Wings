<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T84: En la ficha del alumno (/alumnos/{id}), el botón «Reactivar» con confirmación
 * nativa (data-confirmar) se reemplaza por el interruptor <x-ds.toggle> idéntico al del
 * listado, sin confirmación, tanto para alumno activo como inactivo.
 */
class FichaToggleActivoT84Test extends TestCase
{
    use RefreshDatabase;

    private function crearAlumno(bool $activo): array
    {
        $this->seed(CatalogosSeeder::class);

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $deporte = Deporte::first() ?? Deporte::create([
            'nombre' => 'Patin', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true,
        ]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $alumno = Alumno::create([
            'apellido' => 'Pérez',
            'nombre' => 'Valentina',
            'dni' => '40123456',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2008-05-10',
            'celular' => '11-5555-5555',
            'fecha_alta' => '2026-09-01',
            'activo' => $activo,
        ]);

        return [$admin, $alumno];
    }

    public function test_ficha_alumno_activo_muestra_toggle_sin_confirmacion_ni_boton_reactivar(): void
    {
        [$admin, $alumno] = $this->crearAlumno(activo: true);

        $response = $this->actingAs($admin)->get(route('web.alumnos.show', $alumno->id));

        $response->assertStatus(200);

        $contenido = $response->getContent();
        $urlToggle = route('web.alumnos.toggle-activo', $alumno->id);

        // Contiene el toggle con la URL del endpoint
        $this->assertStringContainsString('data-url="' . $urlToggle . '"', $contenido);
        $this->assertStringContainsString('ds-toggle', $contenido);

        // El checkbox está marcado como activo
        $this->assertStringContainsString('checked', $contenido);

        // No contiene confirmación nativa ni botón de Reactivar
        $this->assertStringNotContainsString('data-confirmar', $contenido);
        $this->assertStringNotContainsString('Reactivar</button>', $contenido);
    }

    public function test_ficha_alumno_inactivo_muestra_toggle_sin_confirmacion_ni_form_reactivar(): void
    {
        [$admin, $alumno] = $this->crearAlumno(activo: false);

        $response = $this->actingAs($admin)->get(route('web.alumnos.show', $alumno->id));

        $response->assertStatus(200);

        $contenido = $response->getContent();
        $urlToggle = route('web.alumnos.toggle-activo', $alumno->id);

        // Contiene el toggle con la URL del endpoint
        $this->assertStringContainsString('data-url="' . $urlToggle . '"', $contenido);
        $this->assertStringContainsString('ds-toggle', $contenido);

        // No contiene confirmación nativa ni botón de Reactivar
        $this->assertStringNotContainsString('data-confirmar', $contenido);
        $this->assertStringNotContainsString('Reactivar</button>', $contenido);
    }
}
