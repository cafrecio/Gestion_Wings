<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\TransferenciaCashflowService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T106 (Carlos, 01/11/2026): una transferencia entre cuentas propias no es plata que entra ni sale del club.
 * Día 44.1 de la prueba: las tarjetas decían ingresos $1.921.200 y habían entrado $421.200.
 */
class TotalesSinTransferenciasT106Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoCaja $efectivo;
    private TipoCaja $banco;
    private Subrubro $ingreso;
    private Subrubro $gasto;
    private int $transferencia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-20 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->actingAs($this->admin);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->banco = TipoCaja::where('nombre', 'Banco Galicia')->firstOrFail();
        $this->ingreso = Subrubro::where('nombre', 'Intereses Banco')->firstOrFail();
        $this->gasto = Subrubro::where('nombre', 'Luz')->firstOrFail();

        $this->movimiento($this->ingreso, $this->efectivo, 421200);
        $this->movimiento($this->gasto, $this->efectivo, -65200);
        $this->transferencia = app(TransferenciaCashflowService::class)->registrar($this->admin->id, [
            'clave' => (string) Str::uuid(), 'salida_id' => $this->efectivo->id, 'destino_id' => $this->banco->id,
            'monto' => '1500000', 'fecha' => '2026-11-17', 'observaciones' => 'Depósito',
        ]);
    }

    private function movimiento(Subrubro $subrubro, TipoCaja $cuenta, float $monto): void
    {
        CashflowMovimiento::create(['fecha' => '2026-11-16', 'subrubro_id' => $subrubro->id, 'tipo_caja_id' => $cuenta->id,
            'monto' => $monto, 'observaciones' => 'Prueba T106', 'usuario_admin_id' => $this->admin->id]);
    }

    private function cashflow(array $filtros = [])
    {
        return $this->get('/cashflow?'.http_build_query(['periodo' => 'mes', 'fecha' => '2026-11-20'] + $filtros))->assertOk();
    }

    private function totales($respuesta, float $ingresos, float $egresos): void
    {
        $respuesta->assertViewHas('totalIngresos', fn ($n) => (float) $n === $ingresos)
            ->assertViewHas('totalEgresos', fn ($n) => (float) $n === $egresos);
    }

    public function test_cashflow_sin_filtro_no_suma_las_transferencias(): void
    {
        $this->totales($this->cashflow(), 421200.0, 65200.0);
    }

    public function test_cashflow_sigue_listando_las_dos_filas_de_la_transferencia(): void
    {
        $this->cashflow()->assertViewHas('movimientos', fn ($m) => $m->where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->count() === 2);
    }

    public function test_cashflow_mirando_una_cuenta_si_las_suma(): void
    {
        $this->totales($this->cashflow(['tipo_caja_id' => $this->efectivo->id]), 421200.0, 1565200.0);
        $this->totales($this->cashflow(['tipo_caja_id' => $this->banco->id]), 1500000.0, 0.0);
    }

    public function test_el_filtro_por_tipo_no_cambia_los_totales(): void
    {
        $this->totales($this->cashflow(['tipo' => 'INGRESO']), 421200.0, 65200.0);
    }

    public function test_historial_no_suma_las_transferencias_y_sigue_listandolas(): void
    {
        $respuesta = $this->get('/caja/historial?desde=2026-11-01&hasta=2026-11-30')->assertOk();

        $this->totales($respuesta, 421200.0, 65200.0);
        $respuesta->assertViewHas('filas', fn ($f) => $f->total() === 4);
    }

    public function test_historial_filtrando_ese_subrubro_si_lo_suma(): void
    {
        $salida = Subrubro::where('nombre', 'Salida a otro medio')->firstOrFail();

        $this->totales($this->get('/caja/historial?desde=2026-11-01&hasta=2026-11-30&subrubro_id='.$salida->id)->assertOk(), 0.0, 1500000.0);
    }

    public function test_los_saldos_de_las_cuentas_siguen_moviendose(): void
    {
        $saldos = collect(app(\App\Services\CashflowSaldoService::class)->obtenerSaldosPorTipoCaja()['por_tipo_caja'])->keyBy('tipo_caja_id');

        $this->assertEquals(421200 - 65200 - 1500000, $saldos[$this->efectivo->id]['saldo']);
        $this->assertEquals(1500000, $saldos[$this->banco->id]['saldo']);
    }

    public function test_una_transferencia_anulada_tampoco_suma(): void
    {
        $pata = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->where('referencia_id', $this->transferencia)->firstOrFail();
        $this->post(route('web.cashflow.transferencia.anular.store', $pata->id), ['motivo' => 'Cuenta equivocada'])->assertSessionHas('success');
        $this->assertSame(4, CashflowMovimiento::where('reporte_clasificacion', 'TRASPASO')->count(), 'La anulación no se registró');

        $this->totales($this->cashflow(), 421200.0, 65200.0);
        $this->totales($this->get('/caja/historial?desde=2026-11-01&hasta=2026-11-30')->assertOk(), 421200.0, 65200.0);
    }

    public function test_un_movimiento_sin_clasificar_sigue_sumando(): void
    {
        CashflowMovimiento::create(['fecha' => '2026-11-16', 'subrubro_id' => $this->ingreso->id, 'tipo_caja_id' => $this->efectivo->id,
            'monto' => 100, 'observaciones' => 'Viejo', 'usuario_admin_id' => $this->admin->id]);
        CashflowMovimiento::where('observaciones', 'Viejo')->update(['reporte_clasificacion' => null]);

        $this->totales($this->cashflow(), 421300.0, 65200.0);
    }
}
