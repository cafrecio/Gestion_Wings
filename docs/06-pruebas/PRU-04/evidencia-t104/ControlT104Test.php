<?php

namespace Tests\Feature;

use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T104: retrata lo que hace hoy Wings con una cuenta «Sin descubierto». Se corre por ruta y no queda en la suite.
 */
class ControlT104Test extends TestCase
{
    use RefreshDatabase;

    private TipoCaja $efectivo;
    private TipoCaja $banco;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-15 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $this->actingAs(User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]));
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        $this->banco = TipoCaja::where('nombre', 'Banco Galicia')->firstOrFail();
        $this->efectivo->update(['saldo_inicial' => 1000, 'permite_descubierto' => false]);
    }

    private function saldoEfectivo(): float
    {
        $s = collect(app(\App\Services\CashflowSaldoService::class)->obtenerSaldosPorTipoCaja()['por_tipo_caja'])->keyBy('tipo_caja_id');

        return (float) $s[$this->efectivo->id]['saldo'];
    }

    public function test_un_gasto_de_cashflow_deja_en_negativo_una_cuenta_sin_descubierto(): void
    {
        $sub = Subrubro::whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->where('es_reservado_sistema', false)->firstOrFail();
        $r = $this->post(route('web.cashflow.movimiento.store'), ['tipo_caja_id' => $this->efectivo->id, 'subrubro_id' => $sub->id,
            'monto' => '5000', 'fecha' => '2026-11-15', 'observaciones' => 'Prueba T104']);
        $r->assertSessionHasNoErrors();
        $this->assertSame(-4000.0, $this->saldoEfectivo());
    }

    public function test_una_transferencia_deja_en_negativo_una_cuenta_sin_descubierto(): void
    {
        $r = $this->post(route('web.cashflow.transferencia.store'), ['clave' => (string) Str::uuid(), 'salida_id' => $this->efectivo->id,
            'destino_id' => $this->banco->id, 'monto' => '5000', 'fecha' => '2026-11-15', 'observaciones' => 'Prueba T104']);
        $r->assertSessionHasNoErrors();
        $this->assertSame(-4000.0, $this->saldoEfectivo());
    }
}
