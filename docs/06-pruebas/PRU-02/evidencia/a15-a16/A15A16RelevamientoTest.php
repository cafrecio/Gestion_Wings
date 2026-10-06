<?php

namespace Tests\Verificacion;

use App\Models\{Clase, Deporte, Grupo, Nivel, Profesor, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Relevamiento previo sobre backend ad7f9fb; ejecutar en ese corte, no en la implementación A15/A16. */
class A15A16RelevamientoTest extends TestCase
{
    use RefreshDatabase;

    private array $grupos = [];
    private array $profesores = [];
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 09:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create([
            'name' => 'Verificación Codex', 'email' => 'clases.verificacion@example.test',
            'rol' => User::ROL_ADMIN, 'activo' => true,
        ]);
        $this->actingAs($this->admin);
        foreach ([
            ['Patín', 'Principiantes', 'Lucía', 'Gaitán'],
            ['Patín', 'Intermedias', 'Verónica', 'Salinas'],
            ['Patín', 'Avanzadas', 'Lucía', 'Gaitán'],
            ['Patín', 'Federadas', 'Mariela', 'Ocampo'],
            ['Fútbol', 'Principiantes', 'Hernán', 'Quintana'],
            ['Fútbol', 'Avanzadas', 'Hernán', 'Quintana'],
        ] as $i => [$deporte, $nivel, $nombre, $apellido]) {
            $dep = Deporte::where('nombre', $deporte)->sole();
            $niv = Nivel::firstOrCreate(['nombre' => $nivel]);
            $this->grupos[$i] = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => $niv->id, 'activo' => true]);
            $this->profesores[$i] = Profesor::firstOrCreate([
                'deporte_id' => $dep->id, 'nombre' => $nombre, 'apellido' => $apellido,
            ], ['dni' => (string) (49000000 + $i), 'fecha_nacimiento' => '1990-01-01',
                'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 0, 'activo' => true]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a15_actual_acepta_media_hora_sin_aviso(): void
    {
        $this->post(route('web.clases.store'), [
            'tipo_creacion' => 'unica', 'grupo_id' => $this->grupos[0]->id,
            'fecha' => '2026-09-24', 'hora_inicio' => '17:30', 'hora_fin' => '18:30',
            'profesores' => [$this->profesores[0]->id],
        ])->assertSessionHas('success')->assertSessionMissing('aviso_cancha');
        $clase = Clase::sole();
        $this->assertSame('17:30', $clase->hora_inicio->format('H:i'));
        $this->assertSame('18:30', $clase->hora_fin->format('H:i'));
    }

    public function test_a16_actual_requiere_diez_cargas_para_las_76_clases(): void
    {
        $series = [
            [0, [1, 3], '16:00', '17:00'],
            [1, [1], '17:00', '18:00'], [1, [5], '16:00', '17:00'],
            [2, [2, 4], '17:00', '18:00'],
            [3, [2, 4], '18:00', '19:00'], [3, [6], '10:00', '11:00'],
            [4, [1], '16:00', '17:00'], [4, [3], '18:00', '19:00'],
            [5, [2, 4], '19:00', '20:00'], [5, [6], '11:00', '12:00'],
        ];
        foreach ($series as [$i, $dias, $inicio, $fin]) {
            $this->post(route('web.clases.store'), [
                'tipo_creacion' => 'recurrente', 'grupo_id' => $this->grupos[$i]->id,
                'fecha_desde' => '2026-09-24', 'fecha_hasta' => '2026-10-31',
                'dias_semana' => $dias, 'hora_inicio' => $inicio, 'hora_fin' => $fin,
                'profesores' => [$this->profesores[$i]->id],
            ])->assertSessionHas('success')->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('clases', 76);
        $this->assertSame(10, Clase::distinct('serie_id')->count('serie_id'));
        $filas = [];
        foreach ($this->grupos as $i => $grupo) {
            $clases = Clase::where('grupo_id', $grupo->id)->orderBy('fecha')->orderBy('hora_inicio')->get();
            $this->assertSame([10, 11, 11, 17, 10, 17][$i], $clases->count());
            $filas[] = [
                'grupo' => $grupo->nombre_completo, 'clases' => $clases->count(),
                'ejemplos' => $clases->take(5)->map(fn ($c) => [
                    'fecha' => $c->fecha->format('Y-m-d'), 'inicio' => $c->hora_inicio->format('H:i'),
                    'fin' => $c->hora_fin->format('H:i'),
                ])->all(),
            ];
        }
        file_put_contents(__DIR__.'/relevamiento-76-clases.json', json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (getenv('WINGS_VERIFICACION_HTTP') === '1') {
            $this->assertSame('wings_testing_codex', \Illuminate\Support\Facades\DB::connection()->getDatabaseName());
            \Illuminate\Support\Facades\DB::commit();
        }
    }
}
