<?php

if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo CLI, APP_ENV=testing y base wings_testing_codex.');
}
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$conexion = config('database.default');
if ($conexion !== 'mysql' || config('database.connections.mysql.database') !== 'wings_testing_codex'
    || config('database.connections.mysql.host') !== '127.0.0.1' || !app()->environment('testing')) {
    throw new RuntimeException('La configuración efectiva no es la base local descartable propia.');
}
echo json_encode(['base' => config('database.connections.mysql.database'), 'entorno' => app()->environment(),
    'zona_app' => config('app.timezone'), 'zona_php' => date_default_timezone_get(), 'raiz' => base_path()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
