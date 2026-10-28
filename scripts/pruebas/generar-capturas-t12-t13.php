<?php

putenv('DB_DATABASE=wings_manual_gemini');
$_ENV['DB_DATABASE'] = 'wings_manual_gemini';

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CashflowMovimiento;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CashflowService;
use App\Services\ManualService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

Carbon::setTestNow('2026-10-28 17:00:00');

$dirEvidencia = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13');
if (!is_dir($dirEvidencia)) {
    mkdir($dirEvidencia, 0775, true);
}

$dirImgOperativo = resource_path('manual/img/operativo');
$dirImgAdmin = resource_path('manual/img/admin');
File::ensureDirectoryExists($dirImgOperativo);
File::ensureDirectoryExists($dirImgAdmin);

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
$subrubroCuotas = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroCuotas->id, 'nombre' => 'Cuota Mensual'],
    ['permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true, 'es_reservado_sistema' => true]
);

$rubroAjustes = Rubro::firstOrCreate(['nombre' => 'Ajustes y compensaciones'], ['tipo' => 'EGRESO', 'observacion' => '']);
$subrubroAjustes = Subrubro::firstOrCreate(
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
        'monto_original'    => 25000,
        'saldo_pendiente'   => 25000,
        'fecha_vencimiento' => '2026-10-10',
        'estado'            => 'PENDIENTE',
    ]
);

\Tests\Support\CajaDeclarada::crear($admin->id, $tipoCaja->id);
\Tests\Support\CajaDeclarada::crear($operativo->id, $tipoCaja->id);

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

$manualService = app(ManualService::class);
$ayudaController = app(\App\Http\Controllers\AyudaWebController::class);
$cajaController = app(\App\Http\Controllers\CajaWebController::class);
$cashflowController = app(\App\Http\Controllers\CashflowWebController::class);
$cashflowService = app(CashflowService::class);

// ──────────────────────────────────────────────────────────────────────────
// A. Pasos REALES e individuales de «Cobrar una cuota»
// ──────────────────────────────────────────────────────────────────────────
Auth::setUser($operativo);

// Paso 1: Buscar alumno en el selector de cobro
$htmlCobrarPaso1 = $sanitizar($cajaController->cobrarCuotaSelect(Request::create('/caja/cobrar', 'GET'))->render());
file_put_contents($dirEvidencia . '/cobrar-cuota-paso1.html', $htmlCobrarPaso1);

// Paso 2: Elegir qué cobrar (pantalla con cuota tildada y total calculado)
$htmlCobrarBase = $cajaController->cobrar($alumno->id)->render();
$htmlCobrarPaso2 = preg_replace(
    '/(name="periodos\[\]"\s+value="2026-10"[^>]*)/',
    '$1 checked="checked"',
    $htmlCobrarBase
);
$htmlCobrarPaso2 = str_replace('required disabled', 'required', $htmlCobrarPaso2);
$htmlCobrarPaso2 = str_replace('required="" disabled=""', 'required=""', $htmlCobrarPaso2);
$htmlCobrarPaso2 = str_replace(
    'id="resumen-total" class="mt-4 pt-4" style="border-top:1px solid var(--color-border); display:none;"',
    'id="resumen-total" class="mt-4 pt-4" style="border-top:1px solid var(--color-border); display:block;"',
    $htmlCobrarPaso2
);
$htmlCobrarPaso2 = preg_replace(
    '/(<span id="total-label"[^>]*>)[^<]*(<\/span>)/',
    '$1$ 25.000$2',
    $htmlCobrarPaso2
);
$htmlCobrarPaso2 = str_replace(
    'id="btn-cobrar" disabled class="ds-btn" style="background:var(--color-btn-primary); color:var(--color-surface); opacity:0.4; cursor:not-allowed;"',
    'id="btn-cobrar" class="ds-btn" style="background:var(--color-btn-primary); color:var(--color-surface); opacity:1; cursor:pointer;"',
    $htmlCobrarPaso2
);
$htmlCobrarPaso2 = str_replace(
    'id="btn-cobrar" disabled="" class="ds-btn" style="background:var(--color-btn-primary); color:var(--color-surface); opacity:0.4; cursor:not-allowed;"',
    'id="btn-cobrar" class="ds-btn" style="background:var(--color-btn-primary); color:var(--color-surface); opacity:1; cursor:pointer;"',
    $htmlCobrarPaso2
);
file_put_contents($dirEvidencia . '/cobrar-cuota-paso2.html', $sanitizar($htmlCobrarPaso2));

