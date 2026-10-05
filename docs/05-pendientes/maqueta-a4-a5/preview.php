<?php

// Maqueta local: renderiza las vistas existentes, no carga ni modifica registros.
// Arrancar SOLO en 127.0.0.1 con APP_ENV=testing y DB_DATABASE=wings_testing_codex.
if (PHP_SAPI !== 'cli-server' || getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    http_response_code(403);
    exit('Solo vista previa local en testing / wings_testing_codex.');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/build/') || str_starts_with($path, '/favicon') || str_starts_with($path, '/images/')) return false;
if ($path === '/propuesta.js') {
    header('Content-Type: application/javascript');
    readfile(__DIR__.'/propuesta.js');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('La maqueta no guarda.');
}
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') throw new RuntimeException('Base ajena a Codex.');
config(['session.driver' => 'array', 'view.compiled' => $root.'/storage/framework/views/a4a5-propuesta']);
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8796');
Carbon\Carbon::setTestNow('2026-10-05 12:00:00');
session()->start();
request()->setLaravelSession(session()->driver());
Illuminate\Support\Facades\Auth::setUser((new App\Models\User)->forceFill(['id' => 97001, 'name' => 'Maqueta · datos ficticios', 'rol' => 'ADMIN', 'activo' => true]));
$patin = (new App\Models\Deporte)->forceFill(['id' => 97101, 'nombre' => 'Patín', 'activo' => true]);
$futbol = (new App\Models\Deporte)->forceFill(['id' => 97102, 'nombre' => 'Fútbol', 'activo' => true]);
$nivel = (new App\Models\Nivel)->forceFill(['id' => 97201, 'nombre' => 'Inicial']);
$grupos = collect([$patin, $futbol])->map(fn($deporte, $i) => (new App\Models\Grupo)->forceFill(['id' => 97301 + $i, 'deporte_id' => $deporte->id, 'nivel_id' => $nivel->id])->setRelation('deporte', $deporte)->setRelation('nivel', $nivel));
$grupo = $grupos->first();
$plan = (new App\Models\GrupoPlan)->forceFill(['id' => 97401, 'grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000]);
$profesores = collect([
    (new App\Models\Profesor)->forceFill(['id' => 97501, 'apellido' => 'Prueba', 'nombre' => 'Ana', 'deporte_id' => $patin->id])->setRelation('deporte', $patin),
    (new App\Models\Profesor)->forceFill(['id' => 97502, 'apellido' => 'Prueba', 'nombre' => 'Luis', 'deporte_id' => $futbol->id])->setRelation('deporte', $futbol),
]);
$alumno = (new App\Models\Alumno)->forceFill(['id' => 97601, 'nombre' => 'Alumno', 'apellido' => 'Prueba', 'dni' => '47000101', 'fecha_nacimiento' => '2016-05-12', 'fecha_alta' => '2026-10-05', 'celular' => '1111111111', 'deporte_id' => $patin->id, 'grupo_id' => $grupo->id])->setRelation('deporte', $patin)->setRelation('grupo', $grupo);
$alumno->setRelation('planActivo', (new App\Models\AlumnoPlan)->setRelation('plan', $plan)->forceFill(['plan_id' => $plan->id]));
$clase = (new App\Models\Clase)->forceFill(['id' => 97701, 'grupo_id' => $grupo->id, 'fecha' => '2026-10-06', 'hora_inicio' => '17:00', 'hora_fin' => '18:00', 'cancelada' => false])->setRelation('grupo', $grupo)->setRelation('profesores', collect([$profesores->first()]))->setRelation('asistencias', collect());
$screen = $_GET['pantalla'] ?? 'alta-alumno';
$views = ['alta-alumno' => 'alumnos.create', 'editar-alumno' => 'alumnos.edit', 'alta-clase' => 'clases.create', 'editar-clase' => 'clases.edit', 'ficha-clase' => 'clases.show'];
if (!isset($views[$screen])) { http_response_code(404); exit; }
$esAlumno = str_contains($screen, 'alumno');
$datos = ['deportes' => collect([$patin, $futbol]), 'grupos' => $grupos, 'grupoPlanesJson' => [$grupo->id => [['id' => $plan->id, 'clases' => 2, 'precio' => 30000]]], 'clase' => $clase, 'profesores' => $screen === 'editar-clase' ? $profesores->where('deporte_id', $patin->id) : $profesores, 'alumnos' => collect(), 'asistenciasMap' => collect(), 'infoSemana' => [], 'profesoresDisponibles' => $profesores->where('deporte_id', $patin->id), 'esAdmin' => true, 'esProfesor' => false, 'esPasada' => false, 'correccionAsistencia' => false, 'correccionProfesores' => false];
if ($screen === 'editar-alumno') $datos['alumno'] = $alumno;
$errores = $esAlumno ? ['nombre_tutor' => 'El nombre del tutor es obligatorio para menores de edad.', 'telefono_tutor' => 'El teléfono del tutor es obligatorio para menores de edad.'] : [];
$bag = (new Illuminate\Support\ViewErrorBag)->put('default', new Illuminate\Support\MessageBag($errores));
Illuminate\Support\Facades\View::share('errors', $bag);
session()->flashInput(['nombre' => 'Alumno', 'apellido' => 'Prueba', 'dni' => '47000101', 'fecha_nacimiento' => '2016-05-12', 'fecha_alta' => '2026-10-05', 'celular' => '1111111111', 'deporte_id' => $patin->id, 'grupo_id' => $grupo->id, 'plan_id' => $plan->id, 'fecha' => '2026-10-06', 'hora_inicio' => '17:00', 'hora_fin' => '18:00']);
$html = view($views[$screen], $datos)->render();
if ($esAlumno) $html = str_replace(' autofocus', '', $html);
// No se ejecuta el JavaScript de la aplicación ni sus peticiones contra una base real.
$html = preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $html);
$html = preg_replace('/<link\b[^>]*rel="modulepreload"[^>]*>/', '', $html);
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$css = '/build/'.$manifest['resources/css/app.css']['file'];
$html = preg_replace('/(<link[^>]*rel="stylesheet"[^>]*href=")[^"]+("[^>]*>)/', '$1'.$css.'$2', $html);
$aviso = '<div class="ds-flash"><strong>Maqueta A4/A5 — 05/10/2026.</strong> Datos ficticios; no guarda nada.</div>';
if ($esAlumno) {
    $aviso .= '<div id="alumno-error-resumen" class="ds-flash ds-flash--error mb-3" tabindex="-1" role="alert"><strong>No se guardó.</strong><div>Revisá estos datos; lo que cargaste se conserva.</div><ul><li><a href="#nombre_tutor">El nombre del tutor es obligatorio para menores de edad.</a></li><li><a href="#telefono_tutor">El teléfono del tutor es obligatorio para menores de edad.</a></li></ul></div>';
}
$html = str_replace('<main class="ds-content">', '<main class="ds-content">'.$aviso, $html);
$html = str_replace('</body>', '<script src="/propuesta.js"></script></body>', $html);
echo $html;
