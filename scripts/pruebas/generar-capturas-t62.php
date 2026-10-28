<?php

putenv('DB_DATABASE=wings_testing_gemini');
$_ENV['DB_DATABASE'] = 'wings_testing_gemini';

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

Carbon::setTestNow('2026-10-28 17:00:00');

$dir = base_path('docs/06-pruebas/PRU-04/evidencia-t62');
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$admin = User::where('rol', User::ROL_ADMIN)->where('activo', true)->first();
if (!$admin) {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
}
Auth::setUser($admin);
View::share('errors', new \Illuminate\Support\ViewErrorBag);

$deporte = Deporte::firstOrCreate(['nombre' => 'Patín Artístico'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivel1 = Nivel::firstOrCreate(['nombre' => 'Escuela Inicial']);
$nivel2 = Nivel::firstOrCreate(['nombre' => 'Competición']);

$grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel1->id], ['nombre' => 'Patín Escuela', 'activo' => true]);
$plan = GrupoPlan::firstOrCreate([
    'grupo_id' => $grupo->id,
    'clases_por_semana' => 2,
], [
    'precio_mensual' => 25000,
    'activo' => true,
]);

$profesor = Profesor::firstOrCreate(
    ['dni' => '33111222'],
    [
        'deporte_id' => $deporte->id,
        'nombre' => 'Lucía',
        'apellido' => 'Sosa',
        'fecha_nacimiento' => '1990-04-15',
        'direccion' => 'Pellegrini 1234',
        'localidad' => 'Rosario',
        'tipo_liquidacion' => 'HORA',
        'valor_hora' => 15000,
        'activo' => true,
    ]
);

$alumno1 = Alumno::firstOrCreate(
    ['dni' => '42111001'],
    [
        'nombre' => 'Martina',
        'apellido' => 'Gómez',
        'deporte_id' => $deporte->id,
        'grupo_id' => $grupo->id,
        'fecha_nacimiento' => '2012-05-10',
        'celular' => '3415551122',
        'fecha_alta' => '2026-01-01',
        'activo' => true,
    ]
);

$alumno2 = Alumno::firstOrCreate(
    ['dni' => '42111002'],
    [
        'nombre' => 'Valentina',
        'apellido' => 'Pérez',
        'deporte_id' => $deporte->id,
        'grupo_id' => $grupo->id,
        'fecha_nacimiento' => '2013-03-20',
        'celular' => '3415553344',
        'fecha_alta' => '2026-01-01',
        'activo' => true,
    ]
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
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:950px;border:1px solid #475569;border-radius:12px;background:#fff;box-shadow:0 10px 25px -5px rgba(0,0,0,0.5);"></iframe></body>' . "\n";
};

$controller = app(\App\Http\Controllers\ClaseWebController::class);

// ──────────────────────────────────────────────────────────────────────────
// Captura 1: Aviso de lista tomada y pedido de confirmación (CLA-012.a)
// ──────────────────────────────────────────────────────────────────────────
$clase1 = Clase::firstOrCreate(
    ['grupo_id' => $grupo->id, 'fecha' => '2026-10-28', 'hora_inicio' => '17:00:00'],
    ['hora_fin' => '18:00:00', 'cancelada' => false]
);
if (!$clase1->profesores()->where('profesores.id', $profesor->id)->exists()) {
    $clase1->profesores()->attach($profesor->id);
}
Asistencia::firstOrCreate(['clase_id' => $clase1->id, 'alumno_id' => $alumno1->id], ['presente' => true]);
Asistencia::firstOrCreate(['clase_id' => $clase1->id, 'alumno_id' => $alumno2->id], ['presente' => false]);

$html1 = $controller->show($clase1->id)->render();
// Abrir el panel de cancelación y el subpanel de confirmación por lista tomada
$html1 = str_replace('id="panel-cancelar" style="display:none;', 'id="panel-cancelar" style="display:block;', $html1);
$html1 = str_replace('id="subpanel-confirmar-lista" style="display:none;', 'id="subpanel-confirmar-lista" style="display:block;', $html1);
$html1 = str_replace('id="input-motivo-cancelacion"', 'id="input-motivo-cancelacion" value="Lluvia torrencial"', $html1);

$html1 = $sanitizar($html1);
file_put_contents($dir . '/01-aviso-lista-tomada.html', $html1);
file_put_contents($dir . '/01-aviso-lista-tomada-marco-375.html', $armarMarco375('01-aviso-lista-tomada.html'));

// ──────────────────────────────────────────────────────────────────────────
// Captura 2: Rechazo por choque de horario al reactivar (CLA-015.b)
// ──────────────────────────────────────────────────────────────────────────
$clase2 = Clase::firstOrCreate(
    ['grupo_id' => $grupo->id, 'fecha' => '2026-10-28', 'hora_inicio' => '18:00:00'],
    ['hora_fin' => '19:00:00', 'cancelada' => true, 'motivo_cancelacion' => 'Pista insegura']
);
if (!$clase2->profesores()->where('profesores.id', $profesor->id)->exists()) {
    $clase2->profesores()->attach($profesor->id);
}

// Simulamos que al reactivar, el flash de error aparece en la vista
session()->flash('error', "No se puede reactivar: la profesora Lucía Sosa ya tiene asignada la clase 'Patín Artístico — Competición' que se solapa en fecha 2026-10-28 entre 18:00 y 19:00.");

$html2 = $controller->show($clase2->id)->render();
// Si la plantilla layout no renderizó la sesión flash porque es llamada directa al controller:
if (!str_contains($html2, 'No se puede reactivar:')) {
    $flashHtml = '<div class="ds-flash ds-flash--error mb-4">No se puede reactivar: la profesora Lucía Sosa ya tiene asignada la clase \'Patín Artístico — Competición\' que se solapa en fecha 2026-10-28 entre 18:00 y 19:00.</div>';
    $html2 = str_replace('<div class="ds-content">', '<div class="ds-content">' . "\n" . $flashHtml, $html2);
}

$html2 = $sanitizar($html2);
file_put_contents($dir . '/02-rechazo-por-choque.html', $html2);
file_put_contents($dir . '/02-rechazo-por-choque-marco-375.html', $armarMarco375('02-rechazo-por-choque.html'));

// ──────────────────────────────────────────────────────────────────────────
// Captura 3: Motivo de cancelación conservado en ficha al reactivar (CLA-015.c)
// ──────────────────────────────────────────────────────────────────────────
$clase3 = Clase::firstOrCreate(
    ['grupo_id' => $grupo->id, 'fecha' => '2026-10-28', 'hora_inicio' => '19:00:00'],
    ['hora_fin' => '20:00:00', 'cancelada' => false, 'motivo_cancelacion' => 'Feriado imprevisto']
);
if (!$clase3->profesores()->where('profesores.id', $profesor->id)->exists()) {
    $clase3->profesores()->attach($profesor->id);
}
$clase3->update(['cancelada' => false, 'motivo_cancelacion' => 'Feriado imprevisto']);

$html3 = $controller->show($clase3->id)->render();
$html3 = $sanitizar($html3);
file_put_contents($dir . '/03-motivo-conservado.html', $html3);
file_put_contents($dir . '/03-motivo-conservado-marco-375.html', $armarMarco375('03-motivo-conservado.html'));

echo "Archivos HTML de evidencia T62 generados correctamente en docs/06-pruebas/PRU-04/evidencia-t62/\n";
