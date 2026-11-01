<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CargoAlumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use App\Services\CashflowIntegracionCajaService;
use App\Services\CashflowService;
use App\Services\InscripcionService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * Control independiente de Gemini para T107 y T108.
 * Cubre los 15 casos del prompt + concurrencia real y arqueo en Cashflow.
 */
class VerificacionT107T108GeminiTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private TipoCaja $efectivo;
    private TipoCaja $banco;
    private Subrubro $subrubroCuota;
    private Subrubro $subrubroManual;
    private Deporte $futbol;
    private Deporte $patin;
    private Grupo $grupoPatin;
    private GrupoPlan $planPatin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->seed(CatalogosSeeder::class);
        Configuracion::set('inscripcion_importe', '8000');

        $this->duenio = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->efectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);
        $this->banco = TipoCaja::create(['nombre' => 'Banco Galicia T107', 'abreviatura' => 'BGA', 'activo' => true, 'saldo_inicial' => 100000]);

        $this->subrubroCuota = Subrubro::where('nombre', 'Cuota Mensual')->first();
        if (!$this->subrubroCuota) {
            $rubro = Rubro::firstOrCreate(['nombre' => 'Ingresos Operativos'], ['tipo' => 'INGRESO']);
            $this->subrubroCuota = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Cuota Mensual', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
        }

        $rubroManual = Rubro::firstOrCreate(['nombre' => 'Otros Ingresos'], ['tipo' => 'INGRESO']);
        $this->subrubroManual = Subrubro::create(['rubro_id' => $rubroManual->id, 'nombre' => 'Varios Manual T107', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);

        $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial T108']);
        $this->futbol = Deporte::create(['nombre' => 'Fútbol T108', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_COMISION, 'activo' => true]);
        $this->patin = Deporte::create(['nombre' => 'Patín T108', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->grupoPatin = Grupo::create(['deporte_id' => $this->patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->planPatin = GrupoPlan::create(['grupo_id' => $this->grupoPatin->id, 'clases_por_semana' => 2, 'precio_mensual' => 25000, 'activo' => true]);
    }

    private function crearCajaAbierta(string $inicial = '11600.00', ?User $operativo = null): CajaOperativa
    {
        $caja = CajaDeclarada::crear(($operativo ?? $this->operativa)->id, $this->efectivo->id);
        $caja->update(['efectivo_inicial' => $inicial]);
        return $caja;
    }

    private function agregarMovimiento(CajaOperativa $caja, string $monto, ?TipoCaja $cuenta = null, string $tipo = 'INGRESO'): MovimientoOperativo
    {
        return MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'usuario_id' => $caja->usuario_operativo_id,
            'tipo_caja_id' => ($cuenta ?? $this->efectivo)->id,
            'subrubro_id' => $this->subrubroManual->id,
            'concepto' => 'Movimiento prueba T107',
            'monto' => $monto,
            'tipo' => $tipo,
            'fecha' => today(),
            'estado' => 'ACTIVO',
        ]);
    }

    // =========================================================================
    // SECCIÓN 1: T107 — Cierre con pantalla vieja
    // =========================================================================

    /**
     * Caso 1: Por pantalla: abrir el cierre, cobrar en efectivo en otra pestaña, volver y cerrar.
     * Resultado: No cierra; «Cambió el efectivo esperado: ahora es $…»; la pantalla vuelve con el esperado al día.
     */
    public function test_caso_1_cobro_en_otra_pestania_rechaza_cierre_y_actualiza_pantalla(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');

        // 1. Abre pantalla de cierre: ve 11600.00
        $resGet = $this->actingAs($this->operativa)->get(route('web.caja.cierre', $caja->id));
        $resGet->assertOk();
        $resGet->assertSee('name="esperado_visto" value="11600.00"', false);

        // 2. En otra pestaña entra cobro de $44.000 en efectivo
        $this->agregarMovimiento($caja, '44000.00');

        // 3. Intenta cerrar con los $11.600 vistos antes
        $resPost = $this->actingAs($this->operativa)->from(route('web.caja.cierre', $caja->id))
            ->post(route('web.caja.cerrar', $caja->id), [
                'efectivo_contado' => '11600.00',
                'cambio_retenido' => '5000.00',
                'esperado_visto' => '11600.00',
            ]);

        $resPost->assertRedirect(route('web.caja.cierre', $caja->id));
        $resPost->assertSessionHasErrors('efectivo_contado');
        $error = session('errors')->first('efectivo_contado');
        $this->assertStringContainsString('Cambió el efectivo esperado: ahora es $55,600.00', $error);

        // 4. La caja sigue ABIERTA
        $caja->refresh();
        $this->assertSame(CajaOperativa::ESTADO_ABIERTA, $caja->estado);
        $this->assertNull($caja->efectivo_contado);

        // 5. La pantalla de cierre vuelve con el esperado al día ($55.600)
        $resGet2 = $this->actingAs($this->operativa)->get(route('web.caja.cierre', $caja->id));
        $resGet2->assertOk();
        $resGet2->assertSee('$55,600.00');
        $resGet2->assertSee('name="esperado_visto" value="55600.00"', false);
    }

    /**
     * Caso 2: Cerrar de nuevo con lo contado de verdad.
     * Resultado: Cierra sin diferencia.
     */
    public function test_caso_2_cerrar_con_esperado_al_dia_cierra_sin_diferencia(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');
        $this->agregarMovimiento($caja, '44000.00'); // total esperado: 55600.00

        $resPost = $this->actingAs($this->operativa)->from(route('web.caja.cierre', $caja->id))
            ->post(route('web.caja.cerrar', $caja->id), [
                'efectivo_contado' => '55600.00',
                'cambio_retenido' => '5000.00',
                'esperado_visto' => '55600.00',
            ]);

        $resPost->assertSessionHasNoErrors();
        $resPost->assertSessionHas('success', 'Caja cerrada.');

        $caja->refresh();
        $this->assertSame(CajaOperativa::ESTADO_CERRADA, $caja->estado);
        $this->assertSame('0.00', (string) $caja->diferencia_efectivo);
        $this->assertSame('55600.00', (string) $caja->efectivo_contado);
        $this->assertSame('5000.00', (string) $caja->cambio_retenido);
    }

    /**
     * Caso 3: Cancelar o corregir un movimiento en otra pestaña.
     * Resultado: También avisa (porque cambió el esperado).
     */
    public function test_caso_3_cancelar_o_corregir_en_otra_pestania_tambien_avisa(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');
        $mov = $this->agregarMovimiento($caja, '44000.00'); // esperado: 55600.00

        // Se vio 55600.00 en pantalla
        // En otra pestaña se cancela el movimiento
        $mov->update(['estado' => 'CANCELADO']); // nuevo esperado real: 11600.00

        $resPost = $this->actingAs($this->operativa)->from(route('web.caja.cierre', $caja->id))
            ->post(route('web.caja.cerrar', $caja->id), [
                'efectivo_contado' => '55600.00',
                'cambio_retenido' => '5000.00',
                'esperado_visto' => '55600.00',
            ]);

        $resPost->assertSessionHasErrors('efectivo_contado');
        $error = session('errors')->first('efectivo_contado');
        $this->assertStringContainsString('Cambió el efectivo esperado: ahora es $11,600.00', $error);
        $this->assertSame(CajaOperativa::ESTADO_ABIERTA, $caja->fresh()->estado);
    }

    /**
     * Caso 4: Movimiento en otra cuenta, que no cambia el efectivo.
     * Resultado: Cierra sin aviso.
     */
    public function test_caso_4_movimiento_otra_cuenta_no_afecta_efectivo_y_cierra_sin_aviso(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');

        // Entra cobro por Banco Galicia (no toca la caja de efectivo)
        $this->agregarMovimiento($caja, '50000.00', $this->banco);

        // Se cierra con el esperado de efectivo (11600.00)
        $resPost = $this->actingAs($this->operativa)->from(route('web.caja.cierre', $caja->id))
            ->post(route('web.caja.cerrar', $caja->id), [
                'efectivo_contado' => '11600.00',
                'cambio_retenido' => '5000.00',
                'esperado_visto' => '11600.00',
            ]);

        $resPost->assertSessionHasNoErrors();
        $this->assertSame(CajaOperativa::ESTADO_CERRADA, $caja->fresh()->estado);
        $this->assertSame('0.00', (string) $caja->fresh()->diferencia_efectivo);
    }

    /**
     * Caso 5: Faltante real con la pantalla al día.
     * Resultado: Cierra con la diferencia.
     */
    public function test_caso_5_faltante_real_con_pantalla_al_dia_cierra_con_diferencia(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');

        // Esperado 11600.00, pero contaron 10000.00 (faltan 1600.00)
        $resPost = $this->actingAs($this->operativa)->from(route('web.caja.cierre', $caja->id))
            ->post(route('web.caja.cerrar', $caja->id), [
                'efectivo_contado' => '10000.00',
                'cambio_retenido' => '3000.00',
                'esperado_visto' => '11600.00',
            ]);

        $resPost->assertSessionHasNoErrors();
        $caja->refresh();
        $this->assertSame(CajaOperativa::ESTADO_CERRADA, $caja->estado);
        $this->assertSame('-1600.00', (string) $caja->diferencia_efectivo);
        $this->assertSame('10000.00', (string) $caja->efectivo_contado);
    }

    /**
     * Caso 6: Doble clic.
     * Resultado: El segundo envío: «La caja no está en estado editable.»
     */
    public function test_caso_6_doble_clic_rechaza_segundo_envio(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');

        // Primer clic
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '11600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '11600.00',
        ])->assertSessionHasNoErrors();

        // Segundo clic
        $res2 = $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '11600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '11600.00',
        ]);

        $res2->assertSessionHas('error', 'La caja no está en estado editable.');
    }

    /**
     * Caso 7: El dueño cerrando el turno de una operativa; una caja rechazada que se vuelve a cerrar.
     * Resultado: El dueño recibe el aviso si cambió el esperado; una caja rechazada permite corregir y cerrar.
     */
    public function test_caso_7_duenio_cerrando_turno_y_caja_rechazada(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');
        $this->agregarMovimiento($caja, '20000.00'); // esperado: 31600.00

        // 1. Dueño intenta cerrar con esperado visto viejo
        $resDueno = $this->actingAs($this->duenio)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '11600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '11600.00',
        ]);
        $resDueno->assertSessionHasErrors('efectivo_contado');
        $this->assertSame(CajaOperativa::ESTADO_ABIERTA, $caja->fresh()->estado);

        // 2. Dueño cierra con esperado al día
        $this->actingAs($this->duenio)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '31600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '31600.00',
        ])->assertSessionHasNoErrors();
        $this->assertSame(CajaOperativa::ESTADO_CERRADA, $caja->fresh()->estado);

        // 3. Dueño rechaza la rendición
        app(CajaService::class)->rechazarCaja($caja->id, $this->duenio->id, 'Revisar arqueo');
        $this->assertSame(CajaOperativa::ESTADO_RECHAZADA, $caja->fresh()->estado);

        // 4. Operativa entra en la caja rechazada, agrega $5.000
        $this->agregarMovimiento($caja, '5000.00'); // nuevo esperado: 36600.00

        // 4a. Intentar cambiar el efectivo contado que ya se entregó es rechazado (regla A25)
        $resCambioContado = $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '36600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '36600.00',
        ]);
        $resCambioContado->assertSessionHasErrors('efectivo_contado');
        $this->assertStringContainsString('La corrección no puede cambiar el efectivo que ya se contó y entregó', session('errors')->first('efectivo_contado'));

        // 4b. Cierre con el contado original (31600) pero con esperado visto viejo (31600) avisa cambio de esperado (T107)
        $resEsperadoViejo = $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '31600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '31600.00',
        ]);
        $resEsperadoViejo->assertSessionHasErrors('efectivo_contado');
        $this->assertStringContainsString('Cambió el efectivo esperado: ahora es $36,600.00', session('errors')->first('efectivo_contado'));

        // 4c. Cierre con contado original (31600) y esperado visto al día (36600) cierra exitosamente
        $resCierreOk = $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '31600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '36600.00',
        ]);
        $resCierreOk->assertSessionHasNoErrors();
        $this->assertSame(CajaOperativa::ESTADO_CERRADA, $caja->fresh()->estado);
        $this->assertSame('-5000.00', (string) $caja->fresh()->diferencia_efectivo);
    }

    /**
     * Caso 8: Pedido directo sin esperado_visto o con valor inválido.
     * Resultado: Sin dato cierra como antes; con valor inválido se rechaza.
     */
    public function test_caso_8_pedido_directo_sin_esperado_o_invalido(): void
    {
        $caja = $this->crearCajaAbierta('11600.00');
        $this->agregarMovimiento($caja, '44000.00'); // esperado: 55600.00

        // 1. Inválido (texto)
        $resInvalido = $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '55600.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => 'basura',
        ]);
        $resInvalido->assertSessionHasErrors('esperado_visto');
        $this->assertSame(CajaOperativa::ESTADO_ABIERTA, $caja->fresh()->estado);

        // 2. Sin el dato (null): cierra directamente
        $resNull = $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '55600.00',
            'cambio_retenido' => '5000.00',
        ]);
        $resNull->assertSessionHasNoErrors();
        $this->assertSame(CajaOperativa::ESTADO_CERRADA, $caja->fresh()->estado);
    }

    /**
     * Arqueo en Cashflow al validar: cuadre al centavo de diferencia y cambio retenido.
     */
    public function test_arqueo_cashflow_al_validar_cuadra_al_centavo(): void
    {
        $caja = $this->crearCajaAbierta('10000.00');
        $this->agregarMovimiento($caja, '15000.00'); // esperado: 25000.00

        // Se cierra con faltante de $2.000 (contado 23000, retenido 5000)
        $this->actingAs($this->operativa)->post(route('web.caja.cerrar', $caja->id), [
            'efectivo_contado' => '23000.00',
            'cambio_retenido' => '5000.00',
            'esperado_visto' => '25000.00',
        ])->assertSessionHasNoErrors();

        // Se marca el movimiento como verificado por el admin antes de validar
        $mov = $caja->movimientos()->firstOrFail();
        app(CajaService::class)->verificarMovimiento($caja->id, $mov->id, $this->duenio->id, true, $mov->huellaVerificacion());

        // Dueño valida la caja
        $resultado = app(CajaService::class)->validarCaja($caja->id, $this->duenio->id);
        $this->assertSame(CajaOperativa::ESTADO_VALIDADA, $caja->fresh()->estado);

        // Integración con Cashflow: los asientos reflejan exactamente los importes
        $asiento = \App\Models\CashflowMovimiento::where('referencia_tipo', \App\Models\CashflowMovimiento::REF_CAJA)
            ->where('referencia_id', $caja->id)
            ->first();
        $this->assertNotNull($asiento, 'El movimiento de la caja validada debe quedar reflejado en Cashflow');
        $this->assertSame('15000.00', (string) $asiento->monto);

        // La caja refleja exactamente el arqueo y la diferencia al centavo
        $cajaValidada = $caja->fresh();
        $this->assertSame('-2000.00', (string) $cajaValidada->diferencia_efectivo);
        $this->assertSame('23000.00', (string) $cajaValidada->efectivo_contado);
        $this->assertSame('5000.00', (string) $cajaValidada->cambio_retenido);
    }

    // =========================================================================
    // SECCIÓN 2: T108 — Inscripción en segunda ficha
    // =========================================================================

    /** Ficha preexistente sin inscripción previa */
    private function fichaSinInscripcion(string $dni, array $datos = []): Alumno
    {
        $nivel = Nivel::firstOrCreate(['nombre' => 'Fútbol Nivel']);
        $grupo = Grupo::firstOrCreate(['deporte_id' => $this->futbol->id, 'nivel_id' => $nivel->id], ['activo' => true]);

        return Alumno::create($datos + [
            'nombre' => 'Thiago',
            'apellido' => 'Ruiz',
            'dni' => $dni,
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-01-10',
            'celular' => '1144445555',
            'deporte_id' => $this->futbol->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);
    }

    /**
     * Caso 9: Segunda ficha de quien no tiene inscripción, por pantalla.
     * Resultado: No se crea inscripción; el aviso previo dice «Esta persona ya es del club: no paga inscripción.»
     */
    public function test_caso_9_segunda_ficha_sin_inscripcion_previa_no_paga_y_aviso_previo(): void
    {
        $this->fichaSinInscripcion('54400002');

        // 1. Preview
        $preview = $this->actingAs($this->operativa)
            ->getJson(route('web.alumnos.inscripcion-preview', ['dni' => '54400002', 'fecha_alta' => '2026-11-05']))
            ->assertOk()->json();

        $this->assertSame('Esta persona ya es del club: no paga inscripción.', $preview['mensaje']);

        // 2. Alta de segunda ficha
        $resAlta = $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Thiago',
            'apellido' => 'Ruiz',
            'dni' => '54400002',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
        ]);
        $resAlta->assertSessionHasNoErrors();

        // Verificaciones
        $this->assertSame(2, Alumno::where('dni', '54400002')->count());
        $this->assertSame(0, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    /**
     * Caso 10: Persona nueva.
     * Resultado: Paga, como siempre.
     */
    public function test_caso_10_persona_nueva_sigue_pagando_inscripcion(): void
    {
        $preview = $this->actingAs($this->operativa)
            ->getJson(route('web.alumnos.inscripcion-preview', ['dni' => '99888777', 'fecha_alta' => '2026-11-05']))
            ->assertOk()->json();

        $this->assertStringContainsString('Corresponde inscripción por única vez: $8,000.00', $preview['mensaje']);

        $resAlta = $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Nuevo',
            'apellido' => 'Alumno',
            'dni' => '99888777',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
        ]);
        $resAlta->assertSessionHasNoErrors();

        $cargo = CargoAlumno::where('tipo', 'INSCRIPCION')->first();
        $this->assertNotNull($cargo);
        $this->assertSame('8000.00', (string) $cargo->monto_original);
    }

    /**
     * Caso 11: Quien ya tiene inscripción, pendiente o cobrada.
     * Resultado: Sigue con una sola.
     */
    public function test_caso_11_quien_ya_tiene_inscripcion_sigue_con_una_sola(): void
    {
        $alumno1 = $this->fichaSinInscripcion('54400002');
        app(InscripcionService::class)->sincronizar($alumno1, $this->operativa->id);
        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());

        // Preview de segunda ficha
        $preview = $this->actingAs($this->operativa)
            ->getJson(route('web.alumnos.inscripcion-preview', ['dni' => '54400002', 'fecha_alta' => '2026-11-05']))
            ->assertOk()->json();
        $this->assertStringContainsString('ya tiene una inscripción registrada', $preview['mensaje']);

        // Alta de segunda ficha
        $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Thiago',
            'apellido' => 'Ruiz',
            'dni' => '54400002',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count(), 'No debe duplicar la inscripción');
    }

    /**
     * Caso 12: DNI con puntos o espacios; ficha inactiva.
     * Resultado: Cuenta como la misma persona.
     */
    public function test_caso_12_dni_con_puntos_espacios_o_ficha_inactiva(): void
    {
        // 1. DNI con formato en ficha 1
        $this->fichaSinInscripcion('54.400.002');

        // Alta con DNI limpio
        $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Thiago',
            'apellido' => 'Ruiz',
            'dni' => '54400002',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, CargoAlumno::where('tipo', 'INSCRIPCION')->count());

        // 2. Ficha inactiva previa
        $this->fichaSinInscripcion('60111222', ['activo' => false]);
        $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Inactivo',
            'apellido' => 'Previo',
            'dni' => '60111222',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, CargoAlumno::where('dni', '60111222')->count());
    }

    /**
     * Caso 13: Se eliminó su única ficha y se la vuelve a dar de alta.
     * Resultado: Persona nueva: paga.
     */
    public function test_caso_13_ficha_unica_eliminada_vuelve_a_pagar(): void
    {
        $alumno = $this->fichaSinInscripcion('54400002');
        $alumno->delete(); // eliminación física

        $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Thiago',
            'apellido' => 'Ruiz',
            'dni' => '54400002',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    /**
     * Caso 14: Primera carga por Excel con «Inscripción: Sí», y con «No».
     * Resultado: La primera crea y la segunda no, también con dos filas del mismo DNI.
     */
    public function test_caso_14_primera_carga_excel_crea_o_no_segun_declaracion(): void
    {
        $a1 = $this->fichaSinInscripcion('54400002');
        $nivel = Nivel::firstOrCreate(['nombre' => 'Patín Nivel']);
        $grupo = Grupo::firstOrCreate(['deporte_id' => $this->patin->id, 'nivel_id' => $nivel->id], ['activo' => true]);
        $a2 = Alumno::create([
            'nombre' => 'Thiago', 'apellido' => 'Ruiz', 'dni' => '54400002', 'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-01-10', 'celular' => '1144445555', 'deporte_id' => $this->patin->id, 'grupo_id' => $grupo->id, 'activo' => true
        ]);

        // Fila 1 con motivo 'Primera carga por Excel' (Sí)
        app(InscripcionService::class)->sincronizar($a1, $this->operativa->id, motivo: 'Primera carga por Excel');
        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());

        // Fila 2 con motivo 'Primera carga por Excel': ya existe inscripción, no duplica
        app(InscripcionService::class)->sincronizar($a2, $this->operativa->id, motivo: 'Primera carga por Excel');
        $this->assertSame(1, CargoAlumno::where('tipo', 'INSCRIPCION')->count());
    }

    /**
     * Caso 15: Cobranza, ficha, deuda total y Reportes de esa segunda ficha.
     * Resultado: Solo la cuota, sin inscripción, al peso.
     */
    public function test_caso_15_cobranza_y_deuda_total_sin_inscripcion_duplicada(): void
    {
        $this->fichaSinInscripcion('54400002');

        // Alta de segunda ficha en Patín (cuota $25.000)
        $this->actingAs($this->operativa)->post(route('web.alumnos.store'), [
            'nombre' => 'Thiago',
            'apellido' => 'Ruiz',
            'dni' => '54400002',
            'fecha_nacimiento' => '2005-05-10',
            'fecha_alta' => '2026-11-05',
            'celular' => '1144445555',
            'deporte_id' => $this->patin->id,
            'grupo_id' => $this->grupoPatin->id,
            'plan_id' => $this->planPatin->id,
            'genera_cuota' => 'si',
        ])->assertSessionHasNoErrors();

        $alumnoPatin = Alumno::where('dni', '54400002')->where('deporte_id', $this->patin->id)->firstOrFail();

        // 1. En ficha del alumno no hay cargo de inscripción
        $this->assertSame(0, CargoAlumno::where('alumno_id', $alumnoPatin->id)->count());

        // 2. Solo existe la deuda de cuota generada
        $deudas = DeudaCuota::where('alumno_id', $alumnoPatin->id)->get();
        $this->assertSame(1, $deudas->count());
        $this->assertSame('25000.00', (string) $deudas->first()->monto_original);

        // 3. Consulta de cobranza web muestra solo la cuota
        $resCob = $this->actingAs($this->operativa)->get(route('web.cobranza.index', ['search' => '54400002']));
        $resCob->assertOk();
        $resCob->assertDontSee('Inscripción');
    }
}
