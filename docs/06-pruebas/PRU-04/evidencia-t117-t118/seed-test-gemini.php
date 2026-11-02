<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

$dbName = config('database.connections.mysql.database');
$descartables = ['wings_testing_gemini', 'wings_testing_claude', 'wings_testing_codex'];

if (!in_array($dbName, $descartables, true)) {
    fwrite(STDERR, "ERROR: La base de datos configurada es '{$dbName}'. El sembrador solo puede correr sobre bases descartables (" . implode(', ', $descartables) . "). Abortando para proteger datos.\n");
    exit(1);
}

echo "Migrando y sembrando catálogos en {$dbName}...\n";
Artisan::call('migrate:fresh', ['--force' => true]);
(new CatalogosSeeder())->run();

\Illuminate\Support\Facades\DB::table('primera_carga')->updateOrInsert(['id' => 1], [
    'estado'     => 'COMPLETADA',
    'updated_at' => now(),
    'created_at' => now(),
]);

$admin = User::firstOrCreate(
    ['email' => 'admin@wings.test'],
    [
        'name' => 'Carlos Administrador',
        'password' => Hash::make('password'),
        'activo' => true,
    ]
);
$admin->rol = 'ADMIN';
$admin->save();

$operativo = User::firstOrCreate(
    ['email' => 'operativo@wings.test'],
    [
        'name' => 'Sandra Vidal',
        'password' => Hash::make('password'),
        'activo' => true,
    ]
);
$operativo->rol = 'OPERATIVO';
$operativo->save();

$profesorUser = User::firstOrCreate(
    ['email' => 'profesor@wings.test'],
    [
        'name' => 'Mariela Docente',
        'password' => Hash::make('password'),
        'activo' => true,
    ]
);
$profesorUser->rol = 'PROFESOR';
$profesorUser->save();

$patin = Deporte::where('nombre', 'Patín')->first();
$futbol = Deporte::where('nombre', 'Fútbol')->first();

$profesor = Profesor::firstOrCreate(
    ['dni' => '28111222'],
    [
        'user_id' => $profesorUser->id,
        'nombre' => 'Mariela',
        'apellido' => 'Docente',
        'deporte_id' => $patin->id,
        'activo' => true,
        'valor_hora' => 5000,
        'direccion' => 'Calle Falsa 123',
        'localidad' => 'CABA',
        'telefono' => '1144556677',
        'fecha_nacimiento' => '1985-05-10',
    ]
);

$nivel = Nivel::first();
$grupo = Grupo::firstOrCreate(
    ['deporte_id' => $patin->id, 'nivel_id' => $nivel->id],
    ['activo' => true]
);

$plan = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupo->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 25000, 'activo' => true]
);

$alumno = Alumno::firstOrCreate(
    ['dni' => '40123456'],
    [
        'nombre' => 'Lucas',
        'apellido' => 'Benítez',
        'fecha_nacimiento' => '2012-04-10',
        'celular' => '1155667788',
        'deporte_id' => $patin->id,
        'grupo_id' => $grupo->id,
        'plan_id' => $plan->id,
        'fecha_alta' => '2026-02-01',
        'activo' => true,
    ]
);

\App\Models\AlumnoPlan::create([
    'alumno_id'   => $alumno->id,
    'plan_id'     => $plan->id,
    'fecha_desde' => '2026-02-01',
    'activo'      => true,
]);

$deuda = DeudaCuota::firstOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => '2026-11'],
    [
        'monto_original' => 25000,
        'monto_pagado' => 0,
        'estado' => 'PENDIENTE',
        'fecha_vencimiento' => '2026-11-10',
    ]
);

$tipoCaja = TipoCaja::where('nombre', 'Efectivo')->first();
\App\Models\Configuracion::updateOrCreate(['clave' => 'caja_mostrador'], ['valor' => (string) $tipoCaja->id, 'tipo' => 'integer']);

$caja = CajaOperativa::create([
    'usuario_operativo_id'  => $operativo->id,
    'tipo_caja_id'          => $tipoCaja->id,
    'tipo_caja_efectivo_id' => $tipoCaja->id,
    'estado'                => 'ABIERTA',
    'apertura_at'           => now(),
    'saldo_inicial'         => 10000,
    'efectivo_inicial'      => 10000,
]);

$clase = Clase::firstOrCreate(
    ['grupo_id' => $grupo->id, 'fecha' => now()->format('Y-m-d')],
    [
        'deporte_id' => $patin->id,
        'profesor_id' => $profesor->id,
        'hora_inicio' => '17:00:00',
        'hora_fin' => '18:00:00',
        'estado' => 'PROGRAMADA',
        'asistencia_tomada' => false,
    ]
);

echo "Base wings_testing_gemini preparada correctamente.\n";
echo "Alumno ID: {$alumno->id}\n";
echo "Clase ID: {$clase->id}\n";
echo "Caja ID: {$caja->id}\n";

// Datos para T118
\App\Models\AlumnoRevisionCobranza::create([
    'alumno_id'             => $alumno->id,
    'motivo'                => 'Mora detectada',
    'periodo_objetivo'      => '2026-11',
    'estado_revision'       => 'RESUELTO',
    'resolucion'            => 'REACTIVADO_AUTO',
    'nota_resolucion'       => 'Reactivado automático por cobro.',
    'usuario_resolucion_id' => $admin->id,
    'fecha_resolucion'      => now(),
]);

$rubroCuotas = \App\Models\Rubro::firstOrCreate(['nombre' => 'Cuotas'], ['nombre' => 'Cuotas', 'tipo' => 'INGRESO']);
$subrubroCuotas = \App\Models\Subrubro::firstOrCreate(['nombre' => 'Cuota social'], [
    'rubro_id'       => $rubroCuotas->id,
    'nombre'         => 'Cuota social',
    'permitido_para' => 'OPERATIVO',
    'afecta_caja'    => true,
    'activo'         => true,
]);

$grupoFutbol = Grupo::firstOrCreate(
    ['deporte_id' => $futbol->id, 'nivel_id' => $nivel->id],
    ['activo' => true]
);
$planFutbol = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupoFutbol->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 20000, 'activo' => true]
);

$alumnoFutbol = Alumno::firstOrCreate(
    ['dni' => '41123456'],
    [
        'nombre'           => 'Martín',
        'apellido'         => 'Gómez',
        'fecha_nacimiento' => '2010-01-01',
        'celular'          => '1122334455',
        'deporte_id'       => $futbol->id,
        'grupo_id'         => $grupoFutbol->id,
        'plan_id'          => $planFutbol->id,
        'fecha_alta'       => '2026-01-01',
        'activo'           => true,
    ]
);

\App\Models\MovimientoOperativo::create([
    'caja_operativa_id' => $caja->id,
    'subrubro_id'       => $subrubroCuotas->id,
    'tipo_caja_id'      => $tipoCaja->id,
    'usuario_id'        => $operativo->id,
    'alumno_id'         => $alumnoFutbol->id,
    'monto'             => 5000,
    'fecha'             => now(),
    'estado'            => 'ACTIVO',
]);

\App\Models\MovimientoOperativo::create([
    'caja_operativa_id' => $caja->id,
    'subrubro_id'       => $subrubroCuotas->id,
    'tipo_caja_id'      => $tipoCaja->id,
    'usuario_id'        => $admin->id,
    'monto'             => 1000,
    'fecha'             => now(),
    'observaciones'     => '12345678901234567890123456789012345ábcdefghij',
    'estado'            => 'ACTIVO',
]);

