<?php

// Enrutador de verificación: sirve la aplicación real y comprueba la base en cada petición.
if (PHP_SAPI !== 'cli-server' || getenv('DB_DATABASE') !== 'wings_testing_codex' || getenv('APP_ENV') !== 'local') {
    http_response_code(503);
    exit('Entorno de verificación no disponible.');
}
$root = dirname(__DIR__, 4);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($root.'/public'.rawurldecode($path));
if ($file && str_starts_with($file, realpath($root.'/public').DIRECTORY_SEPARATOR)
    && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') return false;
define('LARAVEL_START', microtime(true));
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'wings_testing_codex'
    || config('database.connections.mysql.host') !== '127.0.0.1') {
    http_response_code(503);
    exit('Base fuera del alcance de verificación.');
}
$response = $kernel->handle($request);
file_put_contents(__DIR__.'/servidor-peticiones.jsonl', json_encode(['metodo' => $request->method(),
    'ruta' => $request->path(), 'estado' => $response->getStatusCode(),
    'base' => config('database.connections.mysql.database'), 'fecha_servidor' => now()->toIso8601String(),
    'limite_servidor' => App\Support\FechaIngresoRegla::limiteMaximo()->toDateString(),
    'sesion_driver' => config('session.driver'), 'sesion_dominio' => config('session.domain'),
    'sesion_segura' => config('session.secure'),
    'destino' => parse_url((string) $response->headers->get('Location'), PHP_URL_PATH)], JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
$response->send();
$kernel->terminate($request, $response);
