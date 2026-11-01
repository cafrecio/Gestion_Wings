<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Nivel;
use App\Models\Profesor;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Migrando y sembrando para T113 en wings_testing_gemini...\n";
Artisan::call('migrate:fresh', ['--force' => true]);
(new CatalogosSeeder())->run();

DB::table('primera_carga')->updateOrInsert(['id' => 1], [
    'estado'     => 'TERMINADA',
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

$deporte = Deporte::firstOrCreate(['nombre' => 'Patin'], [
    'tipo_liquidacion' => 'HORA',
    'activo' => true,
]);

$nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);

$grupo = Grupo::firstOrCreate(
    ['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id],
    ['activo' => true]
);

$profesor = Profesor::create([
    'deporte_id' => $deporte->id,
    'nombre' => 'Lucia',
    'apellido' => 'Mendez',
    'dni' => '30111222',
    'fecha_nacimiento' => '1985-05-15',
    'direccion' => 'Calle Falsa 123',
    'localidad' => 'Prueba',
    'valor_hora' => 1000.00,
    'activo' => true,
]);

// Caso 1: Clase pasada (10/10/2026), validada, sin liquidación
$clase1 = Clase::create([
    'grupo_id' => $grupo->id,
    'fecha' => '2026-10-10',
    'hora_inicio' => '10:00:00',
    'hora_fin' => '11:00:00',
    'validada_para_liquidacion' => true,
]);
$clase1->profesores()->attach($profesor->id);

// Caso 2: Clase futura (19/12/2026), validada, sin liquidación
$clase2 = Clase::create([
    'grupo_id' => $grupo->id,
    'fecha' => '2026-12-19',
    'hora_inicio' => '10:00:00',
    'hora_fin' => '11:00:00',
    'validada_para_liquidacion' => true,
]);
$clase2->profesores()->attach($profesor->id);

// Caso 6: Clase en liquidación por hora ABIERTA (12/10/2026)
$clase3 = Clase::create([
    'grupo_id' => $grupo->id,
    'fecha' => '2026-10-12',
    'hora_inicio' => '10:00:00',
    'hora_fin' => '11:00:00',
    'validada_para_liquidacion' => true,
]);
$clase3->profesores()->attach($profesor->id);

$liq = Liquidacion::create([
    'profesor_id' => $profesor->id,
    'mes' => 10,
    'anio' => 2026,
    'tipo' => Liquidacion::TIPO_HORA,
    'estado' => Liquidacion::ESTADO_ABIERTA,
    'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
    'total_calculado' => 1000.00,
    'admin_id' => $admin->id,
]);

LiquidacionDetalle::create([
    'liquidacion_id' => $liq->id,
    'tipo_referencia' => LiquidacionDetalle::TIPO_CLASE,
    'referencia_id' => $clase3->id,
    'monto' => 1000.00,
    'minutos' => 60,
    'descripcion' => 'Clase 12/10/2026 - Patin (Validada manual)',
]);

// Caso 9: Clase para doble clic / pantalla vieja (14/10/2026)
$clase4 = Clase::create([
    'grupo_id' => $grupo->id,
    'fecha' => '2026-10-14',
    'hora_inicio' => '10:00:00',
    'hora_fin' => '11:00:00',
    'validada_para_liquidacion' => true,
]);
$clase4->profesores()->attach($profesor->id);

echo "Sembrado completado para T113.\n";
echo "IDs de clases creadas:\n";
echo "Caso 1 (pasada validada): {$clase1->id}\n";
echo "Caso 2 (futura validada): {$clase2->id}\n";
echo "Caso 6 (en liquidacion abierta): {$clase3->id}\n";
echo "Caso 9 (doble clic / pantalla vieja): {$clase4->id}\n";
