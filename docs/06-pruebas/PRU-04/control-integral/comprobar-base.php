<?php
// Preflight de solo lectura. No prepara ni migra la base.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
$actual = $db->selectOne('SELECT DATABASE() AS base')->base;
$info = ['entorno' => app()->environment(), 'conexion' => $db->getName(),
    'host' => $db->getConfig('host'), 'base_configurada' => $db->getDatabaseName(), 'base_efectiva' => $actual];
echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
if ($info['entorno'] !== 'testing' || $info['conexion'] !== 'mysql'
    || !in_array($info['host'], ['127.0.0.1', 'localhost'], true)
    || $actual !== 'wings_testing_codex' || $info['base_configurada'] !== $actual) {
    fwrite(STDERR, "Base no autorizada para esta prueba.\n");
    exit(2);
}
