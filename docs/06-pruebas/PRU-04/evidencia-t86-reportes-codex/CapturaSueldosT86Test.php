<?php

namespace Tests\VerificacionCodex;

require_once dirname(__DIR__, 4).'/tests/Feature/ReporteSueldosTest.php';

use App\Models\{TipoCaja, User};
use App\Services\{CashflowService, SubrubroSueldoService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\{Auth, DB, Notification};

/** Guarda HTML de la ruta real de Wings; ningun layout inventado. */
class CapturaSueldosT86Test extends \Tests\Feature\ReporteSueldosTest
{
    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
            throw new \RuntimeException('Capturas T86: solo wings_testing_codex.');
        }
    }

    private function guardar(string $archivo, string $html): void
    {
        $publico = 'file:///'.str_replace('\\', '/', public_path()).'/';
        $html = preg_replace('~((?:src|href)=")[^"]*/build/~', '$1'.$publico.'build/', $html);
        $html = preg_replace('~((?:src|href)=")[^"]*/(favicon[^"/]*|images/[^\"]*)"~', '$1'.$publico.'$2"', $html);
        $html = preg_replace(['/(<meta name="csrf-token" content=")[^"]+/', '/(name="_token" value=")[^"]+/'], ['$1FICTICIO', '$1FICTICIO'], $html);
        file_put_contents(__DIR__.'/'.$archivo, $html);
    }

    public function test_guardar_pantalla_real_con_pagos_sin_pagos_y_login(): void
    {
        $this->withVite();
        Notification::fake();
        Carbon::setTestNow('2026-10-27 12:00:00');
        $this->seed(CatalogosSeeder::class);
        $admin = User::where('rol', 'ADMIN')->firstOrFail(); $admin->name = 'Administrador T86'; $admin->save();
        $this->guardar('sueldos-sin-pagos.html', $this->actingAs($admin)->get(route('web.reportes.sueldos'))->assertOk()->getContent());
        $medio = TipoCaja::where('activo', true)->firstOrFail();
        foreach (['Sandra Vidal (prueba)' => ['20000', '28000.75'], 'Pablo Diaz (prueba)' => ['50000'], 'Rocio Paz (prueba)' => ['38000.50']] as $nombre => $montos) {
            $u = User::factory()->create(['name' => $nombre, 'rol' => 'OPERATIVO', 'activo' => true]);
            app(SubrubroSueldoService::class)->paraUsuarioOperativo($u);
            foreach ($montos as $monto) app(CashflowService::class)->registrarMovimientoAdmin([
                'usuario_admin_id' => $admin->id, 'subrubro_id' => $u->subrubro_id, 'tipo_caja_id' => $medio->id,
                'monto' => $monto, 'fecha' => '2026-10-27', 'observaciones' => 'Pago sintetico para captura T86']);
        }
        $response = $this->get(route('web.reportes.sueldos'))->assertOk()->assertSee('$136,001.25');
        $this->guardar('sueldos-con-pagos.html', $response->getContent());
        file_put_contents(__DIR__.'/datos-captura.json', json_encode(['base' => DB::connection()->getDatabaseName(),
            'ruta' => route('web.reportes.sueldos'), 'datos_sinteticos' => true, 'reporte' => $response->viewData('reporte'),
            'transformacion_html' => 'Solo URLs locales de assets y neutralizacion de tokens; contenido de la ruta real sin reemplazos.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
        Auth::logout();
        $this->guardar('login.html', $this->get(route('login'))->assertOk()->getContent());
    }
}
