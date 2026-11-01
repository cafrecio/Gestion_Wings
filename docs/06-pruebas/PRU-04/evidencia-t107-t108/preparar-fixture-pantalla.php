<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CajaDeclarada;

if (DB::connection()->getDatabaseName() !== 'wings_testing_gemini') {
    throw new RuntimeException('BASE PROHIBIDA: Se debe correr exclusivamente sobre wings_testing_gemini');
}

Carbon::setTestNow('2026-11-01 10:00:00');

Artisan::call('migrate:fresh', ['--force' => true]);
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);

// Usuarios de prueba
$admin = User::factory()->create([
    'name' => 'Carlos Admin',
    'email' => 'admin@wings.test',
    'password' => Hash::make('Password123!'),
    'rol' => User::ROL_ADMIN,
    'activo' => true,
]);

$operativa = User::factory()->create([
    'name' => 'Rocío Operativa',
    'email' => 'operativa@wings.test',
    'password' => Hash::make('Password123!'),
    'rol' => User::ROL_OPERATIVO,
    'activo' => true,
]);

// Configuración de Efectivo y Matrícula
$efectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);
app(CajaService::class)->configurarMostrador($efectivo->id, $admin->id);

Configuracion::updateOrCreate(
    ['clave' => 'monto_matricula'],
    ['valor' => '8000.00']
);

$rubro = Rubro::firstOrCreate(['nombre' => 'Ingresos'], ['tipo' => 'INGRESO']);
$subrubroCuota = Subrubro::firstOrCreate(['nombre' => 'Cobro de cuota'], [
    'rubro_id' => $rubro->id,
    'permitido_para' => 'OPERATIVO',
    'afecta_caja' => true,
    'clasificacion_resultado' => 'NEGOCIO',
    'activo' => true,
]);

// Catálogos
$depFutbol = Deporte::create(['nombre' => 'Fútbol', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivFutbol = Nivel::create(['nombre' => 'Juveniles', 'activo' => true]);
$grFutbol = Grupo::create(['deporte_id' => $depFutbol->id, 'nivel_id' => $nivFutbol->id, 'activo' => true]);
$planFutbol = GrupoPlan::create(['grupo_id' => $grFutbol->id, 'clases_por_semana' => 2, 'precio_mensual' => 20000, 'activo' => true]);

$depPatin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivPatin = Nivel::create(['nombre' => 'Inicial', 'activo' => true]);
$grPatin = Grupo::create(['deporte_id' => $depPatin->id, 'nivel_id' => $nivPatin->id, 'activo' => true]);
$planPatin = GrupoPlan::create(['grupo_id' => $grPatin->id, 'clases_por_semana' => 2, 'precio_mensual' => 25000, 'activo' => true]);

// Alumno Thiago Silva ya en el club en Fútbol (sin inscripción registrada, caso Thiago día 46.4)
$thiago = Alumno::create([
    'nombre' => 'Thiago',
    'apellido' => 'Silva',
    'dni' => '45000001',
    'fecha_nacimiento' => '2010-05-15',
    'fecha_alta' => '2026-03-01',
    'celular' => '1144445555',
    'deporte_id' => $depFutbol->id,
    'grupo_id' => $grFutbol->id,
    'activo' => true,
]);
AlumnoPlan::create([
    'alumno_id' => $thiago->id,
    'plan_id' => $planFutbol->id,
    'fecha_desde' => '2026-03-01',
    'activo' => true,
]);

// Caja abierta para Rocío Operativa con efectivo inicial $11.600,00
$caja = CajaDeclarada::crear($operativa->id, $efectivo->id);
$caja->update(['efectivo_inicial' => '11600.00']);

echo json_encode([
    'base' => DB::connection()->getDatabaseName(),
    'admin_id' => $admin->id,
    'operativa_id' => $operativa->id,
    'caja_id' => $caja->id,
    'efectivo_inicial' => '11600.00',
    'thiago_dni' => '45000001',
    'patin_deporte_id' => $depPatin->id,
    'patin_grupo_id' => $grPatin->id,
    'patin_plan_id' => $planPatin->id,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
