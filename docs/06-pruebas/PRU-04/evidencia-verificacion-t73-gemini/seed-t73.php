<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Deporte;
use App\Models\Nivel;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Profesor;
use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Clase;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "Iniciando seed de prueba para verificación T73...\n";

// 1. Usuario Administrador y Primera Carga
$admin = User::firstOrNew(['email' => 'admin@wings.test']);
$admin->name = 'Admin T73';
$admin->password = Hash::make('password');
$admin->rol = 'ADMIN';
$admin->activo = true;
$admin->save();
echo "✓ Admin: {$admin->email} (id: {$admin->id})\n";

\App\Models\PrimeraCarga::updateOrCreate(['id' => 1], ['estado' => 'COMPLETADA']);
echo "✓ Primera Carga marcada como COMPLETADA\n";

// 2. Deportes
$dep1 = Deporte::firstOrCreate(
    ['nombre' => 'Gimnasia Artística'],
    ['slug' => 'gimnasia-artistica', 'activo' => true, 'tipo_liquidacion' => 'HORA', 'color' => '#3b82f6']
);
$dep2 = Deporte::firstOrCreate(
    ['nombre' => 'Acrobacia en Tela'],
    ['slug' => 'acrobacia-tela', 'activo' => true, 'tipo_liquidacion' => 'COMISION', 'color' => '#10b981']
);
echo "✓ Deportes: {$dep1->nombre} (id: {$dep1->id}), {$dep2->nombre} (id: {$dep2->id})\n";

// 3. Niveles
$nivel1 = Nivel::firstOrCreate(['nombre' => 'Iniciación T73']);
$nivel2 = Nivel::firstOrCreate(['nombre' => 'Juvenil T73']);
$nivelEliminable = Nivel::firstOrCreate(['nombre' => 'Nivel Borrable T73']);
echo "✓ Nivel eliminable: {$nivelEliminable->nombre} (id: {$nivelEliminable->id})\n";

// 4. Grupos y Planes
$grupo1 = Grupo::firstOrCreate(
    ['deporte_id' => $dep1->id, 'nivel_id' => $nivel1->id],
    ['activo' => true]
);
$plan1 = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupo1->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 45000, 'activo' => true]
);
$planEliminable = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupo1->id, 'clases_por_semana' => 3],
    ['precio_mensual' => 55000, 'activo' => true]
);

$grupo2 = Grupo::firstOrCreate(
    ['deporte_id' => $dep2->id, 'nivel_id' => $nivel2->id],
    ['activo' => true]
);
$plan2 = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupo2->id, 'clases_por_semana' => 1],
    ['precio_mensual' => 35000, 'activo' => true]
);
echo "✓ Grupos y planes creados (Grupo 1 id: {$grupo1->id}, Plan borrable id: {$planEliminable->id})\n";

// 5. Profesores
$prof1 = Profesor::firstOrCreate(
    ['email' => 'laura.artistica@wings.test'],
    [
        'nombre' => 'Laura',
        'apellido' => 'Gimenez',
        'dni' => '30111222',
        'fecha_nacimiento' => '1990-01-01',
        'direccion' => 'Av. Corrientes 1000',
        'localidad' => 'CABA',
        'telefono' => '1144556677',
        'deporte_id' => $dep1->id,
        'activo' => true,
        'valor_hora' => 5000,
        'porcentaje_comision' => 50,
    ]
);

$profAux = Profesor::firstOrCreate(
    ['email' => 'carolina.aux@wings.test'],
    [
        'nombre' => 'Carolina',
        'apellido' => 'Auxiliar',
        'dni' => '30111333',
        'fecha_nacimiento' => '1992-02-02',
        'direccion' => 'Av. Rivadavia 2000',
        'localidad' => 'CABA',
        'telefono' => '1144556688',
        'deporte_id' => $dep1->id,
        'activo' => true,
        'valor_hora' => 4500,
        'porcentaje_comision' => 50,
    ]
);

