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
use App\Models\Clase;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\DeudaCuota;
use App\Models\MovimientoOperativo;
use App\Models\Liquidacion;
use Carbon\Carbon;

echo "Iniciando carga de datos de estrés en wings_testing_gemini...\n";

// 1. Deportes y Niveles (largos y normales)
$deportePatin = Deporte::firstOrCreate(
    ['nombre' => 'Entrenamiento Especial Federadas Patín Artístico'],
    ['tipo_liquidacion' => 'HORA', 'activo' => true]
);
$deporteFutbol = Deporte::firstOrCreate(
    ['nombre' => 'Fútbol Competitivo Juvenil Masculino'],
    ['tipo_liquidacion' => 'HORA', 'activo' => true]
);
$nivelAvanzado = Nivel::firstOrCreate(['nombre' => 'Avanzadas Competición Nacional']);
$nivelInicial = Nivel::firstOrCreate(['nombre' => 'Iniciales Formativo']);

// 2. Grupos
$grupo1 = Grupo::firstOrCreate(
    ['deporte_id' => $deportePatin->id, 'nivel_id' => $nivelAvanzado->id],
    ['activo' => true]
);
$grupo2 = Grupo::firstOrCreate(
    ['deporte_id' => $deporteFutbol->id, 'nivel_id' => $nivelAvanzado->id],
    ['activo' => true]
);

GrupoPlan::updateOrCreate(
    ['grupo_id' => $grupo1->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 1850000.00, 'activo' => true]
);
GrupoPlan::updateOrCreate(
    ['grupo_id' => $grupo2->id, 'clases_por_semana' => 3],
    ['precio_mensual' => 2450000.00, 'activo' => true]
);

// 3. Profesores
$profesor1 = Profesor::updateOrCreate(
    ['email' => 'profesor.quintana@wings.com'],
    [
        'nombre' => 'Quintana',
        'apellido' => 'Maximiliano Hernán',
        'dni' => '28111222',
        'fecha_nacimiento' => '1985-04-12',
        'direccion' => 'Av. Rivadavia 12345, Piso 4 B',
        'localidad' => 'Castelar Norte',
        'deporte_id' => $deporteFutbol->id,
        'celular' => '11-4500-9999',
        'activo' => true
    ]
);

$profesor2 = Profesor::updateOrCreate(
    ['email' => 'profesora.mariela@wings.com'],
    [
        'nombre' => 'Mariela Soledad',
        'apellido' => 'Ocampo de los Santos',
        'dni' => '29333444',
        'fecha_nacimiento' => '1987-08-20',
        'direccion' => 'Belgrano 456',
        'localidad' => 'Morón',
        'deporte_id' => $deportePatin->id,
        'celular' => '11-4500-7777',
        'activo' => true
    ]
);

// 4. Usuarios para los 3 roles
$admin = User::firstOrNew(['email' => 'admin@wings.com']);
$admin->name = 'Admin de Prueba General';
$admin->rol = User::ROL_ADMIN;
$admin->activo = true;
$admin->password = 'password123';
$admin->save();

$operativo = User::firstOrNew(['email' => 'operativo@wings.com']);
$operativo->name = 'Operativo Mostrador Sandra';
$operativo->rol = User::ROL_OPERATIVO;
$operativo->activo = true;
$operativo->password = 'password123';
$operativo->save();

$userProfesor = User::firstOrNew(['email' => 'profesor@wings.com']);
$userProfesor->name = 'Profesor Quintana';
$userProfesor->rol = User::ROL_PROFESOR;
$userProfesor->profesor_id = $profesor1->id;
$userProfesor->activo = true;
$userProfesor->password = 'password123';
$userProfesor->save();

// 5. Crear 20 Alumnos con nombres largos e inscribirlos al grupo
$alumnos = [];
for ($i = 1; $i <= 20; $i++) {
    $dni = str_pad((string)(41000000 + $i), 8, '0', STR_PAD_LEFT);
    $al = Alumno::updateOrCreate(
        ['dni' => $dni],
        [
            'nombre' => "Alumno Nombre Largo $i",
            'apellido' => "Apellido Compuesto $i de la Torre",
            'deporte_id' => $i % 2 === 0 ? $deporteFutbol->id : $deportePatin->id,
            'grupo_id' => $i % 2 === 0 ? $grupo2->id : $grupo1->id,
            'celular' => "11-4500-00" . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            'fecha_nacimiento' => Carbon::now()->subYears(12 + ($i % 8))->format('Y-m-d'),
            'fecha_alta' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'telefono_tutor' => "11-4500-99" . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            'nombre_tutor' => "Tutor Responsable $i",
            'activo' => true
        ]
    );
    $alumnos[] = $al;

    // Deuda para cobranza
    DeudaCuota::updateOrCreate(
        ['alumno_id' => $al->id, 'periodo' => Carbon::now()->format('Y-m')],
        [
            'monto_original' => 1850000.00,
            'monto_adeudado' => 1850000.00,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
            'fecha_vencimiento' => Carbon::now()->addDays(5)->format('Y-m-d')
        ]
    );
}

