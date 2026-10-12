<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CobranzaEstadoService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T29 — Verificación exhaustiva independiente de Gemini (Antigravity).
 *
 * Se verifica la regla decidida por Carlos (12/10):
 * - Sin buscar: la lista trae solo a los alumnos que deben, coincidiendo exactamente con el encabezado.
 * - Buscando por nombre, apellido o DNI: se llega a cualquier alumno activo y se le puede cobrar.
 * - "Deber" se define por el cálculo único de saldo (saldo['total'] > 0).
 */
class VerificacionT29GeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $operativo;
    private User $admin;
    private User $profesor;
    private Deporte $patin;
    private Grupo $grupo;
    private GrupoPlan $plan;
    private TipoCaja $efectivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        Configuracion::set('inscripcion_importe', '5000');
        Carbon::setTestNow('2026-10-05 10:00:00');

        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra Mostrador']);
        $this->admin     = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'name' => 'Carlos Administrador']);
        $this->profesor  = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true, 'name' => 'Profesor Juan']);

        $this->efectivo = TipoCaja::firstOrFail();
        CajaDeclarada::crear($this->operativo->id, $this->efectivo->id);

        $this->patin = Deporte::create([
            'nombre' => 'Patín Artístico',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA,
            'activo' => true,
        ]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Avanzado']);
        $this->grupo = Grupo::create([
            'deporte_id' => $this->patin->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);
        $this->plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 48000,
            'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Requisito 1:
     * Con alumnos que deben y alumnos al día, la lista sin buscar trae solo a los primeros,
     * el encabezado coincide con la lista, y la paginación no pierde ni muestra alumnos al día.
     */
    public function test_01_sin_buscar_lista_solo_deudores_coincide_con_encabezado_y_pagina_correctamente(): void
    {
        // 15 alumnos con deuda
        for ($i = 1; $i <= 15; $i++) {
            $deudor = $this->crearAlumno('Deudor', sprintf('Numero%02d', $i));
            $this->crearDeuda($deudor, '2026-09');
        }

        // 5 alumnos al día (sin deudas o cuotas ya pagadas)
        $alDia1 = $this->crearAlumno('Aldia', 'Uno');
        $alDia2 = $this->crearAlumno('Aldia', 'Dos');
        $alDia3 = $this->crearAlumno('Aldia', 'Tres');
        $alDia4 = $this->crearAlumno('Aldia', 'Cuatro');
        $alDia5 = $this->crearAlumno('Aldia', 'Cinco');
        $this->crearDeuda($alDia5, '2026-09', pagado: 48000, estado: DeudaCuota::ESTADO_PAGADA);

        // Página 1 (12 items por página)
        $resP1 = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();

        $this->assertSame(15, $resP1->viewData('conDeuda'), 'Encabezado debe indicar exactamente 15 alumnos con deuda.');
        $this->assertSame(15, $resP1->viewData('alumnos')->total(), 'El total del paginador debe ser 15.');
        $this->assertCount(12, $resP1->viewData('alumnos')->items(), 'Página 1 debe tener exactamente 12 alumnos.');
        $resP1->assertSee('15');
        $resP1->assertSee('alumnos con deuda pendiente');

        // Ningún alumno al día debe aparecer en página 1
        $resP1->assertDontSee('Aldia, Uno');
        $resP1->assertDontSee('Aldia, Dos');
        $resP1->assertDontSee('Aldia, Tres');
        $resP1->assertDontSee('Aldia, Cuatro');
        $resP1->assertDontSee('Aldia, Cinco');

        // Página 2 (3 items restantes)
        $resP2 = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['page' => 2]))->assertOk();
        $this->assertSame(15, $resP2->viewData('conDeuda'));
        $this->assertSame(15, $resP2->viewData('alumnos')->total());
        $this->assertCount(3, $resP2->viewData('alumnos')->items(), 'Página 2 debe tener los 3 deudores restantes.');

        // Ningún alumno al día en página 2
        $resP2->assertDontSee('Aldia, Uno');
        $resP2->assertDontSee('Aldia, Dos');
        $resP2->assertDontSee('Aldia, Tres');
        $resP2->assertDontSee('Aldia, Cuatro');
        $resP2->assertDontSee('Aldia, Cinco');
    }

    /**
     * Requisito 2:
     * Un alumno al día no aparece sin buscar, aparece buscándolo por nombre o DNI,
     * y desde allí se le puede cobrar de verdad por el camino real completo (POST web.caja.pagar).
     */
    public function test_02_alumno_al_dia_no_aparece_sin_buscar_aparece_buscando_y_se_le_cobra_por_camino_real(): void
    {
        $alDia = $this->crearAlumno('Bustamante', 'Mateo', dni: '35123456');
        $deudor = $this->crearAlumno('Gómez', 'Carlos', dni: '35999999');
        $this->crearDeuda($deudor, '2026-09');

        // Sin buscar: no aparece
        $resSinBuscar = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $resSinBuscar->assertDontSee('Bustamante, Mateo');
        $resSinBuscar->assertSee('Gómez, Carlos');

        // Buscando por apellido y por DNI: aparece con punto verde y botón Cobrar
        foreach (['Bustamante', '35123456'] as $term) {
            $resBusqueda = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => $term]))->assertOk();
            $resBusqueda->assertSee('Bustamante, Mateo');
            $resBusqueda->assertSee('alumno-dot--success');
            $resBusqueda->assertSee(route('web.caja.cobrar', $alDia->id));
        }

        // Pantalla de cobro GET
        $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $alDia->id))
            ->assertOk()
            ->assertSee('Octubre 2026');

        // Camino real de cobro POST: cobrar mes en curso
        $resPago = $this->actingAs($this->operativo)->post(route('web.caja.pagar', $alDia->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => '48000.00'],
            'monto_entregado' => '48000.00',
            'fecha_pago' => '2026-10-05',
        ]);

        $resPago->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('recibo_pago_id')
            ->assertSessionHas('success');

        // Verificar que el pago se registró en la base de datos
        $this->assertDatabaseHas('pagos', [
            'alumno_id' => $alDia->id,
            'monto_final' => 48000,
            'estado' => Pago::ESTADO_COMPLETADO,
        ]);
        $this->assertDatabaseHas('deuda_cuotas', [
            'alumno_id' => $alDia->id,
            'periodo' => '2026-10',
            'monto_pagado' => 48000,
            'estado' => DeudaCuota::ESTADO_PAGADA,
        ]);
        $this->assertDatabaseHas('movimientos_operativos', [
            'tipo_caja_id' => $this->efectivo->id,
            'alumno_id' => $alDia->id,
            'monto' => 48000,
        ]);

        // Al volver al buscador sin buscar (con sesión limpia sin el flash previo de cobro):
        // el alumno sigue sin aparecer en la lista de deudores porque ya pagó.
        $this->flushSession();
        $resPostPago = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $resPostPago->assertDontSee('Bustamante, Mateo');
        $this->assertFalse($resPostPago->viewData('alumnos')->contains('id', $alDia->id));
    }

    /**
     * Requisito 3:
     * Un alumno recién dado de alta, que nace con su cuota del mes generada,
     * aparece sin buscar en el listado de deudores.
     */
    public function test_03_alumno_recien_dado_de_alta_con_cuota_del_mes_aparece_sin_buscar(): void
    {
        $nuevo = $this->crearAlumno('Recien', 'Ingresada', fechaAlta: '2026-10-05');
        // Cuota del mes vigente generada al alta
        $this->crearDeuda($nuevo, '2026-10', monto: 48000, pagado: 0, estado: DeudaCuota::ESTADO_PENDIENTE);

        $res = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $res->assertSee('Recien, Ingresada');
        $res->assertSee('alumno-dot--danger');
        $this->assertSame(1, $res->viewData('conDeuda'));
    }

    /**
     * Requisito 4:
     * Un alumno al que todavía no se le generó la cuota del mes no aparece sin buscar,
     * se lo encuentra buscando y la pantalla de cobro le ofrece el mes en curso.
     */
    public function test_04_alumno_sin_cuota_del_mes_generada_no_aparece_sin_buscar_y_buscando_se_ofrece_el_mes(): void
    {
        // Alumno activo cuyo plan no tiene deudas en la base de datos
        $sinCuota = $this->crearAlumno('Plan', 'SinGenerar');

        // Sin buscar: no aparece
        $res = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $res->assertDontSee('Plan, SinGenerar');
        $this->assertSame(0, $res->viewData('conDeuda'));

        // Buscando: aparece
        $resBusq = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'SinGenerar']))->assertOk();
        $resBusq->assertSee('Plan, SinGenerar');
        $resBusq->assertSee('alumno-dot--success');

        // Pantalla de cobro: le ofrece el mes en curso (Octubre 2026)
        $resCobrar = $this->actingAs($this->operativo)->get(route('web.caja.cobrar', $sinCuota->id))->assertOk();
        $resCobrar->assertSee('Octubre 2026');
    }

    /**
     * Requisito 5:
     * El que debe solo la inscripción aparece sin buscar.
     * El que tiene solo un mes futuro pendiente (ej. cobro adelantado anulado) NO aparece.
     */
    public function test_05_deuda_solo_inscripcion_aparece_sin_buscar_y_mes_futuro_pendiente_no_aparece(): void
    {
        // Alumno 1: debe solo inscripción
        $soloInscripcion = $this->crearAlumno('Inscripcion', 'Pendiente');
        CargoAlumno::create([
            'alumno_id' => $soloInscripcion->id,
            'dni' => $soloInscripcion->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $soloInscripcion->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-10-01'],
            'estado' => 'VIGENTE',
        ]);

        // Alumno 2: cuota de mes futuro (noviembre 2026 estando en octubre)
        $mesFuturo = $this->crearAlumno('Futuro', 'Noviembre');
        $this->crearDeuda($mesFuturo, '2026-11');

        $res = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();

        // Debe verse el de inscripción
        $res->assertSee('Inscripcion, Pendiente');
        // NO debe verse el de mes futuro
        $res->assertDontSee('Futuro, Noviembre');

        $this->assertSame(1, $res->viewData('conDeuda'));
        $this->assertSame(1, $res->viewData('alumnos')->total());
    }

    /**
     * Requisito 6:
     * Un alumno inactivo con deuda no aparece, no se cuenta y tampoco aparece buscándolo.
     */
    public function test_06_alumno_inactivo_con_deuda_no_aparece_ni_se_cuenta_ni_buscandolo(): void
    {
        $inactivo = $this->crearAlumno('Inactivo', 'ConDeuda', dni: '38123123');
        $this->crearDeuda($inactivo, '2026-09');
        $inactivo->update(['activo' => false]);

        // Sin buscar: no aparece ni cuenta
        $res = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $res->assertDontSee('Inactivo, ConDeuda');
        $this->assertSame(0, $res->viewData('conDeuda'));
        $this->assertSame(0, $res->viewData('alumnos')->total());

        // Buscando por nombre o DNI: tampoco aparece porque está inactivo
        $resBusq = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => '38123123']))->assertOk();
        $resBusq->assertDontSee('Inactivo, ConDeuda');
        $resBusq->assertSee('Sin resultados');
    }

    /**
     * Requisito 7:
     * El dueño (ADMIN) entrando a Cobrar sin caja abierta ve solo a los deudores sin buscar,
     * encuentra a quien no debe buscándolo y puede cobrar directo al cashflow sin caja.
     */
    public function test_07_duenio_sin_caja_abierta_ve_mismo_comportamiento_y_cobra_directo(): void
    {
        $deudor = $this->crearAlumno('Deudor', 'ParaAdmin');
        $this->crearDeuda($deudor, '2026-09');

        $alDia = $this->crearAlumno('AlDia', 'ParaAdmin');

        // Admin sin abrir caja entra a cobrar-cuota
        $resAdmin = $this->actingAs($this->admin)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $resAdmin->assertSee('Deudor, ParaAdmin');
        $resAdmin->assertDontSee('AlDia, ParaAdmin');
        $this->assertSame(1, $resAdmin->viewData('conDeuda'));

        // Buscando al alumno al día
        $resAdminBusq = $this->actingAs($this->admin)->get(route('web.caja.cobrar-cuota', ['search' => 'ParaAdmin']))->assertOk();
        $resAdminBusq->assertSee('Deudor, ParaAdmin');
        $resAdminBusq->assertSee('AlDia, ParaAdmin');

        // Cobro directo del dueño sin caja abierta
        $resPago = $this->actingAs($this->admin)->post(route('web.caja.pagar', $alDia->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => '48000.00'],
            'monto_entregado' => '48000.00',
            'fecha_pago' => '2026-10-05',
        ]);

        $resPago->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('recibo_pago_id')
            ->assertSessionHas('success');

        // Verificar que el dueño cobró directo sin caja operativa (impacta en CashflowMovimiento)
        $this->assertDatabaseHas('cashflow_movimientos', [
            'usuario_admin_id' => $this->admin->id,
            'monto' => 48000,
        ]);
        $this->assertDatabaseMissing('movimientos_operativos', [
            'alumno_id' => $alDia->id,
        ]);
    }

    /**
     * Requisito 8:
     * La profesora (PROFESOR) no puede entrar al buscador de cobrar (403 Forbidden).
     */
    public function test_08_profesora_no_puede_entrar_a_cobrar_cuota(): void
    {
        $this->actingAs($this->profesor)
            ->get(route('web.caja.cobrar-cuota'))
            ->assertForbidden();
    }

    /**
     * Requisito 9:
     * Las otras pantallas que llevan al buscador (Caja, Operativo dashboard, Resumen de caja)
     * siguen llegando bien con enlaces válidos.
     */
    public function test_09_pantallas_de_origen_tienen_enlaces_validos_hacia_el_buscador(): void
    {
        // 1. Caja index (operativo con caja abierta)
        $resCaja = $this->actingAs($this->operativo)->get(route('web.caja.index'))->assertOk();
        $resCaja->assertSee(route('web.caja.cobrar-cuota'));

        // 2. Operativo dashboard (/operativo)
        $resOperativo = $this->actingAs($this->operativo)->get('/operativo')->assertOk();
        $resOperativo->assertSee(route('web.caja.cobrar-cuota'));

        // 3. Admin caja index (admin sin caja abierta ve botón "Cobrar cuota")
        $resAdminCaja = $this->actingAs($this->admin)->get(route('web.caja.index'))->assertOk();
        $resAdminCaja->assertSee(route('web.caja.cobrar-cuota'));
    }

    /**
     * Requisito 10:
     * Comparación rigurosa de alumnos con deuda entre el Buscador de Cobrar y Cobranza.
     * Si difieren, documenta matemáticamente y comprueba la causa exacta.
     */
    public function test_10_comparacion_exacta_buscador_cobrar_vs_pantalla_cobranza(): void
    {
        // Caso A: Cuota anterior vencida (DEUDOR)
        $d1 = $this->crearAlumno('A_Deudor', 'Uno');
        $this->crearDeuda($d1, '2026-08');

        // Caso B: Cuota mes en curso (dentro de plazo de gracia de 10 días, fecha 5) -> EN_PLAZO
        $d2 = $this->crearAlumno('B_EnPlazo', 'Dos');
        $this->crearDeuda($d2, '2026-10');

        // Caso C: Otra cuota anterior vencida (DEUDOR)
        $d3 = $this->crearAlumno('C_Deudor', 'Tres');
        $this->crearDeuda($d3, '2026-09');

        // Caso D: Deuda únicamente de inscripción
        $d4 = $this->crearAlumno('D_SoloInscrip', 'Cuatro');
        CargoAlumno::create([
            'alumno_id' => $d4->id,
            'dni' => $d4->dni,
            'tipo' => 'INSCRIPCION',
            'clave_origen' => 'inscripcion:dni:' . $d4->dni,
            'subrubro_id' => Subrubro::where('nombre', 'Inscripción al club')->firstOrFail()->id,
            'monto_original' => 5000,
            'monto_cobrado' => 0,
            'monto_condonado' => 0,
            'calculo' => ['fecha_ingreso' => '2026-10-01'],
            'estado' => 'VIGENTE',
        ]);

        // Caso E: Alumno al día
        $alDia = $this->crearAlumno('E_AlDia', 'Cinco');

        // 1. Buscador de Cobrar: cuenta a TODOS los que tienen saldo['total'] > 0
        $resCobrar = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $conDeudaCobrar = $resCobrar->viewData('conDeuda');

        // D1 (48000), D2 (48000), D3 (48000), D4 (5000) tienen saldo > 0 -> total 4 alumnos
        $this->assertSame(4, $conDeudaCobrar, 'Cobrar cuenta a todo alumno con saldo pendiente de pago.');

        // 2. Pantalla Cobranza:
        $cobranzaService = app(CobranzaEstadoService::class);
        $listadoDeudores = $cobranzaService->listadoCobranza('DEUDORES');
        $resumenCobranza = $cobranzaService->resumenDashboard();

        // En Cobranza, el filtro por defecto 'DEUDORES' agrupa únicamente estados DEUDOR y MOROSO de cuotas:
        // d1 (DEUDOR) y d3 (DEUDOR) -> total 2 en el listado DEUDORES
        $this->assertCount(2, $listadoDeudores, 'En Cobranza DEUDORES entran solo d1 y d3 (cuotas anteriores vencidas).');
        $this->assertSame(2, $resumenCobranza['por_estado']['DEUDOR']);
        $this->assertSame(1, $resumenCobranza['por_estado']['EN_PLAZO']);
        $this->assertSame(2, $resumenCobranza['por_estado']['AL_DIA']); // d4 (cuotas al día, debe inscripción) y alDia

        // El total adeudado en pesos coincide exactamente:
        $this->assertSame(149000.0, (float) $resumenCobranza['total_adeudado']); // 48000*3 + 5000 = 149000

        // Correspondencia matemática exacta:
        // Alumnos con saldo en Cobrar (4) = DEUDOR (2) + EN_PLAZO (1) + alumnos AL_DIA con inscripción pendiente (1)
        $alumnosAlDiaConInscripcion = Alumno::where('activo', true)->get()->filter(function ($a) use ($cobranzaService) {
            $saldo = $cobranzaService->saldoDeAlumnos(collect([$a]))[$a->id];
            $estado = $cobranzaService->estadoAlumno($a->id)['estado'];
            return $estado === CobranzaEstadoService::ESTADO_AL_DIA && $saldo['inscripcion'] > 0;
        })->count();

        $this->assertSame(
            $conDeudaCobrar,
            $resumenCobranza['por_estado']['DEUDOR']
            + $resumenCobranza['por_estado']['MOROSO']
            + $resumenCobranza['por_estado']['EN_PLAZO']
            + $alumnosAlDiaConInscripcion,
            'La suma de deudores de cuota + en plazo + deudores de inscripción iguala exactamente a los deudores del buscador de Cobrar.'
        );
    }

    /**
     * Requisito 11:
     * Casos adicionales no cubiertos por Claude:
     * - Búsqueda por DNI parcial.
     * - Búsqueda insensible a mayúsculas/minúsculas.
     * - Alumno con deuda parcial (pago a cuenta): aparece como deudor con su saldo pendiente exacto.
     * - Alumno con deuda totalmente condonada: no aparece como deudor sin buscar.
     * - Paginación en una búsqueda que devuelve más de 12 alumnos (conserva el parámetro search).
     * - Búsqueda con espacios en blanco ("   "): tratada como vacía, muestra solo deudores.
     */
    public function test_11_casos_adicionales_no_cubiertos_por_claude(): void
    {
        // A. DNI parcial
        $alDni = $this->crearAlumno('Zabaleta', 'Tomás', dni: '44556677');
        $resDni = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => '5566']))->assertOk();
        $resDni->assertSee('Zabaleta, Tomás');

        // B. Búsqueda insensible a mayúsculas/minúsculas
        $resCase = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'zAbAlEtA']))->assertOk();
        $resCase->assertSee('Zabaleta, Tomás');

        // C. Alumno con deuda parcial
        $parcial = $this->crearAlumno('Parcial', 'Pago');
        $this->crearDeuda($parcial, '2026-09', monto: 48000, pagado: 20000, estado: DeudaCuota::ESTADO_PENDIENTE);
        $resParcial = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $resParcial->assertSee('Parcial, Pago');
        $resParcial->assertSee('28'); // Saldo restante (formato $28.000 o $28,000.00)

        // D. Alumno con deuda condonada total
        $condonado = $this->crearAlumno('Condonado', 'Total');
        DeudaCuota::create([
            'alumno_id' => $condonado->id,
            'periodo' => '2026-09',
            'monto_original' => 48000,
            'monto_pagado' => 0,
            'monto_condonado' => 48000,
            'estado' => DeudaCuota::ESTADO_CONDONADA,
        ]);
        $resCondonado = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota'))->assertOk();
        $resCondonado->assertDontSee('Condonado, Total');

        // E. Paginación durante una búsqueda
        for ($i = 1; $i <= 14; $i++) {
            $this->crearAlumno('PerezFamilia', sprintf('Hijo%02d', $i));
        }
        $resBusqP1 = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'PerezFamilia']))->assertOk();
        $this->assertCount(12, $resBusqP1->viewData('alumnos')->items());
        $this->assertSame(14, $resBusqP1->viewData('alumnos')->total());

        $resBusqP2 = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => 'PerezFamilia', 'page' => 2]))->assertOk();
        $this->assertCount(2, $resBusqP2->viewData('alumnos')->items());
        $this->assertStringContainsString('search=PerezFamilia', $resBusqP1->viewData('alumnos')->nextPageUrl());

        // F. Búsqueda con espacios en blanco ("   ")
        $resEspacios = $this->actingAs($this->operativo)->get(route('web.caja.cobrar-cuota', ['search' => '   ']))->assertOk();
        // Debe mostrar a los deudores (Parcial, Pago) y no a los que están al día (Zabaleta, Tomás)
        $resEspacios->assertSee('Parcial, Pago');
        $resEspacios->assertDontSee('Zabaleta, Tomás');
    }

    private function crearAlumno(string $apellido, string $nombre, ?string $dni = null, ?string $fechaAlta = '2026-08-01'): Alumno
    {
        $alumno = Alumno::create([
            'apellido' => $apellido,
            'nombre' => $nombre,
            'dni' => $dni ?? (string) (33000000 + Alumno::count() + 1),
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupo->id,
            'fecha_nacimiento' => '2010-05-10',
            'celular' => '11-4555-5555',
            'fecha_alta' => $fechaAlta,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno->id,
            'plan_id' => $this->plan->id,
            'fecha_desde' => $fechaAlta,
            'activo' => true,
        ]);

        return $alumno;
    }

    private function crearDeuda(Alumno $alumno, string $periodo, float $monto = 48000, float $pagado = 0, string $estado = DeudaCuota::ESTADO_PENDIENTE): DeudaCuota
    {
        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => $monto,
            'monto_pagado' => $pagado,
            'monto_condonado' => 0,
            'estado' => $estado,
        ]);
    }
}
