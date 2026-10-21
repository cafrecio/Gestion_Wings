<?php

// APP_ENV=local permite el reloj simulado real del proveedor; la base sigue siendo la descartable.
if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'local' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo comprobación del servidor local contra wings_testing_codex.');
}
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'wings_testing_codex'
    || config('database.connections.mysql.host') !== '127.0.0.1' || !app()->environment('local')) {
    throw new RuntimeException('Configuración efectiva fuera del alcance local propio.');
}
$fase = $argv[1] ?? 'normal';
if (!in_array($fase, ['normal', 'desfase'], true)) throw new RuntimeException('Fase no admitida.');
$datos = ['fase' => $fase, 'entorno' => app()->environment(), 'base' => config('database.connections.mysql.database'),
    'fecha_servidor' => now()->toIso8601String(), 'limite_servidor' => App\Support\FechaIngresoRegla::limiteMaximo()->toDateString(),
    'fecha_simulada' => config('app.fecha_simulada'), 'url' => config('app.url')];
file_put_contents(__DIR__.'/servidor-'.$fase.'.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode($datos, JSON_UNESCAPED_UNICODE).PHP_EOL;
