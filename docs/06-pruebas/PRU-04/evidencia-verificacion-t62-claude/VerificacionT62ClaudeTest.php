<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T62 / DU-30 / DU-31 / CLA-012 / CLA-015:
 * Cancelar y reactivar una clase: aviso por lista tomada, choque y motivo conservado.
 */
class VerificacionT62ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Profesor $profesor;
    private Grupo $grupo;
    private Deporte $deporte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->profesorUser = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);

        $this->deporte = Deporte::create([
            'nombre' => 'Patín Artístico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::create(['nombre' => 'Iniciación']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->deporte->id,
            'nivel_id' => $nivel->id,
            'nombre' => 'Patín Escuela',
            'activo' => true,
        ]);
        $this->profesor = Profesor::create([
            'deporte_id' => $this->deporte->id,
            'nombre' => 'Lucía',
            'apellido' => 'Sosa',
            'dni' => '33111222',
            'fecha_nacimiento' => '1990-04-15',
            'direccion' => 'Pellegrini 1234',
            'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'valor_hora' => 15000,
            'activo' => true,
        ]);
    }

    private function crearClase(string $fecha, string $inicio = '17:00', string $fin = '18:00', ?int $serieId = null): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'cancelada' => false,
            'serie_id' => $serieId,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function crearAlumno(string $nombre = 'Camila'): Alumno
    {
        return Alumno::create([
            'deporte_id' => $this->deporte->id,
            'grupo_id' => $this->grupo->id,
            'nombre' => $nombre,
            'apellido' => 'Gómez',
            'dni' => (string) rand(30000000, 45000000),
            'email' => 'alumno' . rand(100, 999) . '@test.com',
            'celular' => '3415551122',
            'fecha_nacimiento' => '2012-05-10',
            'fecha_alta' => '2026-01-01',
            'activo' => true,
        ]);
    }

    private function segundaProfesora(): Profesor
    {
        return Profesor::create([
            'deporte_id' => $this->deporte->id, 'nombre' => 'Rocío', 'apellido' => 'Paz', 'dni' => '34999888',
            'fecha_nacimiento' => '1991-01-01', 'direccion' => 'X 1', 'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'valor_hora' => 15000, 'activo' => true,
        ]);
    }

    private function claseDeOtroGrupo(string $fecha, string $ini, string $fin, Profesor $p): Clase
    {
        $g = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Otro' . rand(1, 9999)])->id, 'nombre' => 'Otro grupo', 'activo' => true]);
        $c = Clase::create(['grupo_id' => $g->id, 'fecha' => $fecha, 'hora_inicio' => $ini, 'hora_fin' => $fin, 'cancelada' => false]);
        $c->profesores()->attach($p->id);
        return $c;
    }

    /** Ataque 1: la que choca es la SEGUNDA profesora de la clase. */
    public function test_choque_de_la_segunda_profesora_tambien_frena(): void
    {
        $clase = $this->crearClase('2026-10-28', '17:00', '18:00');
        $rocio = $this->segundaProfesora();
        $clase->profesores()->attach($rocio->id);
        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);
        $this->claseDeOtroGrupo('2026-10-28', '17:30', '18:30', $rocio);

        $res = $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id));
        $res->assertStatus(422);
        $this->assertStringContainsString('Rocío Paz', $res->json('message'));
        $this->assertTrue((bool) $clase->fresh()->cancelada);
    }

    /** Ataque 2: mismo horario pero otro día no es choque. */
    public function test_mismo_horario_otro_dia_reactiva(): void
    {
        $clase = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);
        $this->claseDeOtroGrupo('2026-10-29', '17:00', '18:00', $this->profesor);

        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id))->assertOk();
        $this->assertFalse((bool) $clase->fresh()->cancelada);
    }

    /** Ataque 3: horarios que se tocan sin superponerse (18:00 termina, 18:00 empieza) no frenan. */
    public function test_horarios_contiguos_no_son_choque(): void
    {
        $clase = $this->crearClase('2026-10-28', '17:00', '18:00');
        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);
        $this->claseDeOtroGrupo('2026-10-28', '18:00', '19:00', $this->profesor);

        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id))->assertOk();
    }

    /** Ataque 4: una lista con solo ausentes también es lista tomada. */
    public function test_lista_con_solo_ausentes_pide_confirmacion(): void
    {
        $clase = $this->crearClase('2026-10-28');
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $this->crearAlumno()->id, 'presente' => false]);

        $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id), ['motivo_cancelacion' => 'Lluvia'])
            ->assertStatus(409);
        $this->assertFalse((bool) $clase->fresh()->cancelada);
    }

    /** Ataque 5: confirmar sin motivo sigue rechazado; confirmar no saltea la validación. */
    public function test_confirmar_sin_motivo_no_cancela(): void
    {
        $clase = $this->crearClase('2026-10-28');
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $this->crearAlumno()->id, 'presente' => true]);

        $this->actingAs($this->operativo)->patchJson(route('web.clases.toggle-cancelada', $clase->id), ['confirmar' => true])
            ->assertStatus(422);
        $this->assertFalse((bool) $clase->fresh()->cancelada);
    }

    /** Ataque 6: el profesor no cancela ni forzando confirmar. */
    public function test_profesor_no_cancela_forzando_confirmar(): void
    {
        $clase = $this->crearClase('2026-10-28');
        $res = $this->actingAs($this->profesorUser)->patchJson(route('web.clases.toggle-cancelada', $clase->id), ['motivo_cancelacion' => 'x', 'confirmar' => true]);
        $this->assertContains($res->status(), [302, 403]);
        $this->assertFalse((bool) $clase->fresh()->cancelada);
    }

    /** Ataque 7: cancelar de nuevo despues de reactivar reemplaza el motivo; el rastro guarda solo el ultimo. */
    public function test_recancelar_reemplaza_el_motivo_anterior(): void
    {
        $clase = $this->crearClase('2026-10-28');
        $clase->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);
        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id))->assertOk();
        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id), ['motivo_cancelacion' => 'Feriado'])->assertOk();
        $this->assertSame('Feriado', $clase->fresh()->motivo_cancelacion);
    }
}