// Paso 3: Indicar cómo paga (cuota tildada, Efectivo seleccionado, observaciones cargadas)
$htmlCobrarPaso3 = str_replace(
    '<option value="' . $tipoCaja->id . '" >',
    '<option value="' . $tipoCaja->id . '" selected="selected">',
    $htmlCobrarPaso2
);
$htmlCobrarPaso3 = str_replace(
    '<option value="' . $tipoCaja->id . '">',
    '<option value="' . $tipoCaja->id . '" selected="selected">',
    $htmlCobrarPaso3
);
$htmlCobrarPaso3 = str_replace(
    'placeholder="Opcional">',
    'placeholder="Opcional" value="Pago cuota octubre en mostrador">',
    $htmlCobrarPaso3
);
file_put_contents($dirEvidencia . '/cobrar-cuota-paso3.html', $sanitizar($htmlCobrarPaso3));

// Paso 4: Recibo oficial emitido (render del comprobante oficial de Wings)
$datosRecibo = [
    'numero_recibo'      => 'CUOTA-1042',
    'fecha_emision'      => Carbon::now(),
    'fecha_pago'         => Carbon::now()->toDateString(),
    'anulado'            => false,
    'fecha_cancelacion'  => null,
    'cancelado_por'      => null,
    'motivo_cancelacion' => null,
    'alumno'             => [
        'nombre'  => 'Martina Gómez',
        'dni'     => '44.888.999',
        'deporte' => 'Patín Artístico',
    ],
    'periodos'           => [
        [
            'periodo'        => '2026-10',
            'periodo_texto'  => 'Cuota Octubre 2026',
            'monto_aplicado' => 25000,
        ],
    ],
    'monto_total'        => 25000,
    'medio_cobro'        => [
        'tipo_caja' => 'Efectivo',
        'origen'    => 'Operativo',
    ],
    'observaciones'      => 'Pago cuota octubre en mostrador',
];
$htmlReciboInner = view('pdfs.recibo-cuota', $datosRecibo)->render();
$htmlCobrarPaso4 = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recibo CUOTA-1042 – Wings</title>' .
    '<style>body{background:#0f172a;margin:0;padding:24px 0;display:flex;justify-content:center;font-family:\'DejaVu Sans\',Arial,sans-serif;}' .
    '.recibo-marco{background:#fff;width:560px;padding:24px;border-radius:8px;box-shadow:0 12px 30px rgba(0,0,0,0.5);}</style></head>' .
    '<body><div class="recibo-marco">' . $htmlReciboInner . '</div></body></html>';
file_put_contents($dirEvidencia . '/cobrar-cuota-paso4.html', $htmlCobrarPaso4);

// Pantalla cobrar con signo de pregunta
file_put_contents($dirEvidencia . '/signo-pregunta-cobrar.html', $sanitizar($htmlCobrarBase));
file_put_contents($dirEvidencia . '/signo-pregunta-cobrar-marco-375.html', $armarMarco375('signo-pregunta-cobrar.html'));


// ──────────────────────────────────────────────────────────────────────────
// B. Pasos REALES de «Corregir un movimiento mal cargado en caja validada»
// ──────────────────────────────────────────────────────────────────────────
Auth::setUser($admin);

// Asegurar saldo inicial en Cashflow para que no esté en $0
CashflowMovimiento::firstOrCreate(
    ['observaciones' => 'Saldo inicial de apertura del ciclo'],
    [
        'fecha'            => '2026-10-01',
        'subrubro_id'      => $subrubroCuotas->id,
        'tipo_caja_id'     => $tipoCaja->id,
        'monto'            => 120000,
        'usuario_admin_id' => $admin->id,
        'referencia_tipo'  => null,
        'referencia_id'    => null,
    ]
);

