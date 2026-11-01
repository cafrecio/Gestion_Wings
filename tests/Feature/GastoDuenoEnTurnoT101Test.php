<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T101 (CAJ-041 d): el gasto que el dueño agrega en el turno de una operativa entra en esa caja,
 * pero figura a nombre de quien lo cargó. Antes quedaba siempre a nombre de la operativa.
 */
class GastoDuenoEnTurnoT101Test extends TestCase
{
    use RefreshDatabase;

    private User $duenio;
    private User $operativa;
    private TipoCaja $efectivo;
    private Subrubro $gasto;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duenio = User::factory()->create(['name' => 'Laura Gómez', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Gastos T101', 'tipo' => 'EGRESO']);
        $this->gasto = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Insumos T101', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '10000.00']);
    }

    private function agregar(User $quien, string $observaciones): MovimientoOperativo
    {
        $this->actingAs($quien)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gasto->id,
            'monto' => '3000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => $observaciones,
        ])->assertSessionHasNoErrors();

        return MovimientoOperativo::where('observaciones', $observaciones)->sole();
    }

    public function test_el_gasto_del_duenio_queda_a_su_nombre_y_en_la_caja_de_la_operativa(): void
    {
        $movimiento = $this->agregar($this->duenio, 'Bombitas de luz');

        $this->assertSame($this->duenio->id, $movimiento->usuario_id);
        $this->assertSame($this->caja->id, $movimiento->caja_operativa_id);
        $this->assertSame($this->operativa->id, $this->caja->fresh()->usuario_operativo_id);
    }

    public function test_cambia_el_efectivo_esperado_de_la_operativa(): void
    {
        $esperado = fn () => (float) app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado'];
        $antes = $esperado();

        $this->agregar($this->duenio, 'Bombitas de luz');

        $this->assertSame($antes - 3000, $esperado());
    }

    public function test_la_operativa_lo_ve_en_movimientos_a_nombre_del_duenio(): void
    {
        $this->agregar($this->duenio, 'Bombitas de luz');

        $this->actingAs($this->operativa)->get(route('web.movimientos.index'))
            ->assertOk()->assertSee('Bombitas de luz')->assertSee('Laura Gómez');
    }

    public function test_el_gasto_de_la_operativa_sigue_a_su_nombre(): void
    {
        $movimiento = $this->agregar($this->operativa, 'Lapiceras');

        $this->assertSame($this->operativa->id, $movimiento->usuario_id);
    }

    public function test_el_movimiento_de_la_operativa_desde_caja_sigue_a_su_nombre(): void
    {
        $this->actingAs($this->operativa)->post('/caja/movimiento', [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gasto->id,
            'monto' => '1000.00',
            'observaciones' => 'Resma',
        ])->assertSessionHas('success');

        $this->assertSame($this->operativa->id, MovimientoOperativo::where('observaciones', 'Resma')->sole()->usuario_id);
    }

    public function test_otra_operativa_no_puede_agregar_en_ese_turno(): void
    {
        $otra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $this->actingAs($otra)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gasto->id,
            'monto' => '3000.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Forzado',
        ])->assertForbidden();

        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    public function test_un_pedido_directo_no_cambia_el_autor(): void
    {
        $this->actingAs($this->operativa)->post(route('web.caja.editar.store', $this->caja->id), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->gasto->id,
            'monto' => '100.00',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Con autor falso',
            'usuario_id' => $this->duenio->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->operativa->id, MovimientoOperativo::where('observaciones', 'Con autor falso')->sole()->usuario_id);
    }

    public function test_al_validar_cashflow_muestra_quien_lo_cargo(): void
    {
        $movimiento = $this->agregar($this->duenio, 'Bombitas de luz');

        $this->caja->update(['estado' => 'VALIDADA']);
        app(\App\Services\CashflowIntegracionCajaService::class)->reflejarCajaEnCashflow($this->caja->id, $this->duenio->id);

        $asiento = CashflowMovimiento::where('movimiento_operativo_id', $movimiento->id)->sole();
        $this->assertSame($this->duenio->id, $asiento->usuario_admin_id);
        $this->assertSame('-3000.00', (string) $asiento->monto);
    }
}
