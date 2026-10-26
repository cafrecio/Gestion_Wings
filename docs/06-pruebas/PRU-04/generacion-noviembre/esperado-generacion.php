<?php
/**
 * PRU-04 · Qué TIENE que generar el proceso del 1/11/2026, calculado aparte (Claude, 27/10/2026).
 *
 * Sale de las reglas del contrato (Wings-contrato-estadosAlum-cobranza-asistencia-V1, §2), no del código
 * del generador: solo consultas a la base, sin servicios ni modelos de Wings. Así, si el generador se
 * equivoca, esta lista no se equivoca con él.
 *   - Universo: alumnos activos. Si ya tiene la cuota de noviembre (adelanto), no se toca.
 *   - Lleva cuota si vino al menos una vez en octubre, o si se dio de alta y pagó en los últimos 15 días.
 *   - Si no cumple ninguna, no lleva cuota: va a la cola de Revisión.
 *   - Importe: el precio del plan del alumno que rige en noviembre (un aumento «desde noviembre» cuenta).
 * Solo lectura. Uso en el servidor: php esperado-generacion.php /home/wingstest/app > esperado.json
 */
$raiz = $argv[1] ?? '/home/wingstest/app';
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (config('database.connections.mysql.database') !== 'wingstest') { fwrite(STDERR, "Solo wingstest.\n"); exit(2); }

$periodo = '2026-11'; $dia1 = '2026-11-01';
$octIni = '2026-10-01'; $octFin = '2026-10-31';
$desde15 = '2026-10-17'; // 15 días antes del 1/11

$filas = [];
foreach (DB::select("select a.id, a.apellido, a.nombre, a.fecha_alta, d.nombre deporte, n.nombre nivel
    from alumnos a join grupos g on g.id = a.grupo_id join deportes d on d.id = g.deporte_id join niveles n on n.id = g.nivel_id
    where a.activo = 1 order by d.nombre, n.nombre, a.apellido, a.nombre") as $a) {
    $ya = DB::selectOne("select monto_original, estado from deuda_cuotas where alumno_id = ? and periodo = ?", [$a->id, $periodo]);
    $plan = DB::selectOne("select ap.plan_id, gp.clases_por_semana, gp.grupo_id from alumno_planes ap join grupo_planes gp on gp.id = ap.plan_id
        where ap.alumno_id = ? and ap.activo = 1 and ap.fecha_desde <= ? and (ap.fecha_hasta is null or ap.fecha_hasta >= ?)
        order by ap.fecha_desde desc, ap.id desc limit 1", [$a->id, $dia1, $dia1]);
    $precio = $plan ? DB::selectOne("select precio_mensual, vigente_desde from grupo_plan_precios where grupo_plan_id = ?
        and (vigente_desde is null or vigente_desde <= ?) order by vigente_desde desc, id desc limit 1", [$plan->plan_id, $dia1]) : null;
    $vino = DB::selectOne("select count(*) n from asistencias s join clases c on c.id = s.clase_id
        where s.alumno_id = ? and s.presente = 1 and c.fecha between ? and ?", [$a->id, $octIni, $octFin])->n;
    $altaReciente = $a->fecha_alta >= $desde15 && $a->fecha_alta <= $dia1;
    $pagoReciente = $altaReciente && DB::selectOne("select count(*) n from pagos where alumno_id = ? and estado = 'COMPLETADO'
        and fecha_pago between ? and ?", [$a->id, $desde15, $dia1])->n > 0;

    if ($ya) { $res = 'YA_TIENE'; $motivo = "ya tiene noviembre ({$ya->estado}, \$".number_format((float) $ya->monto_original, 0, ',', '.').')'; }
    elseif (!$plan || !$precio) { $res = 'SIN_PLAN'; $motivo = 'sin plan para noviembre'; }
    elseif ($vino > 0) { $res = 'CUOTA'; $motivo = "vino {$vino} veces en octubre"; }
    elseif ($pagoReciente) { $res = 'CUOTA'; $motivo = 'alta y pago en los últimos 15 días'; }
    else { $res = 'REVISION'; $motivo = 'no vino en octubre'; }

    $filas[] = ['id' => $a->id, 'alumno' => "{$a->apellido}, {$a->nombre}", 'grupo' => "{$a->deporte} — {$a->nivel}",
        'veces' => $plan->clases_por_semana ?? null, 'resultado' => $res,
        'importe' => $res === 'CUOTA' ? (int) round((float) $precio->precio_mensual) : null,
        'precio_vigente_desde' => $precio->vigente_desde ?? null, 'motivo' => $motivo];
}
$res = array_count_values(array_column($filas, 'resultado'));
echo json_encode(['periodo' => $periodo, 'resumen' => $res,
    'total_cuotas' => array_sum(array_map(fn ($f) => $f['importe'] ?? 0, $filas)), 'filas' => $filas], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
