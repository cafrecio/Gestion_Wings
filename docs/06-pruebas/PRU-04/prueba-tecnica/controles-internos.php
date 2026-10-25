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
    $f = DB::table('asistencias as a')->join('clases as c', 'c.id', '=', 'a.clase_id')->where(fn ($q) => $q->where('c.cancelada', 1)->orWhere('c.fecha', '>', now()->toDateString()))
        ->select('a.id', 'c.id as clase', 'c.fecha', 'c.cancelada')->get()->map(fn ($r) => "marca $r->id en clase $r->clase ".($r->cancelada ? 'cancelada' : "futura $r->fecha"));
    control($resultados, 'ASI-2', 'No hay marcas en clases canceladas ni en clases que todavía no ocurrieron', $f);
    $f = [];
    foreach (DB::table('asistencias as a')->join('clases as c', 'c.id', '=', 'a.clase_id')->join('alumnos as al', 'al.id', '=', 'a.alumno_id')
        ->select('a.id', 'a.alumno_id', 'c.id as clase', 'c.fecha', 'c.grupo_id', 'al.grupo_id as grupo_hoy', 'al.grupo_desde', 'al.fecha_alta')->get() as $r) {
        $llego = $r->grupo_desde ?: $r->fecha_alta;
        if ($r->grupo_id === $r->grupo_hoy && $llego && substr($r->fecha, 0, 10) < substr($llego, 0, 10)) $f[] = "alumno $r->alumno_id marcado en clase $r->clase del $r->fecha antes de llegar al grupo ($llego)";
    }
    control($resultados, 'ASI-3', 'Nadie tiene marca en una clase de su grupo actual anterior a su llegada', $f);
    $f = DB::table('clases as c')->leftJoin('clase_profesor as cp', 'cp.clase_id', '=', 'c.id')->whereNull('cp.clase_id')->where('c.cancelada', 0)->pluck('c.id')->map(fn ($id) => "clase $id sin profesora");
    control($resultados, 'CLA-1', 'Toda clase activa tiene al menos una profesora', $f);

    // ── Huérfanos ────────────────────────────────────────────────────────
    $f = [];
    foreach ([['pagos', 'alumno_id', 'alumnos'], ['deuda_cuotas', 'alumno_id', 'alumnos'], ['movimientos_operativos', 'caja_operativa_id', 'cajas_operativas'],
              ['asistencias', 'clase_id', 'clases'], ['asistencias', 'alumno_id', 'alumnos'], ['pago_deuda_cuota', 'deuda_cuota_id', 'deuda_cuotas'],
              ['pago_deuda_cuota', 'pago_id', 'pagos'], ['cashflow_movimientos', 'movimiento_operativo_id', 'movimientos_operativos']] as [$t, $col, $ref]) {
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
