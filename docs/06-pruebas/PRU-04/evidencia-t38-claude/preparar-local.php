<?php

// T38: datos sintéticos para mirar la pantalla, solo en la base descartable de Claude.
// Uso: DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t38-claude/preparar-local.php
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
    $operativa = User::factory()->create([
        'name' => 'Operativa prueba T38', 'email' => 'operativa-t38-'.time().'@example.test',
        'rol' => User::ROL_OPERATIVO, 'activo' => true, 'password' => bcrypt($password),
    ]);
    $deporte = Deporte::create(['nombre' => 'Patín T38', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
    $nivel = Nivel::create(['nombre' => 'Intermedio T38']);
    $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
    $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    $alumno = Alumno::create(['nombre' => 'Ana', 'apellido' => 'Reingreso', 'dni' => '38000001',
        'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-03-01',
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
    AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
    DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => now()->subMonthNoOverflow()->format('Y-m'),
        'monto_original' => 10000, 'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
    $alumno->update(['activo' => false]);
    // Bruno no debe nada: al reactivarlo desde el listado tiene que quedar «En plazo».
    $bruno = Alumno::create(['nombre' => 'Bruno', 'apellido' => 'Reingreso', 'dni' => '38000002',
        'fecha_nacimiento' => '2013-01-01', 'celular' => '11-4000-0001', 'fecha_alta' => '2026-03-01',
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
    AlumnoPlan::create(['alumno_id' => $bruno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
    $bruno->update(['activo' => false]);
    // Credenciales efímeras fuera de la evidencia versionada; nunca se imprimen.
    file_put_contents(storage_path('app/t38-local.json'), json_encode([
        'email' => $operativa->email, 'password' => $password, 'alumno' => $alumno->id, 'bruno' => $bruno->id,
    ], JSON_THROW_ON_ERROR));
});
echo "Datos T38 creados en wings_testing_claude.\n";
