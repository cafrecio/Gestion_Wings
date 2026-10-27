<?php

require __DIR__.'/bootstrap.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== 'wings_testing_codex'
    || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    throw new RuntimeException('No consultar: conexión distinta de wings_testing_codex.');
}
$row = \Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS base, VERSION() AS motor');
if ($row->base !== 'wings_testing_codex') {
    throw new RuntimeException('La conexión efectiva no está en wings_testing_codex.');
}
echo json_encode(['entorno' => app()->environment(), 'conexion' => config('database.default'),
    'base_efectiva' => $row->base, 'motor' => $row->motor], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
