<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransferenciasT97;
use Tests\TestCase;

/** Captura rutas reales, incluida la anulación real. Sin reemplazar HTML ni armar inversos a mano. */
class CapturasFinalesT97Test extends TestCase
{
    use RefreshDatabase, TransferenciasT97 {
        TransferenciasT97::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
            throw new \RuntimeException('Capturas T97 solo permite wings_testing_codex.');
        }
    }

    public function test_capturas_reales_del_circuito_completo(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') $this->markTestSkipped('Capturas explícitas, fuera de la suite.');
        Carbon::setTestNow('2026-10-20 12:00:00');
        $patas = $this->transferir('2026-10-20');
        $url = route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => '2026-10-20']);
        $this->guardar('01-cashflow-anular', $this->get($url)->assertOk()->assertSee('Anular')->getContent());
        $this->guardar('02-confirmacion', $this->get(route('web.cashflow.transferencia.anular', $patas[0]->id))
            ->assertOk()->assertViewIs('cashflow.anular-transferencia')->getContent());
        $this->post($this->rutaT97($patas[1]->id), ['motivo' => 'Se eligió una cuenta de destino equivocada'])
            ->assertSessionHasNoErrors()->assertRedirect($url);
        $this->guardar('03-cashflow-anulada', $this->get($url)->assertOk()
            ->assertSee('Anulación de transferencia #'.$patas[0]->referencia_id)->getContent());
        $this->assertCuentasT97();
        auth()->logout();
        $this->guardar('00-login-control', $this->get('/login')->assertOk()->getContent());
        $fuentes = ['app/Services/AnulacionTransferenciaCashflowService.php',
            'app/Http/Controllers/TransferenciaCashflowWebController.php', 'app/Http/Controllers/CashflowWebController.php',
            'app/Models/CashflowMovimiento.php', 'routes/web.php', 'resources/views/cashflow/index.blade.php',
            'resources/views/cashflow/anular-transferencia.blade.php', 'resources/css/app.css'];
        file_put_contents(__DIR__.'/origen.json', json_encode(['base' => 'wings_testing_codex',
            'fecha_fixture' => '2026-10-20', 'circuito' => 'GET Cashflow, GET confirmación, POST por la otra pata, GET Cashflow, GET login',
            'transferencia' => $patas[0]->referencia_id, 'salida_centavos' => 60000000, 'destino_centavos' => 10000000,
            'fuentes' => collect($fuentes)->mapWithKeys(fn ($f) => [$f => hash_file('sha256', base_path($f))])->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function guardar(string $nombre, string $html): void
    {
        if (!is_dir(__DIR__.'/html')) mkdir(__DIR__.'/html', 0775, true);
        $publico = 'file:///'.str_replace('\\', '/', public_path());
        $html = preg_replace('~(src|href)="(?:https?://[^"/]+)?/(build|images|img|fonts)/~', '$1="'.$publico.'/$2/', $html);
        $html = str_replace('</head>', '<meta http-equiv="Content-Security-Policy" content="form-action &apos;none&apos;; connect-src &apos;none&apos;"></head>', $html);
        file_put_contents(__DIR__.'/html/'.$nombre.'.html', $html);
    }
}
