<?php
/**
 * PRU-04 · Prueba técnica · Comportamiento interno (Claude, 25/10/2026).
 *
 * Controles de SOLO LECTURA sobre la base del sitio de prueba, con lo que dejaron los días.
 * Cada control es una regla que la plata o la asistencia tienen que cumplir siempre.
 * Uso en el servidor:  /usr/bin/php82 controles-internos.php /home/wingstest/app
 * Se niega a correr si la base no es `wingstest`. No escribe nada.
 */
$base = $argv[1] ?? '/home/wingstest/app';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wingstest') { fwrite(STDERR, "Base inesperada: solo wingstest.\n"); exit(2); }

use Illuminate\Support\Facades\DB;

$resultados = [];
function control(array &$r, string $id, string $regla, $fallas, string $detalle = ''): void {
    $fallas = collect($fallas)->values();
    $r[] = ['id' => $id, 'regla' => $regla, 'ok' => $fallas->isEmpty(), 'fallas' => $fallas->count(),
        'muestra' => $fallas->take(5)->all(), 'detalle' => $detalle];
}
$c = fn ($v) => (int) round(((float) $v) * 100);

DB::beginTransaction(); // por las dudas: nada de lo que sigue escribe, y al final se revierte igual.
try {
    $ahora = now()->toDateTimeString();

    // ── Caja ──────────────────────────────────────────────────────────────
    $cajas = DB::table('cajas_operativas')->get();
    $f = [];
    foreach ($cajas->whereIn('estado', ['CERRADA', 'VALIDADA', 'RECHAZADA']) as $caja) {
        $movs = DB::table('movimientos_operativos as m')->join('subrubros as s', 's.id', '=', 'm.subrubro_id')
            ->join('rubros as r', 'r.id', '=', 's.rubro_id')
            ->where('m.caja_operativa_id', $caja->id)->where('m.tipo_caja_id', $caja->tipo_caja_efectivo_id)
            ->where(fn ($q) => $q->whereNull('m.estado')->orWhere('m.estado', '!=', 'CANCELADO'))
            ->select('m.monto', 'r.tipo')->get();
        $neto = $movs->sum(fn ($m) => $m->tipo === 'EGRESO' ? -$c($m->monto) : $c($m->monto));
        $esperado = $c($caja->efectivo_inicial) + $neto;
        if ($esperado !== $c($caja->efectivo_esperado)) $f[] = "caja $caja->id: calculado ".($esperado/100)." guardado $caja->efectivo_esperado";
        if ($caja->efectivo_contado !== null && $c($caja->efectivo_contado) - $c($caja->efectivo_esperado) !== $c($caja->diferencia_efectivo))
            $f[] = "caja $caja->id: diferencia guardada $caja->diferencia_efectivo no es contado - esperado";
        if ($caja->efectivo_contado !== null && $c($caja->cambio_retenido) + $c($caja->efectivo_retirado) !== $c($caja->efectivo_contado))
            $f[] = "caja $caja->id: retenido + retirado no da lo contado";
    }
    control($resultados, 'CAJ-1', 'El efectivo esperado de cada caja cerrada es el inicial más sus movimientos en efectivo no cancelados; la diferencia es contado menos esperado; retenido más retirado es lo contado', $f, $cajas->count().' cajas');

    $f = [];
    foreach ($cajas->sortBy('id')->values() as $i => $caja) {
        if ($caja->caja_origen_id) {
            $origen = $cajas->firstWhere('id', $caja->caja_origen_id);
            if ($origen && $c($origen->cambio_retenido) !== $c($caja->efectivo_heredado)) $f[] = "caja $caja->id hereda $caja->efectivo_heredado y la #$origen->id retuvo $origen->cambio_retenido";
        }
    }
    control($resultados, 'CAJ-2', 'Lo heredado por cada apertura es lo que retuvo la caja de origen', $f);

    // ── Reflejo en Cashflow ───────────────────────────────────────────────
    $validadas = $cajas->where('estado', 'VALIDADA')->pluck('id');
    $activos = DB::table('movimientos_operativos')->whereIn('caja_operativa_id', $validadas)
        ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 'CANCELADO'))->get();
    $reflejos = DB::table('cashflow_movimientos')->whereNotNull('movimiento_operativo_id')->get()->groupBy('movimiento_operativo_id');
    $f = [];
    // Antes del 12/10 el reflejo se enlazaba a la caja entera, no a cada movimiento: para esas cajas
    // se compara la cantidad y el total por caja.
    foreach ($activos->groupBy('caja_operativa_id') as $cajaId => $movsCaja) {
        $conVinculo = $movsCaja->filter(fn ($m) => $reflejos->has($m->id));
        if ($conVinculo->isEmpty()) {
            $porCaja = DB::table('cashflow_movimientos')->where('referencia_tipo', 'CAJA_OPERATIVA')->where('referencia_id', $cajaId)->whereNull('movimiento_operativo_id')->get();
            if ($porCaja->count() !== $movsCaja->count() || abs($porCaja->sum(fn ($x) => abs($c($x->monto))) - $movsCaja->sum(fn ($x) => abs($c($x->monto)))) > 0)
                $f[] = "caja $cajaId (sin vínculo por movimiento): {$porCaja->count()} reflejos para {$movsCaja->count()} movimientos";
            continue;
        }
        foreach ($movsCaja as $m) {
            $n = $reflejos->get($m->id, collect())->count();
            if ($n !== 1) $f[] = "movimiento $m->id de caja $cajaId: $n reflejos";
        }
    }
    $cancelados = DB::table('movimientos_operativos')->where('estado', 'CANCELADO')->pluck('id');
    foreach ($cancelados as $id) if ($reflejos->has($id)) $f[] = "movimiento cancelado $id tiene reflejo en Cashflow";
    control($resultados, 'CSH-1', 'Cada movimiento no cancelado de una caja validada aparece una sola vez en Cashflow; un cancelado no aparece', $f, $activos->count().' movimientos de cajas validadas');

    $f = [];
    foreach (DB::table('cashflow_movimientos as cf')->join('movimientos_operativos as m', 'm.id', '=', 'cf.movimiento_operativo_id')
        ->select('cf.id', 'cf.monto as cfm', 'm.monto as mm', 'cf.fecha as cff', 'm.fecha as mf', 'cf.tipo_caja_id as cft', 'm.tipo_caja_id as mt')->get() as $r)
        if (abs($c($r->cfm)) !== abs($c($r->mm)) || substr($r->cff, 0, 10) !== substr($r->mf, 0, 10) || $r->cft !== $r->mt) $f[] = "cashflow $r->id no coincide con su movimiento";
    control($resultados, 'CSH-2', 'El reflejo conserva importe, fecha del día y medio de pago del movimiento de caja', $f);

    // ── Transferencias ───────────────────────────────────────────────────
    $f = [];
    foreach (DB::table('cashflow_transferencias')->get() as $t) {
        $patas = DB::table('cashflow_movimientos')->where('referencia_tipo', 'TRANSFERENCIA')->where('referencia_id', $t->id)->get();
        $sal = $patas->firstWhere('tipo_caja_id', $t->salida_id); $ent = $patas->firstWhere('tipo_caja_id', $t->destino_id);
        if ($patas->count() !== 2 || !$sal || !$ent || $c($sal->monto) !== -$c($t->monto) || $c($ent->monto) !== $c($t->monto)
            || $sal->reporte_clasificacion !== 'TRASPASO' || $ent->reporte_clasificacion !== 'TRASPASO')
            $f[] = "transferencia $t->id: patas incorrectas";
    }
    $sueltas = DB::table('cashflow_movimientos')->where('reporte_clasificacion', 'TRASPASO')->where(fn ($q) => $q->whereNull('referencia_tipo')->orWhere('referencia_tipo', '!=', 'TRANSFERENCIA'))->pluck('id');
    foreach ($sueltas as $id) $f[] = "movimiento $id TRASPASO sin transferencia";
    control($resultados, 'CSH-3', 'Cada transferencia tiene exactamente dos patas TRASPASO, salida negativa y entrada positiva por el mismo importe; no hay TRASPASO suelto', $f, DB::table('cashflow_transferencias')->count().' transferencias');

    // ── Cobros ───────────────────────────────────────────────────────────
    $f = [];
    $pcc = DB::getSchemaBuilder()->getColumnListing('pago_cargo_alumno');
    $colCargo = in_array('monto_aplicado', $pcc) ? 'monto_aplicado' : null;
    foreach (DB::table('pagos')->get() as $p) {
        if ($p->estado === 'ANULADO') {
            if (DB::table('pago_deuda_cuota')->where('pago_id', $p->id)->exists()) $f[] = "pago anulado $p->id conserva imputaciones de cuota";
            continue;
        }
        $cuotas = DB::table('pago_deuda_cuota')->where('pago_id', $p->id)->sum('monto_aplicado');
        $cargos = $colCargo ? DB::table('pago_cargo_alumno')->where('pago_id', $p->id)->sum($colCargo) : 0;
        if ($c($cuotas) + $c($cargos) !== $c($p->monto_final)) $f[] = "pago $p->id: imputado ".(($c($cuotas)+$c($cargos))/100)." y cobrado $p->monto_final";
        $movs = DB::table('movimientos_operativos')->where('pago_id', $p->id)->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 'CANCELADO'))->sum('monto');
        $cfDirecto = DB::table('cashflow_movimientos')->where('referencia_tipo', 'PAGO_CUOTA')->where('referencia_id', $p->id)->whereNull('anulado_at')->sum('monto');
        if ($c($movs) + $c($cfDirecto) !== $c($p->monto_final)) $f[] = "pago $p->id: movimientos ".(($c($movs)+$c($cfDirecto))/100)." y cobrado $p->monto_final";
    }
    control($resultados, 'COB-1', 'Cada cobro vigente reparte exactamente su importe entre cuotas e inscripción, y entra a caja (o a Cashflow si lo cobró el dueño) por ese mismo importe; un anulado no conserva imputaciones', $f, DB::table('pagos')->count().' cobros');

    $f = [];
    foreach (DB::table('deuda_cuotas')->get() as $d) {
        $aplicado = DB::table('pago_deuda_cuota as i')->join('pagos as p', 'p.id', '=', 'i.pago_id')
            ->where('i.deuda_cuota_id', $d->id)->where('p.estado', '!=', 'ANULADO')->sum('i.monto_aplicado');
        if ($c($aplicado) !== $c($d->monto_pagado)) $f[] = "cuota $d->id ($d->periodo): pagado guardado $d->monto_pagado, imputado ".($c($aplicado)/100);
        $saldo = $c($d->monto_original) - $c($d->monto_pagado);
        if ($d->estado === 'PAGADA' && $saldo !== 0) $f[] = "cuota $d->id PAGADA con saldo ".($saldo/100);
        if ($d->estado === 'PENDIENTE' && $saldo === 0) $f[] = "cuota $d->id PENDIENTE sin saldo"; // un pago a cuenta deja PENDIENTE con saldo: es el diseño
        if ($c($d->monto_pagado) > $c($d->monto_original)) $f[] = "cuota $d->id pagada de más";
    }
    control($resultados, 'COB-2', 'Lo pagado de cada cuota es la suma de sus imputaciones vigentes; PAGADA sin saldo y PENDIENTE con saldo; nunca pagada de más', $f, DB::table('deuda_cuotas')->count().' cuotas');

    $f = DB::table('deuda_cuotas')->select('alumno_id', 'periodo', DB::raw('count(*) n'))->groupBy('alumno_id', 'periodo')->having('n', '>', 1)->get()->map(fn ($r) => "alumno $r->alumno_id periodo $r->periodo: $r->n cuotas");
    control($resultados, 'COB-3', 'Un alumno tiene a lo sumo una cuota por mes', $f);

    // ── Cobranza: un solo número de deuda ─────────────────────────────────
    $svc = app(App\Services\CobranzaEstadoService::class);
    $lista = $svc->listadoCobranza('TODOS');
    $deudaCobranza = $lista->sum(fn ($a) => $c($a->deuda_total));
    $rep = app(App\Services\ReporteMensualService::class)->obtener(now()->format('Y-m'));
    $deudaReportes = (int) ($rep['deuda']['total'] ?? 0);
    $f = [];
    if ($deudaCobranza !== $deudaReportes) $f[] = 'Cobranza '.($deudaCobranza/100).' y Reportes '.($deudaReportes/100);
    $conDeuda = $lista->filter(fn ($a) => $a->deuda_total > 0)->count();
    $vencidos = $lista->whereIn('estado_cobranza', ['MOROSO', 'DEUDOR'])->count();
    $enPlazo = $lista->filter(fn ($a) => $a->deuda_total > 0 && !in_array($a->estado_cobranza, ['MOROSO', 'DEUDOR']))->count();
    if ($vencidos + $enPlazo !== $conDeuda) $f[] = "vencidos $vencidos + en plazo $enPlazo no da los $conDeuda con deuda";
    foreach ($lista as $a) if ($a->estado_cobranza === 'AL_DIA' && $a->deuda_total > 0) $f[] = "alumno $a->id Al día con deuda $a->deuda_total";
    control($resultados, 'CBZ-1', 'La deuda de Cobranza es la de Reportes; vencidos más en plazo son los que deben; nadie Al día con deuda', $f,
        'deuda '.($deudaCobranza/100).'; '.$lista->groupBy('estado_cobranza')->map->count()->toJson());

    $f = [];
    $sinMes = 0;
    foreach (App\Models\Alumno::where('activo', 1)->get() as $a) {
        if (!app(App\Services\PagoCuotaService::class)->obtenerPlanParaPeriodo($a->id, now()->format('Y-m'))?->plan) $f[] = "alumno $a->id activo sin plan del mes";
        if (!DB::table('deuda_cuotas')->where('alumno_id', $a->id)->where('periodo', now()->format('Y-m'))->exists()) $sinMes++;
    }
    control($resultados, 'ALU-1', 'Todo alumno activo tiene plan para el mes en curso', $f, "$sinMes activos sin cuota del mes guardada (decisión pendiente de Carlos, D4-OBS-01)");

    // ── Disponible ───────────────────────────────────────────────────────
    $iniciales = DB::table('reporte_eventos')->where('tipo', 'CAJA_INICIAL')->sum('delta_centavos');
    $cf = DB::table('cashflow_movimientos')->whereNull('anulado_at')->get()->sum(fn ($m) => $c($m->monto));
    $contra = DB::table('cashflow_movimientos')->whereNotNull('anulado_at')->get()->sum(fn ($m) => $c($m->monto));
    $pend = DB::table('movimientos_operativos as m')->join('cajas_operativas as k', 'k.id', '=', 'm.caja_operativa_id')
        ->join('subrubros as s', 's.id', '=', 'm.subrubro_id')->join('rubros as r', 'r.id', '=', 's.rubro_id')
        ->where('k.estado', '!=', 'VALIDADA')->where(fn ($q) => $q->whereNull('m.estado')->orWhere('m.estado', '!=', 'CANCELADO'))
        ->select('m.monto', 'r.tipo')->get()->sum(fn ($m) => $m->tipo === 'EGRESO' ? -$c($m->monto) : $c($m->monto));
    $disp = (int) ($rep['disponible'] ?? 0);
    $f = [];
    if ((int) $iniciales + $cf + $pend !== $disp) $f[] = 'saldos iniciales + Cashflow + cajas sin validar = '.(((int)$iniciales + $cf + $pend)/100).'; Reportes dice '.($disp/100);
    control($resultados, 'REP-1', 'El disponible de Reportes es saldos iniciales más Cashflow más lo de cajas sin validar', $f, 'disponible '.($disp/100).($contra ? '; hay movimientos anulados en Cashflow' : ''));

    // ── Asistencia ───────────────────────────────────────────────────────
    $f = DB::table('asistencias')->select('clase_id', 'alumno_id', DB::raw('count(*) n'))->groupBy('clase_id', 'alumno_id')->having('n', '>', 1)->get()->map(fn ($r) => "clase $r->clase_id alumno $r->alumno_id: $r->n marcas");
    control($resultados, 'ASI-1', 'A lo sumo una marca por alumno y clase', $f);
    // Desde T62 (29/10) cancelar una clase con la lista tomada conserva las marcas: lo que no puede haber
    // es una marca cargada después de cancelarla (la última modificación de la clase es la cancelación).
    $f = DB::table('asistencias as a')->join('clases as c', 'c.id', '=', 'a.clase_id')
        ->where(fn ($q) => $q->where(fn ($q2) => $q2->where('c.cancelada', 1)->whereColumn('a.created_at', '>', 'c.updated_at'))->orWhere('c.fecha', '>', now()->toDateString()))
        ->select('a.id', 'c.id as clase', 'c.fecha', 'c.cancelada')->get()->map(fn ($r) => "marca $r->id en clase $r->clase ".($r->cancelada ? 'cargada después de cancelarla' : "futura $r->fecha"));
    $conservadas = DB::table('asistencias as a')->join('clases as c', 'c.id', '=', 'a.clase_id')->where('c.cancelada', 1)->count();
    control($resultados, 'ASI-2', 'No hay marcas en clases que todavía no ocurrieron ni cargadas en una clase ya cancelada', $f, "$conservadas marcas conservadas en clases canceladas con la lista tomada (T62)");
    $f = [];
    foreach (DB::table('asistencias as a')->join('clases as c', 'c.id', '=', 'a.clase_id')->join('alumnos as al', 'al.id', '=', 'a.alumno_id')
        ->select('a.id', 'a.alumno_id', 'c.id as clase', 'c.fecha', 'c.grupo_id', 'al.grupo_id as grupo_hoy', 'al.grupo_desde', 'al.fecha_alta')->get() as $r) {
        $llego = $r->grupo_desde ?: $r->fecha_alta;
        if ($r->grupo_id === $r->grupo_hoy && $llego && substr($r->fecha, 0, 10) < substr($llego, 0, 10)) $f[] = "alumno $r->alumno_id marcado en clase $r->clase del $r->fecha antes de llegar al grupo ($llego)";
    }
    control($resultados, 'ASI-3', 'Nadie tiene marca en una clase de su grupo actual anterior a su llegada', $f);
    $f = DB::table('clases as c')->leftJoin('clase_profesor as cp', 'cp.clase_id', '=', 'c.id')->whereNull('cp.clase_id')->where('c.cancelada', 0)->pluck('c.id')->map(fn ($id) => "clase $id sin profesora");
    control($resultados, 'CLA-1', 'Toda clase activa tiene al menos una profesora', $f);

    // ── Liquidaciones (día 23) ───────────────────────────────────────────
    $liqs = DB::table('liquidaciones')->where('estado', '!=', 'CANCELADA')->get();
    $f = [];
    foreach ($liqs->where('tipo', 'HORA') as $l) {
        $dets = DB::table('liquidacion_detalles')->where('liquidacion_id', $l->id)->get();
        foreach ($dets as $d) {
            if ($c($d->monto) !== (int) round($c($l->valor_hora_aplicado) * $d->minutos / 60)) $f[] = "liq $l->id detalle $d->id: $d->monto no es valor hora × $d->minutos min";
            $cl = DB::table('clases')->find($d->referencia_id);
            if (!$cl || $cl->cancelada || (int) substr($cl->fecha, 5, 2) !== (int) $l->mes || (int) substr($cl->fecha, 0, 4) !== (int) $l->anio) $f[] = "liq $l->id: clase $d->referencia_id cancelada, inexistente o de otro mes";
            elseif (!DB::table('clase_profesor')->where('clase_id', $cl->id)->where('profesor_id', $l->profesor_id)->exists()) $f[] = "liq $l->id: clase $cl->id no es de la profesora";
            elseif (!$cl->validada_para_liquidacion && !DB::table('asistencias')->where('clase_id', $cl->id)->where('presente', 1)->exists()) $f[] = "liq $l->id: clase $cl->id sin presentes ni validación";
        }
        if ($dets->sum(fn ($d) => $c($d->monto)) !== $c($l->total_calculado)) $f[] = "liq $l->id: total $l->total_calculado no es la suma del detalle";
    }
    control($resultados, 'LIQ-1', 'Por hora: cada clase paga valor hora × minutos; es del mes, de la profesora, no cancelada y con presentes o validada; el total es la suma', $f, $liqs->where('tipo', 'HORA')->count().' liquidaciones por hora');

    $f = [];
    foreach ($liqs->where('tipo', 'COMISION') as $l) {
        $dets = DB::table('liquidacion_detalles')->where('liquidacion_id', $l->id)->get();
        foreach ($dets as $d) {
            if (abs($c($d->monto) - (int) round($c($d->base_cuota) * $l->porcentaje_comision_aplicado / 100)) > 1) $f[] = "liq $l->id detalle $d->id: $d->monto no es {$l->porcentaje_comision_aplicado}% de $d->base_cuota";
            if ($d->origen_cuota === 'atrasado') {
                $ultima = DB::table('liquidacion_decisiones_atrasadas')->where('detalle_id', $d->id)->orderByDesc('id')->value('nueva');
                if ($ultima !== $d->decision_atrasada) $f[] = "liq $l->id detalle $d->id: decisión $d->decision_atrasada sin registro igual ($ultima)";
                if ($l->estado === 'CERRADA' && $d->decision_atrasada === 'PENDIENTE') $f[] = "liq $l->id cerrada con atrasada pendiente";
            } elseif ($d->periodo_cuota && $d->periodo_cuota > sprintf('%04d-%02d', $l->anio, $l->mes)) $f[] = "liq $l->id: cuota adelantada $d->periodo_cuota cuenta en este mes";
        }
        if ($dets->sum(fn ($d) => $c($d->monto)) !== $c($l->total_calculado)) $f[] = "liq $l->id: total $l->total_calculado no es la suma del detalle";
    }
    control($resultados, 'LIQ-2', 'Por comisión: cada parte es el porcentaje de su cuota; ninguna cuota adelantada de un mes posterior; cada decisión de atrasada tiene su registro; ninguna cerrada con pendientes', $f, $liqs->where('tipo', 'COMISION')->count().' liquidaciones por comisión');

    $f = $liqs->groupBy(fn ($l) => "$l->profesor_id-$l->anio-$l->mes")->filter(fn ($g) => $g->count() > 1)->keys()->map(fn ($k) => "profesor-año-mes $k: más de una");
    foreach (DB::table('liquidacion_detalles as d')->join('liquidaciones as l', 'l.id', '=', 'd.liquidacion_id')->where('l.estado', '!=', 'CANCELADA')->where('d.tipo_referencia', 'clase')
        ->select('l.profesor_id', 'd.referencia_id', DB::raw('count(*) n'))->groupBy('l.profesor_id', 'd.referencia_id')->having('n', '>', 1)->get() as $r) $f[] = "clase $r->referencia_id liquidada $r->n veces a la profesora $r->profesor_id";
    control($resultados, 'LIQ-3', 'Una sola liquidación vigente por profesor y mes; ninguna clase se liquida dos veces a la misma profesora', $f);

    $f = [];
    $cerradas = $liqs->where('estado', 'CERRADA')->where('estado_pago', '!=', 'PAGADA');
    $porPagar = $cerradas->sum(fn ($l) => $c($l->monto_final ?? $l->total_calculado));
    $porPagarReportes = (int) ($rep['por_pagar']['total'] ?? -1);
    if ($porPagar !== $porPagarReportes) $f[] = 'cerradas sin pagar '.($porPagar/100).'; Reportes por pagar '.($porPagarReportes/100);
    $conMov = DB::table('cashflow_movimientos')->where('referencia_tipo', 'LIQUIDACION')->whereIn('referencia_id', $liqs->where('estado_pago', '!=', 'PAGADA')->pluck('id'))->count();
    if ($conMov) $f[] = "$conMov movimientos de Cashflow de liquidaciones sin pagar";
    control($resultados, 'LIQ-4', 'Lo cerrado sin pagar suma el «por pagar» de Reportes; ninguna liquidación sin pagar tiene movimiento en Cashflow', $f,
        $cerradas->count().' cerradas sin pagar, '.($porPagar/100).'; abiertas '.$liqs->where('estado', 'ABIERTA')->count());

    // El reporte de octubre no cambió con la generación de noviembre ni con las liquidaciones (CBZ-011 h).
    if (now()->format('Y-m') === '2026-11') {
        $oct = app(App\Services\ReporteMensualService::class)->obtener('2026-10');
        $f = [];
        if ((int) $oct['ingresos'] !== 341785075) $f[] = 'ingresos de octubre '.($oct['ingresos']/100).', el 31/10 eran 3417850.75';
        if ((int) $oct['egresos'] !== 33195000) $f[] = 'egresos de octubre '.($oct['egresos']/100).', el 31/10 eran 331950';
        control($resultados, 'REP-2', 'El reporte de octubre sigue igual que el 31/10', $f, 'ingresos '.($oct['ingresos']/100).', egresos '.($oct['egresos']/100).', deuda '.(($oct['deuda']['total'] ?? 0)/100));
    }

    // ── Días 24 a 28 (2 al 6/11) ─────────────────────────────────────────
    // Cada liquidación pagada tiene un solo egreso en Cashflow, por su importe y con su fecha de pago (LIQ-009, LIQ-011).
    $f = [];
    foreach (DB::table('liquidaciones')->where('estado_pago', 'PAGADA')->get() as $l) {
        $movs = DB::table('cashflow_movimientos')->where('referencia_tipo', 'LIQUIDACION')->where('referencia_id', $l->id)->whereNull('anulado_at')->get();
        $importe = $c($l->monto_final ?? $l->total_calculado);
        if ($movs->count() !== 1) $f[] = "liquidación $l->id: {$movs->count()} egresos";
        elseif (-$c($movs[0]->monto) !== $importe) $f[] = "liquidación $l->id: egreso ".$movs[0]->monto.' por un importe de '.($importe / 100);
        elseif ($movs[0]->fecha !== $l->pagada_fecha) $f[] = "liquidación $l->id: egreso del {$movs[0]->fecha}, pagada el $l->pagada_fecha";
    }
    control($resultados, 'LIQ-5', 'Cada liquidación pagada tiene un único egreso en Cashflow por su importe y con su fecha de pago', $f,
        DB::table('liquidaciones')->where('estado_pago', 'PAGADA')->count().' pagadas');

    // Una segunda corrida de la generación no toca la constancia del mes (CBZ-012 d, día 24.8).
    $gen = DB::table('generaciones_cuotas')->where('periodo', '2026-11')->first();
    $f = [];
    if (!$gen) $f[] = 'no hay constancia de noviembre';
    elseif ((string) $gen->terminada_at !== '2026-11-01 06:02:31' || (int) $gen->ya_existian !== 103 || (int) $gen->creadas !== 0) $f[] = "constancia cambiada: fin $gen->terminada_at, creadas $gen->creadas, ya existían $gen->ya_existian";
    control($resultados, 'CBZ-2', 'La constancia de noviembre sigue como quedó el 1/11 después de la segunda corrida del 2/11', $f,
        $gen ? "fin $gen->terminada_at; creadas $gen->creadas (el 0 viene del día 23, antes de la reparación); ya existían $gen->ya_existian" : '');

    // Cada excepción de B3 tiene motivo, autor y el cobro que la originó, del mismo alumno (COB-009, COB-033).
    $f = [];
    foreach (DB::table('excepciones_cobranza')->get() as $e) {
        if (trim((string) $e->motivo) === '' || !$e->autor_id) $f[] = "excepción $e->id sin motivo o sin autor";
        if ($e->registro_tipo === 'PAGO' && DB::table('pagos')->where('id', $e->registro_id)->value('alumno_id') !== $e->alumno_id) $f[] = "excepción $e->id: el cobro $e->registro_id no es de ese alumno";
    }
    control($resultados, 'COB-4', 'Cada excepción tiene motivo, autor y su cobro, del mismo alumno', $f, DB::table('excepciones_cobranza')->count().' excepciones');

    // Un mes adelantado solo existe pagado y no suma a la deuda (COB-012, día 27.8).
    $f = DB::table('deuda_cuotas')->where('periodo', '>', now()->format('Y-m'))->where('estado', '!=', 'PAGADA')->pluck('id')->map(fn ($id) => "cuota futura $id sin pagar");
    control($resultados, 'COB-5', 'Las cuotas de meses que no empezaron existen solo pagadas por adelantado', $f,
        DB::table('deuda_cuotas')->where('periodo', '>', now()->format('Y-m'))->count().' adelantadas');

    // La fecha de inactividad acompaña al estado (T70): el activo no la tiene; el que se dio de baja desde T70 sí.
    $f = DB::table('alumnos')->where('activo', 1)->whereNotNull('inactivo_desde')->pluck('id')->map(fn ($id) => "alumno $id activo con fecha de baja");
    $sinFecha = DB::table('alumnos')->where('activo', 0)->whereNull('inactivo_desde')->pluck('apellido');
    control($resultados, 'ALU-2', 'Ningún activo conserva fecha de baja; los inactivos sin fecha son anteriores a T70', $f, 'inactivos sin fecha: '.$sinFecha->implode(', '));

    // Un DNI no se repite dentro del mismo deporte (ALU-005, ALU-024 d).
    $f = DB::table('alumnos')->select('dni', 'deporte_id', DB::raw('count(*) n'))->groupBy('dni', 'deporte_id')->having('n', '>', 1)->get()->map(fn ($r) => "DNI $r->dni repetido en el deporte $r->deporte_id");
    control($resultados, 'ALU-3', 'Un DNI aparece a lo sumo una vez por deporte', $f);

    // ── Días 29 a 33 (7 al 11/11) ─────────────────────────────────────────
    $porNombre = fn ($nombre, $apellido = null) => DB::table('alumnos')->where('nombre', $nombre)->when($apellido, fn ($q) => $q->where('apellido', $apellido))->orderBy('id')->first();
    $cobradoRecargos = fn () => $c(DB::table('pago_cargo_alumno as pc')->join('pagos as p', 'p.id', '=', 'pc.pago_id')->join('cargos_alumno as cg', 'cg.id', '=', 'pc.cargo_alumno_id')
        ->where('cg.tipo', 'PUNITORIO')->where('p.estado', '!=', 'ANULADO')->sum('pc.monto_aplicado'));

    // Recargos por mora (33.1, PEN-001): cada cargo PUNITORIO es el porcentaje configurado sobre el original de su
    // cuota (la cuota entera aunque haya un pago a cuenta), de noviembre, de un activo; uno por cuota; todos de la
    // única corrida del 11/11 a las 06:15.
    $pct = (float) App\Models\Configuracion::get('mora_porcentaje', 0);
    $punitorios = DB::table('cargos_alumno')->where('tipo', 'PUNITORIO')->get();
    $f = [];
    foreach ($punitorios as $cg) {
        $d = DB::table('deuda_cuotas')->find($cg->deuda_cuota_id);
        $al = DB::table('alumnos')->find($cg->alumno_id);
        if (!$d) { $f[] = "recargo $cg->id sin cuota"; continue; }
        if ($c($cg->monto_original) !== (int) round($c($d->monto_original) * $pct / 100)) $f[] = "recargo $cg->id: $cg->monto_original no es $pct% de $d->monto_original";
        if ($d->periodo !== '2026-11') $f[] = "recargo $cg->id sobre un mes cerrado ($d->periodo)";
        if (!$al || !$al->activo) $f[] = "recargo $cg->id a un inactivo";
        if ($d->alumno_id !== $cg->alumno_id) $f[] = "recargo $cg->id de otro alumno";
        if ($cg->estado !== 'VIGENTE') $f[] = "recargo $cg->id en estado $cg->estado";
    }
    foreach ($punitorios->groupBy('deuda_cuota_id')->filter(fn ($g) => $g->count() > 1)->keys() as $id) $f[] = "cuota $id con más de un recargo";
    $minutos = $punitorios->map(fn ($cg) => substr((string) $cg->created_at, 0, 16))->unique();
    if ($minutos->count() !== 1 || $minutos->first() !== '2026-11-11 06:15') $f[] = 'recargos aplicados en '.$minutos->implode(', ').' (esperada una sola corrida, 11/11 06:15)';
    foreach (['Santino', 'Candela'] as $n) if (($a = $porNombre($n)) && $punitorios->contains('alumno_id', $a->id)) $f[] = "$n tiene recargo y no correspondía";
    control($resultados, 'PEN-1', 'Cada recargo es el porcentaje configurado sobre el original de su cuota de noviembre, de un activo, uno por cuota, todos de la única corrida del 11/11 06:15; Santino (octubre) y Candela (inactiva) sin recargo', $f,
        $punitorios->count().' recargos por '.($punitorios->sum(fn ($x) => $c($x->monto_original)) / 100).'; porcentaje '.$pct);

    // Lo aplicado se reparte entre cobrado, condonado y pendiente (33.7, 33.8, 33.9); la condonación tiene dueño y motivo.
    $cobrado = $cobradoRecargos();
    $condonado = $punitorios->sum(fn ($x) => $c($x->monto_condonado));
    $aplicado = $punitorios->sum(fn ($x) => $c($x->monto_original));
    $pendiente = $aplicado - $cobrado - $condonado;
    $f = [];
    // Días 34 a 40: 40.300 hasta el 33, más 54.100 de la tanda (cuenta del guion: 16.300 + 7.300 + 7.900 + 7.200 + 9.100 + 3.500 + 2.800).
    if ($cobrado !== 9440000) $f[] = 'cobrado de recargos '.($cobrado / 100).' (esperado 94.400)';
    if ($condonado !== 500000) $f[] = 'condonado '.($condonado / 100).' (pantalla: 5.000)';
    foreach ($punitorios->where('monto_condonado', '>', 0) as $cg) {
        $ev = DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cg->id)->orderByDesc('id')->first();
        if (!$ev || trim((string) $ev->motivo) === '' || !$ev->usuario_id) $f[] = "condonación del recargo $cg->id sin dueño o sin motivo";
        elseif (!DB::table('users')->where('id', $ev->usuario_id)->where('rol', 'ADMIN')->exists()) $f[] = "condonación del recargo $cg->id no la hizo el dueño";
        $al = DB::table('alumnos')->find($cg->alumno_id);
        if ($al && $al->nombre !== 'Guadalupe') $f[] = "condonado un recargo de $al->nombre $al->apellido, no el de Guadalupe";
    }
    $saldoCargos = $punitorios->sum(fn ($x) => $c($x->monto_original) - $c($x->monto_condonado)) - $cobrado;
    if ($saldoCargos !== $pendiente) $f[] = 'recargos pendientes '.($pendiente / 100).' no coinciden con el saldo de los cargos '.($saldoCargos / 100);
    control($resultados, 'PEN-2', 'Lo aplicado en recargos es lo cobrado más lo condonado más lo pendiente; lo cobrado es lo que dice Reportes; cada condonación tiene al dueño y su motivo', $f,
        'aplicado '.($aplicado / 100).' = cobrado '.($cobrado / 100).' + condonado '.($condonado / 100).' + pendiente '.($pendiente / 100));

    // Deuda dada por perdida (32.1, CBZ-024): una sola, de una inactiva, por el dueño, marcada en sus cuotas y sin
    // pago, recibo ni movimiento de plata.
    $perdidas = DB::table('deudas_perdidas')->get();
    $f = [];
    if ($perdidas->count() !== 1) $f[] = $perdidas->count().' deudas perdidas (esperada 1: Julieta)';
    foreach ($perdidas as $pz) {
        $al = DB::table('alumnos')->find($pz->alumno_id);
        if (!$al || $al->activo) $f[] = "perdida $pz->id de un alumno activo o inexistente";
        elseif ($al->nombre !== 'Julieta') $f[] = "perdida $pz->id de $al->nombre, no de Julieta";
        if (!$pz->usuario_id || !DB::table('users')->where('id', $pz->usuario_id)->where('rol', 'ADMIN')->exists()) $f[] = "perdida $pz->id sin dueño registrado";
        $marcado = $c(DB::table('deuda_cuotas')->where('alumno_id', $pz->alumno_id)->sum('monto_perdido')) + $c(DB::table('cargos_alumno')->where('alumno_id', $pz->alumno_id)->sum('monto_perdido'));
        if ($marcado !== $c($pz->importe)) $f[] = "perdida $pz->id: $pz->importe no es lo marcado en cuotas y cargos (".($marcado / 100).')';
        if ($c($pz->importe) !== 7700000) $f[] = "perdida $pz->id por $pz->importe (pantalla: 77.000)";
        $saldo = DB::table('deuda_cuotas')->where('alumno_id', $pz->alumno_id)->where('estado', '!=', 'CONDONADA')->get()
            ->sum(fn ($d) => $c($d->monto_original) - $c($d->monto_pagado) - $c($d->monto_perdido));
        if ($saldo !== 0) $f[] = "perdida $pz->id: a la alumna le sigue quedando ".($saldo / 100).' de saldo';
        if (DB::table('pagos')->where('alumno_id', $pz->alumno_id)->where('created_at', '>=', $pz->created_at)->exists()) $f[] = "perdida $pz->id creó un pago";
        foreach (['cashflow_movimientos', 'movimientos_operativos'] as $tb)
            if (DB::table($tb)->where('created_at', '>=', $pz->created_at)->whereIn('monto', [$pz->importe, '-'.$pz->importe])->exists()) $f[] = "perdida $pz->id dejó un movimiento en $tb";
    }
    control($resultados, 'CBZ-3', 'La deuda perdida es una, de una inactiva, la registró el dueño, está marcada en sus cuotas por el mismo importe y no creó pago ni movimiento de plata', $f,
        $perdidas->map(fn ($p) => $p->importe.' el '.$p->created_at)->implode('; '));

    // Baja de Verónica (29.5 a 29.8, PRO-004, ACC-004, LIQ-006): inactiva, sin clases desde el 7/11, usuario
    // desactivado y sin sesiones, con sus 2 clases dictadas de noviembre; las 7 Intermedias reasignadas a Lucía.
    $vero = DB::table('profesores')->where('apellido', 'Salinas')->first();
    $f = [];
    if (!$vero) $f[] = 'no está Verónica Salinas';
    else {
        if ($vero->activo) $f[] = 'Verónica sigue activa';
        $futuras = DB::table('clases as c')->join('clase_profesor as cp', 'cp.clase_id', '=', 'c.id')->where('cp.profesor_id', $vero->id)->where('c.cancelada', 0)->where('c.fecha', '>=', '2026-11-07')->count();
        if ($futuras) $f[] = "$futuras clases desde el 7/11 siguen con Verónica";
        $u = DB::table('users')->where('profesor_id', $vero->id)->first();
        if (!$u) $f[] = 'Verónica no tiene usuario';
        elseif ($u->activo) $f[] = 'el usuario de Verónica sigue activo';
        elseif (DB::table('sessions')->where('user_id', $u->id)->exists()) $f[] = 'Verónica conserva sesiones abiertas';
        $nov = DB::table('clases as c')->join('clase_profesor as cp', 'cp.clase_id', '=', 'c.id')->where('cp.profesor_id', $vero->id)->where('c.cancelada', 0)->whereBetween('c.fecha', ['2026-11-01', '2026-11-06'])->count();
        if ($nov !== 2) $f[] = "$nov clases dictadas de noviembre con Verónica (esperadas 2)";
        $lucia = DB::table('profesores')->where('nombre', 'Lucía')->first();
        $conLucia = fn ($fechas, $hora) => $lucia ? DB::table('clases as c')->join('clase_profesor as cp', 'cp.clase_id', '=', 'c.id')->where('cp.profesor_id', $lucia->id)
            ->where('c.cancelada', 0)->whereIn('c.fecha', $fechas)->where('c.hora_inicio', $hora)->count() : 0;
        $reasignadas = $conLucia(['2026-11-09', '2026-11-16', '2026-11-23', '2026-11-30'], '17:00:00') + $conLucia(['2026-11-13', '2026-11-20', '2026-11-27'], '16:00:00');
        if ($reasignadas !== 7) $f[] = "$reasignadas de las 7 Intermedias están con Lucía";
    }
    control($resultados, 'PRO-1', 'Verónica inactiva: ninguna clase desde el 7/11, usuario desactivado y sin sesiones, sus 2 clases dictadas de noviembre siguen siendo suyas; las 7 Intermedias quedaron con Lucía', $f);

    // Corrección de Centenera (30.2, CSH-007): el movimiento quedó en 125.000, con una corrección que guarda antes,
    // después, motivo y dueño, y sin anular.
    $mov = DB::table('cashflow_movimientos')->find(173);
    $corr = DB::table('cashflow_correcciones')->where('movimiento_id', 173)->get();
    $f = [];
    if (!$mov) $f[] = 'no existe el movimiento 173';
    else {
        if ($c($mov->monto) !== -12500000) $f[] = "el movimiento 173 quedó en $mov->monto";
        if ($mov->anulado_at) $f[] = 'el movimiento 173 figura anulado';
        if ((int) $mov->version_correccion !== $corr->count()) $f[] = "versión $mov->version_correccion con {$corr->count()} correcciones";
        if ($corr->count() !== 1) $f[] = "{$corr->count()} correcciones del 173 (esperada 1)";
        foreach ($corr as $cr) {
            $antes = json_decode($cr->antes, true); $despues = json_decode($cr->despues, true);
            if ($cr->accion !== 'EDITAR' || $c($antes['monto'] ?? 0) !== -12000000 || $c($despues['monto'] ?? 0) !== -12500000) $f[] = "corrección $cr->id: $cr->accion de ".($antes['monto'] ?? '?').' a '.($despues['monto'] ?? '?');
            if ($cr->motivo !== 'La factura de octubre era de $125.000') $f[] = "corrección $cr->id con motivo «$cr->motivo»";
            if (!DB::table('users')->where('id', $cr->admin_id)->where('rol', 'ADMIN')->exists()) $f[] = "corrección $cr->id sin dueño";
        }
    }
    control($resultados, 'CSH-4', 'Centenera quedó en 125.000 sin anularse, con una sola corrección que conserva antes, después, motivo y dueño', $f);

    // Cajas de la tanda (29.2, 31.1, 31.2, 31.5, 33.2, 33.3): la #53 la cerró el dueño y quedó registrado; la #55
    // abrió con motivo, se rectificó con motivo y autora, se rechazó con motivo, lleva la resma y se validó con diferencia 0.
    $f = [];
    $k = $cajas->firstWhere('id', 53);
    if (!$k) $f[] = 'no está la caja 53';
    else {
        if (!$k->cerrada_por_admin || !$k->usuario_admin_cierre_id || !DB::table('users')->where('id', $k->usuario_admin_cierre_id)->where('rol', 'ADMIN')->exists()) $f[] = 'la caja 53 no registra que la cerró el dueño';
        if ($c($k->efectivo_contado) !== 15500000 || $c($k->diferencia_efectivo) !== -50000 || $c($k->cambio_retenido) !== 1000000 || $c($k->efectivo_retirado) !== 14500000) $f[] = "caja 53: contado $k->efectivo_contado, diferencia $k->diferencia_efectivo, cambio $k->cambio_retenido, entrega $k->efectivo_retirado";
        if ($k->estado !== 'VALIDADA') $f[] = "caja 53 en estado $k->estado";
    }
    $k = $cajas->firstWhere('id', 55);
    if (!$k) $f[] = 'no está la caja 55';
    else {
        $sandra = DB::table('users')->where('name', 'like', 'Sandra%')->value('id');
        if ($c($k->efectivo_heredado) !== 1000000 || $c($k->efectivo_inicial_anterior) !== 10000000 || $c($k->efectivo_inicial) !== 1000000) $f[] = "caja 55: heredado $k->efectivo_heredado, anterior $k->efectivo_inicial_anterior, inicial $k->efectivo_inicial";
        if (trim((string) $k->motivo_apertura) !== 'Conté de nuevo el cajón') $f[] = "caja 55: motivo de apertura «$k->motivo_apertura»";
        if (!$k->rectificado_at || $k->usuario_rectificacion_id !== $sandra || trim((string) $k->motivo_rectificacion) !== 'Escribí un cero de más: recibí $10.000') $f[] = 'caja 55: rectificación sin fecha, autora o motivo esperados';
        if (!str_starts_with((string) $k->motivo_rechazo, 'Faltan $2.000')) $f[] = "caja 55: motivo de rechazo «$k->motivo_rechazo»";
        if ($k->estado !== 'VALIDADA' || $c($k->efectivo_contado) !== 12250000 || $c($k->diferencia_efectivo) !== 0) $f[] = "caja 55: estado $k->estado, contado $k->efectivo_contado, diferencia $k->diferencia_efectivo";
        $resma = DB::table('movimientos_operativos')->where('caja_operativa_id', 55)->where('fecha', 'like', '2026-11-09%')->where('monto', '2000.00')->where('observaciones', 'Resma del lunes')->count();
        if ($resma !== 1) $f[] = "caja 55: $resma resmas del lunes (esperada 1)";
    }
    control($resultados, 'CAJ-3', 'La #53 registra que la cerró el dueño, con faltante 500 y cambio 10.000; la #55 heredó 10.000, abrió con motivo, se rectificó con motivo y autora, se rechazó con motivo, lleva la resma del 9/11 y se validó con diferencia 0', $f);

    // Clases (32.3, 33.4): la 255 reactivada conserva su motivo; la 271 no existe; Principiantes del 11/11 tiene 22
    // presentes y 2 ausentes, con excepción solo para Santino e Iván y con los motivos del guion.
    $f = [];
    $cl = DB::table('clases')->find(255);
    if (!$cl) $f[] = 'no está la clase 255';
    elseif ($cl->cancelada || $cl->motivo_cancelacion !== 'Torneo: el grupo viaja') $f[] = "clase 255: cancelada $cl->cancelada, motivo «$cl->motivo_cancelacion»";
    if (DB::table('clases')->where('id', 271)->exists()) $f[] = 'la clase 271 (el amistoso) sigue existiendo';
    $pri = DB::table('clases')->where('fecha', '2026-11-11')->where('hora_inicio', '18:00:00')->where('cancelada', 0)->get();
    if ($pri->count() !== 1) $f[] = $pri->count().' clases el 11/11 a las 18:00';
    else {
        $marcas = DB::table('asistencias')->where('clase_id', $pri[0]->id)->get();
        if ($marcas->where('presente', 1)->count() !== 22 || $marcas->where('presente', 0)->count() !== 2) $f[] = 'Principiantes 11/11: '.$marcas->where('presente', 1)->count().' presentes y '.$marcas->where('presente', 0)->count().' ausentes';
        $exc = DB::table('excepciones_cobranza')->where('registro_tipo', 'like', '%ASIST%')->whereIn('registro_id', $marcas->pluck('id'))->get();
        $esperados = ['Santino' => 'Octubre lo paga el viernes; lo habló la mamá con Sandra', 'Iván' => 'Pagó la inscripción; la cuota la trae el viernes'];
        if ($exc->count() !== 2) $f[] = 'Principiantes 11/11: '.$exc->count().' excepciones de asistencia (esperadas 2)';
        foreach ($exc as $e) {
            $al = DB::table('alumnos')->find($e->alumno_id);
            if (!$al || !isset($esperados[$al->nombre])) $f[] = 'excepción de asistencia de '.($al->nombre ?? '?').', que no la necesitaba';
            elseif (trim((string) $e->motivo) !== $esperados[$al->nombre]) $f[] = "motivo de $al->nombre: «$e->motivo»";
            if (!$e->autor_id) $f[] = "excepción $e->id sin autor";
        }
    }
    control($resultados, 'CLA-2', 'La clase reactivada conserva su motivo de cancelación; el amistoso eliminado no existe; Principiantes del 11/11 tiene 22 presentes y 2 ausentes, con excepción solo de Santino e Iván y los motivos del guion', $f);

    // Cobros de la tanda (32.4, 33.7, COB-031, COB-034): Iván pagó solo la inscripción y su cuota sigue entera;
    // Florencia pagó cuota y recargo en un solo cobro, con dos renglones en la caja.
    $f = [];
    $ivan = $porNombre('Iván');
    if (!$ivan) $f[] = 'no está Iván';
    else {
        // Días 34 a 40 (38, rutina): la mamá trajo la cuota con su recargo. El primer cobro sigue sin tocar la cuota.
        $pagoIvan = DB::table('pagos')->where('alumno_id', $ivan->id)->where('estado', '!=', 'ANULADO')->orderBy('id')->get();
        if ($pagoIvan->count() !== 2 || $c($pagoIvan[0]->monto_final) !== 500000 || $c($pagoIvan[1]->monto_final) !== 3850000) $f[] = 'Iván: '.$pagoIvan->count().' cobros, '.$pagoIvan->pluck('monto_final')->implode(' y ');
        else {
            if (DB::table('pago_deuda_cuota')->where('pago_id', $pagoIvan[0]->id)->exists()) $f[] = 'el cobro de la inscripción de Iván imputó a una cuota';
            $aCuota = $c(DB::table('pago_deuda_cuota')->where('pago_id', $pagoIvan[1]->id)->sum('monto_aplicado'));
            if ($aCuota !== 3500000) $f[] = 'el segundo cobro de Iván imputó '.($aCuota / 100).' a la cuota (esperado 35.000 y 3.500 al recargo)';
        }
        $cuota = DB::table('deuda_cuotas')->where('alumno_id', $ivan->id)->where('periodo', '2026-11')->first();
        if (!$cuota || $cuota->estado !== 'PAGADA' || $c($cuota->monto_pagado) !== 3500000 || $c($cuota->monto_original) !== 3500000) $f[] = 'la cuota de noviembre de Iván no quedó pagada en 35.000';
        $insc = DB::table('cargos_alumno')->where('alumno_id', $ivan->id)->where('tipo', 'INSCRIPCION')->first();
        if (!$insc || $c($insc->monto_original) !== 500000) $f[] = 'Iván sin inscripción de 5.000';
    }
    $flor = $porNombre('Florencia', 'Vega');
    if (!$flor) $f[] = 'no está Florencia Vega';
    else {
        $pagoF = DB::table('pagos')->where('alumno_id', $flor->id)->where('estado', '!=', 'ANULADO')->where('monto_final', '55000.00')->first();
        if (!$pagoF) $f[] = 'Florencia sin cobro de 55.000';
        else {
            $cuotaF = $c(DB::table('pago_deuda_cuota')->where('pago_id', $pagoF->id)->sum('monto_aplicado'));
            $recF = $c(DB::table('pago_cargo_alumno as pc')->join('cargos_alumno as cg', 'cg.id', '=', 'pc.cargo_alumno_id')->where('pc.pago_id', $pagoF->id)->where('cg.tipo', 'PUNITORIO')->sum('pc.monto_aplicado'));
            if ($cuotaF !== 5000000 || $recF !== 500000) $f[] = 'Florencia: cuota '.($cuotaF / 100).' y recargo '.($recF / 100);
            $renglones = DB::table('movimientos_operativos')->where('pago_id', $pagoF->id)->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 'CANCELADO'))->get();
            if ($renglones->count() !== 2 || $c($renglones->sum('monto')) !== 5500000) $f[] = 'Florencia: '.$renglones->count().' renglones en la caja por '.$renglones->sum('monto');
        }
    }
    control($resultados, 'COB-6', 'Iván pagó primero solo la inscripción (5.000, sin tocar la cuota) y el 16/11 la cuota con su recargo (35.000 + 3.500); Florencia pagó cuota 50.000 y recargo 5.000 en un cobro con dos renglones en la caja', $f);

    // Aumento del plan (30.5, GRU-003): Fútbol Avanzadas 2 por semana vale 52.000 desde el 1/12; las cuotas de
    // noviembre y el diciembre adelantado de Joaquín siguen en 48.000.
    $f = []; $enPlan = '';
    $plan = DB::table('grupo_planes as gp')->join('grupos as g', 'g.id', '=', 'gp.grupo_id')->join('deportes as d', 'd.id', '=', 'g.deporte_id')->join('niveles as n', 'n.id', '=', 'g.nivel_id')
        ->where('d.nombre', 'Fútbol')->where('n.nombre', 'like', 'Avanzad%')->where('gp.clases_por_semana', 2)->select('gp.id')->first();
    if (!$plan) $f[] = 'no está el plan de 2 por semana de Fútbol Avanzadas';
    else {
        $precios = DB::table('grupo_plan_precios')->where('grupo_plan_id', $plan->id)->orderBy('vigente_desde')->orderBy('id')->get();
        $nuevo = $precios->first(fn ($p) => $c($p->precio_mensual) === 5200000);
        if (!$nuevo) $f[] = 'el plan no tiene el precio 52.000';
        // T44: el precio nuevo rige desde el día del cambio para las altas nuevas; las cuotas ya generadas no se tocan
        // y los existentes lo pagan desde la generación del 1/12.
        elseif ((string) $nuevo->vigente_desde !== '2026-11-08') $f[] = "52.000 vigente desde $nuevo->vigente_desde (esperado el día del cambio, 2026-11-08)";
        $alumnosPlan = DB::table('alumno_planes')->where('plan_id', $plan->id)->where('activo', 1)->pluck('alumno_id');
        foreach (DB::table('deuda_cuotas')->whereIn('alumno_id', $alumnosPlan)->where('periodo', '2026-11')->get() as $d) if ($c($d->monto_original) !== 4800000) $f[] = "cuota de noviembre $d->id en $d->monto_original";
        $dic = DB::table('deuda_cuotas')->whereIn('alumno_id', $alumnosPlan)->where('periodo', '2026-12')->get();
        // Joaquín adelantó diciembre antes del aumento (48.000); Dante el 18/11, después (52.000, 40.3).
        $dicPorAlumno = $dic->mapWithKeys(fn ($d) => [DB::table('alumnos')->where('id', $d->alumno_id)->value('nombre') => $c($d->monto_original).' '.$d->estado]);
        $dicEsperado = ['Joaquin' => '4800000 PAGADA', 'Dante' => '5200000 PAGADA'];
        if ($dicPorAlumno->sortKeys()->all() !== collect($dicEsperado)->sortKeys()->all()) $f[] = 'cuotas de diciembre del plan: '.$dicPorAlumno->map(fn ($v, $k) => "$k $v")->implode(', ');
        $enPlan = $alumnosPlan->count().' alumnos en el plan';
    }
    control($resultados, 'GRU-1', 'El plan de 2 por semana de Fútbol Avanzadas vale 52.000 desde el 1/12; sus cuotas de noviembre y el diciembre adelantado de Joaquín siguen en 48.000; el diciembre que Dante adelantó después del aumento es de 52.000', $f, $enPlan);

    // ── Días 34 a 40 (12 al 18/11) ───────────────────────────────────────
    // Lo que un cobro imputó, en centavos: [cuota, recargo, inscripción].
    $imputado = fn (int $pagoId) => [
        $c(DB::table('pago_deuda_cuota')->where('pago_id', $pagoId)->sum('monto_aplicado')),
        $c(DB::table('pago_cargo_alumno as pc')->join('cargos_alumno as cg', 'cg.id', '=', 'pc.cargo_alumno_id')->where('pc.pago_id', $pagoId)->where('cg.tipo', 'PUNITORIO')->sum('pc.monto_aplicado')),
        $c(DB::table('pago_cargo_alumno as pc')->join('cargos_alumno as cg', 'cg.id', '=', 'pc.cargo_alumno_id')->where('pc.pago_id', $pagoId)->where('cg.tipo', 'INSCRIPCION')->sum('pc.monto_aplicado')),
    ];
    $cobros = fn (int $alumnoId) => DB::table('pagos')->where('alumno_id', $alumnoId)->where('estado', '!=', 'ANULADO')->where('fecha_pago', '>=', '2026-11-12')->orderBy('id')->get();

    // 34.1 y 35.1 (CBZ-006): cada ficha de una misma persona cobra lo suyo, cuota y recargo, sin mezclarse con la otra.
    $f = [];
    foreach ([['Camila', 'Benitez', [4950000 => [4500000, 450000], 3080000 => [2800000, 280000]]],
              ['Mora', 'Benítez', [4840000 => [4400000, 440000], 3080000 => [2800000, 280000]]]] as [$n, $ap, $esperado]) {
        $fichas = DB::table('alumnos')->where('nombre', $n)->where('apellido', $ap)->orderBy('id')->get();
        if ($fichas->count() !== 2) { $f[] = "$n $ap: ".$fichas->count().' fichas (esperadas 2)'; continue; }
        $vistos = [];
        foreach ($fichas as $ficha) {
            $p = $cobros($ficha->id);
            if ($p->count() !== 1) { $f[] = "$n $ap #$ficha->id: ".$p->count().' cobros en la tanda'; continue; }
            $monto = $c($p[0]->monto_final);
            [$cu, $re] = $imputado($p[0]->id);
            if (!isset($esperado[$monto]) || $esperado[$monto] !== [$cu, $re]) $f[] = "$n $ap #$ficha->id: cobro de ".($monto / 100).' imputó cuota '.($cu / 100).' y recargo '.($re / 100);
            $vistos[] = $monto;
            $nov = DB::table('deuda_cuotas')->where('alumno_id', $ficha->id)->where('periodo', '2026-11')->first();
            if (!$nov || $nov->estado !== 'PAGADA') $f[] = "$n $ap #$ficha->id: noviembre no quedó pagada";
        }
        sort($vistos); $claves = array_keys($esperado); sort($claves);
        if ($vistos !== $claves) $f[] = "$n $ap: los dos cobros no son uno por ficha";
    }
    control($resultados, 'COB-7', 'Camila y Mora Benítez, con dos fichas cada una, pagaron noviembre con recargo desde cada ficha: un cobro por ficha, cada uno imputado a su cuota y su recargo', $f);

    // 34.2 y 38.2 (PEN-001): Benicio pagó primero solo la cuota (el recargo quedó pendiente) y después solo el recargo.
    // 34.3 y 36.1 (COB-008, COB-009): Joaquín pagó 20.000 a cuenta con motivo de Sandra y el resto el sábado.
    $f = [];
    $ben = $porNombre('Benicio', 'Torres');
    if (!$ben) $f[] = 'no está Benicio Torres';
    else {
        $p = $cobros($ben->id);
        if ($p->count() !== 2) $f[] = 'Benicio: '.$p->count().' cobros en la tanda';
        else {
            if ($imputado($p[0]->id) !== [2800000, 0, 0]) $f[] = 'Benicio, primer cobro: '.implode('/', $imputado($p[0]->id)).' (esperado solo la cuota, 28.000)';
            if ($imputado($p[1]->id) !== [0, 280000, 0]) $f[] = 'Benicio, segundo cobro: '.implode('/', $imputado($p[1]->id)).' (esperado solo el recargo, 2.800)';
            if ((string) $p[0]->fecha_pago !== '2026-11-12' || (string) $p[1]->fecha_pago !== '2026-11-16') $f[] = "Benicio: cobros del {$p[0]->fecha_pago} y {$p[1]->fecha_pago}";
        }
        $rec = DB::table('cargos_alumno')->where('alumno_id', $ben->id)->where('tipo', 'PUNITORIO')->first();
        if (!$rec || $c($rec->monto_original) !== 280000 || $c($rec->monto_condonado) !== 0) $f[] = 'Benicio: recargo distinto de 2.800 sin condonar';
    }
    $joa = $porNombre('Joaquin', 'Rodriguez');
    if (!$joa) $f[] = 'no está Joaquín Rodríguez';
    else {
        $p = $cobros($joa->id)->filter(fn ($x) => DB::table('pago_deuda_cuota as pd')->join('deuda_cuotas as d', 'd.id', '=', 'pd.deuda_cuota_id')->where('pd.pago_id', $x->id)->where('d.periodo', '2026-11')->exists())->values();
        if ($p->count() !== 2) $f[] = 'Joaquín: '.$p->count().' cobros de noviembre en la tanda';
        else {
            if ($imputado($p[0]->id) !== [2000000, 0, 0]) $f[] = 'Joaquín, cobro a cuenta: '.implode('/', $imputado($p[0]->id));
            if ($imputado($p[1]->id) !== [1500000, 350000, 0]) $f[] = 'Joaquín, el resto: '.implode('/', $imputado($p[1]->id)).' (esperado 15.000 + 3.500)';
            $exc = DB::table('excepciones_cobranza')->where('registro_tipo', 'PAGO')->where('registro_id', $p[0]->id)->get();
            if ($exc->count() !== 1 || $exc[0]->tipo !== 'COBRO_PARCIAL' || trim((string) $exc[0]->motivo) !== 'La mamá trae el resto el sábado') $f[] = 'Joaquín: el cobro a cuenta no tiene su motivo de parcial';
            elseif (DB::table('users')->where('id', $exc[0]->autor_id)->value('name') !== 'Sandra Vidal') $f[] = 'Joaquín: el motivo del parcial no es de Sandra';
            if (DB::table('excepciones_cobranza')->where('registro_tipo', 'PAGO')->where('registro_id', $p[1]->id)->exists()) $f[] = 'Joaquín: el pago del resto pidió motivo';
        }
        $nov = DB::table('deuda_cuotas')->where('alumno_id', $joa->id)->where('periodo', '2026-11')->first();
        if (!$nov || $nov->estado !== 'PAGADA' || $c($nov->monto_pagado) !== 3500000) $f[] = 'Joaquín: noviembre no quedó pagada en 35.000';
    }
    control($resultados, 'COB-8', 'Benicio pagó la cuota sola el 12/11 y el recargo solo el 16/11; Joaquín pagó 20.000 a cuenta con el motivo de Sandra y el 14/11 el resto, 15.000 de cuota y 3.500 de recargo, sin motivo', $f);

    // 35 (rutina) y 35.2: Santino pagó octubre, un mes cerrado, sin recargo; Guadalupe pagó noviembre con el recargo perdonado.
    $f = [];
    $san = $porNombre('Santino', 'Romero');
    if (!$san) $f[] = 'no está Santino Romero';
    else {
        $p = $cobros($san->id);
        $oct = DB::table('deuda_cuotas')->where('alumno_id', $san->id)->where('periodo', '2026-10')->first();
        if ($p->count() !== 1 || $imputado($p[0]->id) !== [2800000, 0, 0]) $f[] = 'Santino: cobros de la tanda '.$p->pluck('monto_final')->implode(', ');
        elseif (!$oct || !DB::table('pago_deuda_cuota')->where('pago_id', $p[0]->id)->where('deuda_cuota_id', $oct->id)->exists()) $f[] = 'el cobro de Santino no fue a octubre';
        if (!$oct || $oct->estado !== 'PAGADA') $f[] = 'Santino: octubre no quedó pagada';
        if ($oct && DB::table('cargos_alumno')->where('deuda_cuota_id', $oct->id)->where('tipo', 'PUNITORIO')->exists()) $f[] = 'Santino: octubre tiene recargo';
    }
    $gua = $porNombre('Guadalupe');
    if (!$gua) $f[] = 'no está Guadalupe';
    else {
        $p = $cobros($gua->id);
        if ($p->count() !== 1 || $imputado($p[0]->id) !== [5000000, 0, 0]) $f[] = 'Guadalupe: cobros de la tanda '.$p->pluck('monto_final')->implode(', ');
        $rec = DB::table('cargos_alumno')->where('alumno_id', $gua->id)->where('tipo', 'PUNITORIO')->first();
        if (!$rec || $c($rec->monto_condonado) !== $c($rec->monto_original)) $f[] = 'Guadalupe: el recargo no está perdonado entero';
    }
    control($resultados, 'COB-9', 'Santino pagó octubre (mes cerrado) sin recargo, imputado a octubre; Guadalupe pagó noviembre, 50.000, y su recargo sigue perdonado entero', $f);

    // 36.3 y 40.3 (COB-012): las únicas cuotas de diciembre son los tres adelantos, pagados y con el precio del día del cobro.
    $f = [];
    $dicTodas = DB::table('deuda_cuotas as d')->join('alumnos as a', 'a.id', '=', 'd.alumno_id')->where('d.periodo', '2026-12')
        ->get(['a.nombre', 'a.apellido', 'd.monto_original', 'd.monto_pagado', 'd.estado'])
        ->mapWithKeys(fn ($d) => ["$d->nombre $d->apellido" => $c($d->monto_original).'/'.$c($d->monto_pagado).' '.$d->estado])->sortKeys()->all();
    $dicEsperadas = ['Dante Ramos' => '5200000/5200000 PAGADA', 'Emma Fernandez' => '4500000/4500000 PAGADA', 'Joaquin Vega' => '4800000/4800000 PAGADA'];
    if ($dicTodas !== $dicEsperadas) $f[] = 'cuotas de diciembre: '.json_encode($dicTodas, JSON_UNESCAPED_UNICODE);
    control($resultados, 'COB-10', 'Diciembre no se generó: sus únicas cuotas son los tres adelantos, pagados enteros (Joaquín Vega 48.000, Emma Fernández 45.000 y Dante 52.000, ya con el precio nuevo)', $f);

    // 39.1 a 39.3 (COB-025, CBZ-017): dos altas del 17/11 al 70% de la cuota; Bautista pagó cuota e inscripción
    // en un cobro y fue a su primera clase el 18/11 sin motivo; Emma Villalba no pagó ni vino.
    $f = [];
    foreach ([['Bautista', 'Sosa', 2450000], ['Emma', 'Villalba', 2310000]] as [$n, $ap, $cuota]) {
        $al = $porNombre($n, $ap);
        if (!$al) { $f[] = "no está $n $ap"; continue; }
        if ((string) $al->fecha_alta !== '2026-11-17' || !$al->activo) $f[] = "$n: alta $al->fecha_alta, activo $al->activo";
        $nov = DB::table('deuda_cuotas')->where('alumno_id', $al->id)->where('periodo', '2026-11')->first();
        if (!$nov || $c($nov->monto_original) !== $cuota) $f[] = "$n: cuota de noviembre ".($nov->monto_original ?? 'ninguna').' (esperada '.($cuota / 100).')';
        $insc = DB::table('cargos_alumno')->where('alumno_id', $al->id)->where('tipo', 'INSCRIPCION')->get();
        if ($insc->count() !== 1 || $c($insc[0]->monto_original) !== 500000) $f[] = "$n: inscripción distinta de una de 5.000";
        $p = $cobros($al->id);
        $presentes = DB::table('asistencias')->where('alumno_id', $al->id)->where('presente', 1)->get();
        if ($n === 'Bautista') {
            if ($p->count() !== 1 || $imputado($p[0]->id) !== [2450000, 0, 500000]) $f[] = 'Bautista: cobros '.$p->pluck('monto_final')->implode(', ');
            if (!$nov || $nov->estado !== 'PAGADA') $f[] = 'Bautista: noviembre no quedó pagada';
            $clase = DB::table('clases')->where('fecha', '2026-11-18')->where('hora_inicio', '18:00:00')->first();
            if ($presentes->count() !== 1 || !$clase || $presentes[0]->clase_id !== $clase->id) $f[] = 'Bautista: presentes '.$presentes->count().' (esperado uno, el 18/11 a las 18)';
            elseif (DB::table('excepciones_cobranza')->where('registro_tipo', 'ASISTENCIA')->where('registro_id', $presentes[0]->id)->exists()) $f[] = 'Bautista: su primera clase pidió motivo';
        } else {
            if ($p->count() !== 0) $f[] = 'Emma Villalba tiene cobros';
            if (!$nov || $nov->estado !== 'PENDIENTE' || $c($nov->monto_pagado) !== 0) $f[] = 'Emma Villalba: noviembre no sigue entera y pendiente';
            if ($presentes->count() !== 0) $f[] = 'Emma Villalba tiene presentes';
        }
    }
    control($resultados, 'ALU-4', 'Bautista Sosa y Emma Villalba entraron el 17/11 con la cuota de noviembre al 70% (24.500 y 23.100) y una inscripción de 5.000; Bautista pagó las dos en un cobro y fue el 18/11 sin motivo; Emma no pagó ni vino', $f);

    // 38.1 (ALU-013): Lara cambió a Intermedias el 16/11; el plan nuevo rige desde el 1/12 porque noviembre ya estaba
    // pagada, y desde el 16/11 solo figura en las listas de Intermedias.
    $f = [];
    $lara = $porNombre('Lara', 'Quiroga');
    $grupoDe = fn (string $nivel) => DB::table('grupos as g')->join('niveles as n', 'n.id', '=', 'g.nivel_id')->join('deportes as d', 'd.id', '=', 'g.deporte_id')
        ->where('d.nombre', 'Patín')->where('n.nombre', $nivel)->value('g.id');
    $gPri = $grupoDe('Principiantes'); $gInt = $grupoDe('Intermedias');
    if (!$lara || !$gPri || !$gInt) $f[] = 'no está Lara Quiroga o los grupos de Patín';
    else {
        if ((int) $lara->grupo_id !== (int) $gInt) $f[] = "Lara está en el grupo $lara->grupo_id";
        $asig = DB::table('alumno_planes as ap')->join('grupo_planes as gp', 'gp.id', '=', 'ap.plan_id')->where('ap.alumno_id', $lara->id)->orderBy('ap.fecha_desde')
            ->get(['gp.grupo_id', 'gp.clases_por_semana', 'ap.fecha_desde', 'ap.fecha_hasta', 'ap.activo']);
        $vieja = $asig->firstWhere('grupo_id', $gPri); $nueva = $asig->firstWhere('grupo_id', $gInt);
        if (!$vieja || (string) $vieja->fecha_hasta !== '2026-11-30' || $vieja->activo) $f[] = 'Lara: el plan de Principiantes no cierra el 30/11';
        if (!$nueva || (string) $nueva->fecha_desde !== '2026-12-01' || !$nueva->activo || (int) $nueva->clases_por_semana !== 2) $f[] = 'Lara: el plan de Intermedias no rige desde el 1/12 con 2 por semana';
        $nov = DB::table('deuda_cuotas')->where('alumno_id', $lara->id)->where('periodo', '2026-11')->first();
        if (!$nov || $c($nov->monto_original) !== 4400000 || $nov->estado !== 'PAGADA') $f[] = 'Lara: noviembre no sigue en 44.000 pagada';
        $listas = DB::table('asistencias as a')->join('clases as k', 'k.id', '=', 'a.clase_id')->where('a.alumno_id', $lara->id)->where('k.fecha', '>=', '2026-11-16')->pluck('k.grupo_id')->map(fn ($g) => (int) $g)->unique()->values()->all();
        if ($listas !== [(int) $gInt]) $f[] = 'Lara figura desde el 16/11 en las listas de los grupos '.implode(', ', $listas);
    }
    control($resultados, 'ALU-5', 'Lara pasó a Patín Intermedias el 16/11: el plan de Principiantes cierra el 30/11, el de Intermedias (2 por semana) rige desde el 1/12, noviembre sigue pagada en 44.000 y desde el 16/11 figura solo en las listas de Intermedias', $f);

    // 40.1 (CLA-011): la clase de Adultos del 19/11 quedó cancelada con su motivo y sin lista. 39.4 (CSH-018): cada
    // transferencia son dos renglones opuestos que se anulan, clasificados como traspaso (no mueven el resultado).
    $f = [];
    $adu = DB::table('clases')->where('fecha', '2026-11-19')->where('hora_inicio', '20:30:00')->first();
    if (!$adu || !$adu->cancelada || $adu->motivo_cancelacion !== 'Mariela avisó que no puede: turno médico') $f[] = 'Adultos del 19/11: no está cancelada con el motivo del guion';
    elseif (DB::table('asistencias')->where('clase_id', $adu->id)->exists()) $f[] = 'Adultos del 19/11 tiene lista';
    $trf = DB::table('cashflow_movimientos')->where('referencia_tipo', 'TRANSFERENCIA')->get()->groupBy('referencia_id');
    foreach ($trf as $ref => $filas) {
        if ($filas->count() !== 2 || $filas->sum(fn ($x) => $c($x->monto)) !== 0 || $filas->pluck('reporte_clasificacion')->unique()->all() !== ['TRASPASO']
            || $filas->pluck('fecha')->unique()->count() !== 1 || $filas->pluck('tipo_caja_id')->unique()->count() !== 2) $f[] = "transferencia #$ref no son dos renglones opuestos de traspaso";
    }
    $t4 = $trf->get(4);
    if (!$t4) $f[] = 'no está la transferencia #4';
    else {
        $efe = DB::table('tipos_caja')->where('nombre', 'Efectivo')->value('id'); $bga = DB::table('tipos_caja')->where('nombre', 'Banco Galicia')->value('id');
        $sal = $t4->firstWhere('tipo_caja_id', $efe); $ent = $t4->firstWhere('tipo_caja_id', $bga);
        if (!$sal || !$ent || $c($sal->monto) !== -150000000 || $c($ent->monto) !== 150000000 || (string) $sal->fecha !== '2026-11-17') $f[] = 'la transferencia #4 no es Efectivo → Galicia por 1.500.000 el 17/11';
    }
    control($resultados, 'CSH-5', 'La clase de Adultos del 19/11 está cancelada con el motivo de Mariela y sin lista; cada transferencia son dos renglones opuestos de traspaso del mismo día en dos cuentas, y la #4 es Efectivo → Galicia por 1.500.000 el 17/11', $f, $trf->count().' transferencias');

    // Cajas de la tanda: todas validadas por el dueño y sin diferencia; la del sábado de Pablo, validada el domingo 15/11
    // (37.2); la de Rocío del 18/11 dejó todo lo contado como cambio (7.000) y no entregó nada.
    $f = [];
    $tanda = DB::table('cajas_operativas')->where('apertura_at', '>=', '2026-11-12')->orderBy('id')->get();
    if ($tanda->count() !== 6) $f[] = $tanda->count().' cajas abiertas desde el 12/11 (esperadas 6: no hay caja el domingo)';
    foreach ($tanda as $k) {
        if ($k->estado !== 'VALIDADA' || $c($k->diferencia_efectivo) !== 0) $f[] = "caja $k->id: $k->estado, diferencia $k->diferencia_efectivo";
        if (DB::table('users')->where('id', $k->usuario_admin_validacion_id)->value('rol') !== 'ADMIN') $f[] = "caja $k->id no la validó el dueño";
    }
    $sab = $tanda->first(fn ($k) => substr((string) $k->apertura_at, 0, 10) === '2026-11-14');
    if (!$sab || substr((string) $sab->validada_at, 0, 10) !== '2026-11-15') $f[] = 'la caja del sábado 14/11 no se validó el domingo 15/11';
    $ult = $tanda->last();
    if (!$ult || $c($ult->efectivo_contado) !== 700000 || $c($ult->cambio_retenido) !== 700000 || $c($ult->efectivo_retirado) !== 0) $f[] = 'la caja del 18/11 no dejó los 7.000 contados como cambio';
    control($resultados, 'CAJ-4', 'Las seis cajas de los días 34 a 40 están validadas por el dueño y sin diferencia; la del sábado 14/11 se validó el domingo; la del 18/11 dejó los 7.000 contados como cambio y no entregó nada', $f);

    // ── Huérfanos ────────────────────────────────────────────────────────
    $f = [];
    foreach ([['pagos', 'alumno_id', 'alumnos'], ['deuda_cuotas', 'alumno_id', 'alumnos'], ['movimientos_operativos', 'caja_operativa_id', 'cajas_operativas'],
              ['asistencias', 'clase_id', 'clases'], ['asistencias', 'alumno_id', 'alumnos'], ['pago_deuda_cuota', 'deuda_cuota_id', 'deuda_cuotas'],
              ['pago_deuda_cuota', 'pago_id', 'pagos'], ['cashflow_movimientos', 'movimiento_operativo_id', 'movimientos_operativos'],
              // Días 24 a 28: lo que deja un alumno o una serie eliminados (ALU-024, CLA-021).
              ['alumno_planes', 'alumno_id', 'alumnos'], ['cargos_alumno', 'alumno_id', 'alumnos'], ['excepciones_cobranza', 'alumno_id', 'alumnos'],
              ['alumnos_revision_cobranza', 'alumno_id', 'alumnos'], ['clase_profesor', 'clase_id', 'clases']] as [$t, $col, $ref]) {
        $n = DB::table("$t as x")->leftJoin("$ref as y", 'y.id', '=', "x.$col")->whereNotNull("x.$col")->whereNull('y.id')->count();
        if ($n) $f[] = "$t.$col: $n sin $ref";
    }
    control($resultados, 'SIS-1', 'No hay registros que apunten a algo que no existe', $f);
} finally {
    DB::rollBack();
}

$ok = collect($resultados)->where('ok', true)->count();
echo json_encode(['fecha_wings' => $ahora, 'base' => 'wingstest', 'controles' => count($resultados), 'aprobados' => $ok, 'resultados' => $resultados],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
