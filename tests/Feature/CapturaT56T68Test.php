<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Deporte, Grupo, GrupoPlan, Nivel, Profesor, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaT56T68Test extends TestCase
{
    use RefreshDatabase;

    public function test_genera_html_para_capturas_t56_t68(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-19 10:00:00');

        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $deporte = Deporte::first();
        $niveles = Nivel::all();

        $grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $niveles[0]->id,
            'activo' => true,
        ]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 25000,
            'activo' => true,
        ]);

        $profesor = Profesor::create([
            'nombre' => 'Mariana',
            'apellido' => 'Gómez',
            'dni' => '28999888',
            'fecha_nacimiento' => '1990-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Quilmes',
            'telefono' => '1144556677',
            'valor_hora' => 5000,
            'email' => 'profe@wings.test',
            'deporte_id' => $deporte->id,
            'activo' => true,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Luz',
            'apellido' => 'Gómez',
            'dni' => '40123456',
            'fecha_nacimiento' => '2005-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'celular' => '1144556677',
            'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-09-01',
            'activo' => true,
        ]);

        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t56-t68');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $publicBuild = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
        $publicImg = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';

        // 1. Ficha del grupo
        $htmlGrupo = $this->actingAs($admin)->get(route('web.grupos.show', $grupo->id))->assertOk()->getContent();
        $htmlGrupo = str_replace(
            ['src="http://gestion-wings/build/', 'href="http://gestion-wings/build/', 'src="http://gestion-wings/img/'],
            ['src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicImg],
            $htmlGrupo
        );
        file_put_contents($dir . '/grupo-show.html', $htmlGrupo);

        // 2. Ficha del profesor
        $htmlProf = $this->actingAs($admin)->get(route('web.profesores.show', $profesor->id))->assertOk()->getContent();
        $htmlProf = str_replace(
            ['src="http://gestion-wings/build/', 'href="http://gestion-wings/build/', 'src="http://gestion-wings/img/'],
            ['src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicImg],
            $htmlProf
        );
        file_put_contents($dir . '/profesor-show.html', $htmlProf);

        // 3. Edición de alumno con error de validación al cambiar de grupo sin plan
        $grupo2 = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $niveles[1]->id,
            'activo' => true,
        ]);
        GrupoPlan::create([
            'grupo_id' => $grupo2->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 35000,
            'activo' => true,
        ]);

        $responsePost = $this->actingAs($admin)->from(route('web.alumnos.edit', $alumno->id))->put(route('web.alumnos.update', $alumno->id), [
            'nombre' => $alumno->nombre,
            'apellido' => $alumno->apellido,
            'dni' => $alumno->dni,
            'fecha_nacimiento' => '2005-01-01',
            'fecha_alta' => '2026-09-01',
            'deporte_id' => $alumno->deporte_id,
            'grupo_id' => $grupo2->id,
            'celular' => $alumno->celular,
        ]);
        $responsePost->assertRedirect(route('web.alumnos.edit', $alumno->id));

        $htmlEdit = $this->actingAs($admin)->get(route('web.alumnos.edit', $alumno->id))->assertOk()->getContent();
        $htmlEdit = str_replace(
            ['src="http://gestion-wings/build/', 'href="http://gestion-wings/build/', 'src="http://gestion-wings/img/'],
            ['src="' . $publicBuild, 'href="' . $publicBuild, 'src="' . $publicImg],
            $htmlEdit
        );
        file_put_contents($dir . '/alumno-edit-error.html', $htmlEdit);
    }
}
