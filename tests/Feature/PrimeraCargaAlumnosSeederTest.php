<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use Database\Seeders\PrimeraCargaAlumnosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrimeraCargaAlumnosSeederTest extends TestCase
{
    use RefreshDatabase;

    private Deporte $patin;
    private Deporte $futbol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->futbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION, 'activo' => true]);

        foreach (['Principiantes', 'Intermedias', 'Avanzadas', 'Federadas'] as $nombre) {
            Nivel::create(['nombre' => $nombre]);
        }

        foreach ([
            [$this->patin, 'Principiantes'], [$this->patin, 'Intermedias'], [$this->patin, 'Avanzadas'], [$this->patin, 'Federadas'],
            [$this->futbol, 'Principiantes'], [$this->futbol, 'Avanzadas'],
        ] as [$deporte, $nivel]) {
            $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::where('nombre', $nivel)->sole()->id, 'activo' => true]);
            GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 30000, 'activo' => true]);
            GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 40000, 'activo' => true]);
        }

        $manuales = [
            ['Sofía', 'Morales', '32123456', $this->patin, '2026-03-01'],
            ['Camila', 'Vega', '33456789', $this->patin, '2025-05-14'],
            ['Mateo', 'Duarte', '35678901', $this->futbol, '2025-11-03'],
            ['Julieta', 'Luna', '36789012', $this->patin, '2026-04-18'],
            ['Renata', 'Gil', '37890123', $this->patin, '2026-04-25'],
            ['Tomás', 'Navarro', '38901234', $this->futbol, '2026-05-07'],
            ['Abril', 'Méndez', '40123456', $this->patin, '2026-05-21'],
            ['Bruno', 'Silva', '41234567', $this->futbol, '2026-06-02'],
            ['Emilia', 'Ramos', '42345678', $this->patin, '2026-06-14'],
            ['Franco', 'Leiva', '43456789', $this->futbol, '2026-06-28'],
            ['Sofía', 'Morales', '32123456', $this->futbol, '2026-06-01'],
        ];

        foreach ($manuales as [$nombre, $apellido, $dni, $deporte, $fechaAlta]) {
            $grupo = Grupo::where('deporte_id', $deporte->id)->orderBy('id')->firstOrFail();
            $alumno = Alumno::create([
                'nombre' => $nombre, 'apellido' => $apellido, 'dni' => $dni,
                'fecha_nacimiento' => '2000-01-01', 'celular' => '11' . $dni,
                'email' => strtolower($nombre) . '.' . strtolower($apellido) . '.' . $deporte->id . '@wings.test',
                'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha_alta' => $fechaAlta, 'activo' => true,
            ]);
            AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $grupo->planes()->orderBy('id')->firstOrFail()->id, 'fecha_desde' => $fechaAlta, 'activo' => true]);
        }
    }

    public function test_completa_la_primera_carga_sin_duplicar_ni_tocar_deudas(): void
    {
        $catalogosAntes = [Deporte::count(), Grupo::count(), GrupoPlan::count()];

        $this->seed(PrimeraCargaAlumnosSeeder::class);
        $this->seed(PrimeraCargaAlumnosSeeder::class);

        $this->assertSame(60, Alumno::count());
        $this->assertSame(40, Alumno::where('deporte_id', $this->patin->id)->count());
        $this->assertSame(20, Alumno::where('deporte_id', $this->futbol->id)->count());
        $this->assertSame([12, 18, 30], [
            Alumno::whereBetween('fecha_alta', ['2025-01-01', '2025-12-31'])->count(),
            Alumno::whereBetween('fecha_alta', ['2026-01-01', '2026-03-31'])->count(),
            Alumno::whereBetween('fecha_alta', ['2026-04-01', '2026-06-30'])->count(),
        ]);
        $this->assertSame(0, Alumno::where('fecha_alta', '>=', '2026-07-01')->count());
        $this->assertSame(0, DB::table('deuda_cuotas')->count());
        $this->assertSame(0, DB::table('pagos')->count());
        $this->assertSame(60, AlumnoPlan::where('activo', true)->count());
        $this->assertSame(0, Alumno::selectRaw('dni, deporte_id, count(*) as total')->groupBy('dni', 'deporte_id')->having('total', '>', 1)->count());
        $this->assertSame($catalogosAntes, [Deporte::count(), Grupo::count(), GrupoPlan::count()]);
    }
}
