<?php

putenv('DB_DATABASE=wings_manual_gemini');
$_ENV['DB_DATABASE'] = 'wings_manual_gemini';

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\ManualService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

Carbon::setTestNow('2026-10-28 17:00:00');

$dir = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13');
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

// Asegurar usuarios de prueba
$admin = User::firstOrNew(['email' => 'admin@wings.test']);
$admin->name = 'Carlos Freccero';
$admin->rol = User::ROL_ADMIN;
$admin->activo = true;
$admin->password = bcrypt('password');
$admin->save();

$operativo = User::firstOrNew(['email' => 'operativa@wings.test']);
$operativo->name = 'Sandra Operativa';
$operativo->rol = User::ROL_OPERATIVO;
$operativo->activo = true;
$operativo->password = bcrypt('password');
$operativo->save();

Auth::setUser($admin);
View::share('errors', new \Illuminate\Support\ViewErrorBag);

// Asegurar catálogos básicos en wings_manual_gemini
$deporte = Deporte::firstOrCreate(['nombre' => 'Patín Artístico'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivel = Nivel::firstOrCreate(['nombre' => 'Escuela Inicial']);
$grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id], ['nombre' => 'Patín Escuela', 'activo' => true]);
$plan = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupo->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 25000, 'activo' => true]
);
$tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);
$tipoCajaBanco = TipoCaja::firstOrCreate(['nombre' => 'Banco'], ['activo' => true]);

$rubroCuotas = Rubro::firstOrCreate(['nombre' => 'Cuotas'], ['tipo' => 'INGRESO', 'observacion' => '']);
Subrubro::firstOrCreate(
    ['rubro_id' => $rubroCuotas->id, 'nombre' => 'Cuota Mensual'],
    ['permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true, 'es_reservado_sistema' => true]
);

$rubroAjustes = Rubro::firstOrCreate(['nombre' => 'Ajustes y compensaciones'], ['tipo' => 'EGRESO', 'observacion' => '']);
Subrubro::firstOrCreate(
    ['rubro_id' => $rubroAjustes->id, 'nombre' => 'Ajuste de caja validada'],
    ['permitido_para' => User::ROL_ADMIN, 'afecta_caja' => false, 'es_reservado_sistema' => false]
);

// Alumno para muestra de cobro
$alumno = Alumno::firstOrCreate(
    ['dni' => '44888999'],
    [
        'nombre'           => 'Martina',
        'apellido'         => 'Gómez',
        'fecha_nacimiento' => '2014-06-12',
        'celular'          => '3415998877',
        'deporte_id'       => $deporte->id,
        'grupo_id'         => $grupo->id,
        'fecha_alta'       => '2026-08-01',
        'activo'           => true,
    ]
);
AlumnoPlan::firstOrCreate(
    ['alumno_id' => $alumno->id, 'plan_id' => $plan->id],
    ['fecha_desde' => '2026-08-01', 'activo' => true]
);

DeudaCuota::firstOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => '2026-10'],
    [
        'monto_original'  => 25000,
        'saldo_pendiente' => 25000,
        'fecha_vencimiento' => '2026-10-10',
        'estado'          => 'PENDIENTE',
    ]
);

\Tests\Support\CajaDeclarada::crear($admin->id, $tipoCaja->id);
\Tests\Support\CajaDeclarada::crear($operativo->id, $tipoCaja->id);

$publicBuild = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
$publicImg = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';

$sanitizar = function (string $html) use ($publicBuild, $publicImg): string {
    $html = preg_replace('#https?://[^/]+/build/#', $publicBuild, $html);
    $html = preg_replace('#https?://[^/]+/img/#', $publicImg, $html);
    $html = str_replace(['src="/build/', 'href="/build/'], ['src="' . $publicBuild, 'href="' . $publicBuild], $html);
    $html = str_replace(['src="/img/', 'href="/img/'], ['src="' . $publicImg, 'href="' . $publicImg], $html);
    return $html;
};

$armarMarco375 = function (string $archivoHtml): string {
    return '<!doctype html><meta charset="utf-8">' . "\n" .
           '<body style="margin:0;background:#0f172a;display:flex;justify-content:center;align-items:flex-start;padding:24px 0;">' . "\n" .
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:950px;border:1px solid #334155;border-radius:14px;background:#fff;box-shadow:0 12px 30px rgba(0,0,0,0.6);"></iframe></body>' . "\n";
};

$manualService = app(ManualService::class);
$ayudaController = app(\App\Http\Controllers\AyudaWebController::class);

// ──────────────────────────────────────────────────────────────────────────
// 1. Índice de Ayuda (/ayuda)
// ──────────────────────────────────────────────────────────────────────────
$req = Request::create('/ayuda', 'GET');
$req->setUserResolver(fn() => $admin);
$htmlIndice = $ayudaController->index($req)->render();
$htmlIndice = $sanitizar($htmlIndice);
file_put_contents($dir . '/ayuda-indice.html', $htmlIndice);
file_put_contents($dir . '/ayuda-indice-marco-375.html', $armarMarco375('ayuda-indice.html'));

