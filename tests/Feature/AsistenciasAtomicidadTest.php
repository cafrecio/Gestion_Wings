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
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AsistenciasAtomicidadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Clase $clase;
    private Alumno $alumnoUno;
    private Alumno $alumnoDos;
    private Alumno $alumnoOtroGrupo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);
        $deporte = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivelUno = Nivel::create(['nombre' => 'Grupo uno']);
        $nivelDos = Nivel::create(['nombre' => 'Grupo dos']);
        $grupoUno = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivelUno->id,
            'activo' => true,
        ]);
        $grupoDos = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivelDos->id,
            'activo' => true,
        ]);
        $this->clase = Clase::create([
            'grupo_id' => $grupoUno->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);
        $this->alumnoUno = $this->crearAlumno($grupoUno, '50000001', 'Ana');
        $this->alumnoDos = $this->crearAlumno($grupoUno, '50000002', 'Beto');
        $this->alumnoOtroGrupo = $this->crearAlumno($grupoDos, '50000003', 'Carla');
    }

    public function test_un_fallo_intermedio_no_deja_asistencias_parciales(): void
    {
        $invocaciones = 0;
        $this->partialMock(ClaseService::class, function (MockInterface $mock) use (&$invocaciones) {
            $mock->shouldReceive('verificarDisponibilidadAlumno')
                ->andReturn(['puede_asistir' => true, 'razon' => null]);
            $mock->shouldReceive('contarAsistenciasSemana')
                ->andReturnUsing(function () use (&$invocaciones) {
                    $invocaciones++;
                    if ($invocaciones === 2) {
                        throw new RuntimeException('Fallo simulado durante el guardado');
                    }

                    return ['excede' => false];
                });
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->admin)->postJson(
                route('web.clases.asistencias', $this->clase->id),
                ['items' => [
                    ['alumno_id' => $this->alumnoUno->id, 'presente' => true],
                    ['alumno_id' => $this->alumnoDos->id, 'presente' => true],
                ]]
            );
            $this->fail('El fallo simulado debía propagarse.');
        } catch (RuntimeException $error) {
            $this->assertSame('Fallo simulado durante el guardado', $error->getMessage());
        }

        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('asistencia_excesos', 0);
    }

    public function test_rechaza_alumno_que_no_pertenece_al_grupo_de_la_clase(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('web.clases.asistencias', $this->clase->id), [
                'items' => [
                    ['alumno_id' => $this->alumnoOtroGrupo->id, 'presente' => true],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'El alumno indicado no pertenece al grupo de la clase.']);

        $this->assertDatabaseCount('asistencias', 0);
    }

    private function crearAlumno(Grupo $grupo, string $dni, string $nombre): Alumno
    {
        return Alumno::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'dni' => $dni,
            'fecha_nacimiento' => '2000-01-01',
            'celular' => '1111111111',
            'deporte_id' => $grupo->deporte_id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => today()->toDateString(),
            'activo' => true,
        ]);
    }
}
