<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\PrimeraCargaCompletaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * El seeder existe para no repetir a mano la primera carga del 06/09. Si deja
 * la base en otro punto, la prueba humana arranca de un lugar distinto al que
 * se verificó y las conclusiones no se pueden comparar.
 *
 * Estas pruebas miran el resultado en la base, no que el seeder haya corrido.
 */
class PrimeraCargaCompletaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_deja_los_sesenta_alumnos_repartidos_como_la_carga_manual(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $patin  = Deporte::where('nombre', 'Patín')->sole();
        $futbol = Deporte::where('nombre', 'Fútbol')->sole();

        $this->assertSame(60, Alumno::count());
        $this->assertSame(40, Alumno::where('deporte_id', $patin->id)->count());
        $this->assertSame(20, Alumno::where('deporte_id', $futbol->id)->count());
    }

    public function test_los_tramos_de_fecha_de_alta_son_los_verificados(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $this->assertSame(12, Alumno::whereBetween('fecha_alta', ['2025-01-01', '2025-12-31'])->count());
        $this->assertSame(18, Alumno::whereBetween('fecha_alta', ['2026-01-01', '2026-03-31'])->count());
        $this->assertSame(30, Alumno::whereBetween('fecha_alta', ['2026-04-01', '2026-06-30'])->count());
        $this->assertSame(0, Alumno::where('fecha_alta', '>=', '2026-07-01')->count());
    }

    public function test_sofia_morales_queda_inscripta_en_los_dos_deportes_con_el_mismo_dni(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $filas = Alumno::where('dni', '32123456')->with('deporte')->get();

        $this->assertCount(2, $filas);
        $this->assertEqualsCanonicalizing(
            ['Patín', 'Fútbol'],
            $filas->pluck('deporte.nombre')->all(),
        );
        $this->assertSame(['Morales'], $filas->pluck('apellido')->unique()->values()->all());
    }

    public function test_todos_los_alumnos_tienen_plan_activo_del_grupo_al_que_pertenecen(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $sinPlan = Alumno::whereDoesntHave('planActivo')->count();
        $this->assertSame(0, $sinPlan, 'Un alumno sin plan activo no se puede cobrar.');

        // El plan tiene que ser de su propio grupo: si no, la cuota sale con el
        // precio de otro nivel.
        $planAjeno = DB::table('alumno_planes as ap')
            ->join('alumnos as a', 'a.id', '=', 'ap.alumno_id')
            ->join('grupo_planes as gp', 'gp.id', '=', 'ap.plan_id')
            ->where('ap.activo', true)
            ->whereColumn('gp.grupo_id', '!=', 'a.grupo_id')
            ->count();

        $this->assertSame(0, $planAjeno);
    }

    public function test_el_grupo_de_cada_alumno_es_de_su_propio_deporte(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $cruzados = DB::table('alumnos as a')
            ->join('grupos as g', 'g.id', '=', 'a.grupo_id')
            ->whereColumn('g.deporte_id', '!=', 'a.deporte_id')
            ->count();

        $this->assertSame(0, $cruzados, 'Es el defecto H06 de la carga manual: no puede volver.');
    }

    public function test_deja_los_catalogos_de_la_primera_carga(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $this->assertSame(4, Nivel::count());
        $this->assertNotNull(Nivel::where('nombre', 'Federadas')->first());
        $this->assertSame(6, Grupo::where('activo', true)->count());
        $this->assertSame(12, GrupoPlan::where('activo', true)->count());

        // Patín/Federadas con las dos frecuencias a los precios cargados en C10.
        $federadas = Grupo::whereHas('nivel', fn ($q) => $q->where('nombre', 'Federadas'))->sole();
        $precios = GrupoPlan::where('grupo_id', $federadas->id)
            ->orderBy('clases_por_semana')
            ->pluck('precio_mensual')
            ->map(fn ($precio) => (int) $precio)
            ->all();

        $this->assertSame([40000, 50000], $precios);
    }

    public function test_los_dos_tipos_de_caja_activos_conservan_su_saldo_inicial(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $activos = TipoCaja::where('activo', true)->pluck('saldo_inicial', 'nombre')
            ->map(fn ($saldo) => (int) $saldo)
            ->all();

        $this->assertSame(['Efectivo' => 250000, 'Mercado Pago' => 1320000], $activos);
    }

    public function test_cada_profesor_queda_con_su_subrubro_de_sueldo(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $this->assertSame(4, Profesor::where('activo', true)->count());
        $this->assertSame(0, Profesor::whereNull('subrubro_id')->count());

        // Sin subrubro propio la liquidación no sabe dónde imputar el sueldo.
        $subrubros = Profesor::with('subrubro')->get()->pluck('subrubro.id');
        $this->assertSame(4, $subrubros->unique()->count(), 'Dos profesores no pueden compartir subrubro.');
    }

    public function test_las_cuentas_de_profesor_apuntan_a_una_ficha_real(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $this->assertSame(7, User::where('activo', true)->count());
        $this->assertSame(1, User::where('rol', User::ROL_ADMIN)->count());
        $this->assertSame(2, User::where('rol', User::ROL_OPERATIVO)->count());
        $this->assertSame(4, User::where('rol', User::ROL_PROFESOR)->count());

        $huerfanas = User::where('rol', User::ROL_PROFESOR)
            ->whereNull('profesor_id')
            ->orWhere(fn ($q) => $q->where('rol', User::ROL_PROFESOR)
                ->whereNotIn('profesor_id', Profesor::pluck('id')))
            ->count();

        $this->assertSame(0, $huerfanas);
    }

    public function test_la_clave_de_prueba_permite_entrar_y_cumple_el_minimo(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $this->assertGreaterThanOrEqual(
            \App\Http\Controllers\UsuarioWebController::MINIMO_CONTRASENA,
            strlen(PrimeraCargaCompletaSeeder::CLAVE_DE_PRUEBA),
            'La clave del seeder no puede quedar por debajo del mínimo que exige el sistema.',
        );

        $respuesta = $this->post('/login', [
            'email'    => 'admin@wings.test',
            'password' => PrimeraCargaCompletaSeeder::CLAVE_DE_PRUEBA,
        ]);

        $respuesta->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_no_deja_operacion_cargada(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        // El punto de la primera carga es justamente que no hay nada cobrado
        // todavía: la prueba humana arranca de ahí.
        foreach (['pagos', 'deuda_cuotas', 'cajas_operativas', 'movimientos_operativos', 'clases', 'liquidaciones'] as $tabla) {
            $this->assertSame(0, DB::table($tabla)->count(), "El seeder dejó filas en {$tabla}.");
        }
    }

    public function test_se_niega_a_correr_sobre_una_base_que_ya_tiene_alumnos(): void
    {
        $this->seed(PrimeraCargaCompletaSeeder::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('alumnos');

        // Correrlo dos veces duplicaría los once manuales y rompería la
        // distribución que valida PrimeraCargaAlumnosSeeder.
        $this->seed(PrimeraCargaCompletaSeeder::class);
    }
}
