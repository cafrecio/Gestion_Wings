<?php

// Fixture local reproducible: solo bases descartables propias, nunca la base del club.
$accion = $argv[1] ?? 'preparar';
$visual = in_array($accion, ['visual', 'visual-propia', 'verificar-visual'], true);
$baseDescartable = $visual ? 'wings_testing_codex_t77_visual' : 'wings_testing_codex';
if ($accion === 'visual') {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '');
    if ($pdo->query("SHOW DATABASES LIKE 'wings_testing_codex_t77_visual'")->fetchColumn()) {
        throw new RuntimeException('La base visual ya existe: comprobar su propiedad antes de recrearla.');
    }
    $pdo->exec('CREATE DATABASE wings_testing_codex_t77_visual CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $baseDescartable,
    'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '',
    'FECHA_SIMULADA' => '2026-10-25 15:00:00', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync'] as $clave => $valor) {
    putenv("{$clave}={$valor}");
    $_ENV[$clave] = $_SERVER[$clave] = $valor;
}
$base = dirname(__DIR__, 4);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== $baseDescartable || !$app->environment('testing')) {
    throw new RuntimeException('Base o entorno no autorizado para el fixture T77.');
}
if (in_array($accion, ['verificar', 'verificar-visual'], true)) {
    echo json_encode([
        'base' => config('database.connections.mysql.database'),
        'deudas' => App\Models\DeudaCuota::orderBy('id')->limit(5)->get()->toArray(),
        'pagos' => App\Models\Pago::orderBy('id')->limit(5)->get(['id', 'alumno_id', 'monto_final', 'estado'])->toArray(),
        'imputaciones' => Illuminate\Support\Facades\DB::table('pago_deuda_cuota')->orderBy('id')->limit(5)->get()->toArray(),
        'movimientos' => App\Models\MovimientoOperativo::orderBy('id')->limit(5)->get(['id', 'alumno_id', 'pago_id', 'monto', 'estado'])->toArray(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
$clave = getenv('T77_BROWSER_PASSWORD');
if (!$clave || strlen($clave) < 12) {
    throw new RuntimeException('Definí T77_BROWSER_PASSWORD solo para esta cuenta de prueba local.');
}
// La activación del historial exige mantenimiento para bases fuera de las cuatro
// habituales. Esta base es nueva, privada y sin tráfico; mantenimiento solo en la
// caché en memoria del preparador, sin poner la aplicación compartida en down.
if ($visual) {
    $mantenimiento = new Illuminate\Foundation\CacheBasedMaintenanceMode($app['cache'], 'array', 't77-visual');
    $app->instance(Illuminate\Contracts\Foundation\MaintenanceMode::class, $mantenimiento);
    $mantenimiento->activate([]);
}
try {
    Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
} finally {
    if ($visual) $mantenimiento->deactivate();
}
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\CatalogosSeeder::class, '--force' => true]);
Illuminate\Support\Facades\DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
$usuario = App\Models\User::factory()->create(['name' => 'Operativa T77', 'email' => 't77-local@example.test',
    'password' => Illuminate\Support\Facades\Hash::make($clave), 'rol' => 'OPERATIVO', 'activo' => true]);
$medio = App\Models\TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
Tests\Support\CajaDeclarada::crear($usuario->id, $medio->id);
$deporte = App\Models\Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivel = App\Models\Nivel::create(['nombre' => 'Inicial']);
$grupo = App\Models\Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
$plan = App\Models\GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
$alumnos = [];
foreach (['Al día', 'Con deuda'] as $i => $nombre) {
    $alumno = App\Models\Alumno::create(['nombre' => $nombre, 'apellido' => 'Prueba T77', 'dni' => '7700000'.($i + 1),
        'fecha_nacimiento' => '2010-01-01', 'celular' => '1100000077', 'fecha_alta' => '2026-01-01',
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
    App\Models\AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-01-01', 'activo' => true]);
    if ($i === 1) {
        App\Models\DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 30000,
            'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }
    $alumnos[] = ['id' => $alumno->id, 'nombre' => $nombre, 'cuotas_guardadas' => $alumno->deudaCuotas()->get()->toArray()];
}
echo json_encode(['base' => config('database.connections.mysql.database'), 'usuario' => $usuario->email,
    'alumnos' => $alumnos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
