<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use App\Services\InscripcionService;
use App\Services\ReporteMensualService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

class VerificacionT19Test extends TestCase
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
    private TipoCaja $tipoCaja;
    private Subrubro $subrubroInscripcion;
    private Subrubro $subrubroCuota;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        CajaDeclarada::crear($this->operativo->id, $this->tipoCaja->id);

        $rubroCuotas = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $this->subrubroCuota = Subrubro::create([
            'rubro_id' => $rubroCuotas->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        $this->subrubroInscripcion = Subrubro::create([
            'rubro_id' => $rubroCuotas->id,
            'nombre' => 'Inscripción',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        $this->patin = Deporte::create(['nombre' => 'Patín Artístico', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->futbol = Deporte::create(['nombre' => 'Fútbol Infantil', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);

        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupoPatin = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->grupoFutbol = Grupo::create(['deporte_id' => $this->futbol->id, 'nivel_id' => $nivel->id, 'activo' => true]);

        $this->planPatin = GrupoPlan::create(['grupo_id' => $this->grupoPatin->id, 'clases_por_semana' => 2, 'precio_mensual' => 35000.0, 'activo' => true]);
        $this->planFutbol = GrupoPlan::create(['grupo_id' => $this->grupoFutbol->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000.0, 'activo' => true]);

        DB::table('reporte_cobertura')->updateOrInsert(['id' => 1], ['desde' => '2026-01-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearAlumno(string $nombre, string $apellido, string $dni, Deporte $deporte, Grupo $grupo, ?GrupoPlan $plan = null, bool $activo = true): Alumno
    {
        $plan ??= ($deporte->id === $this->patin->id ? $this->planPatin : $this->planFutbol);
        $alumno = Alumno::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'celular' => '1144445555',
            'fecha_nacimiento' => '2012-05-10',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_alta' => '2026-01-10',
            'activo' => $activo,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-01-10',
            'activo' => true,
        ]);

        return $alumno;
    }

    private function crearDeudaCuota(Alumno $alumno, string $periodo, float $montoOriginal, float $montoPagado = 0, float $montoCondonado = 0, string $estado = DeudaCuota::ESTADO_PENDIENTE, ?string $fechaEvento = null): DeudaCuota
    {
        $deuda = new DeudaCuota([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => $montoOriginal,
            'monto_pagado' => $montoPagado,
            'monto_condonado' => $montoCondonado,
            'estado' => $estado,
        ]);
        if ($fechaEvento) {
            $deuda->fechaHistorial = $fechaEvento;
        }
        $deuda->save();

        return $deuda;
    }

    private function crearCargoInscripcion(Alumno $alumno, float $monto = 5000, float $condonado = 0, ?string $fecha = null): CargoAlumno
    {
        $dni = InscripcionService::dni($alumno->dni);
        return CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'subrubro_id' => $this->subrubroInscripcion->id,
            'tipo' => 'INSCRIPCION',
            'monto_original' => $monto,
            'monto_pagado' => 0,
            'monto_condonado' => $condonado,
            'estado' => 'VIGENTE',
            'dni' => $dni,
            'clave_origen' => 'inscripcion:dni:' . $dni,
            'calculo' => [],
            'created_at' => $fecha ? Carbon::parse($fecha) : now(),
            'updated_at' => $fecha ? Carbon::parse($fecha) : now(),
        ]);
    }

    private function registrarPagoCargo(CargoAlumno $cargo, float $montoAplicado, ?string $fecha = null): Pago
    {
        $fechaPago = $fecha ?? '2026-10-05';
        $pago = Pago::create([
            'alumno_id' => $cargo->alumno_id,
            'plan_id' => null,
            'mes' => (int) substr($fechaPago, 5, 2),
            'anio' => (int) substr($fechaPago, 0, 4),
            'monto_base' => $montoAplicado,
            'porcentaje_aplicado' => 100,
            'monto_final' => $montoAplicado,
            'fecha_pago' => $fechaPago,
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);

        $cargo->pagos()->attach($pago->id, ['monto_aplicado' => $montoAplicado]);

        return $pago;
    }

    /**
     * Devuelve la deuda de las tres pantallas en CENTAVOS.
     */
    private function deudaTresPantallas(?string $mes = null, ?int $deporteId = null): array
    {
        $mes ??= '2026-10';

        // 1. Cobranza
        $alumnos = Alumno::where('activo', true)
            ->when($deporteId !== null, fn($q) => $q->where('deporte_id', $deporteId))
            ->get();
        $saldosCobranza = app(CobranzaEstadoService::class)->saldoDeAlumnos($alumnos);
        $totalCobranzaCentavos = (int) round(collect($saldosCobranza)->sum('total') * 100);

        // 2. Inicio (admin dashboard)
        $respInicio = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $totalInicioCentavos = $respInicio->viewData('reporte')['deuda']['total'] ?? 0;

        // 3. Reportes mensual
        $paramsReporte = ['mes' => $mes];
        if ($deporteId !== null) $paramsReporte['deporte_id'] = $deporteId;
        $respReporte = $this->actingAs($this->admin)->get(route('web.reportes.index', $paramsReporte))->assertOk();
        $totalReportesCentavos = $respReporte->viewData('reporte')['deuda']['total'] ?? 0;

        return [
            'cobranza' => $totalCobranzaCentavos,
            'inicio' => $totalInicioCentavos,
            'reportes' => $totalReportesCentavos,
        ];
    }

    // =========================================================================
    // 1. Igualdad al peso entre Inicio, Reportes y Cobranza
    // =========================================================================

    public function test_01a_inscripcion_pagada_entera(): void
    {
        $a = $this->crearAlumno('Lucia', 'Gomez', '40100001', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-10', 35000);
        $cargo = $this->crearCargoInscripcion($a, 5000);
        $this->registrarPagoCargo($cargo, 5000);

        // Solo debe la cuota de 35.000 ($3.500.000 centavos)
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(3500000, $deuda['cobranza']);
        $this->assertSame(3500000, $deuda['inicio']);
        $this->assertSame(3500000, $deuda['reportes']);
    }

    public function test_01b_inscripcion_pagada_en_parte(): void
    {
        $a = $this->crearAlumno('Marcos', 'Paz', '40100002', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-10', 35000);
        $cargo = $this->crearCargoInscripcion($a, 5000);
        $this->registrarPagoCargo($cargo, 2000);

        // Debe 35.000 cuota + 3.000 resto inscripción = 38.000 ($3.800.000 centavos)
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(3800000, $deuda['cobranza']);
        $this->assertSame(3800000, $deuda['inicio']);
        $this->assertSame(3800000, $deuda['reportes']);
    }

    public function test_01c_inscripcion_condonada_en_parte(): void
    {
        $a = $this->crearAlumno('Sofia', 'Mora', '40100003', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-10', 35000);
        $this->crearCargoInscripcion($a, 5000, 2000); // 2000 condonados

        // Debe 35.000 cuota + 3.000 resto inscripción = 38.000 ($3.800.000 centavos)
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(3800000, $deuda['cobranza']);
        $this->assertSame(3800000, $deuda['inicio']);
        $this->assertSame(3800000, $deuda['reportes']);
    }

    public function test_01d_cuota_pagada_en_parte(): void
    {
        $a = $this->crearAlumno('Tomas', 'Ruiz', '40100004', $this->patin, $this->grupoPatin);
        // Cuota 35.000 con 15.000 pagados = 20.000 pendiente
        $this->crearDeudaCuota($a, '2026-10', 35000, 15000);
        $this->crearCargoInscripcion($a, 5000);

        // Debe 20.000 cuota + 5.000 inscripción = 25.000 ($2.500.000 centavos)
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(2500000, $deuda['cobranza']);
        $this->assertSame(2500000, $deuda['inicio']);
        $this->assertSame(2500000, $deuda['reportes']);
    }

    public function test_01e_cuota_condonada(): void
    {
        $a = $this->crearAlumno('Clara', 'Vega', '40100005', $this->patin, $this->grupoPatin);
        // Cuota 35.000 totalmente condonada
        DeudaCuota::create([
            'alumno_id' => $a->id,
            'periodo' => '2026-10',
            'monto_original' => 35000,
            'monto_pagado' => 0,
            'monto_condonado' => 35000,
            'estado' => DeudaCuota::ESTADO_CONDONADA,
        ]);
        $this->crearCargoInscripcion($a, 5000);

        // Debe solo los 5.000 de inscripción ($500.000 centavos)
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(500000, $deuda['cobranza']);
        $this->assertSame(500000, $deuda['inicio']);
        $this->assertSame(500000, $deuda['reportes']);
    }

    public function test_01f_alumna_en_dos_deportes_una_sola_inscripcion(): void
    {
        $dni = '40100006';
        $aPatin = $this->crearAlumno('Elena', 'Rios', $dni, $this->patin, $this->grupoPatin);
        $aFutbol = $this->crearAlumno('Elena', 'Rios', $dni, $this->futbol, $this->grupoFutbol);

        $this->crearDeudaCuota($aPatin, '2026-10', 35000);
        $this->crearDeudaCuota($aFutbol, '2026-10', 30000);
        $this->crearCargoInscripcion($aPatin, 5000);

        // 35.000 patin + 30.000 futbol + 5.000 una sola inscripcion = 70.000 ($7.000.000 centavos)
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(7000000, $deuda['cobranza']);
        $this->assertSame(7000000, $deuda['inicio']);
        $this->assertSame(7000000, $deuda['reportes']);
    }

    public function test_01g_persona_con_un_alumno_activo_y_otro_inactivo(): void
    {
        $dni = '40100007';
        // Hizo patin (inactivo) y futbol (activo)
        $aInactivo = $this->crearAlumno('Mateo', 'Soler', $dni, $this->patin, $this->grupoPatin, null, false);
        $aActivo = $this->crearAlumno('Mateo', 'Soler', $dni, $this->futbol, $this->grupoFutbol, null, true);

        // Deuda vieja en patín (no debe contar porque el alumno está inactivo)
        $this->crearDeudaCuota($aInactivo, '2026-08', 35000);
        // Deuda en fútbol (debe contar)
        $this->crearDeudaCuota($aActivo, '2026-10', 30000);
        // Inscripción asociada al primer registro
        $this->crearCargoInscripcion($aInactivo, 5000);

        // Como la persona tiene al alumno Mateo activo en fútbol, la inscripción se mantiene activa.
        // Deuda total: 30.000 fútbol + 5.000 inscripción = 35.000 ($3.500.000 centavos).
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(3500000, $deuda['cobranza']);
        $this->assertSame(3500000, $deuda['inicio']);
        $this->assertSame(3500000, $deuda['reportes']);
    }

    public function test_01h_alumno_se_da_de_baja_y_luego_reingresa(): void
    {
        $a = $this->crearAlumno('Bautista', 'Perez', '40100008', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-10', 35000);
        $this->crearCargoInscripcion($a, 5000);

        // Activo: debe 40.000
        $this->assertSame(4000000, $this->deudaTresPantallas()['reportes']);

        // Se da de baja
        $a->update(['activo' => false]);
        $deudaBaja = $this->deudaTresPantallas();
        $this->assertSame(0, $deudaBaja['cobranza']);
        $this->assertSame(0, $deudaBaja['inicio']);
        $this->assertSame(0, $deudaBaja['reportes']);

        // Vuelve (reingreso)
        $a->update(['activo' => true]);
        $deudaReingreso = $this->deudaTresPantallas();
        $this->assertSame(4000000, $deudaReingreso['cobranza']);
        $this->assertSame(4000000, $deudaReingreso['inicio']);
        $this->assertSame(4000000, $deudaReingreso['reportes']);
    }

    // =========================================================================
    // 2. Mes futuro guardado y pendiente no cuenta en ninguna de las tres
    // =========================================================================

    public function test_02_mes_futuro_guardado_y_pendiente_no_cuenta_en_ninguna(): void
    {
        $a = $this->crearAlumno('Zoe', 'Lamas', '40100009', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-10', 35000, 35000, 0, DeudaCuota::ESTADO_PAGADA);

        // Mes futuro guardado y PENDIENTE (ej. por anulación de adelanto)
        $this->crearDeudaCuota($a, '2026-11', 35000, 0, 0, DeudaCuota::ESTADO_PENDIENTE, '2026-10-05');

        // En las tres pantallas debe dar exactamente 0
        $deuda = $this->deudaTresPantallas();
        $this->assertSame(0, $deuda['cobranza']);
        $this->assertSame(0, $deuda['inicio']);
        $this->assertSame(0, $deuda['reportes']);
    }

    // =========================================================================
    // 3. Filtro por deporte en Reportes y alumna en dos deportes
    // =========================================================================

    public function test_03_filtro_por_deporte_suma_el_total_y_no_duplica_inscripcion(): void
    {
        $dni = '40100010';
        $aPatin = $this->crearAlumno('Mia', 'Castro', $dni, $this->patin, $this->grupoPatin);
        $aFutbol = $this->crearAlumno('Mia', 'Castro', $dni, $this->futbol, $this->grupoFutbol);

        $this->crearDeudaCuota($aPatin, '2026-10', 35000);
        $this->crearDeudaCuota($aFutbol, '2026-10', 30000);
        // Inscripción generada con patín
        $this->crearCargoInscripcion($aPatin, 5000);

        // Total general
        $respGen = $this->actingAs($this->admin)->get(route('web.reportes.index'))->viewData('reporte')['deuda'];
        $totalGeneral = $respGen['total'];
        $this->assertSame(7000000, $totalGeneral); // 35.000 + 30.000 + 5.000

        // Filtro Patín
        $respPatin = $this->actingAs($this->admin)->get(route('web.reportes.index', ['deporte_id' => $this->patin->id]))->viewData('reporte')['deuda'];
        $totalPatin = $respPatin['total'];
        // 35.000 cuota + 5.000 inscripción = 40.000
        $this->assertSame(4000000, $totalPatin);
        $this->assertSame(500000, $respPatin['inscripciones']);

        // Filtro Fútbol
        $respFutbol = $this->actingAs($this->admin)->get(route('web.reportes.index', ['deporte_id' => $this->futbol->id]))->viewData('reporte')['deuda'];
        $totalFutbol = $respFutbol['total'];
        // 30.000 cuota + 0 inscripción = 30.000
        $this->assertSame(3000000, $totalFutbol);
        $this->assertSame(0, $respFutbol['inscripciones']);

        // Suma de deportes = Total general
        $this->assertSame($totalGeneral, $totalPatin + $totalFutbol);
    }

    // =========================================================================
    // 4. Meses pasados en Reportes: comportamiento temporal
    // =========================================================================

    public function test_04_meses_pasados_en_reportes_con_eventos_posteriores(): void
    {
        // Alumno dado de alta en agosto con deuda en agosto
        Carbon::setTestNow('2026-08-15 10:00:00');
        $a = $this->crearAlumno('Gaston', 'Arias', '40100011', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-08', 35000, 0, 0, DeudaCuota::ESTADO_PENDIENTE, '2026-08-01');
        $cargo = $this->crearCargoInscripcion($a, 5000, 0, '2026-08-15');

        // En agosto (al 31/08), debía 35.000 + 5.000 = 40.000
        $servicio = app(ReporteMensualService::class);
        $reporteAgosto = $servicio->obtener('2026-08');
        $this->assertSame(4000000, $reporteAgosto['deuda']['total']);

        // Viajamos a septiembre: paga la inscripción el 10/09
        Carbon::setTestNow('2026-09-10 10:00:00');
        $pago = $this->registrarPagoCargo($cargo, 5000, '2026-09-10');

        // Mirando el reporte histórico de agosto desde septiembre:
        // Como la fecha de corte de agosto es 2026-08-31, el pago del 10/09 NO computa para agosto:
        $reporteAgostoDesdeSept = $servicio->obtener('2026-08');
        $this->assertSame(4000000, $reporteAgostoDesdeSept['deuda']['total']);

        // Mirando el reporte de septiembre: la inscripción ya está paga al 30/09:
        $reporteSept = $servicio->obtener('2026-09');
        $this->assertSame(0, $reporteSept['deuda']['inscripciones']);

        // En octubre el alumno se da de baja
        Carbon::setTestNow('2026-10-10 10:00:00');
        $a->update(['activo' => false]);

        // Mirando agosto desde octubre con el alumno inactivo:
        // CUIDADO: como Alumno::where('activo', true) no tiene historial temporal,
        // la cuota de agosto y la inscripción de agosto desaparecen retroactivamente del reporte de agosto:
        $reporteAgostoConBaja = $servicio->obtener('2026-08');
        // Documentamos qué devuelve:
        $totalAgostoConBaja = $reporteAgostoConBaja['deuda']['total'];
        $this->assertSame(0, $totalAgostoConBaja);
    }

    // =========================================================================
    // 5. «Del mes» y «Meses anteriores» en Reportes
    // =========================================================================

    public function test_05_del_mes_y_meses_anteriores_en_reportes(): void
    {
        // Alumno 1: inscripción creada este mes (octubre)
        $a1 = $this->crearAlumno('Valeria', 'Cruz', '40100012', $this->patin, $this->grupoPatin);
        $this->crearCargoInscripcion($a1, 5000, 0, '2026-10-02');
        $this->crearDeudaCuota($a1, '2026-10', 35000);

        // Alumno 2: inscripción creada el mes pasado (septiembre)
        $a2 = $this->crearAlumno('Joaquin', 'Roca', '40100013', $this->futbol, $this->grupoFutbol);
        $this->crearCargoInscripcion($a2, 5000, 0, '2026-09-15');
        $this->crearDeudaCuota($a2, '2026-09', 30000);

        $reporte = app(ReporteMensualService::class)->obtener('2026-10');
        $deuda = $reporte['deuda'];

        // Total: (35.000 + 5.000) + (30.000 + 5.000) = 75.000 ($7.500.000)
        $this->assertSame(7500000, $deuda['total']);

        // Del mes: cuota 2026-10 (35.000) + inscripción creada en 2026-10 (5.000) = 40.000 ($4.000.000)
        $this->assertSame(4000000, $deuda['mes']);

        // Anteriores: cuota 2026-09 (30.000) + inscripción creada en 2026-09 (5.000) = 35.000 ($3.500.000)
        $this->assertSame(3500000, $deuda['anteriores']);

        // La suma de mes + anteriores es exactamente igual al total
        $this->assertSame($deuda['total'], $deuda['mes'] + $deuda['anteriores']);
    }

    // =========================================================================
    // 6. Otros lugares que muestran deuda de alumnos
    // =========================================================================

    public function test_06_otros_lugares_con_deuda(): void
    {
        $a = $this->crearAlumno('Franco', 'Ibarra', '40100014', $this->patin, $this->grupoPatin);
        $this->crearDeudaCuota($a, '2026-10', 35000);
        $this->crearCargoInscripcion($a, 5000);

        // 6a. Ficha del alumno (/alumnos/{id})
        $respFicha = $this->actingAs($this->admin)->get(route('web.alumnos.show', $a->id))->assertOk();
        // Muestra la cuota en el listado y la inscripción en su banner
        $respFicha->assertSee('$35.000');
        $respFicha->assertSee('Saldo pendiente: $5.000,00');

        // 6b. Buscador de Cobrar (/caja/cobrar?search=...)
        $respBuscador = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'Ibarra']))->assertOk();
        // Muestra saldo unificado: 40.000
        $respBuscador->assertSee('$40.000');

        // 6c. Inicio Operativo (/operativo)
        $respOperativo = $this->actingAs($this->operativo)->get(route('web.operativo.dashboard'))->assertOk();
        // Cuenta alumnos con deuda de cuota: 1
        $this->assertSame(1, $respOperativo->viewData('alumnosConDeuda'));
    }

    // =========================================================================
    // 7. «Profesores por pagar» y resto de Reportes no cambiaron
    // =========================================================================

    public function test_07_profesores_por_pagar_y_demas_reportes_intactos(): void
    {
        $profesor = Profesor::create([
            'deporte_id' => $this->patin->id,
            'nombre' => 'Carlos',
            'apellido' => 'Docente',
            'dni' => '25111222',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'CABA',
            'email' => 'profe@wings.test',
            'telefono' => '1122334455',
            'valor_hora' => 4000.0,
            'activo' => true,
        ]);

        $liq = Liquidacion::create([
            'profesor_id' => $profesor->id,
            'mes' => 10,
            'anio' => 2026,
            'tipo' => Liquidacion::TIPO_HORA,
            'total_calculado' => 80000.0,
            'valor_hora_aplicado' => 4000.0,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        ]);

        $reporte = app(ReporteMensualService::class)->obtener('2026-10');

        $this->assertSame(8000000, $reporte['por_pagar']['total']);
        $this->assertCount(1, $reporte['por_pagar']['filas']);
        $this->assertSame($profesor->id, $reporte['por_pagar']['filas'][0]['persona_id']);
    }
}