// Paso 1: Cashflow con saldo y movimientos cargados
$reqCf = Request::create('/cashflow', 'GET');
$reqCf->setUserResolver(fn() => $admin);
$htmlCashflowPaso1 = $sanitizar($cashflowController->index($reqCf)->render());
file_put_contents($dirEvidencia . '/corregir-movimiento-paso1.html', $htmlCashflowPaso1);
file_put_contents($dirEvidencia . '/signo-pregunta-cashflow.html', $htmlCashflowPaso1);
file_put_contents($dirEvidencia . '/signo-pregunta-cashflow-marco-375.html', $armarMarco375('signo-pregunta-cashflow.html'));

// Paso 2: Cashflow destacando el botón Nuevo
$htmlCashflowPaso2 = preg_replace(
    '/(<a\s+class="ds-btn\s+ds-btn--primary"\s+href="[^"]*\/cashflow\/movimiento"[^>]*>)/s',
    '$1<span style="position:absolute; top:-10px; right:-10px; background:var(--color-warning); color:#000; font-size:10px; font-weight:800; padding:2px 6px; border-radius:999px; box-shadow:0 2px 6px rgba(0,0,0,0.3);">PASO 2</span>',
    $htmlCashflowPaso1
);
$htmlCashflowPaso2 = preg_replace(
    '/(<a\s+class="ds-btn\s+ds-btn--primary")/s',
    '$1 style="position:relative; outline:3px solid var(--color-warning); box-shadow:0 0 16px var(--color-warning); transform:scale(1.05);"',
    $htmlCashflowPaso2
);
file_put_contents($dirEvidencia . '/corregir-movimiento-paso2.html', $htmlCashflowPaso2);

// Paso 3: Formulario de nuevo movimiento COMPLETO con datos de ajuste
session()->flashInput([
    'tipo_ie'       => 'EGRESO',
    'rubro_id'      => $rubroAjustes->id,
    'subrubro_id'   => $subrubroAjustes->id,
    'tipo_caja_id'  => $tipoCaja->id,
    'monto'         => '15000',
    'fecha'         => '2026-10-28',
    'observaciones' => 'Ajuste compensatorio por cobro duplicado a Alumno Pérez en turno mañana del 15/10/2026, caja ya validada (DU-27)',
]);
$reqMovForm = Request::create('/cashflow/movimiento', 'GET');
$reqMovForm->setUserResolver(fn() => $admin);
$htmlMovForm = $sanitizar($cashflowController->create($reqMovForm)->render());
// Asegurar que el botón EGRESO esté visualmente activo
$htmlMovForm = str_replace(
    'id="btn-egreso"',
    'id="btn-egreso" style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid var(--color-danger); background:color-mix(in srgb, var(--color-danger) 15%, transparent); color:var(--color-danger); border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700;"',
    $htmlMovForm
);
file_put_contents($dirEvidencia . '/corregir-movimiento-paso3.html', $htmlMovForm);

// Paso 4: Ajuste ya guardado e impactado en Cashflow
$ajusteGuardado = CashflowMovimiento::firstOrCreate(
    ['observaciones' => 'Ajuste compensatorio por cobro duplicado a Alumno Pérez en turno mañana del 15/10/2026, caja ya validada (DU-27)'],
    [
        'usuario_admin_id' => $admin->id,
        'tipo_caja_id'     => $tipoCaja->id,
        'subrubro_id'      => $subrubroAjustes->id,
        'monto'            => -15000,
        'fecha'            => '2026-10-28',
        'referencia_tipo'  => null,
        'referencia_id'    => null,
    ]
);
$htmlCashflowPaso4 = $sanitizar($cashflowController->index($reqCf)->render());
file_put_contents($dirEvidencia . '/corregir-movimiento-paso4.html', $htmlCashflowPaso4);

echo "Archivos HTML de pasos de muestra generados con éxito.\n";
