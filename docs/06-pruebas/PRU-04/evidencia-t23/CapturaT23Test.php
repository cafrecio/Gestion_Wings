<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaT23Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private CajaService $cajaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-11 09:00:00'));

        $this->admin = User::factory()->create([
            'rol' => 'ADMIN',
            'activo' => true,
            'name' => 'Carlos Administrador',
        ]);

        $this->operativo = User::factory()->create([
            'rol' => 'OPERATIVO',
            'activo' => true,
            'name' => 'Sandra Mostrador',
        ]);

        $this->efectivo = TipoCaja::create([
            'nombre' => 'Efectivo Mostrador',
            'abreviatura' => 'EF',
            'saldo_inicial' => '0.00',
            'activo' => true,
        ]);

        $this->cajaService = app(CajaService::class);
        $this->cajaService->configurarMostrador($this->efectivo->id, $this->admin->id);
    }

    private function guardarHtml(string $nombre, string $html): void
    {
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t23');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents("{$dir}/{$nombre}.html", $html);

        $marcoHtml = <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Wings · {$nombre} (360px)</title>
</head>
<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">
<iframe src="{$nombre}.html" style="width:360px;height:1180px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>
</body>
</html>
HTML;
        file_put_contents("{$dir}/{$nombre}-marco-360.html", $marcoHtml);
    }

    public function test_generar_htmls_t23(): void
    {
        // ── 1. Crear cierre previo que dejó $10.000 en cambio ────────────────
        $cajaPrevia = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 10000.00,
            'confirmacion' => 1,
            'caja_origen_id' => null,
        ], $this->admin->id);

        $cierre = $this->cajaService->cerrarCajaOperativa($cajaPrevia->id, $this->operativo->id, false, [
            'efectivo_contado' => 10000.00,
            'cambio_retenido' => 10000.00,
        ]);

        // ── 2. Operativo apertura con aviso por > 50% ($16.000 vs $10.000) ───
        $postOp = $this->actingAs($this->operativo)
            ->from(route('web.caja.apertura'))
            ->post(route('web.caja.abrir'), [
                'caja_origen_id' => $cierre->id,
                'efectivo_inicial' => '16000.00',
                'motivo_apertura' => 'Se contaron más billetes en el cajón',
                'confirmacion' => 1,
            ]);
        $postOp->assertRedirect(route('web.caja.apertura'));
        $htmlOpAviso = $this->actingAs($this->operativo)->get(route('web.caja.apertura'))->getContent();
        $this->guardarHtml('operativo-apertura-aviso', $htmlOpAviso);

        // ── 3. Admin apertura con aviso por > 50% ───────────────────────────
        $postAdmin = $this->actingAs($this->admin)
            ->from(route('web.caja.apertura'))
            ->post(route('web.caja.abrir'), [
                'operativo_id' => $this->operativo->id,
                'caja_origen_id' => $cierre->id,
                'efectivo_inicial' => '16000.00',
                'motivo_apertura' => 'Conteo de apertura con diferencia',
                'confirmacion' => 1,
            ]);
        $postAdmin->assertRedirect(route('web.caja.apertura'));
        $htmlAdminAviso = $this->actingAs($this->admin)->get(route('web.caja.apertura'))->getContent();
        $this->guardarHtml('admin-apertura-aviso', $htmlAdminAviso);

        // ── 4. Abrir caja para capturar la rectificación ─────────────────────
        $cajaAbierta = $this->cajaService->abrirCajaOperativa($this->operativo->id, [
            'efectivo_inicial' => 100000.00,
            'confirmacion' => 1,
            'caja_origen_id' => $cierre->id,
            'motivo_apertura' => 'Se abrio con tipex erróneo',
            'confirmar_diferencia_grande' => 1,
        ], $this->operativo->id);

        // Operativo rectificación
        $htmlOpRect = $this->actingAs($this->operativo)->get(route('web.caja.rectificar', $cajaAbierta->id))->getContent();
        $this->guardarHtml('operativo-rectificacion', $htmlOpRect);

        // Admin rectificación
        $htmlAdminRect = $this->actingAs($this->admin)->get(route('web.caja.rectificar', $cajaAbierta->id))->getContent();
        $this->guardarHtml('admin-rectificacion', $htmlAdminRect);

        // ── 5. Control: Login ───────────────────────────────────────────────
        auth()->logout();
        $htmlLogin = $this->get('/login')->getContent();
        $this->guardarHtml('login-control', $htmlLogin);

        $this->assertTrue(true);
    }
}
