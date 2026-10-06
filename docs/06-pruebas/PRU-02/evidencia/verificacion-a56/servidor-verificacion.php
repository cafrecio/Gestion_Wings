<?php
// PHP -S 127.0.0.1:8786 -t public este-archivo; base descartable por variables de entorno.
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($ruta === '/_verificacion/clases-propuesta.js') {
    header('Content-Type: text/javascript; charset=utf-8');
    readfile(dirname(__DIR__).'/a15-a16/clases-propuesta.js');
    return;
}
if ($ruta === '/_verificacion/guardar-captura' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_GET['nombre'] ?? '';
    if (!preg_match('/^(login|cashflow|clases-antes|clases-propuesta|clases-aviso|clases-final|clases-final-aviso)-(375|375-inferior|escritorio)\.jpg$/D', $nombre)) {
        http_response_code(400);
        exit('Nombre no permitido.');
    }
    $bytes = file_get_contents('php://input');
    if (strlen($bytes) > 5000000 || substr($bytes, 0, 2) !== "\xFF\xD8") {
        http_response_code(400);
        exit('Se requiere captura JPEG.');
    }
    $carpeta = str_starts_with($nombre, 'clases-') ? dirname(__DIR__).'/a15-a16' : __DIR__;
    file_put_contents($carpeta.'/'.$nombre, $bytes);
    exit('Captura guardada.');
}
if (in_array($ruta, ['/_verificacion/clases-final', '/_verificacion/clases-final-aviso'], true)) {
    $archivo = $ruta === '/_verificacion/clases-final' ? 'final-recurrente.html' : 'final-aviso.html';
    $html = file_get_contents(dirname(__DIR__).'/a15-a16/'.$archivo);
    header('Content-Type: text/html; charset=utf-8');
    if (($_GET['marco'] ?? '') !== '375') {
        echo $html;
        return;
    }
    $marco = str_replace('height:1000px', 'height:1500px', file_get_contents(dirname(__DIR__, 2).'/capturas-cashflow/marco-375.html'));
    echo str_replace('src="cashflow-devolucion.html"', 'srcdoc="'.htmlspecialchars($html, ENT_QUOTES, 'UTF-8').'"', $marco);
    return;
}
if (in_array($ruta, ['/_verificacion/marco-login', '/_verificacion/marco-cashflow', '/_verificacion/cashflow', '/_verificacion/clases-antes', '/_verificacion/clases-propuesta', '/_verificacion/clases-aviso'], true)) {
    // La captura toma el cuerpo que devuelve Laravel: no altera X-Frame-Options del sitio.
    require dirname(__DIR__, 5).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 5).'/bootstrap/app.php';
    $esClases = str_contains($ruta, '/clases-');
    $pedido = \Illuminate\Http\Request::create('http://127.0.0.1:8786'.($ruta === '/_verificacion/marco-login' ? '/login' : ($esClases ? '/clases/create' : '/cashflow?anio=2026&mes=10')));
    $app->instance('request', $pedido);
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
        http_response_code(403);
        exit('Este ensayo requiere wings_testing_codex.');
    }
    if ($ruta !== '/_verificacion/marco-login') {
        $admin = \App\Models\User::where('email', $esClases ? 'clases.verificacion@example.test' : 'a56.verificacion@example.test')->firstOrFail();
        \Illuminate\Support\Facades\Auth::setUser($admin);
    }
    $respuesta = $kernel->handle($pedido);
    $html = $respuesta->getContent();
    if ($esClases) {
        $intermedias = \App\Models\Grupo::whereHas('deporte', fn($q) => $q->where('nombre', 'Patín'))->whereHas('nivel', fn($q) => $q->where('nombre', 'Intermedias'))->sole();
        $profesor = \App\Models\Profesor::where('apellido', 'Salinas')->sole();
        $datos = ['tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-09-24', 'fecha_hasta' => '2026-10-31', 'grupo_id' => $intermedias->id,
            'dias_semana' => [1, 5], 'profesores' => [$profesor->id], 'hora_inicio' => '17:00', 'hora_fin' => '18:00',
            'horarios' => [1 => ['hora_inicio' => '17:00', 'hora_fin' => '18:00'], 5 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00']]];
        if ($ruta === '/_verificacion/clases-aviso') {
            $datos = array_replace($datos, ['tipo_creacion' => 'unica', 'fecha' => '2026-09-24', 'hora_inicio' => '17:30', 'hora_fin' => '18:30']);
            $app['session']->put('aviso_cancha', ['firma' => 'propuesta-sin-escritura', 'horarios' => [
                ['detalle' => '17:30–18:30: dura 1 hora, pero ocupa 2 bloques de alquiler: 17:00–18:00 y 18:00–19:00.'],
            ]]);
        }
        $app['session']->flashInput($datos);
        if ($ruta !== '/_verificacion/clases-antes') {
            $app['view']->getFinder()->prependLocation(dirname(__DIR__).'/a15-a16/vistas');
            $app['view']->getFinder()->flush();
        }
        $html = view('clases.create', $respuesta->original->getData())->render();
    }
    header('Content-Type: text/html; charset=utf-8');
    if (in_array($ruta, ['/_verificacion/cashflow', '/_verificacion/clases-antes', '/_verificacion/clases-propuesta', '/_verificacion/clases-aviso'], true) && ($_GET['marco'] ?? '') !== '375') {
        echo $html;
        return;
    }
    $marco = file_get_contents(dirname(__DIR__, 2).'/capturas-cashflow/marco-375.html');
    if ($esClases) $marco = str_replace('height:1000px', 'height:1500px', $marco);
    echo str_replace('src="cashflow-devolucion.html"', 'srcdoc="'.htmlspecialchars($html, ENT_QUOTES, 'UTF-8').'"', $marco);
    return;
}
$publico = dirname(__DIR__, 5).'/public';
if ($ruta !== '/' && is_file($publico.$ruta)) return false;
require $publico.'/index.php';
