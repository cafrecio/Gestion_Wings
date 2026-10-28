<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, MovimientoOperativoCorreccion, Rubro, Subrubro, TipoCaja, User};
use App\Services\{CajaCorreccionService, CajaService, ReporteMensualService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class VerificacionT51ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private CajaOperativa $caja;
    private MovimientoOperativo $movimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true, 'name' => 'Dueño de prueba']);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true, 'name' => 'Operativa de prueba']);
        $medio = TipoCaja::create(['nombre' => 'Efectivo de prueba', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Gastos de prueba', 'tipo' => 'EGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Limpieza de prueba',
            'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'activo' => true, 'clasificacion' => 'NEGOCIO']);
        $this->caja = CajaOperativa::create(['usuario_operativo_id' => $this->operativa->id, 'estado' => 'ABIERTA',
            'apertura_at' => now(), 'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 10000]);
        $this->movimiento = MovimientoOperativo::create(['caja_operativa_id' => $this->caja->id,
            'usuario_id' => $this->operativa->id, 'fecha' => today(), 'tipo_caja_id' => $medio->id,
            'subrubro_id' => $sub->id, 'monto' => 1000, 'observaciones' => 'Escobas', 'estado' => 'ACTIVO']);
    }

    private function datos(int $monto = 2500): array
    {
        return ['tipo_caja_id' => $this->movimiento->tipo_caja_id, 'subrubro_id' => $this->movimiento->subrubro_id,
            'monto' => $monto, 'fecha' => today()->toDateString(), 'observaciones' => 'Escobas y detergente'];
    }

    private function url(string $accion): string
    {
        return route('web.caja.movimientos.'.$accion, [$this->caja->id, $this->movimiento->id]);
    }
    /** Ataque 1: operativa desactivada con la sesion abierta no corrige. */
    public function test_operativa_desactivada_no_corrige(): void
    {
        $this->operativa->forceFill(['activo' => false])->save();
        $res = $this->actingAs($this->operativa)->put($this->url('update'), $this->datos());
        $this->assertNotSame(200, $res->status());
        $this->assertSame('1000.00', $this->movimiento->fresh()->monto);
        $this->assertSame(0, MovimientoOperativoCorreccion::count());
    }

    /** Ataque 2: movimiento de otra caja pedido con el numero de esta caja: 404 y nada escrito. */
    public function test_movimiento_de_otra_caja_por_direccion_cambiada(): void
    {
        $otraOp = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $otraCaja = CajaOperativa::create(['usuario_operativo_id' => $otraOp->id, 'estado' => 'ABIERTA',
            'apertura_at' => now(), 'tipo_caja_efectivo_id' => $this->movimiento->tipo_caja_id, 'efectivo_inicial' => 0]);
        $res = $this->actingAs($this->operativa)->delete(route('web.caja.movimientos.destroy', [$this->caja->id, $this->movimiento->id + 999]));
        $this->assertSame(404, $res->status());
        $res2 = $this->actingAs($this->operativa)->delete(route('web.caja.movimientos.destroy', [$otraCaja->id, $this->movimiento->id]));
        $this->assertContains($res2->status(), [403, 404]);
        $this->assertNotNull($this->movimiento->fresh());
        $this->assertSame(0, MovimientoOperativoCorreccion::count());
    }

    /** Ataque 3: eliminar dos veces deja un solo rastro. */
    public function test_eliminar_dos_veces_un_solo_rastro(): void
    {
        $this->actingAs($this->operativa)->delete($this->url('destroy'))->assertSessionHas('success');
        $res = $this->actingAs($this->operativa)->delete($this->url('destroy'));
        $this->assertSame(404, $res->status());
        $this->assertSame(1, MovimientoOperativoCorreccion::where('accion', 'ELIMINAR')->count());
    }
}
