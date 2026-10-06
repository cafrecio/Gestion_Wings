<?php
// PHP -S 127.0.0.1:8786 -t public este-archivo; base descartable por variables de entorno.
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (in_array($ruta, ['/_verificacion/marco-login', '/_verificacion/marco-cashflow'], true)) {
    // La captura toma el cuerpo que devuelve Laravel: no altera X-Frame-Options del sitio.
    require dirname(__DIR__, 5).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 5).'/bootstrap/app.php';
    $pedido = \Illuminate\Http\Request::create($ruta === '/_verificacion/marco-login' ? '/login' : '/cashflow?anio=2026&mes=10');
    $app->instance('request', $pedido);
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
        http_response_code(403);
        exit('Este ensayo requiere wings_testing_codex.');
    }
    if ($ruta === '/_verificacion/marco-cashflow') {
        $admin = \App\Models\User::where('email', 'a56.verificacion@example.test')->firstOrFail();
        \Illuminate\Support\Facades\Auth::setUser($admin);
    }
    $respuesta = $kernel->handle($pedido);
    header('Content-Type: text/html; charset=utf-8');
    $marco = file_get_contents(dirname(__DIR__, 2).'/capturas-cashflow/marco-375.html');
    echo str_replace('src="cashflow-devolucion.html"', 'srcdoc="'.htmlspecialchars($respuesta->getContent(), ENT_QUOTES, 'UTF-8').'"', $marco);
    return;
}
$publico = dirname(__DIR__, 5).'/public';
if ($ruta !== '/' && is_file($publico.$ruta)) return false;
require $publico.'/index.php';
