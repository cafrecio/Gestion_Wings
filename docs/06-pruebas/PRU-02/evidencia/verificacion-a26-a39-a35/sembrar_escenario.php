<?php

require_once __DIR__ . '/../../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Sembrando escenario en base: " . config('database.connections.mysql.database') . PHP_EOL;

if (config('database.connections.mysql.database') !== 'wings_testing_gemini') {
    die("ERROR: Base de datos no es wings_testing_gemini\n");
}

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
MovimientoOperativo::truncate();
CajaOperativa::truncate();
DB::table('pagos')->truncate();
DB::table('deuda_cuotas')->truncate();
DB::table('cargo_alumno_eventos')->truncate();
DB::table('cargos_alumno')->truncate();
DB::table('alumno_planes')->truncate();
Alumno::truncate();
GrupoPlan::truncate();
Grupo::truncate();
Nivel::truncate();
Deporte::truncate();
Subrubro::truncate();
Rubro::truncate();
User::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

$seeder = new CatalogosSeeder();
$seeder->run();
Configuracion::set('inscripcion_importe', '5000');

$admin = User::create([
    'name' => 'Carlos Admin',
    'email' => 'admin@wings.com',
    'password' => Hash::make('password'),
    'rol' => User::ROL_ADMIN,
    'activo' => true,
]);

$sandra = User::create([
    'name' => 'Sandra Vidal',
    'email' => 'sandra@wings.com',
    'password' => Hash::make('password'),
    'rol' => User::ROL_OPERATIVO,
    'activo' => true,
]);

$marcos = User::create([
    'name' => 'Marcos Peña',
    'email' => 'marcos@wings.com',
    'password' => Hash::make('password'),
    'rol' => User::ROL_OPERATIVO,
    'activo' => true,
]);

$laura = User::create([
    'name' => 'Laura Gómez',
    'email' => 'laura@wings.com',
    'password' => Hash::make('password'),
    'rol' => User::ROL_PROFESOR,
    'activo' => true,
]);

$tipoEfe = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['abreviatura' => 'EFE', 'activo' => true]);
$deporte = Deporte::firstOrCreate(['nombre' => 'Acrobacia'], ['activo' => true]);
$nivel = Nivel::firstOrCreate(['nombre' => 'Inicial']);
$grupo = Grupo::create([
    'nombre' => 'Acrobacia Niños',
    'deporte_id' => $deporte->id,
    'nivel_id' => $nivel->id,
    'activo' => true,
]);
$plan = GrupoPlan::create([
    'grupo_id' => $grupo->id,
    'clases_por_semana' => 2,
    'precio_mensual' => 25000,
    'activo' => true,
]);

// Cajas operativas abiertas
$cajaSandra = CajaOperativa::create([
    'usuario_operativo_id' => $sandra->id,
    'tipo_caja_efectivo_id' => $tipoEfe->id,
    'efectivo_inicial' => 10000,
    'estado' => CajaOperativa::ESTADO_ABIERTA,
    'apertura_at' => now(),
]);

$cajaMarcos = CajaOperativa::create([
    'usuario_operativo_id' => $marcos->id,
    'tipo_caja_efectivo_id' => $tipoEfe->id,
    'efectivo_inicial' => 5000,
    'estado' => CajaOperativa::ESTADO_ABIERTA,
    'apertura_at' => now(),
]);

$cajaAdmin = CajaOperativa::create([
    'usuario_operativo_id' => $admin->id,
    'tipo_caja_efectivo_id' => $tipoEfe->id,
    'efectivo_inicial' => 50000,
    'estado' => CajaOperativa::ESTADO_ABIERTA,
    'apertura_at' => now(),
]);

// Rubros y Subrubros para A39
$rubroOperativoIng = Rubro::create(['nombre' => 'Cobranzas', 'tipo' => 'INGRESO']);
$rubroOperativoEgr = Rubro::create(['nombre' => 'Gastos Mostrador', 'tipo' => 'EGRESO']);
$rubroAdminSueldo = Rubro::create(['nombre' => 'Sueldos Profesores', 'tipo' => 'EGRESO']);
$rubroAdminAlquiler = Rubro::create(['nombre' => 'Alquiler Cancha', 'tipo' => 'EGRESO']);
$rubroMixto = Rubro::create(['nombre' => 'Mantenimiento General', 'tipo' => 'EGRESO']);

