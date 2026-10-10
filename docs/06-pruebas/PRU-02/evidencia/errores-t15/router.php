<?php
// Router exclusivamente local de evidencia; no agrega rutas al sitio desplegado.
$base = dirname(__DIR__, 5);
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    http_response_code(403); exit('Solo entorno descartable de Codex');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$public = realpath($base.'/public');
$file = realpath($public.$path);
if ($path !== '/' && $file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
if ($path === '/__t15/marco') {
    $destino = $_GET['url'] ?? '/login';
    if (!str_starts_with($destino, '/') || str_starts_with($destino, '//')) {
        http_response_code(400); exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Marco real 360</title><body><iframe title="Wings a 360" width="360" height="1000" frameborder="0" src="'.htmlspecialchars($destino, ENT_QUOTES).'" ></iframe></body></html>';
    return;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->useStoragePath(getenv('LARAVEL_STORAGE_PATH'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if ($path === '/__t15/entorno') {
    // Metadatos seguros del servidor HTTP real, sin conectar ni revelar claves.
    header('Content-Type: application/json');
    echo json_encode([
        'app_env' => $app->environment(),
        'database' => config('database.connections.mysql.database'),
        'db_host' => config('database.connections.mysql.host'),
        'db_port' => config('database.connections.mysql.port'),
        'session_driver' => config('session.driver'),
        'maintenance_driver' => config('app.maintenance.driver'),
        'storage' => str_replace($base.DIRECTORY_SEPARATOR, '', $app->storagePath()),
    ], JSON_THROW_ON_ERROR);
    return;
}
Illuminate\Support\Facades\Route::middleware('web')->get('/__t15/error/{status}', function (int $status) {
    if ($status === 500) throw new RuntimeException('FICTICIO-T15: fallo de prueba registrado');
    if (!in_array($status, [404, 429, 503], true)) abort(404);
    abort($status, '', ['Retry-After' => '120']);
});
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
// Solo este servidor local de capturas permite alojar la respuesta real en el marco.
// El middleware y los headers de Wings desplegado no se modifican.
$response->headers->remove('X-Frame-Options');
$response->send();
$kernel->terminate($request, $response);
