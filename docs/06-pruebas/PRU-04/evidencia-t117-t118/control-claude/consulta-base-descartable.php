<?php
// Consulta suelta del control de T117. Solo corre contra la base descartable.
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.'.config('database.default').'.database') !== 'wings_testing_claude') {
    fwrite(STDERR, "base equivocada\n");
    exit(2);
}
$q = $argv[1];
if (preg_match('/^\s*select/i', $q)) {
    echo json_encode(Illuminate\Support\Facades\DB::select($q), JSON_UNESCAPED_UNICODE);
} else {
    echo Illuminate\Support\Facades\DB::affectingStatement($q);
}
