<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\PrimeraCarga;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

(new CatalogosSeeder)->run();
Configuracion::updateOrCreate(['clave' => 'dias_plazo_alta'], ['valor' => '7', 'tipo' => 'integer']);
Configuracion::updateOrCreate(['clave' => 'dias_gracia_cobranza'], ['valor' => '10', 'tipo' => 'integer']);
Configuracion::updateOrCreate(['clave' => 'porcentaje_recargo_segundo_vencimiento'], ['valor' => '10', 'tipo' => 'integer']);
PrimeraCarga::updateOrCreate(['id' => 1], ['estado' => 'COMPLETADA']);

$dir = base_path('docs/06-pruebas/PRU-04/evidencia-t54');
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$admin = User::where('rol', User::ROL_ADMIN)->where('activo', true)->first();
if (!$admin) {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'name' => 'Carlos Administrador', 'activo' => true]);
}
Auth::setUser($admin);

$deportePatin = Deporte::firstOrCreate(['nombre' => 'Patín Artístico'], ['activo' => true]);
$deporteFutbol = Deporte::firstOrCreate(['nombre' => 'Fútbol'], ['activo' => true]);

$nivelInter = Nivel::firstOrCreate(['nombre' => 'Intermedio']);
$nivelInf = Nivel::firstOrCreate(['nombre' => 'Infantil']);

$grupoPatin = Grupo::firstOrCreate(
    ['deporte_id' => $deportePatin->id, 'nivel_id' => $nivelInter->id],
    ['color' => '#10B981', 'activo' => true]
);

$grupoFutbol = Grupo::firstOrCreate(
    ['deporte_id' => $deporteFutbol->id, 'nivel_id' => $nivelInf->id],
    ['color' => '#3B82F6', 'activo' => true]
);

// Alumnos para las capturas
$alumnoGomez = Alumno::updateOrCreate(
    ['dni' => '30111222'],
    [
        'nombre' => 'Martín',
        'apellido' => 'Gómez',
        'deporte_id' => $deportePatin->id,
        'grupo_id' => $grupoPatin->id,
        'fecha_nacimiento' => '2010-05-15',
        'celular' => '11-4455-6677',
        'fecha_alta' => '2026-03-01',
        'activo' => true,
    ]
);

$alumnoGonzalez = Alumno::updateOrCreate(
    ['dni' => '32444555'],
    [
        'nombre' => 'Rodrigo',
        'apellido' => 'González',
        'deporte_id' => $deporteFutbol->id,
        'grupo_id' => $grupoFutbol->id,
        'fecha_nacimiento' => '2011-08-20',
        'celular' => '11-7788-9900',
        'fecha_alta' => '2026-03-01',
        'activo' => true,
    ]
);

$alumnoPerezJuan = Alumno::updateOrCreate(
    ['dni' => '35123456'],
    [
        'nombre' => 'Juan',
        'apellido' => 'Pérez',
        'deporte_id' => $deportePatin->id,
        'grupo_id' => $grupoPatin->id,
        'fecha_nacimiento' => '2009-12-10',
        'celular' => '11-2233-4455',
        'fecha_alta' => '2026-03-01',
        'activo' => true,
    ]
);

$alumnoPerezCarlos = Alumno::updateOrCreate(
    ['dni' => '38777888'],
    [
        'nombre' => 'Carlos',
        'apellido' => 'Pérez',
        'deporte_id' => $deporteFutbol->id,
        'grupo_id' => $grupoFutbol->id,
        'fecha_nacimiento' => '2012-04-03',
        'celular' => '11-9988-7766',
        'fecha_alta' => '2026-03-01',
        'activo' => true,
    ]
);

function generarMarco(string $nombreBase, string $dir): void
{
    $marcoHtml = '<!doctype html><meta charset="utf-8">' .
        '<body style="margin:0;background:#64748b;display:flex;justify-content:center;padding:20px 0;">' .
        '<div style="box-shadow:0 10px 25px -5px rgba(0,0,0,0.3);border-radius:12px;overflow:hidden;">' .
        '<iframe src="' . $nombreBase . '.html" style="width:375px;height:950px;border:0;background:#fff;display:block;"></iframe>' .
        '</div></body>';
    file_put_contents($dir . '/' . $nombreBase . '-marco-375.html', $marcoHtml);
}

