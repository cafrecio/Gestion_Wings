<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un alumno no puede quedar en un grupo de otro deporte.
 *
 * Encontrado por Codex el 06/09/2026 durante la primera carga (H06): el formulario
 * aceptó un alumno de Patín con un grupo de Fútbol, y la base quedó con el alumno 17
 * en ese estado.
 *
 * El deporte manda en asistencias, liquidaciones y cobranza; el grupo manda en las
 * clases. Un alumno así aparece en las listas de un deporte al que no pertenece y su
 * cuota se cuenta donde no va.
 */
class AlumnoGrupoDelDeporteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deporte $patin;
    private Grupo $grupoDePatin;
    private Grupo $grupoDeFutbol;
    private GrupoPlan $planDePatin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $this->patin = Deporte::create([
            'nombre'           => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo'           => true,
        ]);
        $futbol = Deporte::create([
            'nombre'           => 'Fútbol',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo'           => true,
        ]);

        $nivel = Nivel::create(['nombre' => 'Avanzadas']);

        $this->grupoDePatin = Grupo::create([
            'deporte_id' => $this->patin->id,
            'nivel_id'   => $nivel->id,
            'activo'     => true,
        ]);
        $this->grupoDeFutbol = Grupo::create([
            'deporte_id' => $futbol->id,
            'nivel_id'   => $nivel->id,
            'activo'     => true,
        ]);

        $this->planDePatin = GrupoPlan::create([
            'grupo_id'          => $this->grupoDePatin->id,
            'clases_por_semana' => 2,
            'precio_mensual'    => 45000,
            'activo'            => true,
        ]);
    }

    public function test_rechaza_un_alumno_con_grupo_de_otro_deporte(): void
    {
        $respuesta = $this->actingAs($this->admin)
            ->post('/alumnos', $this->datos([
                'deporte_id' => $this->patin->id,
                'grupo_id'   => $this->grupoDeFutbol->id,
                'plan_id'    => $this->planDePatin->id,
            ]));

        $respuesta->assertSessionHasErrors('grupo_id');

        $this->assertSame(
            0,
            Alumno::count(),
            'El rechazo tiene que dejar la base como estaba. Este es el caso que fallaba: '.
            'se creaba un alumno de Patín dentro de un grupo de Fútbol.'
        );
    }

    public function test_acepta_un_alumno_con_grupo_de_su_deporte(): void
    {
        $respuesta = $this->actingAs($this->admin)
            ->post('/alumnos', $this->datos([
                'deporte_id' => $this->patin->id,
                'grupo_id'   => $this->grupoDePatin->id,
                'plan_id'    => $this->planDePatin->id,
            ]));

        $respuesta->assertSessionHasNoErrors();
        $this->assertSame(1, Alumno::count());
    }

    public function test_tampoco_se_puede_mover_a_un_grupo_de_otro_deporte_editando(): void
    {
        $alumno = Alumno::create($this->datos([
            'deporte_id' => $this->patin->id,
            'grupo_id'   => $this->grupoDePatin->id,
        ]));

        $this->actingAs($this->admin)
            ->put("/alumnos/{$alumno->id}", $this->datos([
                'deporte_id' => $this->patin->id,
                'grupo_id'   => $this->grupoDeFutbol->id,
                'plan_id'    => $this->planDePatin->id,
            ]))
            ->assertSessionHasErrors('grupo_id');

        $this->assertSame(
            $this->grupoDePatin->id,
            $alumno->fresh()->grupo_id,
            'La edición rechazada no puede dejar el cambio aplicado.'
        );
    }

    /**
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'nombre'           => 'Prueba',
            'apellido'         => 'Grupo Ajeno',
            'dni'              => '31445566',
            'celular'          => '3413334444',
            'fecha_nacimiento' => '1998-03-15',
            'fecha_alta'       => '2026-06-01',
        ], $extra);
    }
}
