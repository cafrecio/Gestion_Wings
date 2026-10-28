<?php

// Regenera exclusivamente la base descartable propia después de las suites.
// No ejecutar mientras otro proceso usa wings_testing_codex.
require dirname(__DIR__).'/evidencia-verificacion-t92-codex/bootstrap.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    throw new RuntimeException('Base incorrecta: no se ejecutó ninguna migración.');
}
Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
$privadoAnterior = $raiz.'/storage/app/aprobaciones-t54-t87-t89.json';
if (is_file($privadoAnterior)) {
    $respaldo = $raiz.'/storage/app/aprobaciones-antes-t89-'.date('Ymd-His').'.json';
    if (!rename($privadoAnterior, $respaldo)) throw new RuntimeException('No se pudo conservar el metadata privado anterior.');
}
require dirname(__DIR__).'/evidencia-aprobaciones-t54-t87-t89/preparar.php';
