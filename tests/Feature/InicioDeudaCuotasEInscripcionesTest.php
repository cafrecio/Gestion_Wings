<?php

namespace Tests\Feature;

use App\Models\{Alumno, CargoAlumno, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, Subrubro, TipoCaja, User};
use App\Services\{CobranzaEstadoService, InscripcionService, PagoCuotaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InicioDeudaCuotasEInscripcionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;
    private GrupoPlan $planPatin;
    private GrupoPlan $planFutbol;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 10:00:00');
        $this->seed(CatalogosSeeder::class);
        \App\Models\ReglaPrimerPago::query()->delete();

        // Configuración de inscripción activa
        Configuracion::set('inscripcion_importe', 5000);
        Configuracion::set('dias_gracia_cobranza', 10);

        // Asegurar cobertura e historia básica para que reporte['deuda']['total'] esté activo
        if (DB::getSchemaBuilder()->hasTable('reporte_cobertura')) {
            DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
        }

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->patin = Deporte::firstOrCreate(['nombre' => 'Patín'], ['tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->futbol = Deporte::firstOrCreate(['nombre' => 'Fútbol'], ['tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);

        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
        $this->grupoPatin = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->grupoFutbol = Grupo::create(['deporte_id' => $this->futbol->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->planPatin = GrupoPlan::create(['grupo_id' => $this->grupoPatin->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
        $this->planFutbol = GrupoPlan::create(['grupo_id' => $this->grupoFutbol->id, 'clases_por_semana' => 2, 'precio_mensual' => 25000, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearAlumno(string $nombre, string $apellido, string $dni, Deporte $deporte, Grupo $grupo): Alumno
    {
        return Alumno::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'fecha_nacimiento' => '2010-01-01',
            'fecha_alta' => '2026-10-01',
            'celular' => '11-4000-0000',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);
    }

    private function crearDeudaCuota(Alumno $alumno, string $periodo, float $monto): DeudaCuota
    {
        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => $monto,
            'monto_pagado' => 0,
            'monto_condonado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);
    }

    private function crearCargoInscripcion(Alumno $alumno, float $monto = 5000): CargoAlumno
    {
        $subrubro = Subrubro::where('nombre', 'Inscripción al club')->firstOrFail();
        return CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'tipo' => 'INSCRIPCION',
            'dni' => InscripcionService::dni($alumno->dni),
            'clave_origen' => 'inscripcion:dni:' . InscripcionService::dni($alumno->dni),
            'subrubro_id' => $subrubro->id,
            'monto_original' => $monto,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-10-01'],
            'estado' => 'VIGENTE',
        ]);
    }

    private function registrarPagoCargo(CargoAlumno $cargo, float $monto): void
    {
        $pago = Pago::create([
            'alumno_id' => $cargo->alumno_id,
            'plan_id' => $this->planPatin->id,
            'mes' => 10,
            'anio' => 2026,
            'fecha_pago' => today(),
            'monto_base' => $monto,
            'porcentaje_aplicado' => 100,
            'monto_final' => $monto,
            'monto_cuota' => 0,
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);

        $cargo->pagos()->attach($pago->id, ['monto_aplicado' => $monto]);
    }

    /**
     * Caso 1: Solo cuotas (sin inscripción).
     * El total de Inicio coincide exactamente con Cobranza.
     */
    public function test_inicio_y_cobranza_concilian_con_solo_cuotas(): void
    {
        $a1 = $this->crearAlumno('Lucas', 'Gomez', '40000001', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a1, '2026-10', 30000);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        $this->assertSame(30000.0, $totalCobranza);
        $this->assertSame($totalCobranza, $totalInicioPesos);
        $respInicio->assertSee('$30.000');
    }

    /**
     * Caso 2: Cuotas + inscripción pendiente.
     * Inicio suma cuotas + inscripciones y coincide al peso con Cobranza.
     */
    public function test_inicio_y_cobranza_concilian_con_cuotas_mas_inscripcion(): void
    {
        $a1 = $this->crearAlumno('Sofia', 'Perez', '40000002', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a1, '2026-10', 30000);
        $this->crearCargoInscripcion($a1, 5000);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        $this->assertSame(35000.0, $totalCobranza);
        $this->assertSame($totalCobranza, $totalInicioPesos);
        $respInicio->assertSee('$35.000');
    }

    /**
     * Caso 3: Inscripción totalmente pagada.
     * Solo cuenta la cuota en ambos lugares.
     */
    public function test_inicio_y_cobranza_concilian_con_inscripcion_pagada(): void
    {
        $a1 = $this->crearAlumno('Martina', 'Lopez', '40000003', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a1, '2026-10', 30000);
        $cargo = $this->crearCargoInscripcion($a1, 5000);
        $this->registrarPagoCargo($cargo, 5000);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        $this->assertSame(30000.0, $totalCobranza);
        $this->assertSame($totalCobranza, $totalInicioPesos);
        $respInicio->assertSee('$30.000');
    }

    /**
     * Caso 4: Inscripción pagada en parte ($2.000 de $5.000).
     * Solo suma los $3.000 restantes en ambos lugares.
     */
    public function test_inicio_y_cobranza_concilian_con_inscripcion_pagada_parcial(): void
    {
        $a1 = $this->crearAlumno('Valentin', 'Diaz', '40000004', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a1, '2026-10', 30000);
        $cargo = $this->crearCargoInscripcion($a1, 5000);
        $this->registrarPagoCargo($cargo, 2000);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        // 30.000 cuota + 3.000 resto de inscripción = 33.000
        $this->assertSame(33000.0, $totalCobranza);
        $this->assertSame($totalCobranza, $totalInicioPesos);
        $respInicio->assertSee('$33.000');
    }

    /**
     * Caso 5: Alumna en dos deportes (mismo DNI).
     * Una sola inscripción compartida por persona; debe sumar cuota deporte 1 + cuota deporte 2 + 1 inscripción.
     */
    public function test_inicio_y_cobranza_concilian_con_alumna_en_dos_deportes_una_sola_inscripcion(): void
    {
        $dni = '40000005';
        $aPatin = $this->crearAlumno('Camila', 'Benitez', $dni, $this->patin, $this->grupoPatin);
        $aFutbol = $this->crearAlumno('Camila', 'Benitez', $dni, $this->futbol, $this->grupoFutbol);

        $this->crearDeudaCuota($aPatin, '2026-10', 30000);
        $this->crearDeudaCuota($aFutbol, '2026-10', 25000);
        // Una sola inscripción vinculada al DNI
        $this->crearCargoInscripcion($aPatin, 5000);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        // 30.000 + 25.000 + 5.000 = 60.000
        $this->assertSame(60000.0, $totalCobranza);
        $this->assertSame($totalCobranza, $totalInicioPesos);
        $respInicio->assertSee('$60.000');
    }

    /**
     * Caso 6: Inscripción condonada.
     * Una inscripción condonada no suma deuda en Cobranza ni en Inicio.
     */
    public function test_inicio_y_cobranza_concilian_con_inscripcion_condonada(): void
    {
        $a1 = $this->crearAlumno('Facundo', 'Sosa', '40000006', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a1, '2026-10', 30000);
        $cargo = $this->crearCargoInscripcion($a1, 5000);
        $cargo->update(['monto_condonado' => 5000]);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        $this->assertSame(30000.0, $totalCobranza);
        $this->assertSame($totalCobranza, $totalInicioPesos);
        $respInicio->assertSee('$30.000');
    }

    /**
     * Caso 7: Alumno inactivo con inscripción y cuota impagas.
     * Alumno inactivo se excluye en Cobranza y en las inscripciones de alumnos activos.
     */
    public function test_inicio_y_cobranza_excluyen_inscripcion_de_alumno_inactivo(): void
    {
        $inactivo = $this->crearAlumno('Julian', 'Navarro', '40000007', $this->patin, $this->grupoPatin);
        $this->crearCargoInscripcion($inactivo, 5000);
        $inactivo->update(['activo' => false]);

        $resCobranza = app(CobranzaEstadoService::class)->resumenDashboard();
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $reporteInicio = $respInicio->viewData('reporte');

        $totalCobranza = (float) $resCobranza['total_adeudado'];
        $totalInicioPesos = (float) ($reporteInicio['deuda']['total'] / 100);

        $this->assertSame(0.0, $totalCobranza);
        $this->assertSame(0.0, $totalInicioPesos);
    }
}
