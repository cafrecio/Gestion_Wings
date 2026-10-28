<?php

putenv('DB_DATABASE=wings_manual_gemini');
$_ENV['DB_DATABASE'] = 'wings_manual_gemini';

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\ManualService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

Carbon::setTestNow('2026-10-28 17:00:00');

$dirEvidencia = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13');
$admin = User::where('email', 'admin@wings.test')->firstOrFail();
Auth::setUser($admin);
View::share('errors', new \Illuminate\Support\ViewErrorBag);

$publicBuild = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
$publicImg = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';
$manualImgOperativo = 'file:///' . str_replace('\\', '/', resource_path('manual/img/operativo')) . '/';
$manualImgAdmin = 'file:///' . str_replace('\\', '/', resource_path('manual/img/admin')) . '/';

$sanitizar = function (string $html) use ($publicBuild, $publicImg, $manualImgOperativo, $manualImgAdmin): string {
    $html = preg_replace('#https?://[^/]+/build/#', $publicBuild, $html);
    $html = preg_replace('#https?://[^/]+/img/#', $publicImg, $html);
    $html = str_replace(['src="/build/', 'href="/build/'], ['src="' . $publicBuild, 'href="' . $publicBuild], $html);
    $html = str_replace(['src="/img/', 'href="/img/'], ['src="' . $publicImg, 'href="' . $publicImg], $html);
    $html = str_replace('/ayuda/img/operativo/', $manualImgOperativo, $html);
    $html = str_replace('/ayuda/img/admin/', $manualImgAdmin, $html);
    return $html;
};

$armarMarco375 = function (string $archivoHtml): string {
    return '<!doctype html><meta charset="utf-8">' . "\n" .
           '<body style="margin:0;background:#0f172a;display:flex;justify-content:center;align-items:flex-start;padding:24px 0;">' . "\n" .
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:950px;border:1px solid #334155;border-radius:14px;background:#fff;box-shadow:0 12px 30px rgba(0,0,0,0.6);"></iframe></body>' . "\n";
};

$ayudaController = app(\App\Http\Controllers\AyudaWebController::class);

// 1. Índice de Ayuda
$req = Request::create('/ayuda', 'GET');
$req->setUserResolver(fn() => $admin);
$htmlIndice = $ayudaController->index($req)->render();
$htmlIndice = $sanitizar($htmlIndice);
file_put_contents($dirEvidencia . '/ayuda-indice.html', $htmlIndice);
file_put_contents($dirEvidencia . '/ayuda-indice-marco-375.html', $armarMarco375('ayuda-indice.html'));

// 2. Muestra 1: Cobrar una cuota
$reqCobrar = Request::create('/ayuda/cobrar-cuota', 'GET');
$reqCobrar->setUserResolver(fn() => $admin);
$htmlMuestra1 = $ayudaController->show($reqCobrar, 'cobrar-cuota')->render();
$htmlMuestra1 = $sanitizar($htmlMuestra1);
file_put_contents($dirEvidencia . '/muestra1-cobrar-cuota.html', $htmlMuestra1);
file_put_contents($dirEvidencia . '/muestra1-cobrar-cuota-marco-375.html', $armarMarco375('muestra1-cobrar-cuota.html'));

// Versión impresa
$htmlImpresion = str_replace(
    '</head>',
    '<style>.ds-sidebar, .ds-sidebar-overlay, .ds-topbar, .ds-module-header, .ayuda-acciones-barra, .ds-btn { display: none !important; } body, .ds-layout, .ds-main, .ds-content { background:#fff !important; margin:0 !important; padding:12px 24px !important; display:block !important; } .ayuda-documento { border:none !important; box-shadow:none !important; padding:0 !important; } .ayuda-markdown-cuerpo h1 { display:block !important; font-size:1.75rem !important; margin-bottom:1rem !important; }</style></head>',
    $htmlMuestra1
);
file_put_contents($dirEvidencia . '/ayuda-impresion.html', $htmlImpresion);

// 3. Muestra 2: Corregir movimiento en caja validada
$reqAjuste = Request::create('/ayuda/corregir-movimiento-caja-validada', 'GET');
$reqAjuste->setUserResolver(fn() => $admin);
$htmlMuestra2 = $ayudaController->show($reqAjuste, 'corregir-movimiento-caja-validada')->render();
$htmlMuestra2 = $sanitizar($htmlMuestra2);
file_put_contents($dirEvidencia . '/muestra2-corregir-movimiento.html', $htmlMuestra2);
file_put_contents($dirEvidencia . '/muestra2-corregir-movimiento-marco-375.html', $armarMarco375('muestra2-corregir-movimiento.html'));

echo "Archivos HTML de muestras y páginas de ayuda generados con éxito.\n";
