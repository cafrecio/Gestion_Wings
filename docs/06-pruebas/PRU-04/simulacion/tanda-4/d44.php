<?php
// Día 44, domingo 22/11, 20:00 — el dueño desde su casa: Historial y Cashflow de la semana; valida la caja sin
// movimientos de Pablo; sube la inscripción a $8.000; mira la ficha del profesor nuevo.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ConfiguracionWebController};
use App\Models\{CajaOperativa, CashflowMovimiento, MovimientoOperativo, Profesor};
$admin = U('Admin Prueba'); $caja = app(CajaWebController::class);
estado('inicio 44');
// 44.1 Historial de la semana 16 al 21/11 contra Cashflow
$act = MovimientoOperativo::where('estado', 'ACTIVO')->whereBetween('fecha', ['2026-11-16 00:00:00', '2026-11-21 23:59:59']);
$ing = (clone $act)->whereHas('subrubro.rubro', fn ($q) => $q->where('tipo', 'INGRESO'))->sum('monto');
$egr = (clone $act)->whereHas('subrubro.rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->sum('monto');
echo '   Historial 16 al 21/11: ingresos '.plata($ing).' · egresos '.plata($egr).' · renglones '.(clone $act)->count().' (cancelados '.MovimientoOperativo::where('estado', '!=', 'ACTIVO')->whereBetween('fecha', ['2026-11-16 00:00:00', '2026-11-21 23:59:59'])->count().")\n";
$cf = CashflowMovimiento::whereNull('anulado_at')->whereNull('contra_origen_id')->whereBetween('fecha', ['2026-11-16', '2026-11-21'])->where(fn ($q) => $q->whereNull('reporte_clasificacion')->orWhere('reporte_clasificacion', '!=', 'TRASPASO'));
echo '   Cashflow 16 al 21/11 (sin traspasos ni anulados): ingresos '.plata((clone $cf)->where('monto', '>', 0)->sum('monto')).' · egresos '.plata(-(clone $cf)->where('monto', '<', 0)->sum('monto'))."\n";
echo '   en Cashflow y no en Historial: '.CashflowMovimiento::whereBetween('fecha', ['2026-11-16', '2026-11-21'])->whereNull('movimiento_operativo_id')->get()->map(fn ($m) => $m->subrubro->nombre.' '.plata($m->monto).($m->anulado_at ? ' (anulado)' : '').($m->contra_origen_id ? ' (contra)' : ''))->implode(' · ')."\n";
echo '   cajas abiertas del 16 al 21/11 sin validar: '.CajaOperativa::whereBetween('apertura_at', ['2026-11-16', '2026-11-21 23:59:59'])->where('estado', '!=', 'VALIDADA')->pluck('id')->implode(',')."\n";
[$st] = http($admin, '/movimientos'); echo "   Historial sin fechas: $st · renglones totales ".MovimientoOperativo::count()." · el más viejo ".MovimientoOperativo::min('fecha')."\n";
// 44.2 valida la caja de Pablo del sábado (sin movimientos)
$k = CajaOperativa::where('estado', 'CERRADA')->where('usuario_operativo_id', U('Pablo Ledesma')->id)->firstOrFail();
$antes = CashflowMovimiento::count(); $rep = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
paso('44.2', "valida la caja #{$k->id} de Pablo", enviar($admin, 'POST', "/cajas/{$k->id}/validar", [], fn ($r) => $caja->validar($k->id)), 200);
$rep2 = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
echo '   movimientos nuevos en Cashflow: '.(CashflowMovimiento::count() - $antes).' · Reportes ingresos '.plata($rep['ingresos'] / 100).' → '.plata($rep2['ingresos'] / 100).' · disponible '.plata(($rep2['disponible'] ?? 0) / 100)."\n";
// 44.3 inscripción a $8.000
$em = A('Villalba, Emma');
$c0 = app(App\Services\CobranzaEstadoService::class)->resumenDashboard()['total_adeudado'];
paso('44.3', 'inscripción a $8.000', enviar($admin, 'PATCH', '/configuraciones/inscripcion_importe', ['valor' => '8000'], fn ($r) => app(ConfiguracionWebController::class)->update($r, 'inscripcion_importe'), true), 200);
echo '   inscripción de Emma (pendiente): '.plata(DB::table('cargos_alumno')->where('alumno_id', $em->id)->where('tipo', 'INSCRIPCION')->value('monto_original')).' · total adeudado '.plata($c0).' → '.plata(app(App\Services\CobranzaEstadoService::class)->resumenDashboard()['total_adeudado'])."\n";
// 44.4 fichas de profesor
foreach (['Ibarra', 'Ocampo'] as $ap) {
    $p = Profesor::where('apellido', $ap)->firstOrFail();
    [$st, $t, $h] = http($admin, "/profesores/{$p->id}");
    $txt = preg_replace('/\s+/', ' ', strip_tags(preg_replace('/<(script|style)[^>]*>.*?<\/\1>/s', '', substr($h, strpos($h, 'ds-main')))));
    echo "   ficha de {$p->nombre} ($st): ".mb_substr($txt, 0, 900)."\n";
}
$h = http(U('Sandra Vidal'), '/profesores/'.Profesor::where('apellido', 'Ibarra')->value('id')); echo '   Sandra abre esa ficha: '.$h[0].' '.$h[1]."\n";
$h = http(U('Lucía Gaitán'), '/profesores/1'); echo '   Lucía abre su propia ficha: '.$h[0].' '.$h[1]."\n";
estado('fin 44');
