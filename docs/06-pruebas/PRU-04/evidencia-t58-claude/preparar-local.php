<?php

// T58: datos sintéticos para mirar la ficha, solo en la base descartable de Claude.
// Uso: DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t58-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
    throw new RuntimeException('Solo wings_testing_claude.');
}

use App\Models\{Alumno, AlumnoPlan, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, User};
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $password = bin2hex(random_bytes(20));
    $dueno = User::factory()->create([
        'name' => 'Dueño prueba T58', 'email' => 'dueno-t58-'.time().'@example.test',
        'rol' => User::ROL_ADMIN, 'activo' => true, 'password' => bcrypt($password),
    ]);
    $deporte = Deporte::create(['nombre' => 'Patín T58', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
    $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Intermedio T58'])->id, 'activo' => true]);
    $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 12000, 'activo' => true]);
    $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Olvidada', 'dni' => '58000001',
        'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => now()->subMonths(2)->startOfMonth()->addDays(9)->toDateString(),
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
    AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => $alumno->fecha_alta, 'activo' => true]);
    // Hace dos meses y el mes actual existen; el mes pasado nunca se generó.
    foreach ([now()->subMonths(2)->format('Y-m'), now()->format('Y-m')] as $periodo) {
        DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo,
            'monto_original' => 12000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    }
    file_put_contents(storage_path('app/t58-local.json'), json_encode([
        'email' => $dueno->email, 'password' => $password, 'alumno' => $alumno->id,
    ], JSON_THROW_ON_ERROR));
});
echo "Datos T58 creados en wings_testing_claude.\n";
