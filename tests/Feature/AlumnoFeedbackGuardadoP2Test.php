<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumnoFeedbackGuardadoP2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $deporte = Deporte::firstOrCreate(['nombre' => 'Patín'], ['activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 38000,
            'activo' => true,
        ]);

        // Alumno mayor de edad (nacido en 1995) sin tutor
        $this->alumno = Alumno::create([
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'dni' => '32111222',
            'fecha_nacimiento' => '1995-05-10',
            'fecha_alta' => '2026-08-01',
            'celular' => '1133334444',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'nombre_tutor' => null,
            'telefono_tutor' => null,
            'activo' => true,
        ]);
    }

    public function test_edicion_alumno_muestra_banner_superior_cuando_falla_validacion(): void
    {
        // Se intenta cambiar la fecha de nacimiento a menor de edad (2015) sin enviar tutor
        $datos = [
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'dni' => '32111222',
            'fecha_nacimiento' => '2015-05-10', // 11 años: menor, tutor obligatorio
            'fecha_alta' => '2026-08-01',
            'celular' => '1133334444',
            'deporte_id' => $this->alumno->deporte_id,
            'grupo_id' => $this->alumno->grupo_id,
            'nombre_tutor' => '', // Falla
        ];

        $postRes = $this->actingAs($this->admin)
            ->from(route('web.alumnos.edit', $this->alumno->id))
            ->put(route('web.alumnos.update', $this->alumno->id), $datos);

        $postRes->assertRedirect(route('web.alumnos.edit', $this->alumno->id));
        $postRes->assertSessionHasErrors(['nombre_tutor']);

        // Al seguir la redirección
        $getRes = $this->actingAs($this->admin)->get(route('web.alumnos.edit', $this->alumno->id));
        $getRes->assertOk();

        // Debe contener el banner superior de aviso de error
        $getRes->assertSee('No se pudo guardar');
    }

    public function test_formulario_alumno_incluye_deteccion_de_cambios_sin_guardar(): void
    {
        $res = $this->actingAs($this->admin)->get(route('web.alumnos.edit', $this->alumno->id));
        $res->assertOk();

        // Debe incluir el script que detecta cambios sin guardar (beforeunload)
        $res->assertSee('beforeunload');
    }
}