$prof2 = Profesor::firstOrCreate(
    ['email' => 'marcos.acrobacia@wings.test'],
    [
        'nombre' => 'Marcos',
        'apellido' => 'Valenzuela',
        'dni' => '30111444',
        'fecha_nacimiento' => '1988-08-08',
        'direccion' => 'Av. Santa Fe 3000',
        'localidad' => 'CABA',
        'telefono' => '1199887766',
        'deporte_id' => $dep2->id,
        'activo' => true,
        'valor_hora' => 6000,
        'porcentaje_comision' => 45,
    ]
);
echo "✓ Profesores creados: {$prof1->apellido}, {$profAux->apellido}, {$prof2->apellido}\n";

// 6. Alumnos para búsqueda y asistencia
$alum1 = Alumno::firstOrCreate(
    ['dni' => '40111222'],
    [
        'nombre' => 'Marcos',
        'apellido' => 'Gomez',
        'fecha_nacimiento' => '2015-05-15',
        'celular' => '1155667788',
        'deporte_id' => $dep1->id,
        'grupo_id' => $grupo1->id,
        'activo' => true,
        'fecha_alta' => today()->subMonths(2),
    ]
);
AlumnoPlan::firstOrCreate(
    ['alumno_id' => $alum1->id, 'plan_id' => $plan1->id],
    ['fecha_desde' => today()->subMonths(2), 'activo' => true]
);

$alum2 = Alumno::firstOrCreate(
    ['dni' => '40111333'],
    [
        'nombre' => 'Mariano',
        'apellido' => 'Gomez',
        'fecha_nacimiento' => '2015-06-16',
        'celular' => '1155667788',
        'deporte_id' => $dep1->id,
        'grupo_id' => $grupo1->id,
        'activo' => true,
        'fecha_alta' => today()->subMonths(2),
    ]
);
AlumnoPlan::firstOrCreate(
    ['alumno_id' => $alum2->id, 'plan_id' => $plan1->id],
    ['fecha_desde' => today()->subMonths(2), 'activo' => true]
);

// Alumno con <b> en su nombre para probar seguridad XSS
$alum3 = Alumno::firstOrCreate(
    ['dni' => '40111444'],
    [
        'nombre' => '<b>Martin</b>',
        'apellido' => 'Gomez',
        'fecha_nacimiento' => '2015-07-17',
        'celular' => '1155667788',
        'deporte_id' => $dep1->id,
        'grupo_id' => $grupo1->id,
        'activo' => true,
        'fecha_alta' => today()->subMonths(2),
    ]
);
AlumnoPlan::firstOrCreate(
    ['alumno_id' => $alum3->id, 'plan_id' => $plan1->id],
    ['fecha_desde' => today()->subMonths(2), 'activo' => true]
);
echo "✓ Alumnos creados (3 con Gómez, uno con <b>Martin</b>)\n";

// 7. Clases
// Clases previas de la semana para que Alum 1 tenga 2 asistencias (su plan es de 2 clases/semana)
$lunes = today()->startOfWeek();
$clasePrev1 = Clase::firstOrCreate(
    ['grupo_id' => $grupo1->id, 'fecha' => $lunes->toDateString(), 'hora_inicio' => '08:00:00'],
    ['hora_fin' => '09:00:00', 'cancelada' => false, 'validada_para_liquidacion' => true]
);
$clasePrev1->profesores()->syncWithoutDetaching([$prof1->id]);
Asistencia::firstOrCreate(['clase_id' => $clasePrev1->id, 'alumno_id' => $alum1->id], ['presente' => true]);

$clasePrev2 = Clase::firstOrCreate(
    ['grupo_id' => $grupo1->id, 'fecha' => $lunes->toDateString(), 'hora_inicio' => '09:00:00'],
    ['hora_fin' => '10:00:00', 'cancelada' => false, 'validada_para_liquidacion' => true]
);
$clasePrev2->profesores()->syncWithoutDetaching([$prof1->id]);
Asistencia::firstOrCreate(['clase_id' => $clasePrev2->id, 'alumno_id' => $alum1->id], ['presente' => true]);

// Clase de hoy para Grupo 1 con Prof 1 (limpiar asistencias y dejar solo Prof 1)
$claseHoy = Clase::firstOrCreate(
    ['grupo_id' => $grupo1->id, 'fecha' => today()->toDateString(), 'hora_inicio' => '10:00:00'],
    ['hora_fin' => '11:00:00', 'cancelada' => false, 'validada_para_liquidacion' => false]
);
$claseHoy->profesores()->sync([$prof1->id]);
Asistencia::where('clase_id', $claseHoy->id)->delete();

