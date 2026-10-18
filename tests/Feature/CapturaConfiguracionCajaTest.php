<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaConfiguracionCajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_html_para_capturas(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-18 10:00:00');

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $tipo1 = TipoCaja::firstOrCreate(['nombre' => 'Efectivo Mostrador'], ['abreviatura' => 'EFT', 'activo' => true]);
        $tipo2 = TipoCaja::firstOrCreate(['nombre' => 'Caja Fuerte / Alternativo'], ['abreviatura' => 'CFA', 'activo' => true]);

        $cajaService = app(CajaService::class);
        $cajaService->configurarMostrador($tipo1->id, $admin->id);

        $destinoDir = base_path('docs/06-pruebas/PRU-04/evidencia-t57');
        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0775, true);
        }

        // 1. Estado sin caja abierta: Habilitada
        $htmlHabilitada = $this->actingAs($admin)->get(route('web.caja.configuracion'))->assertOk()->getContent();
        $htmlHabilitada = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $htmlHabilitada);
        file_put_contents($destinoDir.'/caja-configuracion-habilitada.html', $htmlHabilitada);

        // 2. Estado con caja abierta: Deshabilitada
        CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'tipo_caja_efectivo_id' => $tipo1->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 1000,
            'apertura_at' => now(),
        ]);

        $htmlDeshabilitada = $this->actingAs($admin)->get(route('web.caja.configuracion'))->assertOk()->getContent();
        $htmlDeshabilitada = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $htmlDeshabilitada);
        file_put_contents($destinoDir.'/caja-configuracion-deshabilitada.html', $htmlDeshabilitada);

        // Marquetado marco 375
        file_put_contents($destinoDir.'/marco-375-habilitada.html', '<!doctype html><meta charset="utf-8"><body style="margin:0;background:#888;display:flex;justify-content:center"><iframe src="caja-configuracion-habilitada.html" style="width:375px;height:800px;border:0;background:#fff"></iframe></body>');
        file_put_contents($destinoDir.'/marco-375-deshabilitada.html', '<!doctype html><meta charset="utf-8"><body style="margin:0;background:#888;display:flex;justify-content:center"><iframe src="caja-configuracion-deshabilitada.html" style="width:375px;height:800px;border:0;background:#fff"></iframe></body>');

        Carbon::setTestNow();
    }
}
