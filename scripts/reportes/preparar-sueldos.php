<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR,"Solo se reconstruye wings_testing_codex; ninguna base del club.\n");
    exit(1);
}
// Guardia verificada en la misma conexión ANTES de cualquier DDL destructivo.
$estado = \Illuminate\Support\Facades\Artisan::call('migrate:fresh',['--force'=>true]);
echo \Illuminate\Support\Facades\Artisan::output();
if ($estado !== 0) exit($estado);
$estado = \Illuminate\Support\Facades\Artisan::call('db:seed',['--class'=>\Database\Seeders\ReportesSueldosEscenarioSeeder::class,'--force'=>true]);
echo \Illuminate\Support\Facades\Artisan::output();
exit($estado);
