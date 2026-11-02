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
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Control de Claude sobre T113, 01/11/2026: lo que las pruebas de Gemini no cubren.
 * Vive en evidencia a propósito: no cambia el contador.
 * Uso: DB_DATABASE=wings_testing_claude php artisan test docs/06-pruebas/PRU-04/evidencia-t113/ControlT113ClaudeTest.php
 */
class ControlT113ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $profesora;
    private Profesor $porHora;
    private Profesor $otra;
    private Profesor $porComision;
    private Grupo $grupo;
    private Grupo $grupoComision;
    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-18 12:00:00');
        $this->duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $hora = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $comision = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Inicial']);
        $this->grupo = Grupo::create(['deporte_id' => $hora->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->grupoComision = Grupo::create(['deporte_id' => $comision->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $datos = fn (string $ap, string $dni, int $dep) => ['deporte_id' => $dep, 'nombre' => 'Prueba', 'apellido' => $ap, 'dni' => $dni, 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'x', 'localidad' => 'x', 'activo' => true];
        $this->porHora = Profesor::create($datos('Hora', '30000001', $hora->id) + ['valor_hora' => 1000]);
        $this->otra = Profesor::create($datos('Otra', '30000002', $hora->id) + ['valor_hora' => 2000]);
        $this->porComision = Profesor::create($datos('Comision', '30000003', $comision->id) + ['porcentaje_comision' => 35]);
        $this->profesora = User::factory()->create(['rol' => 'PROFESOR', 'profesor_id' => $this->porHora->id, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Sofia', 'apellido' => 'Gomez', 'dni' => '40123456', 'celular' => '1122334455', 'deporte_id' => $hora->id, 'grupo_id' => $this->grupo->id,
            'fecha_alta' => '2026-09-01', 'fecha_nacimiento' => '2010-01-01', 'nombre_tutor' => 'Tutor', 'telefono_tutor' => '1122334455', 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 1, 'precio_mensual' => 5000, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function clase(string $fecha, bool $validada, ?Profesor $p = null, ?Grupo $g = null): Clase
    {
        $c = Clase::create(['grupo_id' => ($g ?? $this->grupo)->id, 'fecha' => $fecha, 'hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00', 'validada_para_liquidacion' => $validada]);
        $c->profesores()->attach(($p ?? $this->porHora)->id);

        return $c;
    }

    private function desvalidar(Clase $c)
    {
        return $this->actingAs($this->duenio)->patch(route('web.clases.toggle-validada', $c->id), ['accion' => 'desvalidar']);
    }

    /** La salida que el aviso promete: eliminar la abierta, desvalidar y generar de nuevo. Con la plata. */
    public function test_el_camino_del_aviso_funciona_y_la_clase_deja_de_pagarse(): void
    {
        $this->clase('2026-10-05', true);
        $mal = $this->clase('2026-10-10', true);
        $svc = app(LiquidacionService::class);
        $liq = $svc->generarLiquidacionMensual($this->porHora->id, 10, 2026);
        $this->assertEquals(2000, (float) $liq->fresh()->total_calculado);

        $this->desvalidar($mal)->assertSessionHas('error');
        $this->assertTrue((bool) $mal->fresh()->validada_para_liquidacion);

        $this->actingAs($this->duenio)->delete(route('web.liquidaciones.eliminar', $liq->id))->assertSessionHas('success');
        $this->desvalidar($mal)->assertSessionHas('success', 'Clase desmarcada de validación.');

        $nueva = $svc->generarLiquidacionMensual($this->porHora->id, 10, 2026);
        $this->assertEquals(1000, (float) $nueva->fresh()->total_calculado, 'Se paga solo la que quedó validada');
    }

    /** Una clase compartida: la de una profesora está abierta y la de la otra no tiene liquidación. */
    public function test_clase_compartida_con_una_liquidacion_abierta_no_se_desvalida(): void
    {
        $c = $this->clase('2026-10-10', true);
        $c->profesores()->attach($this->otra->id);
        app(LiquidacionService::class)->generarLiquidacionMensual($this->otra->id, 10, 2026);

        $this->desvalidar($c)->assertSessionHas('error');
        $this->assertTrue((bool) $c->fresh()->validada_para_liquidacion);
        $this->assertFalse($c->fresh()->puedeDesvalidar());
    }

    /** Una liquidación por comisión no guarda las clases: abierta no bloquea, cerrada sí. */
    public function test_liquidacion_por_comision(): void
    {
        $c = $this->clase('2026-09-10', true, $this->porComision, $this->grupoComision);
        $svc = app(LiquidacionService::class);
        $liq = $svc->generarLiquidacionMensual($this->porComision->id, 9, 2026);
        $this->assertTrue($c->fresh()->puedeDesvalidar(), 'Abierta por comisión');

        $svc->cerrarLiquidacion($liq->id);
        $this->assertFalse($c->fresh()->puedeDesvalidar(), 'Cerrada por comisión');
        $this->desvalidar($c)->assertSessionHas('error', 'No se puede modificar la validación: la clase integra una liquidación cerrada.');
        $this->assertTrue((bool) $c->fresh()->validada_para_liquidacion);
    }

    /** Validar en una clase con liquidación cerrada sigue rechazado, y con acción inventada no cambia nada. */
    public function test_validar_con_cerrada_y_accion_inventada(): void
    {
        $validada = $this->clase('2026-09-05', true);
        $sin = $this->clase('2026-09-10', false);
        Asistencia::create(['clase_id' => $sin->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
        $svc = app(LiquidacionService::class);
        $svc->cerrarLiquidacion($svc->generarLiquidacionMensual($this->porHora->id, 9, 2026)->id);

        $this->actingAs($this->duenio)->patch(route('web.clases.toggle-validada', $sin->id), ['accion' => 'validar'])
            ->assertSessionHas('error', 'No se puede modificar la validación: la clase integra una liquidación cerrada.');
        $this->assertFalse((bool) $sin->fresh()->validada_para_liquidacion);

        $this->actingAs($this->duenio)->patch(route('web.clases.toggle-validada', $validada->id), ['accion' => 'borrar'])
            ->assertSessionHas('error', 'Acción de validación no reconocida.');
        $this->assertTrue((bool) $validada->fresh()->validada_para_liquidacion);
    }

    /** Lo que ve cada uno: la pantalla no ofrece lo que el servidor rechaza, y sin sesión no entra. */
    public function test_lo_que_ofrece_la_ficha(): void
    {
        $libre = $this->clase('2026-10-10', true);
        $this->actingAs($this->duenio)->get(route('web.clases.show', $libre->id))->assertSee('Desvalidar')->assertDontSee('name="accion" value="validar"', false)
            ->assertDontSee(route('web.clases.edit', $libre->id), false);
        $sinValidar = $this->clase('2026-10-11', false);
        $this->actingAs($this->duenio)->get(route('web.clases.show', $sinValidar->id))->assertDontSee('Desvalidar')->assertSee(route('web.clases.edit', $sinValidar->id), false);

        $this->actingAs($this->profesora)->get(route('web.clases.show', $libre->id))->assertOk()->assertDontSee('Desvalidar');
        $this->actingAs($this->profesora)->patch(route('web.clases.toggle-validada', $libre->id), ['accion' => 'desvalidar'])->assertStatus(403);

        auth()->logout();
        $this->patch(route('web.clases.toggle-validada', $libre->id), ['accion' => 'desvalidar'])->assertRedirect(route('login'));
        $this->assertTrue((bool) $libre->fresh()->validada_para_liquidacion);
    }

    /** Una clase cancelada que estaba validada: desvalidarla no da error, y sigue sin pagarse. */
    public function test_clase_cancelada_validada(): void
    {
        $c = $this->clase('2026-10-10', true);
        $c->update(['cancelada' => true, 'motivo_cancelacion' => 'Lluvia']);

        $this->desvalidar($c)->assertSessionHas('success');
        $this->assertFalse((bool) $c->fresh()->validada_para_liquidacion);
        $this->assertTrue((bool) $c->fresh()->cancelada);
    }

    /** Lo que Gemini dejó sin cerrar: un envío sin acción todavía da vuelta el dato. La pantalla siempre la manda. */
    public function test_retrato_envio_sin_accion_todavia_da_vuelta(): void
    {
        $c = $this->clase('2026-10-10', true);
        $this->actingAs($this->duenio)->patch(route('web.clases.toggle-validada', $c->id))->assertSessionHas('success');
        $this->assertFalse((bool) $c->fresh()->validada_para_liquidacion);
    }
}
