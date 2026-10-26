<?php

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
use App\Models\PrimeraCarga;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Carbon::setTestNow('2026-10-26 12:00:00');

PrimeraCarga::updateOrCreate(['id' => 1], ['estado' => 'COMPLETADA']);

$dir = base_path('docs/06-pruebas/PRU-04/evidencia-t84');
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$admin = User::where('rol', User::ROL_ADMIN)->where('activo', true)->first();
if (!$admin) {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
}
Auth::setUser($admin);

$deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
$grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
$plan = GrupoPlan::firstOrCreate([
    'grupo_id' => $grupo->id,
    'clases_por_semana' => 2,
    'precio_mensual' => 48000,
    'activo' => true,
]);

$alumno = Alumno::firstOrCreate(
    ['dni' => '40999888'],
    [
        'apellido' => 'Sosa',
        'nombre' => 'Martina',
        'deporte_id' => $deporte->id,
        'grupo_id' => $grupo->id,
        'fecha_nacimiento' => '2008-03-15',
        'celular' => '11-3333-4444',
        'fecha_alta' => '2026-09-01',
        'activo' => true,
    ]
);
$alumno->update(['activo' => true]);

AlumnoPlan::firstOrCreate([
    'alumno_id' => $alumno->id,
    'plan_id' => $plan->id,
    'fecha_desde' => '2026-09-01',
    'activo' => true,
]);

DeudaCuota::firstOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => '2026-09'],
    ['monto_original' => 48000, 'monto_pagado' => 48000, 'saldo_pendiente' => 0, 'estado' => 'PAGADA']
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
           '<body style="margin:0;background:#334155;display:flex;justify-content:center;align-items:flex-start;padding:24px 0;">' . "\n" .
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:950px;border:1px solid #64748b;border-radius:12px;background:#fff;box-shadow:0 10px 25px -5px rgba(0,0,0,0.3);"></iframe></body>' . "\n";
};

$controller = app(\App\Http\Controllers\AlumnoWebController::class);
$cobranzaService = app(\App\Services\CobranzaEstadoService::class);

// 1. Ficha Alumno ACTIVO
$htmlActivo = $sanitizar($controller->show($alumno->id, $cobranzaService)->render());
file_put_contents($dir . '/01-ficha-alumno-activo.html', $htmlActivo);
file_put_contents($dir . '/01-ficha-alumno-activo-marco-375.html', $armarMarco375('01-ficha-alumno-activo.html'));

// 2. Ficha Alumno INACTIVO
$alumno->update(['activo' => false]);
$htmlInactivo = $sanitizar($controller->show($alumno->id, $cobranzaService)->render());
file_put_contents($dir . '/02-ficha-alumno-inactivo.html', $htmlInactivo);
file_put_contents($dir . '/02-ficha-alumno-inactivo-marco-375.html', $armarMarco375('02-ficha-alumno-inactivo.html'));

// 3. Ficha Alumno REACTIVADO CON CUOTA GENERADA Y AVISO FLASH
$alumno->update(['activo' => true]);
// Crear cuota reingreso si no existe
$cuotaReingreso = DeudaCuota::firstOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => '2026-10'],
    ['monto_original' => 48000, 'monto_pagado' => 0, 'saldo_pendiente' => 48000, 'estado' => 'PENDIENTE', 'porcentaje_alta' => 100]
);

$htmlReactivado = $controller->show($alumno->id, $cobranzaService)->render();

// Inyectar el mensaje de flash success en ds-content tal como hace ds-app.js al recargar
$flashHtml = '<div class="ds-flash ds-flash--success">Alumno activado correctamente. Se generó la cuota de octubre: $48.000 (100 % por reingreso).</div>';
$htmlReactivado = preg_replace('/(<main class="ds-content">)/', '$1' . "\n" . $flashHtml, $htmlReactivado);

$htmlReactivado = $sanitizar($htmlReactivado);
file_put_contents($dir . '/03-ficha-alumno-reactivado-cuota.html', $htmlReactivado);
file_put_contents($dir . '/03-ficha-alumno-reactivado-cuota-marco-375.html', $armarMarco375('03-ficha-alumno-reactivado-cuota.html'));

echo "HTMLs generados correctamente en docs/06-pruebas/PRU-04/evidencia-t84/\n";
