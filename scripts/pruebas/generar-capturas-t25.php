<?php

putenv('DB_DATABASE=wings_testing_gemini');
$_ENV['DB_DATABASE'] = 'wings_testing_gemini';

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
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\Auth;

Carbon::setTestNow('2026-10-20 10:00:00');

$dir = base_path('docs/06-pruebas/PRU-04/evidencia-t25');
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$admin = User::where('rol', User::ROL_ADMIN)->where('activo', true)->first();
if (!$admin) {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
}
Auth::setUser($admin);
\Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);

$deporte = Deporte::firstOrCreate(['nombre' => 'Patín T25'], ['tipo_liquidacion' => 'COMISION', 'activo' => true]);
$nivel = Nivel::firstOrCreate(['nombre' => 'Nivel T25']);
$grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id], ['activo' => true]);
$plan = GrupoPlan::firstOrCreate([
    'grupo_id' => $grupo->id,
    'clases_por_semana' => 2,
], [
    'precio_mensual' => 10000,
    'activo' => true,
]);

$alumno = Alumno::firstOrCreate(
    ['dni' => '25000009'],
    [
        'apellido' => 'Inactivo',
        'nombre' => 'Ana',
        'deporte_id' => $deporte->id,
        'grupo_id' => $grupo->id,
        'fecha_nacimiento' => '2012-01-01',
        'celular' => '11-4000-0000',
        'fecha_alta' => '2026-03-01',
        'activo' => false,
    ]
);
$alumno->update(['activo' => false]);

AlumnoPlan::firstOrCreate([
    'alumno_id' => $alumno->id,
    'plan_id' => $plan->id,
], [
    'fecha_desde' => '2026-03-01',
    'activo' => true,
]);

DeudaCuota::firstOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => '2026-09'],
    ['monto_original' => 10000, 'monto_pagado' => 0, 'saldo_pendiente' => 10000, 'estado' => DeudaCuota::ESTADO_PENDIENTE]
);

DeudaCuota::firstOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => '2026-10'],
    ['monto_original' => 10000, 'monto_pagado' => 0, 'saldo_pendiente' => 10000, 'estado' => DeudaCuota::ESTADO_PENDIENTE]
);

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
           '<body style="margin:0;background:#1e293b;display:flex;justify-content:center;align-items:flex-start;padding:24px 0;">' . "\n" .
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:900px;border:1px solid #475569;border-radius:12px;background:#fff;box-shadow:0 10px 25px -5px rgba(0,0,0,0.5);"></iframe></body>' . "\n";
};

$controller = app(\App\Http\Controllers\CajaWebController::class);
$viewResult = $controller->cobrar($alumno->id)->render();

// Caso 1: Deuda vieja (Septiembre). Muestra panel con Sí, No y Cancelar.
$html1 = $viewResult;
// Marcar checkbox 2026-09 como checked
$html1 = str_replace('value="2026-09" class="cuota-check"', 'value="2026-09" class="cuota-check" checked', $html1);
// Mostrar modal-reactivar con Sí, No, Cancelar
$mensaje1 = "{$alumno->apellido}, {$alumno->nombre} está inactivo. ¿Pasa a activo? Si vuelve, se le genera la cuota de octubre.";
$html1 = str_replace('id="modal-reactivar" class="alumno-card mt-4" style="display:none;"', 'id="modal-reactivar" class="alumno-card mt-4" style="display:block;"', $html1);
$html1 = str_replace('<p id="mensaje-reactivar" role="status" style="font-size:0.85rem; margin-bottom:1rem;"></p>', '<p id="mensaje-reactivar" role="status" style="font-size:0.85rem; margin-bottom:1rem;">' . htmlspecialchars($mensaje1) . '</p>', $html1);
// Agregar script para autoscroll hacia el panel
$scrollScript = '<script>document.addEventListener("DOMContentLoaded", function() { var el = document.getElementById("modal-reactivar"); if (el) el.scrollIntoView({block:"center"}); });</script>';
$html1 = str_replace('</body>', $scrollScript . '</body>', $html1);

$html1 = $sanitizar($html1);
file_put_contents($dir . '/01-deuda-vieja-si-no-cancelar.html', $html1);
file_put_contents($dir . '/01-deuda-vieja-si-no-cancelar-marco-375.html', $armarMarco375('01-deuda-vieja-si-no-cancelar.html'));

// Caso 2: Cobro que incluye el mes (Octubre). Muestra panel con Sí y Cancelar (No oculto).
$html2 = $viewResult;
// Marcar checkbox 2026-09 y 2026-10 como checked
$html2 = str_replace('value="2026-09" class="cuota-check"', 'value="2026-09" class="cuota-check" checked', $html2);
$html2 = str_replace('value="2026-10" class="cuota-check"', 'value="2026-10" class="cuota-check" checked', $html2);
// Mostrar modal-reactivar con solo Sí y Cancelar
$mensaje2 = "{$alumno->apellido}, {$alumno->nombre} está inactivo. ¿Pasa a activo? El cobro incluye el mes en curso, así que para cobrarlo tiene que volver.";
$html2 = str_replace('id="modal-reactivar" class="alumno-card mt-4" style="display:none;"', 'id="modal-reactivar" class="alumno-card mt-4" style="display:block;"', $html2);
$html2 = str_replace('<p id="mensaje-reactivar" role="status" style="font-size:0.85rem; margin-bottom:1rem;"></p>', '<p id="mensaje-reactivar" role="status" style="font-size:0.85rem; margin-bottom:1rem;">' . htmlspecialchars($mensaje2) . '</p>', $html2);
// Ocultar botón No
$html2 = str_replace('id="reactivar-no"', 'id="reactivar-no" style="display:none;"', $html2);
$html2 = str_replace('</body>', $scrollScript . '</body>', $html2);

$html2 = $sanitizar($html2);
file_put_contents($dir . '/02-cobro-con-el-mes-si-cancelar.html', $html2);
file_put_contents($dir . '/02-cobro-con-el-mes-si-cancelar-marco-375.html', $armarMarco375('02-cobro-con-el-mes-si-cancelar.html'));

echo "Archivos HTML de evidencia generados en docs/06-pruebas/PRU-04/evidencia-t25/\n";
