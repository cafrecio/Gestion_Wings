<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evidencia, no prueba permanente: le pide a la aplicación el Historial de caja como
 * dueño y como operativa, con los mismos datos, y guarda el HTML que devuelve.
 * Las imágenes se sacan después con tomar-capturas.mjs.
 */
class CapturaHistorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_generar_htmls_para_capturas(): void
    {
        $admin  = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'name' => 'Carlos Administrador']);
        $sandra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra Mostrador']);
        $pablo  = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Pablo Mostrador']);

        $efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $ingresos = Rubro::create(['nombre' => 'Cuotas', 'tipo' => 'INGRESO', 'observacion' => '']);
        $egresos  = Rubro::create(['nombre' => 'Sueldos', 'tipo' => 'EGRESO', 'observacion' => '']);
        $gastos   = Rubro::create(['nombre' => 'Gastos', 'tipo' => 'EGRESO', 'observacion' => '']);
        $cuota  = Subrubro::create(['rubro_id' => $ingresos->id, 'nombre' => 'Cuota Mensual', 'permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true]);
        $libreria = Subrubro::create(['rubro_id' => $gastos->id, 'nombre' => 'Librería', 'permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true]);
        $sueldo = Subrubro::create(['rubro_id' => $egresos->id, 'nombre' => 'Sueldo profesora', 'permitido_para' => User::ROL_ADMIN, 'afecta_caja' => true]);

        $caja = CajaOperativa::create(['usuario_operativo_id' => $pablo->id, 'apertura_at' => now(), 'estado' => CajaOperativa::ESTADO_ABIERTA]);
        $mov = fn (Subrubro $s, float $monto, string $obs, array $extra = []) => MovimientoOperativo::create($extra + [
            'caja_operativa_id' => $caja->id, 'fecha' => today(), 'tipo_caja_id' => $efectivo->id, 'subrubro_id' => $s->id,
            'monto' => $monto, 'observaciones' => $obs, 'usuario_id' => $pablo->id, 'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);

        $mov($cuota, 48000, 'Cuota de octubre');
        $mov($cuota, 38000, 'Cobro al alumno equivocado', ['estado' => MovimientoOperativo::ESTADO_CANCELADO]);
        $mov($libreria, 2500, 'Resma y biromes');
        $mov($sueldo, 90000, 'Sueldo de septiembre');
        $mov($cuota, 45000, 'Cuota de mayo', ['fecha' => today()->subDays(150)]);

        foreach ([32000 => 'Cobro del dueño sin caja', -32000 => 'Anulación del cobro #7 - error de tipeo'] as $importe => $obs) {
            CashflowMovimiento::create([
                'fecha' => today(), 'subrubro_id' => $cuota->id, 'tipo_caja_id' => $efectivo->id, 'monto' => $importe,
                'observaciones' => $obs, 'usuario_admin_id' => $admin->id,
                'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA, 'referencia_id' => 7,
            ]);
        }

        $this->guardar('duenio', $this->actingAs($admin)->get(route('web.caja.historial'))->assertOk()->getContent());
        $this->guardar('duenio-desde-hace-200-dias', $this->actingAs($admin)->get(route('web.caja.historial', ['desde' => today()->subDays(200)->toDateString()]))->assertOk()->getContent());
        $this->guardar('operativa', $this->actingAs($sandra)->get(route('web.caja.historial'))->assertOk()->getContent());
        auth()->logout();
        $this->guardar('login-control', $this->get(route('login'))->assertOk()->getContent());
    }

    private function guardar(string $nombre, string $html): void
    {
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-historial');
        $build = str_replace(chr(92), '/', public_path('build'));
        $html = str_replace(['src="/build/', 'href="/build/'], ['src="'.$build.'/', 'href="'.$build.'/'], $html);
        file_put_contents("{$dir}/{$nombre}.html", $html);
        file_put_contents("{$dir}/{$nombre}-marco-360.html",
            '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · '.$nombre.' (360px)</title></head>'
            .'<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">'
            .'<iframe src="'.$nombre.'.html" style="width:360px;height:1150px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>'
            .'</body></html>');
    }
}
