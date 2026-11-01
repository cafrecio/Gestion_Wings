<?php
// cierre.php — las cifras de cierre de un día, como las lee Codex en Cobranza, Reportes e Inicio.
require __DIR__.'/lib.php';
$c = app(App\Services\CobranzaEstadoService::class)->resumenDashboard();
$rep = app(App\Services\ReporteMensualService::class)->obtener(now()->format('Y-m'));
$inact = DB::table('deuda_cuotas as d')->join('alumnos as a', 'a.id', '=', 'd.alumno_id')->where('a.activo', 0)->where('d.estado', '!=', 'CONDONADA')
    ->selectRaw('sum(d.monto_original - d.monto_pagado - d.monto_perdido) s')->value('s');
$cajas = DB::table('cajas_operativas')->whereDate('apertura_at', today())->get();
$porValidar = DB::table('cajas_operativas')->where('estado', 'CERRADA')->count();
$sinLista = App\Models\Clase::where('cancelada', false)->whereDate('fecha', '<=', today())->where('validada_para_liquidacion', false)->doesntHave('asistencias')->count();
echo now()->format('D d/m').' · deuda '.plata($c['total_adeudado']).' · activos '.$c['total_alumnos_activos'].' '.json_encode($c['por_estado']).' · inactivos '.plata($inact)."\n";
echo '   ingresos '.plata($rep['ingresos'] / 100).' · egresos '.plata($rep['egresos'] / 100).' · resultado '.plata(($rep['ingresos'] - $rep['egresos']) / 100).' · disponible '.plata(($rep['disponible'] ?? 0) / 100).' · por validar (cajas) '.$porValidar.' · sin lista '.$sinLista."\n";
foreach ($cajas as $k) echo "   caja #{$k->id} ".App\Models\User::find($k->usuario_operativo_id)->name." {$k->estado} esperado ".plata($k->efectivo_esperado).' contado '.plata($k->efectivo_contado).' cambio '.plata($k->cambio_retenido)."\n";
$sal = DB::select("select t.nombre n, t.saldo_inicial + coalesce((select sum(m.monto) from cashflow_movimientos m where m.tipo_caja_id=t.id),0) s from tipos_caja t order by t.id");
echo '   saldos: '.implode(' · ', array_map(fn ($x) => $x->n.' '.plata($x->s), $sal))."\n";
