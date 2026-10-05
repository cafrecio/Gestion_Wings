<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, Clase, Deporte, Grupo, GrupoPlan, Nivel, Profesor, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

// Borrador fuera de tests/: no altera la suite compartida durante la aprobación visual.
class FormulariosA4A5Test extends TestCase
{
    use RefreshDatabase;

    private Grupo $grupo;
    private GrupoPlan $plan;
    private Profesor $correcto;
    private Profesor $ajeno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('wings_testing_codex', config('database.connections.mysql.database'));
        $this->actingAs(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
        $patin = Deporte::create(['nombre' => 'Patín A5', 'activo' => true, 'tipo_liquidacion' => 'HORA']);
        $futbol = Deporte::create(['nombre' => 'Fútbol A5', 'activo' => true, 'tipo_liquidacion' => 'HORA']);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial A5']);
        $this->grupo = Grupo::create(['deporte_id' => $patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $base = ['fecha_nacimiento' => '1990-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true];
        $this->correcto = Profesor::create($base + ['deporte_id' => $patin->id, 'nombre' => 'Profe', 'apellido' => 'Patín', 'dni' => '46000001']);
        $this->ajeno = Profesor::create($base + ['deporte_id' => $futbol->id, 'nombre' => 'Profe', 'apellido' => 'Fútbol', 'dni' => '46000002']);
    }

    private function clase(): Clase
    {
        return Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => today()->addDay()->format('Y-m-d'), 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false]);
    }

    private function datosClase(): array
    {
        return ['tipo_creacion' => 'unica', 'grupo_id' => $this->grupo->id, 'fecha' => today()->addDay()->format('Y-m-d'), 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'profesores' => [$this->ajeno->id]];
    }

    private function alumno(): Alumno
    {
        $alumno = Alumno::create(['nombre' => 'Formulario', 'apellido' => 'Prueba', 'dni' => '47000101', 'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => today(), 'celular' => '1111111111', 'deporte_id' => $this->grupo->deporte_id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => today(), 'activo' => true]);
        return $alumno;
    }

    public function test_a4_alta_presenta_resumen_accesible_antes_de_los_campos(): void
    {
        $errores = (new ViewErrorBag)->put('default', new MessageBag(['nombre_tutor' => 'El nombre del tutor es obligatorio para menores de edad.']));
        $html = $this->withSession(['errors' => $errores])->get(route('web.alumnos.create'))->assertOk()->getContent();
        $this->assertStringContainsString('id="alumno-error-resumen"', $html);
        $this->assertLessThan(strpos($html, 'id="nombre"'), strpos($html, 'id="alumno-error-resumen"'));
        $this->assertStringContainsString('href="#nombre_tutor"', $html);
    }

    public function test_a4_edicion_presenta_el_mismo_resumen(): void
    {
        $errores = (new ViewErrorBag)->put('default', new MessageBag(['telefono_tutor' => 'El teléfono del tutor es obligatorio para menores de edad.']));
        $html = $this->withSession(['errors' => $errores])->get(route('web.alumnos.edit', $this->alumno()))->assertOk()->getContent();
        $this->assertStringContainsString('id="alumno-error-resumen"', $html);
        $this->assertLessThan(strpos($html, 'id="nombre"'), strpos($html, 'id="alumno-error-resumen"'));
    }

    public function test_a4_rechazo_conserva_datos_y_no_modifica_alumno(): void
    {
        $alumno = $this->alumno();
        $datos = array_merge($alumno->only(['nombre', 'apellido', 'dni', 'celular', 'deporte_id', 'grupo_id']), ['fecha_nacimiento' => today()->subYears(10)->format('Y-m-d'), 'fecha_alta' => today()->format('Y-m-d'), 'nombre_tutor' => '', 'telefono_tutor' => '']);
        $this->from(route('web.alumnos.edit', $alumno))->put(route('web.alumnos.update', $alumno), $datos)
            ->assertSessionHasErrors(['nombre_tutor', 'telefono_tutor'])->assertSessionHasInput('nombre', 'Formulario');
        $this->assertSame('2000-01-01', $alumno->fresh()->fecha_nacimiento->format('Y-m-d'));
    }

    public function test_a5_alta_rechaza_profesor_de_otro_deporte_sin_crear_clase(): void
    {
        $this->post(route('web.clases.store'), $this->datosClase())->assertSessionHasErrors('profesores');
        $this->assertDatabaseCount('clases', 0);
        $this->assertDatabaseCount('clase_profesor', 0);
    }

    public function test_a5_serie_rechazada_no_deja_clases_parciales(): void
    {
        $datos = $this->datosClase() + ['fecha_desde' => today()->format('Y-m-d'), 'fecha_hasta' => today()->addDays(7)->format('Y-m-d'), 'dias_semana' => [0, 1, 2, 3, 4, 5, 6]];
        $datos['tipo_creacion'] = 'recurrente';
        $this->post(route('web.clases.store'), $datos)->assertSessionHasErrors('profesores');
        $this->assertDatabaseCount('clases', 0);
    }

    public function test_a5_edicion_rechaza_profesor_ajeno_sin_cambiar_horario(): void
    {
        $clase = $this->clase();
        $clase->profesores()->attach($this->correcto);
        $datos = $this->datosClase();
        $datos['hora_inicio'] = '16:00';
        $this->put(route('web.clases.update', $clase), $datos)->assertSessionHasErrors('profesores');
        $this->assertSame('17:00', $clase->fresh()->hora_inicio->format('H:i'));
        $this->assertSame([$this->correcto->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_a5_reasignacion_rechaza_profesor_ajeno_y_conserva_asignacion(): void
    {
        $clase = $this->clase();
        $clase->profesores()->attach($this->correcto);
        $this->patchJson(route('web.clases.profesores', $clase), ['profesores' => [$this->ajeno->id]])->assertUnprocessable();
        $this->assertSame([$this->correcto->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_a5_edicion_no_ofrece_casilla_de_otro_deporte(): void
    {
        $this->get(route('web.clases.edit', $this->clase()))->assertOk()
            ->assertDontSee('value="'.$this->ajeno->id.'"', false);
    }

    public function test_a5_profesor_correcto_sigue_pudiendo_asignarse(): void
    {
        $datos = $this->datosClase();
        $datos['profesores'] = [$this->correcto->id];
        $this->post(route('web.clases.store'), $datos)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clase_profesor', ['profesor_id' => $this->correcto->id]);
    }
}
