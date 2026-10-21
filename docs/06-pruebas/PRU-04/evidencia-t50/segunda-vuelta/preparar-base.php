<?php

require_once __DIR__ . '/../../../../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Guardia de seguridad estricta: solo bases de testing autorizadas en local/testing (comprueba conexión efectiva y DB_URL)
$allowedDbs = ['wings_testing', 'wings_testing_gemini', 'wings_testing_claude', 'wings_testing_codex'];
$dbNameConfig = config('database.connections.mysql.database');
$dbNameConnection = DB::connection()->getDatabaseName();

$dbUrl = env('DB_URL') ?: config('database.connections.mysql.url');
$dbNameUrl = null;
if ($dbUrl) {
    $parsedUrl = parse_url($dbUrl, PHP_URL_PATH);
    $dbNameUrl = $parsedUrl ? ltrim($parsedUrl, '/') : null;
}

if (!app()->environment('testing', 'local')
    || !in_array($dbNameConnection, $allowedDbs, true)
    || !in_array($dbNameConfig, $allowedDbs, true)
    || ($dbNameUrl !== null && !in_array($dbNameUrl, $allowedDbs, true))
) {
    fwrite(STDERR, "ERROR DE SEGURIDAD: Este auxiliar solo puede ejecutarse en entorno local/testing sobre una base de pruebas autorizada (efectiva: {$dbNameConnection}, config: {$dbNameConfig}).\n");
    exit(1);
}

echo "Preparando base para prueba de navegador T50...\n";

// Asegurar esquema y catálogos
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => CatalogosSeeder::class, '--force' => true]);

// Asegurar estado de primera carga terminada
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);

// Usuario ADMIN para login
$admin = User::firstOrNew(['email' => 'admin@wings.test']);
$admin->name = 'Admin T50';
$admin->password = Hash::make('password');
$admin->rol = 'ADMIN';
$admin->activo = true;
$admin->save();

echo "✓ Base lista. Admin: {$admin->email}\n";
