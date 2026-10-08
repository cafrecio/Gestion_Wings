<?php
// Nombres de personas y usuario inactivo ficticios, solo en la base descartable de captura.
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo laboratorio Codex');
}
require dirname(__DIR__, 5).'/vendor/autoload.php';
$app = require dirname(__DIR__, 5).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('testing') || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    throw new RuntimeException('Base incorrecta');
}
\App\Models\User::where('email', 'celular.admin@example.test')->firstOrFail();
\App\Models\User::where('email', 'celular.admin@example.test')->update(['name' => 'Pedro']);
\App\Models\User::where('email', 'celular.operativo@example.test')->update(['name' => 'Sandra']);
$usuario = \App\Models\User::firstOrNew(['email' => 'celular.inactivo@example.test']);
$usuario->forceFill(['name' => 'Victoria', 'rol' => 'OPERATIVO', 'activo' => false, 'es_superadmin' => false]);
if (!$usuario->exists) $usuario->password = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32));
$usuario->save();
echo "Escenario usuarios listo en wings_testing_codex; cuenta ficticia inactiva\n";