// 1. Desplegable con una letra "g"
$req1 = Request::create('/alumnos', 'GET');
$req1->setUserResolver(fn () => $admin);
$res1 = $app->handle($req1);
if ($res1->getStatusCode() !== 200) {
    echo "STATUS: " . $res1->getStatusCode() . "\n";
    if (isset($res1->exception)) {
        echo "EXCEPTION: " . $res1->exception->getMessage() . "\n";
        echo $res1->exception->getFile() . ":" . $res1->exception->getLine() . "\n";
    }
    exit(1);
}
$html1 = $res1->getContent();

// Insertamos la simulación del dropdown abierto con 'g' exactamente como lo renderiza alumnos-index.js
$dropdownItems = '<ul id="search-dropdown" role="listbox" style="position: absolute; top: calc(100% + 4px); left: 0px; right: 0px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-md); list-style: none; margin: 0px; padding: 4px 0px; z-index: 50; display: block;">' .
    '<li role="option" data-index="0" style="padding: 8px 12px; cursor: pointer; display: flex; flex-direction: column; gap: 1px; background: var(--color-surface-alt);">' .
    '<span style="font-size: 0.82rem; font-weight: 600; color: var(--color-text);">Gómez, Martín</span>' .
    '<span style="font-size: 0.7rem; color: var(--color-text-muted);">30111222 · Patín Artístico — Intermedio</span>' .
    '</li>' .
    '<li role="option" data-index="1" style="padding: 8px 12px; cursor: pointer; display: flex; flex-direction: column; gap: 1px;">' .
    '<span style="font-size: 0.82rem; font-weight: 600; color: var(--color-text);">González, Rodrigo</span>' .
    '<span style="font-size: 0.7rem; color: var(--color-text-muted);">32444555 · Fútbol — Infantil</span>' .
    '</li>' .
    '<li role="separator" style="height: 1px; background: var(--color-border); margin: 4px 0px;"></li>' .
    '<li role="option" style="padding: 8px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px;">' .
    '<svg style="width: 13px; height: 13px; flex-shrink: 0; color: var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>' .
    '<span style="font-size: 0.78rem; color: var(--color-btn-primary); font-weight: 500;">Ver todos los resultados</span>' .
    '</li>' .
    '</ul>';

$html1 = str_replace('value=""', 'value="g"', $html1);
$html1 = str_replace('<ul id="search-dropdown"', $dropdownItems . '<!-- <ul id="search-dropdown-old"', $html1);
file_put_contents($dir . '/01-desplegable-una-letra.html', $html1);
generarMarco('01-desplegable-una-letra', $dir);
echo "Generado 01-desplegable-una-letra.html y marco\n";

// 2. Búsqueda "gomez" encontrando a "Gómez"
$req2 = Request::create('/alumnos', 'GET', ['search' => 'gomez']);
$req2->setUserResolver(fn () => $admin);
$res2 = $app->handle($req2);
if ($res2->getStatusCode() !== 200) {
    throw new Exception("Error en /alumnos?search=gomez status code: " . $res2->getStatusCode());
}
$html2 = $res2->getContent();
file_put_contents($dir . '/02-gomez-encuentra-gomez.html', $html2);
generarMarco('02-gomez-encuentra-gomez', $dir);
echo "Generado 02-gomez-encuentra-gomez.html y marco\n";

// 3. Búsqueda "perez juan" encontrando a Juan Pérez
$req3 = Request::create('/alumnos', 'GET', ['search' => 'perez juan']);
$req3->setUserResolver(fn () => $admin);
$res3 = $app->handle($req3);
if ($res3->getStatusCode() !== 200) {
    throw new Exception("Error en /alumnos?search=perez juan status code: " . $res3->getStatusCode());
}
$html3 = $res3->getContent();
file_put_contents($dir . '/03-perez-juan.html', $html3);
generarMarco('03-perez-juan', $dir);
echo "Generado 03-perez-juan.html y marco\n";

echo "Archivos HTML listos para captura con Chrome.\n";