$subOperativoIng = Subrubro::create([
    'rubro_id' => $rubroOperativoIng->id,
    'nombre' => 'Cuotas Gimnasia',
    'permitido_para' => 'OPERATIVO',
    'afecta_caja' => true,
    'activo' => true,
]);
$subOperativoEgr = Subrubro::create([
    'rubro_id' => $rubroOperativoEgr->id,
    'nombre' => 'Librería y Papelería',
    'permitido_para' => 'OPERATIVO',
    'afecta_caja' => true,
    'activo' => true,
]);
$subAdminSueldo = Subrubro::create([
    'rubro_id' => $rubroAdminSueldo->id,
    'nombre' => 'Sueldo Profesor Titular',
    'permitido_para' => 'ADMIN',
    'afecta_caja' => true,
    'activo' => true,
]);
$subAdminAlquiler = Subrubro::create([
    'rubro_id' => $rubroAdminAlquiler->id,
    'nombre' => 'Alquiler Predio Mensual',
    'permitido_para' => 'ADMIN',
    'afecta_caja' => true,
    'activo' => true,
]);
$subMixtoAdmin = Subrubro::create([
    'rubro_id' => $rubroMixto->id,
    'nombre' => 'Reparación Techo Estructural',
    'permitido_para' => 'ADMIN',
    'afecta_caja' => true,
    'activo' => true,
]);
$subMixtoOperativo = Subrubro::create([
    'rubro_id' => $rubroMixto->id,
    'nombre' => 'Pintura y Arreglos Menores',
    'permitido_para' => 'OPERATIVO',
    'afecta_caja' => true,
    'activo' => true,
]);

// 1. Sandra - rubro operativo (Ingreso $15.000)
MovimientoOperativo::create([
    'caja_operativa_id' => $cajaSandra->id,
    'usuario_id' => $sandra->id,
    'subrubro_id' => $subOperativoIng->id,
    'tipo_caja_id' => $tipoEfe->id,
    'observaciones' => 'Cobro Cuota Alumno Prueba por Sandra',
    'monto' => 15000,
    'fecha' => now()->toDateString(),
    'estado' => MovimientoOperativo::ESTADO_ACTIVO,
]);

// 2. Marcos - rubro operativo (Egreso $7.500)
MovimientoOperativo::create([
    'caja_operativa_id' => $cajaMarcos->id,
    'usuario_id' => $marcos->id,
    'subrubro_id' => $subOperativoEgr->id,
    'tipo_caja_id' => $tipoEfe->id,
    'observaciones' => 'Compra Artículos Mostrador por Marcos',
    'monto' => 7500,
    'fecha' => now()->toDateString(),
    'estado' => MovimientoOperativo::ESTADO_ACTIVO,
]);

// 3. Admin - rubro admin sueldos (Egreso $250.000)
MovimientoOperativo::create([
    'caja_operativa_id' => $cajaAdmin->id,
    'usuario_id' => $admin->id,
    'subrubro_id' => $subAdminSueldo->id,
    'tipo_caja_id' => $tipoEfe->id,
    'observaciones' => 'Pago Sueldo Profesor Septiembre por Admin',
    'monto' => 250000,
    'fecha' => now()->toDateString(),
    'estado' => MovimientoOperativo::ESTADO_ACTIVO,
]);

// 4. Admin - rubro admin alquiler (Egreso $50.000)
MovimientoOperativo::create([
    'caja_operativa_id' => $cajaAdmin->id,
    'usuario_id' => $admin->id,
    'subrubro_id' => $subAdminAlquiler->id,
    'tipo_caja_id' => $tipoEfe->id,
    'observaciones' => 'Pago Alquiler Canchas por Admin',
    'monto' => 50000,
    'fecha' => now()->toDateString(),
    'estado' => MovimientoOperativo::ESTADO_ACTIVO,
]);

// 5. Admin - rubro mixto subrubro admin (Egreso $12.000)
MovimientoOperativo::create([
    'caja_operativa_id' => $cajaAdmin->id,
    'usuario_id' => $admin->id,
    'subrubro_id' => $subMixtoAdmin->id,
    'tipo_caja_id' => $tipoEfe->id,
    'observaciones' => 'Reparación Estructural Galpón por Admin',
    'monto' => 12000,
    'fecha' => now()->toDateString(),
    'estado' => MovimientoOperativo::ESTADO_ACTIVO,
]);

// 6. Sandra - rubro mixto subrubro operativo (Egreso $3.000)
MovimientoOperativo::create([
    'caja_operativa_id' => $cajaSandra->id,
    'usuario_id' => $sandra->id,
    'subrubro_id' => $subMixtoOperativo->id,
    'tipo_caja_id' => $tipoEfe->id,
    'observaciones' => 'Compra Pintura Puerta por Sandra',
    'monto' => 3000,
    'fecha' => now()->toDateString(),
    'estado' => MovimientoOperativo::ESTADO_ACTIVO,
]);

echo "Escenario sembrado con éxito en wings_testing_gemini." . PHP_EOL;
