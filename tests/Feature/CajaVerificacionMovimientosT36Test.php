<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, CashflowMovimiento, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CajaVerificacionMovimientosT36Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private CajaOperativa $caja;
    private MovimientoOperativo $primero;
    private MovimientoOperativo $segundo;
    private CajaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $medio = TipoCaja::create(['nombre' => 'Efectivo T36', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingresos T36', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id, 'nombre' => 'Venta T36',
            'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true,
        ]);
        $this->caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativa->id, 'estado' => 'CERRADA',
            'apertura_at' => now()->subHours(2), 'cierre_at' => now(),
            'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 100,
            'efectivo_esperado' => 200, 'efectivo_contado' => 200,
            'cambio_retenido' => 100, 'efectivo_retirado' => 100, 'diferencia_efectivo' => 0,
        ]);
        $datos = [
            'caja_operativa_id' => $this->caja->id, 'fecha' => today(),
            'tipo_caja_id' => $medio->id, 'subrubro_id' => $subrubro->id,
            'usuario_id' => $this->operativa->id, 'estado' => 'ACTIVO',
        ];
        $this->primero = MovimientoOperativo::create($datos + ['monto' => 30]);
        $this->segundo = MovimientoOperativo::create($datos + ['monto' => 70]);
        $this->service = app(CajaService::class);
    }

    private function marcar(MovimientoOperativo $movimiento, bool $valor = true): void
    {
        $this->service->verificarMovimiento(
            $this->caja->id, $movimiento->id, $this->admin->id, $valor, $movimiento->fresh()->huellaVerificacion()
        );
    }

    private function url(MovimientoOperativo $movimiento, ?int $cajaId = null): string
    {
        return route('web.cajas.movimientos.verificar', [$cajaId ?? $this->caja->id, $movimiento->id]);
    }

    public function test_tildar_destildar_y_recargar_conserva_el_estado_guardado(): void
    {
        $this->actingAs($this->admin)->patchJson($this->url($this->primero), [
            'verificado' => true, 'huella' => $this->primero->huellaVerificacion(),
        ])->assertOk()->assertJson(['verificado' => true]);
        $this->assertTrue($this->primero->fresh()->estaVerificado());
        $this->assertSame($this->admin->id, $this->primero->fresh()->verificado_por);
        $this->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('1 de 2 verificados');
        $this->patchJson($this->url($this->primero), [
            'verificado' => false, 'huella' => $this->primero->fresh()->huellaVerificacion(),
        ])->assertOk()->assertJson(['verificado' => false]);
        $this->assertNull($this->primero->fresh()->verificado_at);
        $this->get(route('web.caja.detalle', $this->caja->id))->assertOk()->assertSee('0 de 2 verificados');
    }

    public function test_validar_con_todo_tildado_conserva_marcas_y_refleja_una_sola_vez(): void
    {
        $this->marcar($this->primero);
        $this->marcar($this->segundo);
        $this->actingAs($this->admin)->post(route('web.cajas.validar', $this->caja->id))
            ->assertRedirect(route('web.caja.index'))->assertSessionHasNoErrors();
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
        $this->assertTrue($this->primero->fresh()->estaVerificado());
        $this->assertTrue($this->segundo->fresh()->estaVerificado());
        $this->assertDatabaseCount('cashflow_movimientos', 2);
        $this->service->validarCaja($this->caja->id, $this->admin->id);
        $this->assertDatabaseCount('cashflow_movimientos', 2);
    }

    public function test_validar_con_uno_sin_tildar_avisa_y_no_escribe(): void
    {
        $this->marcar($this->primero);
        $detalle = route('web.caja.detalle', $this->caja->id);
        $this->actingAs($this->admin)->from($detalle)->post(route('web.cajas.validar', $this->caja->id))
            ->assertRedirect($detalle)->assertSessionHasErrors(['movimientos' => 'Falta 1 movimiento por verificar antes de validar la caja.']);
        $this->assertSame('CERRADA', $this->caja->fresh()->estado);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
        $this->get($detalle)->assertOk()->assertSee('Falta 1 movimiento por verificar');
    }

    public function test_validar_desde_resumen_lleva_a_detalle_con_cantidad_faltante(): void
    {
        $this->actingAs($this->admin)->from(route('web.caja.resumen', $this->caja->id))
            ->post(route('web.cajas.validar', $this->caja->id))
            ->assertRedirect(route('web.caja.detalle', $this->caja->id))
            ->assertSessionHasErrors(['movimientos' => 'Faltan 2 movimientos por verificar antes de validar la caja.']);
        $this->assertSame('CERRADA', $this->caja->fresh()->estado);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_servicio_directo_no_salta_la_verificacion(): void
    {
        try {
            $this->service->validarCaja($this->caja->id, $this->admin->id);
            $this->fail('Validó sin verificar movimientos.');
        } catch (ValidationException $e) {
            $this->assertSame(['Faltan 2 movimientos por verificar antes de validar la caja.'], $e->errors()['movimientos']);
        }
        $this->assertSame('CERRADA', $this->caja->fresh()->estado);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_caja_sin_movimientos_se_valida(): void
    {
        $this->caja->movimientos()->delete();
        $this->service->validarCaja($this->caja->id, $this->admin->id);
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_la_operativa_y_el_profesor_no_pueden_tildar(): void
    {
        foreach ([$this->operativa, User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true])] as $usuario) {
            $this->actingAs($usuario)->patchJson($this->url($this->primero), [
                'verificado' => true, 'huella' => $this->primero->huellaVerificacion(),
            ])->assertForbidden();
            try {
                $this->service->verificarMovimiento($this->caja->id, $this->primero->id, $usuario->id, true, $this->primero->huellaVerificacion());
                $this->fail('Un rol sin permiso verificó por servicio.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
            $this->assertFalse($this->primero->fresh()->estaVerificado());
        }
    }

    public function test_rechazar_conserva_marcas_y_corregir_solo_destilda_el_movimiento_cambiado(): void
    {
        $this->marcar($this->primero);
        $this->marcar($this->segundo);
        $this->service->rechazarCaja($this->caja->id, $this->admin->id, 'Corregir el primer movimiento.');
        $this->assertTrue($this->primero->fresh()->estaVerificado());
        $this->assertTrue($this->segundo->fresh()->estaVerificado());
        $mov = $this->primero->fresh();
        $this->service->actualizarMovimientoEnCaja($this->caja->id, $mov->id, [
            'tipo_caja_id' => $mov->tipo_caja_id, 'subrubro_id' => $mov->subrubro_id,
            'monto' => 35, 'fecha' => $mov->fecha->format('Y-m-d'), 'observaciones' => 'Corregido',
        ]);
        $this->assertFalse($this->primero->fresh()->estaVerificado());
        $this->assertNull($this->primero->fresh()->verificado_at);
        $this->assertTrue($this->segundo->fresh()->estaVerificado());
        $this->assertSame('200.00', $this->caja->fresh()->efectivo_contado);
        $this->assertSame('100.00', $this->caja->fresh()->cambio_retenido);
    }

    public function test_una_actualizacion_masiva_tambien_invalida_la_revision(): void
    {
        $this->marcar($this->primero);
        $this->marcar($this->segundo);
        $this->service->rechazarCaja($this->caja->id, $this->admin->id, 'Cancelar un cobro.');
        MovimientoOperativo::whereKey($this->primero->id)->update(['estado' => 'CANCELADO', 'motivo_cancelacion' => 'Cancelado']);
        $this->assertFalse($this->primero->fresh()->estaVerificado());
        $this->assertTrue($this->segundo->fresh()->estaVerificado());
        $this->caja->refresh()->update(['estado' => 'CERRADA']);
        try {
            $this->service->validarCaja($this->caja->id, $this->admin->id);
            $this->fail('Validó un movimiento alterado mediante update masivo.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('movimientos', $e->errors());
        }
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_cancelados_visibles_exigen_tilde_pero_no_se_suman_en_cashflow(): void
    {
        $this->segundo->update(['estado' => 'CANCELADO', 'motivo_cancelacion' => 'Prueba']);
        $this->marcar($this->primero);
        try {
            $this->service->validarCaja($this->caja->id, $this->admin->id);
            $this->fail('Ignoró el movimiento cancelado que se muestra en Detalle.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('1 movimiento', $e->getMessage());
        }
        $this->marcar($this->segundo);
        $this->service->validarCaja($this->caja->id, $this->admin->id);
        $this->assertDatabaseCount('cashflow_movimientos', 1);
        $this->assertSame('30.00', CashflowMovimiento::firstOrFail()->monto);
    }

    public function test_una_pantalla_vieja_no_puede_marcar_contenido_distinto(): void
    {
        $huella = $this->primero->huellaVerificacion();
        $this->primero->update(['observaciones' => 'Cambió después de abrir la pantalla']);
        $this->actingAs($this->admin)->patchJson($this->url($this->primero), [
            'verificado' => true, 'huella' => $huella,
        ])->assertUnprocessable()->assertJsonValidationErrors('movimientos');
        $this->assertFalse($this->primero->fresh()->estaVerificado());
    }

    public function test_no_se_verifica_un_movimiento_por_otra_caja(): void
    {
        $otra = $this->caja->replicate();
        $otra->save();
        $this->actingAs($this->admin)->patchJson($this->url($this->primero, $otra->id), [
            'verificado' => true, 'huella' => $this->primero->huellaVerificacion(),
        ])->assertNotFound();
        $this->assertFalse($this->primero->fresh()->estaVerificado());
    }
}
