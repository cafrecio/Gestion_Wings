<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guarda el HTML real de Cashflow con un cobro y su devolución, para sacarle la captura
 * que pide `AGENTS.md` §1 antes de pedir la autorización de diseño.
 *
 * Se salta sola salvo que se pida con WINGS_CAPTURAS=1. La captura sale de la aplicación,
 * no de un HTML escrito a mano: esa es la regla que nació de A37.
 */
class CapturaCashflowSignoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_el_html_de_cashflow_con_una_devolucion(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        Carbon::setTestNow('2026-10-06 10:00:00');

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $tipoCaja = TipoCaja::create(['nombre' => 'Efectivo', 'abreviatura' => 'EFE', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $subrubro = Subrubro::create([
            'rubro_id' => $rubro->id,
            'nombre' => 'Cuota Mensual',
            'permitido_para' => User::ROL_OPERATIVO,
            'afecta_caja' => true,
            'es_reservado_sistema' => true,
        ]);

        foreach ([
            [48000, 'Cuota Octubre 2026 - Gómez, Luz'],
            [-48000, 'Anulación del cobro #1 - se equivocó de medio de pago'],
        ] as [$monto, $texto]) {
            CashflowMovimiento::create([
                'fecha' => '2026-10-06',
                'subrubro_id' => $subrubro->id,
                'tipo_caja_id' => $tipoCaja->id,
                'monto' => $monto,
                'observaciones' => $texto,
                'usuario_admin_id' => $admin->id,
            ]);
        }

        $html = $this->actingAs($admin)->get(route('web.cashflow.index'))->assertOk()->getContent();
        $html = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $html);

        $destino = base_path('docs/06-pruebas/PRU-02/capturas-cashflow/cashflow-devolucion.html');
        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0775, true);
        }
        file_put_contents($destino, $html);

        $this->assertStringContainsString('−$48.000', $html);
        Carbon::setTestNow();
    }
}