// 6. Clases de HOY (sábado 10 de octubre)
$hoy = Carbon::today();
$claseHoy1 = Clase::updateOrCreate(
    ['fecha' => $hoy, 'hora_inicio' => '11:00:00', 'grupo_id' => $grupo2->id],
    [
        'hora_fin' => '12:00:00',
        'cancelada' => false,
        'validada_para_liquidacion' => false
    ]
);
$claseHoy1->profesores()->sync([$profesor1->id]);

// Crear asistencias para los 20 alumnos en claseHoy1
foreach ($alumnos as $idx => $al) {
    Asistencia::updateOrCreate(
        ['clase_id' => $claseHoy1->id, 'alumno_id' => $al->id],
        [
            'presente' => $idx % 2 === 0,
            'observaciones' => $idx % 5 === 0 ? 'Observación de asistencia extendida' : null
        ]
    );
}

$claseHoy2 = Clase::updateOrCreate(
    ['fecha' => $hoy, 'hora_inicio' => '15:30:00', 'grupo_id' => $grupo1->id],
    [
        'hora_fin' => '17:00:00',
        'cancelada' => false,
        'validada_para_liquidacion' => false
    ]
);
$claseHoy2->profesores()->sync([$profesor2->id]);

// 7. Caja operativa abierta para Sandra
$tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo Mostrador Central'], ['activo' => true]);

$caja = CajaOperativa::updateOrCreate(
    ['usuario_apertura_id' => $operativo->id, 'estado' => CajaOperativa::ESTADO_ABIERTA],
    [
        'usuario_operativo_id' => $operativo->id,
        'tipo_caja_efectivo_id' => $tipoCaja->id,
        'apertura_at' => Carbon::now()->subHours(2),
        'efectivo_inicial' => 150000.00,
        'efectivo_esperado' => 150000.00,
        'motivo_apertura' => 'Caja del turno mañana'
    ]
);

// 8. Rubros y Subrubros
$rubroIngreso = Rubro::firstOrCreate(
    ['nombre' => 'Cuotas Sociales y Deportivas'],
    ['tipo' => 'INGRESO', 'activo' => true]
);
$subrubroIngreso = Subrubro::firstOrCreate(
    ['nombre' => 'Arancel Mensual Federado', 'rubro_id' => $rubroIngreso->id],
    ['activo' => true]
);

$rubroEgreso = Rubro::firstOrCreate(
    ['nombre' => 'Mantenimiento de Instalaciones Generales'],
    ['tipo' => 'EGRESO', 'activo' => true]
);
$subrubroEgreso = Subrubro::firstOrCreate(
    ['nombre' => 'Reparación de Pista y Cancha', 'rubro_id' => $rubroEgreso->id],
    ['activo' => true]
);

// Movimiento operativo
MovimientoOperativo::updateOrCreate(
    ['caja_operativa_id' => $caja->id, 'subrubro_id' => $subrubroEgreso->id],
    [
        'usuario_id' => $operativo->id,
        'tipo' => 'EGRESO',
        'tipo_caja_id' => $tipoCaja->id,
        'monto' => 450000.00,
        'concepto' => 'Compra urgente de reflectores para pista exterior',
        'created_at' => Carbon::now()->subHour()
    ]
);

// 9. Liquidación para profesor
Liquidacion::updateOrCreate(
    ['profesor_id' => $profesor1->id, 'mes' => 10, 'anio' => 2026],
    [
        'tipo' => Liquidacion::TIPO_HORA,
        'valor_hora_aplicado' => 15000.00,
        'total_calculado' => 150000.00,
        'estado' => Liquidacion::ESTADO_ABIERTA,
        'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE
    ]
);

echo "Datos de estrés cargados correctamente en wings_testing_gemini.\n";