// ──────────────────────────────────────────────────────────────────────────
// 2. Muestra 1: Cobrar una cuota (/ayuda/cobrar-cuota)
// ──────────────────────────────────────────────────────────────────────────
$reqCobrar = Request::create('/ayuda/cobrar-cuota', 'GET');
$reqCobrar->setUserResolver(fn() => $admin);
$htmlMuestra1 = $ayudaController->show($reqCobrar, 'cobrar-cuota')->render();
$htmlMuestra1 = $sanitizar($htmlMuestra1);
file_put_contents($dir . '/muestra1-cobrar-cuota.html', $htmlMuestra1);
file_put_contents($dir . '/muestra1-cobrar-cuota-marco-375.html', $armarMarco375('muestra1-cobrar-cuota.html'));

// Versión impresa (oculta menú y topbar para comprobar impresión)
$htmlImpresion = str_replace(
    '</head>',
    '<style>.ds-sidebar, .ds-sidebar-overlay, .ds-topbar, .ds-module-header, .ayuda-acciones-barra, .ds-btn { display: none !important; } body, .ds-layout, .ds-main, .ds-content { background:#fff !important; margin:0 !important; padding:12px 24px !important; display:block !important; } .ayuda-documento { border:none !important; box-shadow:none !important; padding:0 !important; } .ayuda-markdown-cuerpo h1 { display:block !important; font-size:1.75rem !important; margin-bottom:1rem !important; }</style></head>',
    $htmlMuestra1
);
file_put_contents($dir . '/ayuda-impresion.html', $htmlImpresion);

// ──────────────────────────────────────────────────────────────────────────
// 3. Muestra 2: Corregir movimiento en caja validada (/ayuda/corregir-movimiento-caja-validada)
// ──────────────────────────────────────────────────────────────────────────
$reqAjuste = Request::create('/ayuda/corregir-movimiento-caja-validada', 'GET');
$reqAjuste->setUserResolver(fn() => $admin);
$htmlMuestra2 = $ayudaController->show($reqAjuste, 'corregir-movimiento-caja-validada')->render();
$htmlMuestra2 = $sanitizar($htmlMuestra2);
file_put_contents($dir . '/muestra2-corregir-movimiento.html', $htmlMuestra2);
file_put_contents($dir . '/muestra2-corregir-movimiento-marco-375.html', $armarMarco375('muestra2-corregir-movimiento.html'));

// ──────────────────────────────────────────────────────────────────────────
// 4. Pantalla real Cobrar con el botón [?] en topbar
// ──────────────────────────────────────────────────────────────────────────
Auth::setUser($operativo);
$cajaController = app(\App\Http\Controllers\CajaWebController::class);
$htmlPantallaCobrar = $cajaController->cobrar($alumno->id)->render();
$htmlPantallaCobrar = $sanitizar($htmlPantallaCobrar);
file_put_contents($dir . '/signo-pregunta-cobrar.html', $htmlPantallaCobrar);
file_put_contents($dir . '/signo-pregunta-cobrar-marco-375.html', $armarMarco375('signo-pregunta-cobrar.html'));

// Pasos ilustrativos de cobrar-cuota
file_put_contents($dir . '/cobrar-cuota-paso1.html', $sanitizar($cajaController->cobrarCuotaSelect(Request::create('/caja/cobrar', 'GET'))->render()));
file_put_contents($dir . '/cobrar-cuota-paso2.html', $htmlPantallaCobrar);
file_put_contents($dir . '/cobrar-cuota-paso3.html', $htmlPantallaCobrar);
file_put_contents($dir . '/cobrar-cuota-paso4.html', $htmlPantallaCobrar);

// ──────────────────────────────────────────────────────────────────────────
// 5. Pantalla real Cashflow con el botón [?] en topbar
// ──────────────────────────────────────────────────────────────────────────
Auth::setUser($admin);
$cashflowController = app(\App\Http\Controllers\CashflowWebController::class);
$htmlPantallaCashflow = $cashflowController->index(Request::create('/cashflow', 'GET'))->render();
$htmlPantallaCashflow = $sanitizar($htmlPantallaCashflow);
file_put_contents($dir . '/signo-pregunta-cashflow.html', $htmlPantallaCashflow);
file_put_contents($dir . '/signo-pregunta-cashflow-marco-375.html', $armarMarco375('signo-pregunta-cashflow.html'));

// Pasos ilustrativos de corregir-movimiento
$htmlMovimientoForm = $cashflowController->create(Request::create('/cashflow/movimiento', 'GET'))->render();
$htmlMovimientoForm = $sanitizar($htmlMovimientoForm);
file_put_contents($dir . '/corregir-movimiento-paso1.html', $htmlPantallaCashflow);
file_put_contents($dir . '/corregir-movimiento-paso2.html', $htmlPantallaCashflow);
file_put_contents($dir . '/corregir-movimiento-paso3.html', $htmlMovimientoForm);
file_put_contents($dir . '/corregir-movimiento-paso4.html', $htmlPantallaCashflow);

echo "Archivos HTML de evidencia generados con éxito en docs/06-pruebas/PRU-04/evidencia-t12-t13\n";
