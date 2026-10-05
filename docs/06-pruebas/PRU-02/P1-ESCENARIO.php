<?php

// Datos ficticios para repetir las capturas. Nunca limpia una base ni usa seeders.
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo testing / wings_testing_codex.');
}
if (($argv[1] ?? '') === 'estado') {
    echo json_encode([
        'estado' => App\Models\PrimeraCarga::findOrFail(1)->estado,
        'alumnos' => App\Models\Alumno::orderBy('id')->get(['dni', 'nombre', 'deporte_id'])->toArray(),
        'deudas' => App\Models\DeudaCuota::orderBy('id')->get(['alumno_id', 'periodo', 'monto_original', 'monto_pagado'])->toArray(),
        'cargos' => App\Models\CargoAlumno::all()->map(fn ($cargo) => ['tipo' => $cargo->tipo, 'monto_original' => $cargo->monto_original, 'monto_cobrado' => $cargo->monto_cobrado])->all(),
        'pagos' => App\Models\Pago::count(), 'cashflow' => App\Models\CashflowMovimiento::count(),
        'movimientos' => App\Models\MovimientoOperativo::count(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit;
}
if (App\Models\Alumno::exists()) throw new RuntimeException('La base de capturas debe estar vacía. No se limpia automáticamente.');
$password = getenv('P1_TEST_PASSWORD');
if (!$password || strlen($password) < 12) throw new RuntimeException('Definí P1_TEST_PASSWORD para la cuenta ficticia. No se imprime.');
Illuminate\Support\Facades\DB::transaction(function () use ($password) {
    App\Models\User::updateOrCreate(['email' => 'p1@example.invalid'], ['name' => 'P1 — datos ficticios', 'password' => Illuminate\Support\Facades\Hash::make($password)])
        ->forceFill(['rol' => 'ADMIN', 'activo' => true])->save();
    foreach ([['Fútbol', 'Juveniles', 48000], ['Patín', 'Inicial', 52000]] as [$nombre, $nivel, $precio]) {
        $deporte = App\Models\Deporte::firstOrCreate(['nombre' => $nombre], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
        $n = App\Models\Nivel::firstOrCreate(['nombre' => $nivel]);
        $grupo = App\Models\Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $n->id], ['activo' => true]);
        App\Models\GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id, 'clases_por_semana' => 2], ['precio_mensual' => $precio, 'activo' => true]);
    }
    App\Models\PrimeraCarga::whereKey(1)->update(['estado' => 'PENDIENTE', 'detalle' => null]);
});
echo 'Escenario ficticio listo; cero alumnos. Usuarios y catálogos conservados.'.PHP_EOL;
