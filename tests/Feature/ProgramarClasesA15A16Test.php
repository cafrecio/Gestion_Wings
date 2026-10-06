<?php

namespace Tests\Feature;

use App\Models\{Clase, Deporte, Grupo, Nivel, Profesor, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramarClasesA15A16Test extends TestCase
{
    use RefreshDatabase;

    private array $grupos = [];
    private array $profesores = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 09:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->actingAs(User::factory()->create(['rol' => 'ADMIN', 'activo' => true]));
        foreach ([['Patín', 'Principiantes', 'Lucía', 'Gaitán'], ['Patín', 'Intermedias', 'Verónica', 'Salinas'],
            ['Patín', 'Avanzadas', 'Lucía', 'Gaitán'], ['Patín', 'Federadas', 'Mariela', 'Ocampo'],
            ['Fútbol', 'Principiantes', 'Hernán', 'Quintana'], ['Fútbol', 'Avanzadas', 'Hernán', 'Quintana']]
            as $i => [$deporte, $nivel, $nombre, $apellido]) {
            $dep = Deporte::where('nombre', $deporte)->sole();
            $niv = Nivel::firstOrCreate(['nombre' => $nivel]);
            $this->grupos[$i] = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => $niv->id, 'activo' => true]);
            $this->profesores[$i] = Profesor::firstOrCreate(['deporte_id' => $dep->id, 'nombre' => $nombre, 'apellido' => $apellido],
                ['dni' => (string) (49000000 + $i), 'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Prueba',
                    'localidad' => 'Prueba', 'valor_hora' => 0, 'activo' => true]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function unica(): array
    {
        return ['tipo_creacion' => 'unica', 'grupo_id' => $this->grupos[0]->id, 'fecha' => '2026-09-24',
            'hora_inicio' => '17:30', 'hora_fin' => '18:30', 'profesores' => [$this->profesores[0]->id]];
    }

    private function serie(int $grupo, array $horarios): array
    {
        return ['tipo_creacion' => 'recurrente', 'grupo_id' => $this->grupos[$grupo]->id,
            'fecha_desde' => '2026-09-24', 'fecha_hasta' => '2026-10-31', 'dias_semana' => array_keys($horarios),
            'horarios' => array_map(fn ($h) => ['hora_inicio' => $h[0], 'hora_fin' => $h[1]], $horarios),
            'profesores' => [$this->profesores[$grupo]->id]];
    }

    public function test_a15_avisa_sin_guardar_y_confirmar_guarda_una_vez(): void
    {
        $datos = $this->unica();
        $this->post(route('web.clases.store'), $datos)->assertRedirect(route('web.clases.create'))
            ->assertSessionHasNoErrors()->assertSessionHas('aviso_cancha')->assertSessionHasInput('hora_inicio', '17:30');
        $firma = session('aviso_cancha.firma');
        $this->assertDatabaseCount('clases', 0);
        $this->assertDatabaseCount('clase_profesor', 0);
        $this->get(route('web.clases.create'))->assertOk()->assertSee('2 bloques de alquiler')
            ->assertSee('Confirmar')->assertSee('name="confirmar_cancha"', false)->assertSee('value="17:30"', false);
        $this->post(route('web.clases.store'), $datos + ['confirmar_cancha' => $firma])->assertSessionHas('success');
        $clase = Clase::sole();
        $this->assertSame('17:30', $clase->hora_inicio->format('H:i'));
        $this->assertSame('18:30', $clase->hora_fin->format('H:i'));
        $this->assertSame([$this->profesores[0]->id], $clase->profesores()->pluck('profesores.id')->all());
    }

    public function test_a15_horario_en_punto_se_guarda_directamente(): void
    {
        $datos = array_replace($this->unica(), ['hora_inicio' => '17:00', 'hora_fin' => '18:00']);
        $this->post(route('web.clases.store'), $datos)->assertSessionHas('success')->assertSessionMissing('aviso_cancha');
        $this->assertDatabaseCount('clases', 1);
    }

    public function test_a15_firma_falsa_o_de_horario_anterior_no_permite_guardar(): void
    {
        $datos = $this->unica();
        $this->post(route('web.clases.store'), $datos + ['confirmar_cancha' => 'si'])->assertSessionHas('aviso_cancha');
        $firma = session('aviso_cancha.firma');
        $datos['hora_fin'] = '19:30';
        $this->post(route('web.clases.store'), $datos + ['confirmar_cancha' => $firma])->assertSessionHas('aviso_cancha');
        $this->assertNotSame($firma, session('aviso_cancha.firma'));
        $this->assertDatabaseCount('clases', 0);
    }

    public function test_a16_seis_cargas_guardan_las_76_clases_y_seis_series(): void
    {
        $cronograma = [
            [1 => ['16:00', '17:00'], 3 => ['16:00', '17:00']],
            [1 => ['17:00', '18:00'], 5 => ['16:00', '17:00']],
            [2 => ['17:00', '18:00'], 4 => ['17:00', '18:00']],
            [2 => ['18:00', '19:00'], 4 => ['18:00', '19:00'], 6 => ['10:00', '11:00']],
            [1 => ['16:00', '17:00'], 3 => ['18:00', '19:00']],
            [2 => ['19:00', '20:00'], 4 => ['19:00', '20:00'], 6 => ['11:00', '12:00']],
        ];
        foreach ($cronograma as $i => $horarios) {
            $this->post(route('web.clases.store'), $this->serie($i, $horarios))->assertSessionHasNoErrors()->assertSessionHas('success');
            $clases = Clase::where('grupo_id', $this->grupos[$i]->id)->get();
            $this->assertCount([10, 11, 11, 17, 10, 17][$i], $clases);
            $this->assertCount(1, $clases->pluck('serie_id')->unique());
            foreach ($clases as $clase) {
                $esperado = $horarios[$clase->fecha->dayOfWeek];
                $this->assertSame($esperado[0], $clase->hora_inicio->format('H:i'));
                $this->assertSame($esperado[1], $clase->hora_fin->format('H:i'));
                $this->assertSame([$this->profesores[$i]->id], $clase->profesores()->pluck('profesores.id')->all());
            }
        }
        $this->assertDatabaseCount('clases', 76);
        $this->assertSame(6, Clase::distinct('serie_id')->count('serie_id'));
    }

    public function test_serie_con_media_hora_avisa_toda_la_carga_y_admite_domingo(): void
    {
        $datos = $this->serie(0, [0 => ['10:30', '11:30'], 1 => ['17:00', '18:00']]);
        $datos['fecha_hasta'] = '2026-09-28';
        $this->post(route('web.clases.store'), $datos)->assertSessionHas('aviso_cancha')->assertSessionHasNoErrors();
        $firma = session('aviso_cancha.firma');
        $this->assertStringStartsWith('Domingo:', session('aviso_cancha.horarios.0.detalle'));
        $this->assertDatabaseCount('clases', 0);
        $this->get(route('web.clases.create'))->assertOk()->assertSee('value="10:30"', false)->assertSee('Confirmar');
        $this->post(route('web.clases.store'), $datos + ['confirmar_cancha' => $firma])->assertSessionHas('success');
        $clases = Clase::orderBy('fecha')->get();
        $this->assertSame(['2026-09-27', '2026-09-28'], $clases->map(fn ($c) => $c->fecha->toDateString())->all());
        $this->assertSame(['10:30', '17:00'], $clases->map(fn ($c) => $c->hora_inicio->format('H:i'))->all());
        $this->assertCount(1, $clases->pluck('serie_id')->unique());
    }

    public function test_horario_invalido_de_un_dia_conserva_el_formulario_sin_guardar(): void
    {
        $datos = $this->serie(1, [1 => ['17:00', '18:00'], 5 => ['16:00', '15:00']]);
        $this->from(route('web.clases.create'))->post(route('web.clases.store'), $datos)
            ->assertSessionHasErrors('horarios.5.hora_fin')->assertSessionHasInput('horarios.1.hora_inicio', '17:00');
        $this->get(route('web.clases.create'))->assertOk()->assertSee('Viernes: la hora de fin debe ser posterior al inicio.')
            ->assertSee('value="15:00"', false)->assertSee('value="recurrente" checked', false);
        $this->assertDatabaseCount('clases', 0);
    }

    public function test_conflicto_en_segundo_dia_revierte_la_serie_entera(): void
    {
        $existente = Clase::create(['grupo_id' => $this->grupos[0]->id, 'fecha' => '2026-09-25',
            'hora_inicio' => '16:00', 'hora_fin' => '17:00', 'cancelada' => false]);
        $existente->profesores()->attach($this->profesores[0]->id);
        $datos = $this->serie(0, [4 => ['17:00', '18:00'], 5 => ['16:00', '17:00']]);
        $datos['fecha_hasta'] = '2026-09-25';
        $this->post(route('web.clases.store'), $datos)->assertSessionHas('error');
        $this->assertDatabaseCount('clases', 1);
        $this->assertSame($existente->id, Clase::sole()->id);
        $this->assertDatabaseCount('clase_profesor', 1);
    }

    public function test_horarios_por_dia_no_eluden_el_deporte_del_profesor(): void
    {
        $datos = $this->serie(0, [4 => ['17:00', '18:00']]);
        $datos['profesores'] = [$this->profesores[4]->id];
        $this->post(route('web.clases.store'), $datos)->assertSessionHasErrors('profesores');
        $this->assertDatabaseCount('clases', 0);
    }

    public function test_horario_comun_anterior_sigue_creando_series(): void
    {
        $datos = $this->serie(0, [1 => ['17:00', '18:00'], 5 => ['17:00', '18:00']]);
        unset($datos['horarios']);
        $datos += ['hora_inicio' => '17:00', 'hora_fin' => '18:00'];
        $this->post(route('web.clases.store'), $datos)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseCount('clases', 11);
    }
}
