<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Controles propios de Claude al verificar T36 (8a7d102), 18/10/2026.
 * Se corre desde esta carpeta, no desde tests/, para no cambiar el conteo de la carpeta compartida.
 */
class VerificacionT36ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private CajaOperativa $caja;
    private MovimientoOperativo $mov;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $medio = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingresos', 'tipo' => 'INGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Venta', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true]);
        $this->caja = CajaOperativa::create(['usuario_operativo_id' => $operativa->id, 'estado' => 'CERRADA',
            'apertura_at' => now()->subHours(2), 'cierre_at' => now(), 'tipo_caja_efectivo_id' => $medio->id,
            'efectivo_inicial' => 100, 'efectivo_esperado' => 130, 'efectivo_contado' => 130,
            'cambio_retenido' => 100, 'efectivo_retirado' => 30, 'diferencia_efectivo' => 0]);
        $this->mov = MovimientoOperativo::create(['caja_operativa_id' => $this->caja->id, 'fecha' => today(),
            'tipo_caja_id' => $medio->id, 'subrubro_id' => $sub->id, 'usuario_id' => $operativa->id,
            'estado' => 'ACTIVO', 'monto' => 30]);
    }

    private function url(): string
    {
        return route('web.cajas.movimientos.verificar', [$this->caja->id, $this->mov->id]);
    }

    public function test_un_dueno_desactivado_no_puede_tildar(): void
    {
        $inactivo = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $inactivo->activo = false;
        $inactivo->save();

        try {
            app(CajaService::class)->verificarMovimiento($this->caja->id, $this->mov->id, $inactivo->id, true, $this->mov->huellaVerificacion());
            $this->fail('Un dueño desactivado no debía poder tildar.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertFalse($this->mov->fresh()->estaVerificado());
    }

    public function test_una_huella_mal_formada_se_rechaza_sin_tildar(): void
    {
        $this->actingAs($this->admin)
            ->patchJson($this->url(), ['verificado' => true, 'huella' => 'abc'])
            ->assertStatus(422);
        $this->assertFalse($this->mov->fresh()->estaVerificado());
    }

    public function test_tildado_por_pantalla_permite_validar(): void
    {
        $this->actingAs($this->admin)
            ->patchJson($this->url(), ['verificado' => true, 'huella' => $this->mov->huellaVerificacion()])
            ->assertOk()->assertJson(['verificado' => true]);

        $this->actingAs($this->admin)->post(route('web.cajas.validar', $this->caja->id))
            ->assertRedirect(route('web.caja.index'));
        $this->assertSame('VALIDADA', $this->caja->fresh()->estado);
    }
}
