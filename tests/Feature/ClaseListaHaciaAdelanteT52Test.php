<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T52 (DU-09, ALU-013, ASI-009): los cambios son hacia adelante.
 *
 * Antes la lista de una clase se armaba con los alumnos que HOY están activos en el grupo:
 * quien se cambió de grupo o se dio de baja desaparecía de las clases viejas donde había
 * estado presente, y corregir esa lista lo rechazaba («no pertenece al grupo de la clase»).
 */
class ClaseListaHaciaAdelanteT52Test extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $profesorUser;
    private Grupo $viejo;
    private Grupo $nuevo;
    private Clase $semanaPasada;
    private Clase $hoyViejo;
    private Clase $hoyNuevo;
    private Alumno $cambio;
    private Alumno $baja;
    private Alumno $ausente;
    private Alumno $sigue;
    private int $dni = 52000001;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-18 12:00:00'));

        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $this->viejo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Principiantes'])->id, 'activo' => true]);
        $this->nuevo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Intermedias'])->id, 'activo' => true]);
        $profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Lucia', 'apellido' => 'Mendez', 'dni' => '30111222',
            'fecha_nacimiento' => '1985-05-15', 'direccion' => 'Calle 1', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
        $this->profesorUser = User::factory()->create(['rol' => 'PROFESOR', 'profesor_id' => $profesor->id, 'activo' => true]);

        $this->cambio = $this->alumno('Cambio', 'Degrupo');
        $this->baja = $this->alumno('Baja', 'Deayer');
        $this->ausente = $this->alumno('Ausente', 'Marcada');
        $this->sigue = $this->alumno('Sigue', 'Enelgrupo');

        $this->semanaPasada = $this->clase($this->viejo, '2026-10-11', $profesor);
        $this->hoyViejo = $this->clase($this->viejo, '2026-10-18', $profesor);
        $this->hoyNuevo = $this->clase($this->nuevo, '2026-10-18', $profesor);

        // La semana pasada tomaron lista: tres presentes y una ausente.
        foreach ([[$this->cambio, true], [$this->baja, true], [$this->sigue, true], [$this->ausente, false]] as [$a, $presente]) {
            Asistencia::create(['clase_id' => $this->semanaPasada->id, 'alumno_id' => $a->id, 'presente' => $presente]);
        }

        // Ayer: uno cambió de grupo, otra se dio de baja, la ausente también cambió.
        $this->cambio->update(['grupo_id' => $this->nuevo->id]);
        $this->ausente->update(['grupo_id' => $this->nuevo->id]);
        $this->baja->update(['activo' => false]);
    }

    public function test_la_clase_vieja_sigue_mostrando_a_quien_tenia_marca(): void
    {
        $vista = $this->actingAs($this->operativo)->get(route('web.clases.show', $this->semanaPasada->id))->assertOk();

        $ids = $vista->viewData('alumnos')->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$this->cambio, $this->baja, $this->ausente, $this->sigue])->pluck('id')->sort()->values()->all(), $ids);
        $this->assertEquals(
            collect([$this->cambio->id => 'Cambió de grupo', $this->baja->id => 'De baja', $this->ausente->id => 'Cambió de grupo'])->sortKeys()->all(),
            $vista->viewData('yaNoEstan')->sortKeys()->all()
        );
        $vista->assertSee('Cambio, Degrupo')->assertSee('Baja, Deayer')->assertSee('Ausente, Marcada')
            ->assertSee('· Cambió de grupo')->assertSee('· De baja')
            ->assertSee('ya no están');
        // La clase conserva su cantidad de presentes.
        $this->assertSame(3, $vista->viewData('asistenciasMap')->where('presente', true)->count());
    }

    public function test_hoy_cada_uno_aparece_solo_en_su_grupo_y_la_baja_en_ninguno(): void
    {
        $viejo = $this->actingAs($this->operativo)->get(route('web.clases.show', $this->hoyViejo->id))->assertOk();
        $this->assertSame([$this->sigue->id], $viejo->viewData('alumnos')->pluck('id')->all());
        $this->assertTrue($viejo->viewData('yaNoEstan')->isEmpty());
        $viejo->assertDontSee('ya no está');

        $nuevo = $this->actingAs($this->operativo)->get(route('web.clases.show', $this->hoyNuevo->id))->assertOk();
        $this->assertEqualsCanonicalizing([$this->cambio->id, $this->ausente->id], $nuevo->viewData('alumnos')->pluck('id')->all());
        $this->assertTrue($nuevo->viewData('yaNoEstan')->isEmpty());
    }

    public function test_corregir_la_lista_vieja_acepta_a_quien_ya_no_esta_y_no_pierde_marcas(): void
    {
        $items = [
            ['alumno_id' => $this->cambio->id, 'presente' => 1],
            ['alumno_id' => $this->baja->id, 'presente' => 1],
            ['alumno_id' => $this->sigue->id, 'presente' => 1],
            // Se había marcado ausente por error: se corrige.
            ['alumno_id' => $this->ausente->id, 'presente' => 1],
        ];

        $this->actingAs($this->operativo)
            ->postJson(route('web.clases.asistencias', $this->semanaPasada->id), ['items' => $items, 'motivo' => 'Se marcó mal a una alumna'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame(4, Asistencia::where('clase_id', $this->semanaPasada->id)->where('presente', true)->count());
        $this->assertSame('Se marcó mal a una alumna', Asistencia::where('clase_id', $this->semanaPasada->id)->where('alumno_id', $this->ausente->id)->value('motivo_correccion'));
    }

    public function test_quien_no_es_del_grupo_ni_tiene_marca_sigue_rechazado(): void
    {
        $extrano = $this->alumno('Extrano', 'Otrogrupo', $this->nuevo);

        $this->actingAs($this->operativo)
            ->postJson(route('web.clases.asistencias', $this->semanaPasada->id), ['items' => [['alumno_id' => $extrano->id, 'presente' => 1]], 'motivo' => 'Prueba'])
            ->assertStatus(422)->assertJson(['success' => false]);

        // Tampoco en la clase de hoy del grupo viejo puede cargarse al que ya se cambió.
        $this->actingAs($this->operativo)
            ->postJson(route('web.clases.asistencias', $this->hoyViejo->id), ['items' => [['alumno_id' => $this->cambio->id, 'presente' => 1]]])
            ->assertStatus(422);
        $this->assertDatabaseMissing('asistencias', ['alumno_id' => $extrano->id]);
        $this->assertDatabaseMissing('asistencias', ['clase_id' => $this->hoyViejo->id, 'alumno_id' => $this->cambio->id]);
    }

    public function test_la_profesora_ve_la_misma_lista_vieja(): void
    {
        $vista = $this->actingAs($this->profesorUser)->get(route('web.clases.show', $this->semanaPasada->id))->assertOk();
        $this->assertCount(4, $vista->viewData('alumnos'));
        $vista->assertSee('Cambio, Degrupo')->assertSee('Baja, Deayer');
    }

    private function alumno(string $apellido, string $nombre, ?Grupo $grupo = null): Alumno
    {
        $grupo ??= $this->viejo;
        return Alumno::create(['nombre' => $nombre, 'apellido' => $apellido, 'dni' => (string) $this->dni++,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1111111111', 'deporte_id' => $grupo->deporte_id,
            'grupo_id' => $grupo->id, 'fecha_alta' => '2026-09-01', 'activo' => true]);
    }

    private function clase(Grupo $grupo, string $fecha, Profesor $profesor): Clase
    {
        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => '10:00', 'hora_fin' => '11:00',
            'cancelada' => false, 'validada_para_liquidacion' => false]);
        $clase->profesores()->attach($profesor->id);
        return $clase;
    }
}
