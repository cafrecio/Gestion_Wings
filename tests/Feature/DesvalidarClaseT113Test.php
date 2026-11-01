<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesvalidarClaseT113Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private User $profesorUser;
    private Profesor $profesor;
    private Grupo $grupo;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-18 12:00:00'));

        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);

        $deporte = Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create([
            'deporte_id' => $deporte->id,
            'nivel_id' => $nivel->id,
            'activo' => true,
        ]);

        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id,
            'nombre' => 'Lucia',
            'apellido' => 'Mendez',
            'dni' => '30111222',
            'fecha_nacimiento' => '1985-05-15',
            'direccion' => 'Calle Falsa 123',
            'localidad' => 'Prueba',
            'valor_hora' => 1000.00,
            'activo' => true,
        ]);

        $this->profesorUser = User::factory()->create([
            'rol' => 'PROFESOR',
            'profesor_id' => $this->profesor->id,
            'activo' => true,
        ]);

        $this->alumno = Alumno::create([
            'nombre' => 'Sofia',
            'apellido' => 'Gomez',
            'dni' => '40123456',
            'celular' => '1122334455',
            'deporte_id' => $deporte->id,
            'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-09-01',
            'fecha_nacimiento' => '2010-01-01',
            'nombre_tutor' => 'Tutor',
            'telefono_tutor' => '1122334455',
            'activo' => true,
        ]);

        $plan = GrupoPlan::create([
            'grupo_id' => $this->grupo->id,
            'clases_por_semana' => 1,
            'precio_mensual' => 5000.00,
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $this->alumno->id,
            'plan_id' => $plan->id,
            'fecha_desde' => '2026-09-01',
        ]);
    }

    private function crearClase(string $fecha, bool $validada = false): Clase
    {
        $clase = Clase::create([
            'grupo_id' => $this->grupo->id,
            'fecha' => $fecha,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'validada_para_liquidacion' => $validada,
        ]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    /**
     * Caso 1: Dueño, clase validada sin liquidación.
     * Ve Desvalidar. Al tocarlo: «Clase desmarcada de validación.», vuelve a ofrecer
     * Validar y desaparece la marca «Validada para liquidación».
     */
    public function test_caso_1_duenio_desvalida_clase_sin_liquidacion(): void
    {
        $clase = $this->crearClase('2026-10-10', true);

        // Vista inicial: ve "Desvalidar" y ve la marca "Validada para liquidación"
        $respHtml = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respHtml->assertOk();
        $respHtml->assertSee('Desvalidar');
        $respHtml->assertDontSee('Validar');
        $respHtml->assertSee('Validada para liquidación');

        // Toca Desvalidar
        $respPost = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respPost->assertRedirect();
        $respPost->assertSessionHas('success', 'Clase desmarcada de validación.');

        $this->assertFalse($clase->fresh()->validada_para_liquidacion);

        // Vista posterior: vuelve a ofrecer Validar y desaparece la marca
        $respHtmlDespues = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respHtmlDespues->assertOk();
        $respHtmlDespues->assertSee('Validar');
        $respHtmlDespues->assertDontSee('Desvalidar');
        $respHtmlDespues->assertDontSee('Validada para liquidación');
    }

    /**
     * Caso 2: Clase futura validada por error, como la Hockey del 19/12 de la tanda 5.
     * Se desvalida igual. Deja de contar para una liquidación generada después.
     */
    public function test_caso_2_clase_futura_validada_por_error_se_desvalida_y_no_cuenta_en_liquidacion(): void
    {
        $claseFutura = $this->crearClase('2026-12-19', true);

        // Previsualización de liquidación de diciembre con la clase validada: cuenta
        $liqService = app(LiquidacionService::class);
        $prevConValidada = $liqService->previsualizarLiquidacion($this->profesor->id, 12, 2026);
        $this->assertSame(1000.0, (float) $prevConValidada['total_estimado']);
        $this->assertCount(1, $prevConValidada['detalles']);

        // Se desvalida
        $resp = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $claseFutura->id), [
            'accion' => 'desvalidar',
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('success', 'Clase desmarcada de validación.');
        $this->assertFalse($claseFutura->fresh()->validada_para_liquidacion);

        // Deja de contar para una liquidación generada después
        $prevSinValidada = $liqService->previsualizarLiquidacion($this->profesor->id, 12, 2026);
        $this->assertSame(0.0, (float) $prevSinValidada['total_estimado']);
        $this->assertCount(0, $prevSinValidada['detalles']);
    }

    /**
     * Caso 3: La clase desvalidada no tiene asistencias.
     * Vuelve a contar en «Sin asistencia» si ya pasó, y vuelve a poder Eliminarse si cumple lo demás.
     */
    public function test_caso_3_clase_desvalidada_sin_asistencia_cuenta_en_sin_asistencia_y_es_eliminable(): void
    {
        // Clase pasada (10 de octubre, reloj en 18 de octubre)
        $clase = $this->crearClase('2026-10-10', true);

        // Mientras está validada: esEliminable es falso y no aparece en sin_asistencia
        $this->assertFalse($clase->esEliminable());
        $respFiltroAntes = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'sin_presentes']));
        $this->assertFalse($respFiltroAntes->viewData('clasesFiltradas')->pluck('id')->contains($clase->id));

        // Desvalidar
        $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);

        $this->assertFalse($clase->fresh()->validada_para_liquidacion);

        // Ahora es eliminable
        $this->assertTrue($clase->fresh()->esEliminable());

        // El botón Eliminar aparece en la vista
        $respView = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respView->assertSee('Eliminar');

        // Vuelve a contar en sin_asistencia (filtro 'finalizada' en clases/index)
        $respFiltroDespues = $this->actingAs($this->admin)->get(route('web.clases.index', ['estado' => 'finalizada']));
        $this->assertTrue($respFiltroDespues->viewData('clasesFiltradas')->pluck('id')->contains($clase->id));
    }

    /**
     * Caso 4: Clase validada que después tuvo presentes.
     * Se puede desvalidar. Se sigue pagando por los presentes: no cambia el importe.
     */
    public function test_caso_4_clase_validada_con_presentes_se_desvalida_y_sigue_pagandose(): void
    {
        $clase = $this->crearClase('2026-10-10', true);

        // Se registra una asistencia presente posterior
        Asistencia::create([
            'clase_id' => $clase->id,
            'alumno_id' => $this->alumno->id,
            'presente' => true,
        ]);

        // La vista ofrece Desvalidar aunque cantPresentes > 0
        $respHtml = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respHtml->assertSee('Desvalidar');

        // Desvalidar
        $resp = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('success', 'Clase desmarcada de validación.');
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);

        // Se sigue pagando por los presentes: no cambia el importe
        $liqService = app(LiquidacionService::class);
        $prev = $liqService->previsualizarLiquidacion($this->profesor->id, 10, 2026);
        $this->assertSame(1000.0, (float) $prev['total_estimado']);
        $this->assertCount(1, $prev['detalles']);
    }

    /**
     * Caso 5: Clase en una liquidación cerrada o pagada.
     * No ofrece Desvalidar. Forzado: el rechazo que ya existe. Nada cambia.
     */
    public function test_caso_5_clase_en_liquidacion_cerrada_o_pagada_no_ofrece_desvalidar_y_rechaza(): void
    {
        $clase = $this->crearClase('2026-09-15', true);

        $liqService = app(LiquidacionService::class);
        $liq = $liqService->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $liqService->cerrarLiquidacion($liq->id);

        $this->assertTrue($clase->fresh()->integraLiquidacionCerrada());

        // En la vista: no ofrece Desvalidar ni Validar
        $respHtml = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respHtml->assertDontSee('Desvalidar');
        $respHtml->assertDontSee('Validar');

        // Intento forzado vía POST/PATCH
        $respForzado = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respForzado->assertSessionHas('error', 'No se puede modificar la validación: la clase integra una liquidación cerrada.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);

        // Caso pagada
        $liq->update(['estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA]);
        $respPagada = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respPagada->assertSessionHas('error', 'No se puede modificar la validación: la clase integra una liquidación cerrada.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Caso 6: Clase en una liquidación por hora abierta.
     * No ofrece Desvalidar. Forzado: «No se puede desvalidar: la clase está en una liquidación abierta.
     * Eliminá esa liquidación, desvalidá y generala de nuevo.» Nada cambia.
     */
    public function test_caso_6_clase_en_liquidacion_hora_abierta_no_ofrece_desvalidar_y_rechaza(): void
    {
        $clase = $this->crearClase('2026-10-10', true);

        // Se genera la liquidación por hora para octubre, dejándola ABIERTA
        $liqService = app(LiquidacionService::class);
        $liq = $liqService->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->assertSame(Liquidacion::ESTADO_ABIERTA, $liq->estado);
        $this->assertTrue($clase->fresh()->integraLiquidacionHoraAbierta());

        // En la vista: no ofrece Desvalidar
        $respHtml = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respHtml->assertDontSee('Desvalidar');

        // Intento forzado directo
        $respForzado = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respForzado->assertSessionHas('error', 'No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Caso 7: Clase de una liquidación cancelada.
     * Se puede desvalidar.
     */
    public function test_caso_7_clase_en_liquidacion_cancelada_se_puede_desvalidar(): void
    {
        $clase = $this->crearClase('2026-09-10', true);

        $liqService = app(LiquidacionService::class);
        $liq = $liqService->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $liqService->cerrarLiquidacion($liq->id);
        $liqService->cancelarLiquidacion($liq->id, 'Cancelada por prueba', $this->admin->id);

        $this->assertSame(Liquidacion::ESTADO_CANCELADA, $liq->fresh()->estado);
        $this->assertFalse($clase->fresh()->integraLiquidacionCerrada());
        $this->assertFalse($clase->fresh()->integraLiquidacionHoraAbierta());

        // La vista ofrece Desvalidar
        $respHtml = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respHtml->assertSee('Desvalidar');

        // Desvalidar funciona
        $resp = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('success', 'Clase desmarcada de validación.');
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Caso 8: Operativa y profesora.
     * No ven Desvalidar. Forzado: 403 y nada cambia. Sin sesión: al ingreso.
     */
    public function test_caso_8_operativa_y_profesora_no_ven_desvalidar_y_reciben_403(): void
    {
        $clase = $this->crearClase('2026-10-10', true);

        // Operativa no ve Desvalidar en la vista
        $respOpView = $this->actingAs($this->operativo)->get(route('web.clases.show', $clase->id));
        $respOpView->assertOk();
        $respOpView->assertDontSee('Desvalidar');
        $respOpView->assertDontSee('Validar');

        // Operativa forzado recibe 403
        $respOpPost = $this->actingAs($this->operativo)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respOpPost->assertForbidden();
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);

        // Profesora no ve Desvalidar en la vista
        $respProfView = $this->actingAs($this->profesorUser)->get(route('web.clases.show', $clase->id));
        $respProfView->assertOk();
        $respProfView->assertDontSee('Desvalidar');
        $respProfView->assertDontSee('Validar');

        // Profesora forzado recibe 403
        $respProfPost = $this->actingAs($this->profesorUser)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respProfPost->assertForbidden();
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);

        // Sin sesión redirige a login
        auth()->logout();
        $respAnon = $this->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $respAnon->assertRedirect(route('login'));
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Caso 9: Doble clic y pantalla vieja.
     * El botón manda lo que quiere hacer, no «dar vuelta». Dos pestañas abiertas: una desvalida y la
     * otra toca Desvalidar con la pantalla vieja; la clase no vuelve a quedar validada.
     * Lo mismo al revés con Validar. Texto: «La clase cambió mientras la mirabas. Recargá la pantalla.»
     */
    public function test_caso_9_doble_clic_y_pantalla_vieja_rechazan_con_mensaje(): void
    {
        $clase = $this->crearClase('2026-10-10', true);

        // Pestaña 1 desvalida
        $resp1 = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $resp1->assertSessionHas('success', 'Clase desmarcada de validación.');
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);

        // Pestaña 2 (o doble clic) envía nuevamente 'desvalidar' sobre la pantalla vieja
        $resp2 = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);
        $resp2->assertSessionHas('error', 'La clase cambió mientras la mirabas. Recargá la pantalla.');
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);

        // Lo mismo al revés con Validar:
        // Pestaña 1 valida
        $resp3 = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'validar',
        ]);
        $resp3->assertSessionHas('success', 'Clase validada para liquidación.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);

        // Pestaña 2 envía 'validar' nuevamente sobre la pantalla vieja
        $resp4 = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'validar',
        ]);
        $resp4->assertSessionHas('error', 'La clase cambió mientras la mirabas. Recargá la pantalla.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Caso 10: Validar sigue igual en todos los casos que hoy lo ofrecen.
     * Solo admin, solo si no validada y cantPresentes === 0.
     */
    public function test_caso_10_validar_sigue_igual_en_casos_permitidos(): void
    {
        $clase = $this->crearClase('2026-10-10', false);

        // Admin ve Validar y Editar
        $respView = $this->actingAs($this->admin)->get(route('web.clases.show', $clase->id));
        $respView->assertOk();
        $respView->assertSee('Validar');
        $respView->assertSee('Editar');
        $respView->assertDontSee('Desvalidar');

        // Valida con éxito
        $respValidar = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'validar',
        ]);
        $respValidar->assertRedirect();
        $respValidar->assertSessionHas('success', 'Clase validada para liquidación.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Mutación 1: Sacar el control de liquidación abierta.
     * Debe fallar si la regla no bloquea la desvalidación de una clase en liquidación abierta.
     */
    public function test_mutacion_1_control_abierta_bloquea_desvalidacion(): void
    {
        $clase = $this->crearClase('2026-10-10', true);
        $liqService = app(LiquidacionService::class);
        $liqService->generarLiquidacionMensual($this->profesor->id, 10, 2026);

        $resp = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);

        $resp->assertSessionHas('error', 'No se puede desvalidar: la clase está en una liquidación abierta. Eliminá esa liquidación, desvalidá y generala de nuevo.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Mutación 2: Sacar el control de liquidación cerrada.
     * Debe fallar si la regla no bloquea la modificación en liquidación cerrada.
     */
    public function test_mutacion_2_control_cerrada_bloquea_modificacion(): void
    {
        $clase = $this->crearClase('2026-09-10', true);
        $liqService = app(LiquidacionService::class);
        $liq = $liqService->generarLiquidacionMensual($this->profesor->id, 9, 2026);
        $liqService->cerrarLiquidacion($liq->id);

        $resp = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);

        $resp->assertSessionHas('error', 'No se puede modificar la validación: la clase integra una liquidación cerrada.');
        $this->assertTrue($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Mutación 3: Volver a «dar vuelta».
     * Debe fallar si la lógica simplemente invierte el booleano en vez de verificar la intención.
     */
    public function test_mutacion_3_no_se_debe_dar_vuelta_a_ciegas(): void
    {
        $clase = $this->crearClase('2026-10-10', false);

        // Envío de 'desvalidar' cuando ya está desvalidada no debe convertirla en validada
        $resp = $this->actingAs($this->admin)->patch(route('web.clases.toggle-validada', $clase->id), [
            'accion' => 'desvalidar',
        ]);

        $resp->assertSessionHas('error', 'La clase cambió mientras la mirabas. Recargá la pantalla.');
        $this->assertFalse($clase->fresh()->validada_para_liquidacion);
    }

    /**
     * Mutación 4: Mostrar el botón a la operativa.
     * La operativa nunca debe ver el formulario o botón de Validar/Desvalidar en el HTML.
     */
    public function test_mutacion_4_operativa_nunca_recibe_boton_en_html(): void
    {
        $claseValidada = $this->crearClase('2026-10-10', true);
        $claseNoValidada = $this->crearClase('2026-10-11', false);

        $resp1 = $this->actingAs($this->operativo)->get(route('web.clases.show', $claseValidada->id));
        $resp1->assertDontSee('name="accion"', false);
        $resp1->assertDontSee('Desvalidar');

        $resp2 = $this->actingAs($this->operativo)->get(route('web.clases.show', $claseNoValidada->id));
        $resp2->assertDontSee('name="accion"', false);
        $resp2->assertDontSee('Validar');
    }
}