// Clase anterior del mes para Prof 2 SIN asistencia (para que salte el aviso en liquidaciones/create)
$fechaPasadaMes = today()->day > 1 ? today()->subDay()->toDateString() : today()->toDateString();
$claseSinAsistencia = Clase::firstOrCreate(
    ['grupo_id' => $grupo2->id, 'fecha' => $fechaPasadaMes, 'hora_inicio' => '15:00:00'],
    ['hora_fin' => '16:00:00', 'cancelada' => false, 'validada_para_liquidacion' => false]
);
$claseSinAsistencia->profesores()->syncWithoutDetaching([$prof2->id]);
// Asegurar que no tenga asistencias
Asistencia::where('clase_id', $claseSinAsistencia->id)->delete();
echo "✓ Clase de hoy (id: {$claseHoy->id}) y Clase sin asistencia de Prof 2 (id: {$claseSinAsistencia->id})\n";

// 8. Caja y Movimiento
$tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);

// Buscar o crear rubro y subrubro de Ingreso y Egreso
$rubroIngreso = Rubro::firstOrCreate(
    ['nombre' => 'Cuotas Gimnasia'],
    ['tipo' => 'INGRESO', 'activo' => true, 'es_reservado_sistema' => false]
);
$subIngreso = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroIngreso->id, 'nombre' => 'Cuota Mensual Infantil'],
    ['activo' => true, 'es_reservado_sistema' => false]
);

$rubroIngresoBorrable = Rubro::firstOrCreate(
    ['nombre' => 'Rubro Ingreso Borrable T73'],
    ['tipo' => 'INGRESO', 'activo' => true, 'es_reservado_sistema' => false]
);

$rubroEgreso = Rubro::firstOrCreate(
    ['nombre' => 'Materiales'],
    ['tipo' => 'EGRESO', 'activo' => true, 'es_reservado_sistema' => false]
);
$subEgreso = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroEgreso->id, 'nombre' => 'Colchonetas'],
    ['activo' => true, 'es_reservado_sistema' => false]
);

// Rubro borrable con comillas para la prueba del confirm
$rubroConComillas = Rubro::firstOrCreate(
    ['nombre' => 'Rubro "Especial T73"'],
    ['tipo' => 'EGRESO', 'activo' => true, 'es_reservado_sistema' => false]
);

$caja = CajaOperativa::firstOrCreate(
    ['usuario_operativo_id' => $admin->id, 'estado' => 'ABIERTA'],
    ['apertura_at' => now(), 'efectivo_inicial' => 20000, 'tipo_caja_efectivo_id' => $tipoCaja->id]
);

$movimiento = MovimientoOperativo::firstOrCreate(
    ['caja_operativa_id' => $caja->id, 'observaciones' => 'Compra de cinta T73'],
    [
        'monto' => 1500,
        'tipo_caja_id' => $tipoCaja->id,
        'subrubro_id' => $subEgreso->id,
        'fecha' => today()->toDateString(),
        'usuario_id' => $admin->id,
        'estado' => 'ACTIVO',
    ]
);
echo "✓ Caja abierta (id: {$caja->id}) con movimiento editable (id: {$movimiento->id})\n";
echo "✓ Rubro con comillas id: {$rubroConComillas->id}\n";

// 9. Liquidación en estado BORRADOR (ABIERTA) del mes anterior para que puedeCerrarse() sea true y aparezca el botón Cerrar
$mesLiq = today()->subMonth()->month;
$anioLiq = today()->subMonth()->year;
$liq = Liquidacion::updateOrCreate(
    ['profesor_id' => $prof1->id, 'mes' => $mesLiq, 'anio' => $anioLiq, 'tipo' => 'HORA'],
    [
        'estado' => Liquidacion::ESTADO_ABIERTA,
        'estado_pago' => Liquidacion::ESTADO_PAGO_PENDIENTE,
        'valor_hora_aplicado' => 5000,
        'total_calculado' => 5000,
        'monto_final' => 5000,
    ]
);
echo "✓ Liquidación abierta creada (id: {$liq->id}) para Prof {$prof1->apellido}\n";

echo "Seed completado con éxito.\n";
