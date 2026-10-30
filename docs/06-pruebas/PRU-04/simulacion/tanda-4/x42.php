<?php
// Día 42: COB-013 con Liam (el que viene y no paga).
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
$r0 = U('Rocío Paz'); $c = app(CajaWebController::class); $li = A('Pereyra, Liam');
$b = ['tipo_caja_id'=>M('Efectivo'),'fecha_pago'=>'2026-11-20','confirmar_pago_adelantado'=>'1'];
paso('42.0a','diciembre de Liam debiendo noviembre', enviar($r0,'POST',"/caja/cobrar/{$li->id}",$b+['periodos'=>['2026-12'],'montos_cuota'=>['2026-12'=>'35000']],fn($r)=>$c->pagar($r,$li->id),true),422);
paso('42.0b','un mes a más de doce meses', enviar($r0,'POST',"/caja/cobrar/{$li->id}",$b+['periodos'=>['2027-12'],'montos_cuota'=>['2027-12'=>'35000']],fn($r)=>$c->pagar($r,$li->id),true),422);
echo '   cuotas futuras de Liam: '.App\Models\DeudaCuota::where('alumno_id',$li->id)->where('periodo','>','2026-11')->count()."\n";
