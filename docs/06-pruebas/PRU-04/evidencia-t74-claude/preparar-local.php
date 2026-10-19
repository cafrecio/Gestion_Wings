<?php

// T74: padrón sintético para mirar el Inicio de la operativa, solo en la base descartable de Claude.
// Uso (fecha simulada 05/10, día 5 con 10 de gracia):
//   FECHA_SIMULADA="2026-10-05 10:00:00" DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t74-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
    throw new RuntimeException('Solo wings_testing_claude.');
}

use App\Models\{Alumno, AlumnoPlan, CajaOperativa, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, TipoCaja, User};
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    Configuracion::set('dias_gracia_cobranza', 10);
    $password = bin2hex(random_bytes(20));
    $operativa = User::factory()->create([
        'name' => 'Operativa prueba T74', 'email' => 'operativa-t74-'.time().'@example.test',
        'rol' => User::ROL_OPERATIVO, 'activo' => true, 'password' => bcrypt($password),
    ]);
    CajaOperativa::create([
        'usuario_operativo_id' => $operativa->id, 'usuario_apertura_id' => $operativa->id,
        'apertura_at' => now(), 'estado' => 'ABIERTA',
        'tipo_caja_efectivo_id' => TipoCaja::firstOrFail()->id, 'efectivo_inicial' => 0,
    ]);
    $deporte = Deporte::create(['nombre' => 'Patín T74', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
    $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Inicial T74'])->id, 'activo' => true]);
    $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);

    $dni = 74000001;
    $alumno = function (string $apellido, string $nombre) use (&$dni, $deporte, $grupo, $plan) {
        $a = Alumno::create(['nombre' => $nombre, 'apellido' => $apellido, 'dni' => (string) $dni++,
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-09-01',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true]);
        return $a;
    };
    $deuda = fn (Alumno $a, string $periodo, float $pagado = 0, string $estado = DeudaCuota::ESTADO_PENDIENTE) => DeudaCuota::create([
        'alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => 48000,
        'monto_pagado' => $pagado, 'monto_condonado' => 0, 'estado' => $estado,
    ]);

    // Igual que InicioVencidosEnPlazoT74Test: 2 vencidos, 2 en plazo, el resto no cuenta.
    $deuda($alumno('Vencida', 'Uno'), '2026-09');
    $doble = $alumno('Vencida', 'Dos');
    $deuda($doble, '2026-09');
    $deuda($doble, '2026-10');
    $deuda($alumno('Plazo', 'Uno'), '2026-10');
    $deuda($alumno('Plazo', 'Dos'), '2026-10', 20000);
    $deuda($alumno('Aldia', 'Pagada'), '2026-10', 48000, DeudaCuota::ESTADO_PAGADA);
    $alumno('Aldia', 'SinCuotas');
    $deuda($alumno('Adelantada', 'Noviembre'), '2026-11');
    $inactiva = $alumno('Inactiva', 'Deudora');
    $deuda($inactiva, '2026-09');
    $inactiva->update(['activo' => false]);

    file_put_contents(storage_path('app/t74-local.json'), json_encode([
        'email' => $operativa->email, 'password' => $password,
    ], JSON_THROW_ON_ERROR));
});
echo 'Datos T74 creados en wings_testing_claude, fecha '.now()->toDateTimeString().".\n";
