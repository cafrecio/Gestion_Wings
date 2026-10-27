<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\LiquidacionPagoService;
use App\Services\LiquidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T90 / DU-30 / CLA-013: una clase que integra una liquidación cerrada o pagada no se
 * cancela, ni desde la pantalla ni forzando el envío, para ningún rol. Hallado en la
 * prueba técnica del día 23 (PRU-04): la operativa cancelaba la clase y la liquidación
 * quedaba pagando una clase cancelada.
 */
class CancelarClaseLiquidadaT90Test extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE = 'No se puede cancelar: la clase está en una liquidación cerrada o pagada.';

    private User $admin;
    private User $operativo;
    private Profesor $profesor;
    private Grupo $grupo;
    private LiquidacionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LiquidacionService::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $this->grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Avanzadas'])->id, 'activo' => true]);
        $this->profesor = Profesor::create([
            'deporte_id' => $deporte->id, 'nombre' => 'Mariela', 'apellido' => 'Ocampo', 'dni' => '24111222',
            'fecha_nacimiento' => '1980-05-10', 'direccion' => 'Calle 1', 'localidad' => 'Rosario',
            'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'valor_hora' => 18000, 'activo' => true,
        ]);
    }

    private function clase(string $fecha, string $hora = '17:00'): Clase
    {
        $clase = Clase::create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha, 'hora_inicio' => $hora,
            'hora_fin' => date('H:i', strtotime($hora) + 3600), 'validada_para_liquidacion' => true, 'cancelada' => false]);
        $clase->profesores()->attach($this->profesor->id);
        return $clase;
    }

    private function cancelar(User $quien, Clase $clase)
    {
        return $this->actingAs($quien)->patchJson(route('web.clases.toggle-cancelada', $clase->id), ['motivo_cancelacion' => 'Llovió']);
    }

    public function test_clase_de_una_liquidacion_cerrada_no_se_cancela_para_ningun_rol(): void
    {
        $clase = $this->clase('2026-08-10');
        $liq = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liq->id);

        foreach ([$this->operativo, $this->admin] as $quien) {
            $this->cancelar($quien, $clase)->assertStatus(422)->assertJson(['success' => false, 'message' => self::MENSAJE]);
        }
        // Sin pedir JSON (envío directo de formulario): mismo rechazo, con el aviso de error.
        $this->actingAs($this->admin)->patch(route('web.clases.toggle-cancelada', $clase->id), ['motivo_cancelacion' => 'Llovió'])
            ->assertSessionHas('error', self::MENSAJE);
        // Tampoco cancelando la serie desde esa clase.
        $this->actingAs($this->admin)->patchJson(route('web.clases.toggle-cancelada', $clase->id), ['motivo_cancelacion' => 'Llovió', 'cancelar_serie' => true])
            ->assertStatus(422);

        $clase->refresh();
        $this->assertFalse((bool) $clase->cancelada);
        $this->assertNull($clase->motivo_cancelacion);
        $this->assertSame(1, $liq->fresh()->detalles()->count());
    }

    public function test_clase_de_una_liquidacion_pagada_no_se_cancela(): void
    {
        $caja = TipoCaja::create(['nombre' => 'Caja Central', 'saldo_inicial' => 500000, 'activo' => true]);
        $sub = Subrubro::create(['nombre' => 'Sueldo - Ocampo, Mariela', 'rubro_id' => Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO'])->id,
            'permitido_para' => 'ADMIN', 'afecta_caja' => false, 'activo' => true]);
        $this->profesor->update(['subrubro_id' => $sub->id]);
        $clase = $this->clase('2026-08-10');
        $liq = $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->service->cerrarLiquidacion($liq->id);
        app(LiquidacionPagoService::class)->marcarComoPagada($liq->id, ['fecha_pago' => '2026-09-01', 'tipo_caja_id' => $caja->id,
            'subrubro_id' => $sub->id, 'admin_id' => $this->admin->id]);
        $this->assertTrue($liq->fresh()->estaPagada());

        $this->cancelar($this->admin, $clase)->assertStatus(422)->assertJson(['message' => self::MENSAJE]);
        $this->assertFalse((bool) $clase->fresh()->cancelada);
    }

    public function test_clase_del_mes_de_una_liquidacion_por_comision_cerrada_no_se_cancela(): void
    {
        $clase = $this->clase('2026-08-12');
        Liquidacion::create(['profesor_id' => $this->profesor->id, 'mes' => 8, 'anio' => 2026, 'tipo' => Liquidacion::TIPO_COMISION,
            'porcentaje_comision_aplicado' => 40, 'total_calculado' => 0, 'estado' => Liquidacion::ESTADO_CERRADA,
            'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE]);

        $this->cancelar($this->operativo, $clase)->assertStatus(422)->assertJson(['message' => self::MENSAJE]);
        $this->assertFalse((bool) $clase->fresh()->cancelada);
    }

    public function test_sin_liquidacion_cerrada_se_sigue_cancelando(): void
    {
        // Liquidación abierta: la clase se puede cancelar (al recalcular deja de contar).
        $abierta = $this->clase('2026-08-10');
        $this->service->generarLiquidacionMensual($this->profesor->id, 8, 2026);
        $this->cancelar($this->operativo, $abierta)->assertOk()->assertJson(['success' => true]);
        $this->assertTrue((bool) $abierta->fresh()->cancelada);

        // Una clase de otro mes, sin liquidación, tampoco cambia.
        $otra = $this->clase('2026-09-10');
        $this->cancelar($this->operativo, $otra)->assertOk();
        $this->assertSame('Llovió', $otra->fresh()->motivo_cancelacion);
    }
}
