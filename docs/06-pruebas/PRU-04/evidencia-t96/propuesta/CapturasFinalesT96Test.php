<?php

namespace Tests\Feature;

use App\Models\CashflowMovimiento;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T96 ya implementada: HTML real, sin reemplazos, para comparar con la propuesta aprobada
 * y para la captura del manual. Se corre por ruta, con WINGS_CAPTURAS=1; no queda en la suite.
 */
class CapturasFinalesT96Test extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_html_real(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['name' => 'Laura Gómez', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativa = User::factory()->create(['name' => 'Sandra Ríos', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $efectivo = TipoCaja::where('nombre', TipoCaja::NOMBRE_EFECTIVO)->firstOrFail();

        $destino = base_path('docs/06-pruebas/PRU-04/evidencia-t96/final/html');
        if (!is_dir($destino)) {
            mkdir($destino, 0775, true);
        }
        $build = 'file:///'.str_replace('\\', '/', public_path('build'));
        $guardar = function (string $nombre, User $quien, string $ruta) use ($destino, $build) {
            $html = $this->actingAs($quien)->get($ruta)->assertOk()->getContent();
            $html = preg_replace('#(src|href)="(https?://[^"/]+)?/build/#', '$1="'.$build.'/', $html);
            file_put_contents($destino.'/'.$nombre.'.html', $html);
        };

        $guardar('caja-sin-configurar', $admin, route('web.caja.index'));
        $guardar('configuracion-saldo-editable', $admin, route('web.caja.configuracion'));
        $guardar('operativa-apertura', $operativa, route('web.caja.apertura'));

        $mov = CashflowMovimiento::create([
            'fecha' => today()->toDateString(), 'tipo_caja_id' => $efectivo->id, 'monto' => 10, 'usuario_admin_id' => $admin->id,
            'subrubro_id' => Subrubro::where('nombre', 'Ajuste de saldo: entrada')->sole()->id,
        ]);
        $efectivo->update(['saldo_inicial' => 2310000]);
        $guardar('configuracion-con-movimientos', $admin, route('web.caja.configuracion'));
        $mov->delete();

        app(CajaService::class)->configurarEfectivo($admin->id, '2310000');
        $guardar('caja-configurada', $admin, route('web.caja.index'));
        $guardar('tipo-caja-efectivo', $admin, route('web.tipos-caja.edit', $efectivo->id));
    }
}
