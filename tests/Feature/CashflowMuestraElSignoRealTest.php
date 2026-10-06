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
 * La devolución de un cobro anulado se veía como si hubiera entrado plata.
 *
 * Lo encontró Codex verificando A13: al anular el cobro del dueño, el sistema le pone al
 * cashflow un asiento en contra —importe negativo, en el mismo subrubro de Cuotas— para no
 * borrar lo que pasó. Pero la pantalla deducía el signo del **rubro** y no del importe, así
 * que ese menos $48.000 aparecía en verde como "+$48.000": dos ingresos donde hubo uno y
 * una devolución. El saldo siempre estuvo bien; lo que mentía era lo que se leía.
 */
class CashflowMuestraElSignoRealTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_importe_negativo_de_un_rubro_de_ingresos_se_muestra_en_contra(): void
    {
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

        CashflowMovimiento::create([
            'fecha' => '2026-10-06',
            'subrubro_id' => $subrubro->id,
            'tipo_caja_id' => $tipoCaja->id,
            'monto' => 48000,
            'observaciones' => 'Cobro de cuota de prueba',
            'usuario_admin_id' => $admin->id,
        ]);
        CashflowMovimiento::create([
            'fecha' => '2026-10-06',
            'subrubro_id' => $subrubro->id,
            'tipo_caja_id' => $tipoCaja->id,
            'monto' => -48000,
            'observaciones' => 'Anulación del cobro #1 - se equivocó de medio',
            'usuario_admin_id' => $admin->id,
        ]);

        $html = $this->actingAs($admin)->get(route('web.cashflow.index'))->assertOk()->getContent();

        // La devolución se lee como lo que es: plata que sale.
        $this->assertStringContainsString('−$48.000', $html,
            'La devolución tiene que mostrarse con el signo en contra, no como un ingreso más.');

        // Una sola va en contra: el cobro original sigue siendo un ingreso.
        $this->assertSame(1, substr_count($html, '−$48.000'), 'Solo la devolución va en contra.');
        $this->assertSame(2, substr_count($html, '$48.000'), 'Tienen que verse los dos movimientos.');

        // Y la letra del tipo acompaña: la devolución no es un ingreso.
        $this->assertSame(1, preg_match_all('/>\s*E\s*</', $html), 'La devolución tiene que figurar como salida.');
        $this->assertSame(1, preg_match_all('/>\s*I\s*</', $html), 'El cobro original es el único ingreso.');

        Carbon::setTestNow();
    }
}
