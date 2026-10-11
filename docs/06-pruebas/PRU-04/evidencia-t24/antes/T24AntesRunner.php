<?php
namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\ImportesT24;
use Tests\TestCase;

/** Solo evidencia de la versión dd62c64: no cambia fuentes ni hace pasar sus defectos. */
class T24AntesTest extends TestCase
{
    use RefreshDatabase;
    public function test_registra_el_comportamiento_anterior_en_cada_pantalla(): void
    {
        $this->assertSame(realpath(dirname(__DIR__, 2)), realpath(base_path()));
        $this->assertSame('wings_testing_codex', config('database.connections.mysql.database'));
        Carbon::setTestNow('2026-10-11 10:00:00'); Notification::fake();
        $salida = dirname(base_path(), 4).'/docs/06-pruebas/PRU-04/evidencia-t24/antes';
        if (!is_dir($salida.'/html')) mkdir($salida.'/html', 0775, true);
        $resultado = [];
        foreach (ImportesT24::CASOS as $caso) {
            if ($caso === 'rectificacion') continue; // T23 todavía no existía en dd62c64.
            DB::beginTransaction();
            try {
                $f = new ImportesT24($caso);
                [$metodo, $ruta, , $clave, $pantalla, $selector] = $f->pedido($caso, '48.50');
                if ($pantalla) {
                    $html = $this->actingAs($caso === 'caja-movimiento' ? $f->operativo : $f->admin)->get($pantalla)->assertOk()->getContent();
                    $html = preg_replace('~https?://[^/"\s]+(/(?:build|img|favicon|apple-touch|site\.webmanifest)[^"\s]*)~', '$1', $html);
                    $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1captura-anterior', $html);
                    $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1captura-anterior', $html);
                    file_put_contents("$salida/html/$caso.html", $html);
                }
                $pruebas = [];
                foreach (['48.505', '48.000', '48.0000'] as $importe) {
                    DB::beginTransaction();
                    [$metodo, $ruta, $datos, $clave] = $f->pedido($caso, $importe);
                    $antes = ImportesT24::fotoDatos();
                    $this->withSession(['errors' => null]);
                    $r = $this->actingAs($caso === 'caja-movimiento' ? $f->operativo : $f->admin)->json($metodo, $ruta, $datos);
                    $errores = $this->app['session']->get('errors');
                    $rechazo = $r->status() === 422 || ($errores && $errores->getBag('default')->has($clave));
                    $pruebas[] = ['entrada' => $importe, 'estadoHTTP' => $r->status(), 'rechazado' => (bool) $rechazo,
                        'escribio' => $antes !== ImportesT24::fotoDatos()];
                    DB::rollBack();
                }
                $resultado[] = ['caso' => $caso, 'ruta' => $pantalla, 'selector' => $selector, 'pedidos' => $pruebas];
            } finally { DB::rollBack(); }
        }
        file_put_contents($salida.'/pedidos.json', json_encode(['revision' => 'dd62c64', 'casos' => $resultado], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        $this->assertCount(19, $resultado);
        Carbon::setTestNow();
    }
}
