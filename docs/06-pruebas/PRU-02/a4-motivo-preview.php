<?php

// Captura local del formulario real: solo GET, datos sintéticos y base de Codex.
if (PHP_SAPI !== 'cli-server' || getenv('APP_ENV') !== 'testing'
    || getenv('DB_DATABASE') !== 'wings_testing_codex'
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Solo vista previa local de Codex.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Esta vista previa no guarda.');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/build/') || str_starts_with($path, '/favicon') || str_starts_with($path, '/images/')) {
    return false;
}
if (!preg_match('#^/alumnos/\d+/edit$#', $path) && $path !== '/alumnos/inscripcion-preview') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
    throw new RuntimeException('Base ajena a Codex.');
}
Illuminate\Support\Facades\Auth::setUser(App\Models\User::where('email', 'a4a5@example.invalid')->firstOrFail());
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
