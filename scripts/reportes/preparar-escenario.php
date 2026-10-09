<?php

// Base descartable de Codex. El control ocurre ANTES del primer borrado.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR, "Solo se permite wings_testing_codex. No se modificó ninguna base.\n");
    exit(1);
}
\Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
(new \Database\Seeders\ReportesEscenarioSeeder())->run();
echo "Escenario ficticio preparado en wings_testing_codex.\n";
