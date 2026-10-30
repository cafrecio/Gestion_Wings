<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobranzaInscripcionEstadoTest extends TestCase
{
    use RefreshDatabase;

    private CobranzaEstadoService $service;
    private Deporte $patin;
    private Deporte $futbol;
    private Grupo $grupoPatin;
    private Grupo $grupoFutbol;
    private Subrubro $subrubroInscripcion;
    private User $operativo;
    private int $siguienteDni = 35000000;

    protected function setUp(): void
    {
        parent::setUp();

        Configuracion::set('dias_gracia_cobranza', 10);
        Configuracion::set('dias_plazo_alta', 7);

        $this->service = app(CobranzaEstadoService::class);

        $this->patin = Deporte::create([
            'nombre' => 'Patín',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $this->futbol = Deporte::create([
            'nombre' => 'Fútbol',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);

        $nivel = Nivel::create(['nombre' => 'Competencia']);
        $this->grupoPatin = Grupo::create([
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->grupoFutbol = Grupo::create([
            'deporte_id' => $this->futbol->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $rubro = Rubro::firstOrCreate(['nombre' => 'Ingresos'], ['tipo' => 'INGRESO', 'activo' => true]);
        $this->subrubroInscripcion = Subrubro::firstOrCreate(
            ['nombre' => 'Inscripción'],
            ['rubro_id' => $rubro->id, 'activo' => true]
        );

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Configuraciones
    // ─────────────────────────────────────────────────────────────────────────

    public function test_configuracion_dias_plazo_alta_existe_y_vale_7_por_defecto(): void
    {
        $this->assertSame(7, Configuracion::get('dias_plazo_alta'));
    }

    public function test_servicio_lanza_logic_exception_si_falta_dias_plazo_alta_o_es_menor_o_igual_a_cero(): void
    {
        $alumno = $this->crearAlumno('Test', 'Alta', '2026-10-01');

        Configuracion::where('clave', 'dias_plazo_alta')->delete();
        $this->expectException(\LogicException::class);
        $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-05'));
    }

    public function test_servicio_lanza_logic_exception_si_dias_plazo_alta_es_cero_o_negativo(): void
    {
        $alumno = $this->crearAlumno('Test', 'Alta', '2026-10-01');

        Configuracion::set('dias_plazo_alta', 0);
        $this->expectException(\LogicException::class);
        $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-05'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Jerarquía de Estados (Punto 1)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_jerarquia_de_estados_el_peor_gana(): void
    {
        // 1. Cuotas AL_DIA + Inscripción DEUDOR => DEUDOR
        $a1 = $this->crearAlumno('A1', 'DeudorPorInscripcion', '2025-03-15');
        $this->crearCargoInscripcion($a1, 5000);
        $res1 = $this->service->estadoAlumno($a1->id, Carbon::parse('2026-10-13'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $res1['estado']);

        // 2. Cuotas DEUDOR + Inscripción EN_PLAZO => DEUDOR (DEUDOR gana sobre EN_PLAZO)
        $a2 = $this->crearAlumno('A2', 'DeudorCuotaEnPlazoInscripcion', '2026-10-10');
        $this->crearCargoInscripcion($a2, 5000);
        $this->crearDeuda($a2, '2026-08', 10000); // Cuota de agosto es DEUDOR
        $res2 = $this->service->estadoAlumno($a2->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $res2['estado']);

        // 3. Cuotas AL_DIA + Inscripción MOROSO => MOROSO
        $a3 = $this->crearAlumno('A3', 'InscripcionMorosa', '2026-10-01');
        $this->crearCargoInscripcion($a3, 5000);
        $res3 = $this->service->estadoAlumno($a3->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $res3['estado']);

        // 4. Cuotas MOROSO + Inscripción AL_DIA => MOROSO
        $a4 = $this->crearAlumno('A4', 'CuotaMorosaInscripcionAlDia', '2026-01-01');
        $this->crearDeuda($a4, '2026-10', 10000);
        $res4 = $this->service->estadoAlumno($a4->id, Carbon::parse('2026-10-15'));
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $res4['estado']);

        // 5. Cuotas AL_DIA + Inscripción EN_PLAZO => EN_PLAZO
        $a5 = $this->crearAlumno('A5', 'EnPlazo', '2026-10-10');
        $this->crearCargoInscripcion($a5, 5000);
        $res5 = $this->service->estadoAlumno($a5->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $res5['estado']);

        // 6. Cuotas AL_DIA + Inscripción AL_DIA (sin cargo) => AL_DIA
        $a6 = $this->crearAlumno('A6', 'AlDia', '2026-10-01');
        $res6 = $this->service->estadoAlumno($a6->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_AL_DIA, $res6['estado']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pagos y Condonaciones (Punto 3)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_inscripcion_pagada_entera_o_condonada_no_altera_estado(): void
    {
        $alumno = $this->crearAlumno('Pagado', 'Entero', '2025-01-01');
        $cargo = $this->crearCargoInscripcion($alumno, 5000);

        // Sin pagar, con fecha vieja es DEUDOR
        $res = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-13'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $res['estado']);

        // Condonada entera
        $cargo->update(['monto_condonado' => 5000]);
        $resCondonada = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-13'));
        $this->assertSame(CobranzaEstadoService::ESTADO_AL_DIA, $resCondonada['estado']);
    }

    public function test_inscripcion_pagada_en_parte_sigue_contando_por_saldo_pendiente(): void
    {
        $alumno = $this->crearAlumno('Pagado', 'Parcial', '2025-01-01');
        $cargo = $this->crearCargoInscripcion($alumno, 5000);
        $cargo->update(['monto_condonado' => 2000]); // Saldo 3000

        $res = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-13'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $res['estado']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Persona en dos deportes (Punto 4 y Borde baja)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_persona_en_dos_deportes_inscripcion_afecta_solo_al_registro_imputado(): void
    {
        $dni = '38111222';
        $patin = $this->crearAlumnoConDni('Dos', 'Deportes', $dni, $this->patin, $this->grupoPatin, '2025-03-01');
        $futbol = $this->crearAlumnoConDni('Dos', 'Deportes', $dni, $this->futbol, $this->grupoFutbol, '2025-03-01');

        // Inscripción imputada a Patín
        $this->crearCargoInscripcion($patin, 5000);

        $resPatin = $this->service->estadoAlumno($patin->id, Carbon::parse('2026-10-13'));
        $resFutbol = $this->service->estadoAlumno($futbol->id, Carbon::parse('2026-10-13'));

        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $resPatin['estado']);
        $this->assertSame(CobranzaEstadoService::ESTADO_AL_DIA, $resFutbol['estado']);
    }

    public function test_registro_que_carga_inscripcion_dado_de_baja_con_otro_deporte_activo_hereda_la_deuda(): void
    {
        $dni = '38999888';
        $patin = $this->crearAlumnoConDni('Baja', 'Registro', $dni, $this->patin, $this->grupoPatin, '2025-03-01');
        $futbol = $this->crearAlumnoConDni('Baja', 'Registro', $dni, $this->futbol, $this->grupoFutbol, '2025-03-01');

        $this->crearCargoInscripcion($patin, 5000);

        // Se da de baja Patín
        $patin->update(['activo' => false]);

        // Fútbol (activo) hereda la inscripción
        $resFutbol = $this->service->estadoAlumno($futbol->id, Carbon::parse('2026-10-13'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $resFutbol['estado']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Paridad Cobranza vs Buscador de Cobrar (Punto 5)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_cobranza_y_buscador_de_cobrar_listan_exactamente_a_los_mismos(): void
    {
        // 1. Alumno al día (sin deudas)
        $a1 = $this->crearAlumno('Sin', 'Deuda', '2026-01-01');

        // 2. Alumno que debe solo cuotas (mes cerrado)
        $a2 = $this->crearAlumno('Debe', 'Cuota', '2026-01-01');
        $this->crearDeuda($a2, '2026-08', 12000);

        // 3. Alumno que debe solo inscripción (mes cerrado)
        $a3 = $this->crearAlumno('Debe', 'Inscripcion', '2025-03-01');
        $this->crearCargoInscripcion($a3, 5000);

        // 4. Alumno que debe ambas cosas
        $a4 = $this->crearAlumno('Debe', 'Ambas', '2026-02-01');
        $this->crearDeuda($a4, '2026-07', 15000);
        $this->crearCargoInscripcion($a4, 5000);

        // Evaluar en fecha de octubre 2026
        Carbon::setTestNow('2026-10-13 10:00:00');

        // Cobranza (filtro por defecto: DEUDORES / MOROSOS)
        $listadoCobranza = $this->service->listadoCobranza();
        $idsCobranza = $listadoCobranza->pluck('id')->sort()->values()->all();

        // Buscador de Cobrar (filtro de alumnos activos con saldo total > 0)
        $alumnosActivos = Alumno::where('activo', true)->get(['id', 'dni']);
        $saldos = $this->service->saldoDeAlumnos($alumnosActivos);
        $idsBuscadorCobrar = collect($saldos)
            ->filter(fn (array $s) => $s['total'] > 0)
            ->keys()
            ->sort()
            ->values()
            ->all();

        $this->assertSame($idsBuscadorCobrar, $idsCobranza);
        $this->assertCount(3, $idsCobranza);
        $this->assertNotContains($a1->id, $idsCobranza);
        $this->assertContains($a2->id, $idsCobranza);
        $this->assertContains($a3->id, $idsCobranza);
        $this->assertContains($a4->id, $idsCobranza);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ordenamiento por antigüedad en Cobranza (Punto 6)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_en_cobranza_quien_debe_solo_inscripcion_se_ordena_por_antiguedad_de_esa_deuda(): void
    {
        // Alumno 1: debe cuota de septiembre 2026
        $a1 = $this->crearAlumno('Zapata', 'Septiembre', '2026-01-01');
        $this->crearDeuda($a1, '2026-09', 10000);

        // Alumno 2: debe solo inscripción de marzo 2025
        $a2 = $this->crearAlumno('Alvarez', 'InscripcionVieja', '2025-03-15');
        $this->crearCargoInscripcion($a2, 5000);

        Carbon::setTestNow('2026-10-13 10:00:00');

        $listado = $this->service->listadoCobranza();

        // Alvarez (2025-03) debe ordenarse antes que Zapata (2026-09)
        $this->assertGreaterThanOrEqual(2, $listado->count());
        $this->assertSame($a2->id, $listado->first()->id);
        $this->assertSame('2025-03', $listado->first()->deuda_mas_antigua);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Bordes del Agregado 13/10/2026 (Plazo propio del alumno nuevo)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_borde_alumno_ingresa_un_lunes_con_7_dias_en_plazo_hasta_domingo_moroso_el_lunes_siguiente(): void
    {
        // Lunes 05 de octubre 2026
        $alumno = $this->crearAlumno('Lunes', 'Ingreso', '2026-10-05');
        $this->crearCargoInscripcion($alumno, 5000);

        // Domingo 11 de octubre: dentro de los 7 días corridos => EN_PLAZO
        $resDomingo = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-11'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $resDomingo['estado']);
        $this->assertSame(0, $resDomingo['dias_gracia_restantes']); // Último día

        // Lunes 12 de octubre: pasaron los 7 días, mes de octubre en curso => MOROSO
        $resLunes = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $resLunes['estado']);
        $this->assertSame(0, $resLunes['dias_gracia_restantes']);
    }

    public function test_borde_alumno_ingresa_dia_3_con_gracia_general_10_y_plazo_7_rige_el_general_hasta_dia_10(): void
    {
        // Ingreso día 3 de octubre con días de gracia = 10 y plazo propio = 7
        // 3 + 7 = 10, pero la gracia general es hasta el 10 del mes. Rige la general (hasta el 10 inclusive)
        $alumno = $this->crearAlumno('Dia3', 'Ingreso', '2026-10-03');
        $this->crearCargoInscripcion($alumno, 5000);

        // Día 10: EN_PLAZO
        $resDia10 = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-10'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $resDia10['estado']);

        // Día 11: vencieron ambas => MOROSO
        $resDia11 = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-11'));
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $resDia11['estado']);
    }

    public function test_borde_alta_28_septiembre_7_dias_en_plazo_hasta_4_octubre_deudor_el_5(): void
    {
        // Ingreso 28 de septiembre (mes de 30 días) con plazo propio = 7 días
        $alumno = $this->crearAlumno('Cruce', 'Mes', '2026-09-28');
        $this->crearCargoInscripcion($alumno, 5000);

        // Del 28/09 al 04/10: EN_PLAZO aunque septiembre haya cerrado
        $res04Oct = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-04'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $res04Oct['estado']);

        // A partir del 05/10: DEUDOR (el mes de septiembre ya cerró y el plazo venció)
        $res05Oct = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-05'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $res05Oct['estado']);
    }

    public function test_borde_alumno_con_fecha_alta_a_futuro_queda_en_plazo(): void
    {
        $alumno = $this->crearAlumno('Futuro', 'Ingreso', '2026-11-01');
        $this->crearCargoInscripcion($alumno, 5000);

        $res = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-15'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $res['estado']);
    }

    public function test_borde_alumno_reactivado_conserva_fecha_alta_original(): void
    {
        // Alumno creado en marzo 2026
        $alumno = $this->crearAlumno('Reactivado', 'Original', '2026-03-01');
        $this->crearCargoInscripcion($alumno, 5000);

        // Se desactiva y reactiva
        $alumno->update(['activo' => false]);
        $alumno->update(['activo' => true]);

        // La fecha_alta sigue siendo 2026-03-01
        $this->assertSame('2026-03-01', $alumno->fresh()->fecha_alta->format('Y-m-d'));

        // Evaluado en octubre: mes cerrado, plazo vencido => DEUDOR
        $res = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-13'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $res['estado']);
    }

    public function test_borde_correccion_de_fecha_alta_recalcula_plazo_contra_nueva_fecha(): void
    {
        // Creado inicialmente con alta el 01/10/2026
        $alumno = $this->crearAlumno('Corregido', 'FechaAlta', '2026-10-01');
        $this->crearCargoInscripcion($alumno, 5000);

        // El 12/10 estaría MOROSO
        $res1 = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $res1['estado']);

        // Se corrige la fecha de ingreso al 10/10/2026
        $alumno->update(['fecha_alta' => '2026-10-10']);

        // Con fecha 10/10, el 12/10 está dentro de los 7 días => EN_PLAZO
        $res2 = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-12'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $res2['estado']);
    }

    public function test_borde_cambio_de_mes_la_misma_inscripcion_pasa_de_morosa_a_deudora_al_cerrar_el_mes_de_ingreso(): void
    {
        // Ingreso 01 de octubre 2026
        $alumno = $this->crearAlumno('MesCierra', 'Cambio', '2026-10-01');
        $this->crearCargoInscripcion($alumno, 5000);

        // El 20 de octubre: mes en curso, plazo vencido => MOROSO
        $resOctubre = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-20'));
        $this->assertSame(CobranzaEstadoService::ESTADO_MOROSO, $resOctubre['estado']);

        // El 01 de noviembre: mes de octubre cerrado => DEUDOR
        $resNoviembre = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-11-01'));
        $this->assertSame(CobranzaEstadoService::ESTADO_DEUDOR, $resNoviembre['estado']);
    }

    public function test_dias_gracia_restantes_devuelve_el_mas_largo_cuando_esta_en_plazo(): void
    {
        // Alumno ingresa el lunes 05/10/2026. Plazo propio = 7 días (hasta 11/10 inclusive).
        // Gracia general de octubre = 10 días (hasta 10/10 inclusive).
        // El vencimiento más tardío es el 11/10 (plazo propio).
        $alumno = $this->crearAlumno('Restantes', 'Comparacion', '2026-10-05');
        $this->crearCargoInscripcion($alumno, 5000);

        // Evaluado el martes 06/10:
        // Días restantes plazo propio: hasta 11/10 inclusive = 5 días (mié, jue, vie, sáb, dom).
        $res = $this->service->estadoAlumno($alumno->id, Carbon::parse('2026-10-06'));
        $this->assertSame(CobranzaEstadoService::ESTADO_EN_PLAZO, $res['estado']);
        $this->assertSame(5, $res['dias_gracia_restantes']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Prueba por días, 32.4 (Iván): un cobro que no tocó ninguna cuota se nombra por lo que cobró
     * («Inscripción») en el historial de la ficha, no con el mes del pago.
     */
    public function test_la_ficha_nombra_inscripcion_un_cobro_que_no_toco_ninguna_cuota(): void
    {
        $alumno = $this->crearAlumno('Ortiz', 'Iván', '2026-11-03');
        $cargo = $this->crearCargoInscripcion($alumno, 5000);
        $pago = \App\Models\Pago::create(['alumno_id' => $alumno->id, 'mes' => 3, 'anio' => 2025, 'monto_base' => 5000, 'porcentaje_aplicado' => 100,
            'monto_final' => 5000, 'fecha_pago' => '2026-11-10', 'estado' => \App\Models\Pago::ESTADO_COMPLETADO]);
        $pago->cargos()->attach($cargo->id, ['monto_aplicado' => 5000]);

        $html = $this->actingAs($this->operativo)->get(route('web.alumnos.show', $alumno->id))->assertOk()->getContent();
        $this->assertStringContainsString('· Inscripción', $html);
        $this->assertStringNotContainsString('Mar 2025', $html);
    }

    private function crearAlumno(string $apellido, string $nombre, string $fechaAlta): Alumno
    {
        return $this->crearAlumnoConDni(
            $apellido,
            $nombre,
            (string) $this->siguienteDni++,
            $this->patin,
            $this->grupoPatin,
            $fechaAlta
        );
    }

    private function crearAlumnoConDni(
        string $apellido,
        string $nombre,
        string $dni,
        Deporte $deporte,
        Grupo $grupo,
        string $fechaAlta
    ): Alumno {
        return Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => $dni,
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01',
            'celular' => '11-4000-0000',
            'fecha_alta' => $fechaAlta,
            'activo' => true,
        ]);
    }

    private function crearCargoInscripcion(Alumno $alumno, float $monto): CargoAlumno
    {
        return CargoAlumno::create([
            'alumno_id' => $alumno->id,
            'dni' => $alumno->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'INSCRIPCION-' . $alumno->dni,
            'subrubro_id' => $this->subrubroInscripcion->id,
            'monto_original' => $monto,
            'monto_condonado' => 0,
            'estado' => 'VIGENTE',
            'calculo' => ['tipo' => 'fijo'],
        ]);
    }

    private function crearDeuda(Alumno $alumno, string $periodo, float $monto): DeudaCuota
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
}
