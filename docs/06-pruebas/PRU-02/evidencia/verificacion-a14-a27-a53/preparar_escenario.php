<?php
require __DIR__ . '/../../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Deporte;
use App\Models\Nivel;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Alumno;
use App\Models\Profesor;
use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\DeudaCuota;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

// 1. Usuarios
User::updateOrCreate(
    ['email' => 'admin@wings.com'],
    ['name' => 'Admin Wings', 'rol' => User::ROL_ADMIN, 'activo' => true, 'password' => 'password123']
);
User::updateOrCreate(
    ['email' => 'operativo@wings.com'],
    ['name' => 'Operativo Wings', 'rol' => User::ROL_OPERATIVO, 'activo' => true, 'password' => 'password123']
);

// 2. Deporte y Nivel largo (A53)
$deporteLargo = Deporte::firstOrCreate(['nombre' => 'Entrenamiento Especial Federadas Patín Artístico'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivelLargo = Nivel::firstOrCreate(['nombre' => 'Avanzado Competitivo Internacional']);

// Deporte y Nivel corto para comparar A53 punto 5
$deporteCorto = Deporte::firstOrCreate(['nombre' => 'Patín'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$nivelCorto = Nivel::firstOrCreate(['nombre' => 'Iniciales']);

// 3. Grupo con nombre largo y 3 tarifas de 7 cifras (A53)
$grupoLargo = Grupo::firstOrCreate(
    ['deporte_id' => $deporteLargo->id, 'nivel_id' => $nivelLargo->id],
    ['activo' => true]
);

GrupoPlan::updateOrCreate(
    ['grupo_id' => $grupoLargo->id, 'clases_por_semana' => 1],
    ['precio_mensual' => 1250000.00, 'activo' => true]
);
GrupoPlan::updateOrCreate(
    ['grupo_id' => $grupoLargo->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 2450000.00, 'activo' => true]
);
GrupoPlan::updateOrCreate(
    ['grupo_id' => $grupoLargo->id, 'clases_por_semana' => 3],
    ['precio_mensual' => 3850000.00, 'activo' => true]
);

// Grupo corto para comparar A53 punto 5
$grupoCorto = Grupo::firstOrCreate(
    ['deporte_id' => $deporteCorto->id, 'nivel_id' => $nivelCorto->id],
    ['activo' => true]
);
GrupoPlan::updateOrCreate(
    ['grupo_id' => $grupoCorto->id, 'clases_por_semana' => 1],
    ['precio_mensual' => 25000.00, 'activo' => true]
);

// 4. Alumno para el selector de cobro (A53) y prueba de DNI repetido (A14)
$alumno = Alumno::updateOrCreate(
    ['dni' => '40111222'],
    [
        'nombre' => 'Valentina Guillermina',
        'apellido' => 'Domínguez de la Sierra',
        'deporte_id' => $deporteLargo->id,
        'grupo_id' => $grupoLargo->id,
        'celular' => '11-4500-8888',
        'fecha_nacimiento' => '2012-05-15',
        'fecha_alta' => Carbon::now()->subMonths(2)->format('Y-m-d'),
        'activo' => true
    ]
);

// Deuda para que aparezca en el selector de cobrar cuota
DeudaCuota::updateOrCreate(
    ['alumno_id' => $alumno->id, 'periodo' => Carbon::now()->format('Y-m')],
    [
        'monto_original' => 1250000.00,
        'monto_adeudado' => 1250000.00,
        'estado' => DeudaCuota::ESTADO_PENDIENTE,
        'fecha_vencimiento' => Carbon::now()->addDays(5)->format('Y-m-d')
    ]
);

// 5. Profesor existente para editar (A14)
$profesor = Profesor::firstOrCreate(
    ['email' => 'profesor.titular@wings.com'],
    [
        'nombre' => 'Mariela',
        'apellido' => 'Ocampo',
        'dni' => '28111222',
        'fecha_nacimiento' => '1985-04-12',
        'direccion' => 'Av. Rivadavia 1234',
        'localidad' => 'Morón',
        'deporte_id' => $deporteCorto->id,
        'celular' => '11-4500-9999',
        'activo' => true
    ]
);

// 6. Caja abierta para Operativo (A27)
$tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo Mostrador'], ['activo' => true]);
$operativo = User::where('email', 'operativo@wings.com')->first();
$admin = User::where('email', 'admin@wings.com')->first();

CajaOperativa::updateOrCreate(
    ['usuario_apertura_id' => $operativo->id, 'estado' => CajaOperativa::ESTADO_ABIERTA],
    [
        'usuario_operativo_id' => $operativo->id,
        'tipo_caja_efectivo_id' => $tipoCaja->id,
        'apertura_at' => Carbon::now(),
        'efectivo_inicial' => 10000.00,
        'efectivo_esperado' => 10000.00,
        'motivo_apertura' => 'Caja de prueba operativa'
    ]
);

// 7. Rubros y Subrubros para Movimiento (A27)
$rubro = Rubro::firstOrCreate(
    ['nombre' => 'Insumos de Limpieza'],
    ['tipo' => 'EGRESO', 'activo' => true]
);
Subrubro::firstOrCreate(
    ['nombre' => 'Artículos de Mantenimiento', 'rubro_id' => $rubro->id],
    ['activo' => true]
);

echo "Escenario de verificación inicializado exitosamente.\n";
