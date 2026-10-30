<?php
// Día 44: el dueño cambia la gracia y vuelve (CFG-001); gasto con fecha mal y gasto de octubre (CSH-005, REP-005); la operativa y la profesora forzando (REP-008, CSH-012, CAT-010).
require __DIR__.'/lib.php';
use App\Http\Controllers\{ConfiguracionWebController, CashflowWebController};
$adm = U('Admin Prueba'); $cf = app(App\Services\CobranzaEstadoService::class); $cfg = app(ConfiguracionWebController::class);
$res = fn () => json_encode($cf->resumenDashboard()['por_estado']).' deuda '.plata($cf->resumenDashboard()['total_adeudado']);
echo '   antes: '.$res()."\n";
paso('44.5a','gracia a 25', enviar($adm,'PATCH','/configuraciones/dias_gracia_cobranza',['valor'=>'25'],fn($r)=>$cfg->update($r,'dias_gracia_cobranza'),true),200);
echo '   con 25: '.$res()."\n";
paso('44.5b','gracia a 40', enviar($adm,'PATCH','/configuraciones/dias_gracia_cobranza',['valor'=>'40'],fn($r)=>$cfg->update($r,'dias_gracia_cobranza'),true));
paso('44.5c','gracia a 10', enviar($adm,'PATCH','/configuraciones/dias_gracia_cobranza',['valor'=>'10'],fn($r)=>$cfg->update($r,'dias_gracia_cobranza'),true),200);
echo '   con 10: '.$res()."\n";
$rm = app(App\Services\ReporteMensualService::class); $oct = $rm->obtener('2026-10'); echo '   octubre antes: egresos '.plata($oct['egresos']/100)."\n";
$d = ['subrubro_id'=>S('Luz'),'tipo_caja_id'=>M('Banco Galicia'),'monto'=>'9400','observaciones'=>'Edesur, octubre: llegó tarde'];
$st = fn($x)=>enviar($adm,'POST','/cashflow/movimiento',$x,fn($r)=>app(CashflowWebController::class)->store($r));
paso('44.6a','fecha de mañana', $st($d+['fecha'=>'2026-11-23']),422);
paso('44.6b','fecha del 28/10 sin confirmar', $st($d+['fecha'=>'2026-10-28']));
echo '   movimientos: '.App\Models\CashflowMovimiento::where('observaciones',$d['observaciones'])->count()."\n";
paso('44.6c','confirma', $st($d+['fecha'=>'2026-10-28','confirmar_fecha_vieja'=>'1']),200);
echo '   octubre después: egresos '.plata($rm->obtener('2026-10')['egresos']/100).' · movimientos '.App\Models\CashflowMovimiento::where('observaciones',$d['observaciones'])->count()."\n";
foreach ([U('Sandra Vidal'),U('Lucía Gaitán')] as $u) foreach (['/cashflow','/reportes','/reportes/alumnos','/deportes','/niveles','/rubros','/tipos-caja','/configuraciones','/usuarios','/dashboard/admin','/grupos'] as $uri) { $h=http($u,$uri); echo "   {$u->name} $uri → {$h[0]} {$h[1]}\n"; }
