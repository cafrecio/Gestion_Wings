<?php
/**
 * PRU-04 · Qué TIENEN que dar las liquidaciones de octubre (y la de septiembre de Lucía), calculado aparte
 * (Claude, 27/10/2026). Reglas de LIQUIDACIONES_CONTRATO_V2 (§3.1, §3.2 y enmiendas DU-19/T42 y T43), solo
 * con consultas a la base, sin servicios de Wings.
 *   HORA: clases del mes del profesor, no canceladas, con al menos un presente o validadas a mano;
 *         cada una vale round(valor_hora × minutos / 60, 2).
 *   COMISIÓN: por alumno del deporte, lo cobrado de cuotas (pago_deuda_cuota, pagos no anulados) cuyo mes
 *         efectivo es el del período: el mayor entre el mes del cobro y el de la cuota. Solo si el alumno vino
 *         al menos una vez a una clase de ese profesor en el mes. Comisión = round(suma × % / 100, 2).
 *         Las partes de cuotas de meses anteriores son «atrasadas»: el dueño decide antes de cerrar (T43).
 * Solo lectura. Uso: php esperado-liquidaciones.php /home/wingstest/app
 */
$raiz = $argv[1] ?? '/home/wingstest/app';
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (config('database.connections.mysql.database') !== 'wingstest') { fwrite(STDERR, "Solo wingstest.\n"); exit(2); }

$out = [];
$profes = DB::select("select p.id, p.nombre, p.apellido, p.valor_hora, p.porcentaje_comision, p.deporte_id, d.tipo_liquidacion tipo
    from profesores p join deportes d on d.id = p.deporte_id where p.activo = 1 order by p.id");
foreach ($profes as $p) {
    foreach ([['2026-10-01', '2026-10-31', '2026-10'], ['2026-09-01', '2026-09-30', '2026-09']] as [$ini, $fin, $per]) {
        if ($p->tipo === 'HORA') {
            $clases = DB::select("select c.id, c.fecha, c.hora_inicio, c.hora_fin,
                timestampdiff(minute, c.hora_inicio, c.hora_fin) minutos
                from clases c join clase_profesor cp on cp.clase_id = c.id
                where cp.profesor_id = ? and c.fecha between ? and ? and c.cancelada = 0
                and (c.validada_para_liquidacion = 1 or exists (select 1 from asistencias s where s.clase_id = c.id and s.presente = 1))
                and not exists (select 1 from liquidacion_detalles ld join liquidaciones l on l.id = ld.liquidacion_id
                    where ld.tipo_referencia = 'clase' and ld.referencia_id = c.id and l.estado = 'CERRADA')
                order by c.fecha", [$p->id, $ini, $fin]);
            if (!$clases) continue;
            $total = array_sum(array_map(fn ($c) => round((float) $p->valor_hora * $c->minutos / 60, 2), $clases));
            $out[] = ['profesor' => "{$p->nombre} {$p->apellido}", 'periodo' => $per, 'tipo' => 'HORA', 'valor_hora' => (float) $p->valor_hora,
                'clases' => count($clases), 'minutos' => array_sum(array_column($clases, 'minutos')), 'total' => round($total, 2)];
        } elseif ($per === '2026-10') {
            // Partes de cuota cobradas: mes efectivo = mayor(mes del cobro, mes de la cuota).
            $partes = DB::select("select a.id alumno_id, concat(a.apellido, ', ', a.nombre) alumno, dc.periodo, pa.fecha_pago, pdc.monto_aplicado
                from pago_deuda_cuota pdc join pagos pa on pa.id = pdc.pago_id join deuda_cuotas dc on dc.id = pdc.deuda_cuota_id
                join alumnos a on a.id = dc.alumno_id
                where pa.estado = 'COMPLETADO' and a.deporte_id = ?
                and greatest(date_format(pa.fecha_pago, '%Y-%m'), dc.periodo) = ?", [$p->deporte_id, $per]);
            $vino = fn ($alumnoId) => DB::selectOne("select count(*) n from asistencias s join clases c on c.id = s.clase_id
                join clase_profesor cp on cp.clase_id = c.id where s.alumno_id = ? and s.presente = 1 and cp.profesor_id = ?
                and c.fecha between ? and ?", [$alumnoId, $p->id, $ini, $fin])->n > 0;
            $porAlumno = [];
            foreach ($partes as $x) {
                if (!$vino($x->alumno_id)) continue;
                $k = $x->alumno_id.'|'.$x->periodo;
                $porAlumno[$k] ??= ['alumno' => $x->alumno, 'periodo' => $x->periodo, 'monto' => 0.0];
                $porAlumno[$k]['monto'] += (float) $x->monto_aplicado;
            }
            $pct = (float) $p->porcentaje_comision;
            $detalle = array_values(array_map(fn ($r) => $r + ['comision' => round($r['monto'] * $pct / 100, 2), 'atrasada' => $r['periodo'] < $per], $porAlumno));
            $total = array_sum(array_column($detalle, 'comision'));
            $atrasadas = array_sum(array_map(fn ($d) => $d['atrasada'] ? $d['comision'] : 0, $detalle));
            $out[] = ['profesor' => "{$p->nombre} {$p->apellido}", 'periodo' => $per, 'tipo' => 'COMISION', 'porcentaje' => $pct,
                'alumnos' => count(array_unique(array_map(fn ($d) => $d['alumno'], $detalle))), 'base_cobrada' => array_sum(array_column($detalle, 'monto')),
                'total' => round($total, 2), 'de_cuotas_atrasadas' => round($atrasadas, 2),
                'atrasadas' => array_values(array_filter($detalle, fn ($d) => $d['atrasada']))];
        }
    }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
