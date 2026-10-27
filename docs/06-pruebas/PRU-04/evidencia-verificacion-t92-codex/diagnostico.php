<?php

require __DIR__.'/bootstrap.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex'
    || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    throw new RuntimeException('T92: diagnostico fuera de la base autorizada.');
}
echo json_encode(\Illuminate\Support\Facades\DB::select("SELECT ID, DB, COMMAND, TIME, STATE, LEFT(INFO,180) AS INFO FROM information_schema.PROCESSLIST WHERE DB = 'wings_testing_codex'"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
