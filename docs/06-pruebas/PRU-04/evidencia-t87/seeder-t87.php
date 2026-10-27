<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Crear o actualizar admin
$admin = \App\Models\User::firstOrNew(['email' => 'admin@wings.test']);
$admin->name = 'Admin Wings';
$admin->password = 'PruebaWings2026';
$admin->rol = 'ADMIN';
$admin->activo = true;
$admin->save();

// 2. Deporte y Nivel
$deporte = \App\Models\Deporte::first();
$nivel = \App\Models\Nivel::first();

// 3. Grupo
$grupo = \App\Models\Grupo::create([
    'deporte_id' => $deporte->id,
    'nivel_id' => $nivel->id,
    'activo' => true
]);

// 4. Profesores
$lucia = \App\Models\Profesor::create([
    'deporte_id' => $deporte->id,
    'nombre' => 'Lucía',
    'apellido' => 'Gaitán',
    'dni' => '30111222',
    'fecha_nacimiento' => '1990-05-15',
    'direccion' => 'Calle Falsa 123',
    'localidad' => 'Quilmes',
    'valor_hora' => 12000,
    'tipo_liquidacion' => 'HORA',
    'activo' => true
]);

$mariana = \App\Models\Profesor::create([
    'deporte_id' => $deporte->id,
    'nombre' => 'Mariana',
    'apellido' => 'Sin Clases',
    'dni' => '30333444',
    'fecha_nacimiento' => '1992-08-20',
    'direccion' => 'Calle 456',
    'localidad' => 'Bernal',
    'valor_hora' => 10000,
    'tipo_liquidacion' => 'HORA',
    'activo' => true
]);

// 17 clases validadas en octubre 2026
for ($i = 1; $i <= 17; $i++) {
    $c = \App\Models\Clase::create([
        'grupo_id' => $grupo->id,
        'fecha' => '2026-10-' . str_pad($i, 2, '0', STR_PAD_LEFT),
        'hora_inicio' => '10:00',
        'hora_fin' => '11:00',
        'cancelada' => false,
        'validada_para_liquidacion' => true
    ]);
    $c->profesores()->attach($lucia->id);
}

// 3 clases validadas en septiembre 2026
for ($i = 1; $i <= 3; $i++) {
    $c = \App\Models\Clase::create([
        'grupo_id' => $grupo->id,
        'fecha' => '2026-09-' . str_pad($i, 2, '0', STR_PAD_LEFT),
        'hora_inicio' => '10:00',
        'hora_fin' => '11:00',
        'cancelada' => false,
        'validada_para_liquidacion' => true
    ]);
    $c->profesores()->attach($lucia->id);
}

// 2 clases sin asistencia en octubre 2026 (pasadas, no validadas, sin asistencias)
for ($i = 1; $i <= 2; $i++) {
    $c = \App\Models\Clase::create([
        'grupo_id' => $grupo->id,
        'fecha' => '2026-10-2' . $i,
        'hora_inicio' => '15:00',
        'hora_fin' => '16:00',
        'cancelada' => false,
        'validada_para_liquidacion' => false
    ]);
    $c->profesores()->attach($lucia->id);
}

// 1 clase sin asistencia en septiembre 2026 (pasada, no validada, sin asistencias)
$cSinSep = \App\Models\Clase::create([
    'grupo_id' => $grupo->id,
    'fecha' => '2026-09-28',
    'hora_inicio' => '15:00',
    'hora_fin' => '16:00',
    'cancelada' => false,
    'validada_para_liquidacion' => false
]);
$cSinSep->profesores()->attach($lucia->id);

echo "Escenario T87 sembrado correctamente.\n";
