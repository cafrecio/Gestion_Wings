<?php
/**
 * PRU-04 · Prueba técnica · Volumen de dos años (Claude, 25/10/2026).
 *
 * Carga en la base descartable `wings_testing_claude` un club con dos años de historia, para medir
 * cómo crecen las pantallas: 300 alumnos, cuotas y cobros de 24 meses, una caja validada por día
 * hábil con sus reflejos en Cashflow, y tres clases por semana por grupo con asistencia.
 * Datos ficticios. Se niega a correr en cualquier otra base.
 * Uso: DB_DATABASE=wings_testing_claude php volumen-generar.php <ruta-app>
 *      (antes: migrate:fresh --seeder=CatalogosSeeder en esa misma base)
 */
$base = $argv[1] ?? getcwd();
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') { fwrite(STDERR, "Solo wings_testing_claude.\n"); exit(2); }

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

mt_srand(2026);
$hoy = CarbonImmutable::parse('2026-10-15');
$desde = $hoy->subMonths(24)->startOfMonth();
$t = fn () => now()->toDateTimeString();
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);

$admin = DB::table('users')->insertGetId(['name' => 'Dueño volumen', 'email' => 'duenio-vol@example.test', 'password' => bcrypt(bin2hex(random_bytes(12))), 'rol' => 'ADMIN', 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
$oper = DB::table('users')->insertGetId(['name' => 'Operativa volumen', 'email' => 'oper-vol@example.test', 'password' => bcrypt(bin2hex(random_bytes(12))), 'rol' => 'OPERATIVO', 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);

$efectivo = DB::table('tipos_caja')->where('nombre', 'Efectivo')->value('id');
$cuotaSub = DB::table('subrubros')->where('nombre', 'Cuota Mensual')->value('id');

// Deportes, niveles, grupos y planes
$grupos = [];
foreach (['Patín' => 'COMISION', 'Fútbol' => 'HORA'] as $dep => $liq) {
    $depId = DB::table('deportes')->insertGetId(['nombre' => "$dep vol", 'tipo_liquidacion' => $liq, 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
    $profIds = [];
    for ($p = 1; $p <= 3; $p++) {
        $pid = DB::table('profesores')->insertGetId(['deporte_id' => $depId, 'nombre' => "Profe$p", 'apellido' => "$dep vol", 'dni' => (string) (40000000 + count($grupos) * 10 + $p + ($dep === 'Patín' ? 0 : 500)),
            'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Calle 1', 'localidad' => 'Prueba', 'valor_hora' => 9000, 'porcentaje_comision' => 30, 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
        $profIds[] = $pid;
        DB::table('users')->insert(['name' => "Profe$p $dep", 'email' => "profe$p-".strtolower(substr($dep, 0, 3))."-vol@example.test", 'password' => bcrypt(bin2hex(random_bytes(12))), 'rol' => 'PROFESOR', 'profesor_id' => $pid, 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
    }
    foreach (['Inicial', 'Medio', 'Avanzado'] as $i => $niv) {
        $nivId = DB::table('niveles')->insertGetId(['nombre' => "$niv $dep vol", 'created_at' => $t(), 'updated_at' => $t()]);
        $gid = DB::table('grupos')->insertGetId(['deporte_id' => $depId, 'nivel_id' => $nivId, 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
        $precio = 30000 + $i * 6000;
        $plan = DB::table('grupo_planes')->insertGetId(['grupo_id' => $gid, 'clases_por_semana' => 2, 'precio_mensual' => $precio, 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
        // Desde T44 todo plan tiene historial de precios; sin él, la ficha del alumno no se puede armar.
        DB::table('grupo_plan_precios')->insert(['grupo_plan_id' => $plan, 'precio_mensual' => $precio, 'vigente_desde' => null, 'created_at' => $t(), 'updated_at' => $t()]);
        $grupos[] = ['id' => $gid, 'dep' => $depId, 'plan' => $plan, 'precio' => $precio, 'prof' => $profIds[$i]];
    }
}

// 300 alumnos: 40 dados de alta en estos dos años y 260 de antes; 10 % deben el último mes, 5 % dos meses
$alumnos = [];
for ($n = 0; $n < 300; $n++) {
    $g = $grupos[$n % 6];
    $alta = $n < 260 ? $desde->subMonths(mt_rand(1, 30)) : $desde->addDays(mt_rand(0, 690));
    $id = DB::table('alumnos')->insertGetId(['nombre' => 'Alumno'.$n, 'apellido' => 'Volumen', 'dni' => (string) (60000000 + $n), 'fecha_nacimiento' => '2012-05-05',
        'celular' => '1140000000', 'deporte_id' => $g['dep'], 'grupo_id' => $g['id'], 'fecha_alta' => $alta->toDateString(), 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
    DB::table('alumno_planes')->insert(['alumno_id' => $id, 'plan_id' => $g['plan'], 'fecha_desde' => $alta->toDateString(), 'activo' => 1, 'created_at' => $t(), 'updated_at' => $t()]);
    $alumnos[] = ['id' => $id, 'g' => $g, 'alta' => $alta, 'debe' => $n % 20 === 0 ? 2 : ($n % 10 === 0 ? 1 : 0)];
}

// Cajas: una por día hábil, validada, con los cobros de ese día
$diasHabiles = [];
for ($d = $desde; $d <= $hoy->subDay(); $d = $d->addDay()) if ($d->isWeekday()) $diasHabiles[] = $d;
$cobrosPorDia = [];
foreach ($alumnos as $a) {
    for ($m = $desde; $m <= $hoy->startOfMonth(); $m = $m->addMonth()) {
        if ($m->endOfMonth() < $a['alta']) continue;
        $periodo = $m->format('Y-m');
        $mesesDesdeHoy = $hoy->startOfMonth()->diffInMonths($m->startOfMonth());
        $impaga = abs($mesesDesdeHoy) < $a['debe'];
        $deuda = DB::table('deuda_cuotas')->insertGetId(['alumno_id' => $a['id'], 'periodo' => $periodo, 'monto_original' => $a['g']['precio'],
            'monto_pagado' => $impaga ? 0 : $a['g']['precio'], 'estado' => $impaga ? 'PENDIENTE' : 'PAGADA', 'porcentaje_alta' => 100, 'created_at' => $t(), 'updated_at' => $t()]);
        if ($impaga) continue;
        $dia = $m->addDays(mt_rand(0, 9));
        while (!$dia->isWeekday()) $dia = $dia->addDay();
        if ($dia >= $hoy) $dia = $hoy->subDays(1);
        $cobrosPorDia[$dia->toDateString()][] = ['alumno' => $a['id'], 'deuda' => $deuda, 'monto' => $a['g']['precio'], 'periodo' => $periodo, 'dep' => $a['g']['dep'], 'plan' => $a['g']['plan']];
    }
}
$inicial = 10000; $origen = null; $nCajas = 0; $nPagos = 0;
foreach ($diasHabiles as $d) {
    $fecha = $d->toDateString();
    $cobros = $cobrosPorDia[$fecha] ?? [];
    $total = array_sum(array_column($cobros, 'monto'));
    $caja = DB::table('cajas_operativas')->insertGetId(['usuario_operativo_id' => $oper, 'usuario_apertura_id' => $oper, 'apertura_at' => "$fecha 15:00:00",
        'cierre_at' => "$fecha 20:00:00", 'estado' => 'VALIDADA', 'usuario_admin_validacion_id' => $admin, 'validada_at' => "$fecha 21:00:00",
        'tipo_caja_efectivo_id' => $efectivo, 'caja_origen_id' => $origen, 'efectivo_heredado' => $inicial, 'efectivo_inicial' => $inicial,
        'efectivo_esperado' => $inicial + $total, 'efectivo_contado' => $inicial + $total, 'diferencia_efectivo' => 0, 'cambio_retenido' => 10000,
        'efectivo_retirado' => $inicial + $total - 10000, 'usuario_cierre_id' => $oper, 'cerrada_por_admin' => 0, 'created_at' => "$fecha 15:00:00", 'updated_at' => "$fecha 21:00:00"]);
    $origen = $caja; $inicial = 10000; $nCajas++;
    foreach ($cobros as $co) {
        [$anio, $mes] = explode('-', $co['periodo']);
        $pago = DB::table('pagos')->insertGetId(['alumno_id' => $co['alumno'], 'plan_id' => $co['plan'], 'mes' => (int) $mes, 'anio' => (int) $anio, 'monto_base' => $co['monto'],
            'porcentaje_aplicado' => 100, 'monto_final' => $co['monto'], 'monto_cuota' => $co['monto'], 'fecha_pago' => $fecha, 'estado' => 'COMPLETADO', 'created_at' => "$fecha 16:00:00", 'updated_at' => "$fecha 16:00:00"]);
        DB::table('pago_deuda_cuota')->insert(['pago_id' => $pago, 'deuda_cuota_id' => $co['deuda'], 'monto_aplicado' => $co['monto'], 'created_at' => "$fecha 16:00:00", 'updated_at' => "$fecha 16:00:00"]);
        $mov = DB::table('movimientos_operativos')->insertGetId(['caja_operativa_id' => $caja, 'fecha' => "$fecha 16:00:00", 'tipo_caja_id' => $efectivo, 'subrubro_id' => $cuotaSub,
            'monto' => $co['monto'], 'observaciones' => 'Cuota '.$co['periodo'], 'usuario_id' => $oper, 'alumno_id' => $co['alumno'], 'pago_id' => $pago, 'estado' => 'ACTIVO',
            'reporte_deporte_id' => $co['dep'], 'reporte_clasificacion' => 'NEGOCIO', 'reporte_tipo' => 'INGRESO', 'created_at' => "$fecha 16:00:00", 'updated_at' => "$fecha 16:00:00"]);
        DB::table('cashflow_movimientos')->insert(['fecha' => $fecha, 'subrubro_id' => $cuotaSub, 'tipo_caja_id' => $efectivo, 'monto' => $co['monto'], 'observaciones' => 'Cuota '.$co['periodo'],
            'usuario_admin_id' => $admin, 'referencia_tipo' => 'CAJA_OPERATIVA', 'referencia_id' => $caja, 'movimiento_operativo_id' => $mov,
            'reporte_deporte_id' => $co['dep'], 'reporte_clasificacion' => 'NEGOCIO', 'reporte_tipo' => 'INGRESO', 'created_at' => "$fecha 21:00:00", 'updated_at' => "$fecha 21:00:00"]);
        $nPagos++;
    }
}

// Clases: lunes, miércoles y viernes por grupo; asistencia del 80 % de los alumnos que ya estaban
$nClases = 0; $nAsis = 0; $lote = [];
foreach ($grupos as $i => $g) {
    $delGrupo = array_values(array_filter($alumnos, fn ($a) => $a['g']['id'] === $g['id']));
    for ($d = $desde; $d <= $hoy; $d = $d->addDay()) {
        if (!in_array($d->dayOfWeekIso, [1, 3, 5])) continue;
        $hora = 16 + $i % 4;
        $clase = DB::table('clases')->insertGetId(['grupo_id' => $g['id'], 'fecha' => $d->toDateString(), 'hora_inicio' => sprintf('%02d:00:00', $hora), 'hora_fin' => sprintf('%02d:00:00', $hora + 1),
            'validada_para_liquidacion' => 0, 'cancelada' => 0, 'created_at' => $t(), 'updated_at' => $t()]);
        DB::table('clase_profesor')->insert(['clase_id' => $clase, 'profesor_id' => $g['prof'], 'created_at' => $t(), 'updated_at' => $t()]);
        $nClases++;
        if ($d >= $hoy) continue;
        foreach ($delGrupo as $a) {
            if ($a['alta'] > $d) continue;
            $lote[] = ['clase_id' => $clase, 'alumno_id' => $a['id'], 'presente' => mt_rand(1, 10) <= 8 ? 1 : 0, 'created_at' => $t(), 'updated_at' => $t()];
            if (count($lote) >= 1000) { DB::table('asistencias')->insert($lote); $nAsis += count($lote); $lote = []; }
        }
    }
}
if ($lote) { DB::table('asistencias')->insert($lote); $nAsis += count($lote); }

echo json_encode(['alumnos' => count($alumnos), 'cuotas' => DB::table('deuda_cuotas')->count(), 'cobros' => $nPagos, 'cajas' => $nCajas,
    'cashflow' => DB::table('cashflow_movimientos')->count(), 'clases' => $nClases, 'asistencias' => $nAsis], JSON_PRETTY_PRINT)."\n";
