<?php
// Día 54, miércoles 2/12, 11:00, por pantalla: la Revisión del mes, los dos pagos que la cierran solos, los cambios de
// plan al cobrar y los pagos de las liquidaciones de noviembre.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, LiquidacionWebController, RevisionCobranzaWebController};
use App\Models\{Alumno, AlumnoRevisionCobranza, CajaOperativa, CashflowMovimiento, DeudaCuota, GrupoPlan, Liquidacion, Pago, Profesor, User};
$ad = U('Admin Prueba'); $ro = U('Rocío Paz'); $caja = app(CajaWebController::class); $rc = app(RevisionCobranzaWebController::class); $lc = app(LiquidacionWebController::class); $cob = app(App\Services\CobranzaEstadoService::class);
estado('inicio 54 (después de la rutina)');
$rev = fn (Alumno $a) => AlumnoRevisionCobranza::where('alumno_id', $a->id)->where('periodo_objetivo', '2026-12')->firstOrFail();
$res = fn (User $q, Alumno $a, string $r, string $n) => enviar($q, 'POST', '/revision-cobranza/'.$rev($a)->id.'/resolver', ['resolucion' => $r, 'nota_resolucion' => $n], fn ($x) => $rc->resolver($x, $rev($a)->id));
$pend = fn () => AlumnoRevisionCobranza::where('estado_revision', 'PENDIENTE')->count();
$dic = function (Alumno $a) { $d = DeudaCuota::where('alumno_id', $a->id)->where('periodo', '2026-12')->first(); return $d ? "{$d->estado} ".plata($d->monto_original) : 'sin cuota'; };
$deuda = fn () => plata($cob->resumenDashboard()['total_adeudado']);
echo '   Revisión pendientes: '.$pend().' · deuda '.$deuda()."\n";
// 54.1 CBZ-014
$pi = A('Ramos, Pilar'); $va = A('Suarez, Valentina');
paso('54.1a', 'Pilar sin nota', $res($ro, $pi, 'CONTINUA', ''), 422);
paso('54.1b', 'con tres letras', $res($ro, $pi, 'CONTINUA', 'Sí.'), 422);
paso('54.1c', 'Pilar continúa', $res($ro, $pi, 'CONTINUA', 'La mamá confirma que vuelve en diciembre'), 200);
echo '   Pilar: diciembre '.$dic($pi).' · '.$cob->listadoCobranza('TODOS')->firstWhere('id', $pi->id)?->estado_cobranza.' '.plata($cob->listadoCobranza('TODOS')->firstWhere('id', $pi->id)?->deuda_total).' · pendientes '.$pend().' · deuda '.$deuda()."\n";
paso('54.1d', 'Valentina, inactivo', $res($ad, $va, 'INACTIVO', 'Se mudó: no vuelve'), 200);
$va->refresh(); echo '   Valentina: activo '.(int) $va->activo.' · diciembre '.$dic($va).' · en Cobranza '.($cob->listadoCobranza('TODOS')->firstWhere('id', $va->id) ? 'SÍ' : 'no').' · deuda '.$deuda().' · pendientes '.$pend()."\n";
echo '   nota de Pilar: '.json_encode($rev($pi)->only(['estado_revision', 'resolucion', 'nota_resolucion']), JSON_UNESCAPED_UNICODE).' por '.User::find($rev($pi)->usuario_resolucion_id)?->name."\n";
// 54.2 CBZ-015
$jo = A('Luna, Joaquina');
paso('54.2a', 'Joaquina continúa', $res($ad, $jo, 'CONTINUA', 'Vuelve después del viaje'), 200);
paso('54.2b', 'Rocío, con la pantalla vieja, inactivo', $res($ro, $jo, 'INACTIVO', 'Avisó que deja'), 400);
echo '   Joaquina: activo '.(int) $jo->fresh()->activo.' · diciembre '.$dic($jo).' · cuotas de diciembre '.DeudaCuota::where('alumno_id', $jo->id)->where('periodo', '2026-12')->count()."\n";
// 54.3 CBZ-016 b: Emma paga y sale sola
$em = A('Villalba, Emma'); $fe = $cob->listadoCobranza('TODOS')->firstWhere('id', $em->id); echo '   Emma debe '.plata($fe?->deuda_total)."\n";
paso('54.3', 'Emma paga noviembre, inscripción y recargo en efectivo', enviar($ro, 'POST', "/caja/cobrar/{$em->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-11'], 'montos_cuota' => ['2026-11' => '23100'], 'fecha_pago' => '2026-12-02', 'observaciones' => 'Paga lo de noviembre'], fn ($r) => $caja->pagar($r, $em->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $em->id)->latest('id')->value('monto_final')).' · revisión '.json_encode($rev($em)->only(['estado_revision', 'nota_resolucion']), JSON_UNESCAPED_UNICODE).' · pendientes '.$pend().' · diciembre '.$dic($em)."\n";
paso('54.3b', 'Emma paga diciembre, $33.000', enviar($ro, 'POST', "/caja/cobrar/{$em->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'periodos_manuales' => ['2026-12'], 'montos_cuota' => ['2026-12' => '33000'], 'fecha_pago' => '2026-12-02'], fn ($r) => $caja->pagar($r, $em->id), true), 200);
echo '   revisión '.json_encode($rev($em)->only(['estado_revision', 'resolucion', 'nota_resolucion']), JSON_UNESCAPED_UNICODE).' · pendientes '.$pend().' · diciembre '.$dic($em)."\n";
// 54.4 lo que queda de Revisión
$th = Alumno::where('apellido', 'Ruiz')->where('nombre', 'Thiago')->where('grupo_id', 1)->firstOrFail(); $al = A('Navarro, Alma');
paso('54.4a', 'la ficha de Patín de Thiago, inactivo', $res($ro, $th, 'INACTIVO', 'Sigue solo en Fútbol'), 200);
echo '   Thiago Patín: activo '.(int) $th->fresh()->activo.' · Fútbol sigue activo '.(int) Alumno::find(315)->activo.'; '.$cob->listadoCobranza('TODOS')->firstWhere('id', 315)?->estado_cobranza.' '.plata($cob->listadoCobranza('TODOS')->firstWhere('id', 315)?->deuda_total).' · deuda '.$deuda()."\n";
paso('54.4b', 'Alma continúa', $res($ro, $al, 'CONTINUA', 'Se cortó la luz en su única clase: sigue'), 200);
$to = A('Vera, Tobías'); echo '   Tobías, que tiene un presente del 30/11: revisión '.$rev($to)->estado_revision."\n";
paso('54.4c', 'Tobías continúa', $res($ro, $to, 'CONTINUA', 'Vino a probar el 30/11: sigue'), 200);
paso('54.4d', 'Ciro continúa', $res($ro, A('Paz, Ciro'), 'CONTINUA', 'Pagó y empieza en diciembre'), 200);
foreach (AlumnoRevisionCobranza::where('estado_revision', 'PENDIENTE')->get() as $r) { $a = Alumno::find($r->alumno_id); echo "   queda pendiente: {$a->apellido}, {$a->nombre}\n"; }
// 54.5 CBZ-021
foreach (['/revision-cobranza', '/revision-cobranza?estado=RESUELTO', '/revision-cobranza?estado=&periodo=2026-12'] as $u) { [$st,, $h] = http($ad, $u); echo "   $u: $st · ".substr_count($h, 'Reactivado automáticamente').' reactivados automáticamente'."\n"; }
// 54.6 COB-024
$sf = A('Flores, Santino'); $p2 = GrupoPlan::where('grupo_id', $sf->grupo_id)->where('clases_por_semana', 2)->firstOrFail();
paso('54.6a', 'Santino Flores, sin clases este mes, pasa a 2 por semana y paga', enviar($ro, 'POST', "/caja/cobrar/{$sf->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'nuevo_plan_id' => $p2->id, 'fecha_pago' => '2026-12-02'], fn ($r) => $caja->pagar($r, $sf->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $sf->id)->latest('id')->value('monto_final')).' · diciembre '.$dic($sf)."\n";
$zo = A('Pereyra, Zoe'); $z1 = GrupoPlan::where('grupo_id', $zo->grupo_id)->where('clases_por_semana', 1)->firstOrFail();
paso('54.6b', 'Zoe Pereyra, que vino el 1/12, pasa a 1 por semana y paga', enviar($ro, 'POST', "/caja/cobrar/{$zo->id}", ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => ['2026-12'], 'nuevo_plan_id' => $z1->id, 'fecha_pago' => '2026-12-02'], fn ($r) => $caja->pagar($r, $zo->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $zo->id)->latest('id')->value('monto_final')).' · diciembre '.$dic($zo).' · planes '.DB::table('alumno_planes')->where('alumno_id', $zo->id)->get(['plan_id', 'fecha_desde', 'fecha_hasta', 'activo'])->toJson()."\n";
// 54.6c CBZ-004: Liam paga el mes nuevo y sigue debiendo el viejo
$li = A('Pereyra, Liam'); $fli = fn () => $cob->listadoCobranza('TODOS')->firstWhere('id', $li->id);
echo '   Liam antes: '.$fli()?->estado_cobranza.' '.plata($fli()?->deuda_total)."\n";
paso('54.6c', 'Liam paga diciembre debiendo noviembre, sin motivo', enviar($ro, 'POST', "/caja/cobrar/{$li->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => '35000'], 'fecha_pago' => '2026-12-02'], fn ($r) => $caja->pagar($r, $li->id), true), 409);
paso('54.6d', 'con motivo', enviar($ro, 'POST', "/caja/cobrar/{$li->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => '35000'], 'motivo' => 'Paga diciembre: noviembre lo trae la semana que viene', 'fecha_pago' => '2026-12-02'], fn ($r) => $caja->pagar($r, $li->id), true), 200);
echo '   Liam después: '.$fli()?->estado_cobranza.' '.plata($fli()?->deuda_total).' · deuda '.$deuda()."\n";
// 54.7 pagos de las liquidaciones
$liq = fn (string $ap) => Liquidacion::where('profesor_id', Profesor::where('apellido', $ap)->value('id'))->where('mes', 11)->where('anio', 2026)->where('estado', 'CERRADA')->orderByDesc('id')->firstOrFail();
$pagar = fn (Liquidacion $l, string $medio) => enviar($ad, 'POST', "/liquidaciones/{$l->id}/pagar", ['fecha_pago' => '2026-12-02', 'tipo_caja_id' => M($medio), 'monto_esperado' => (string) $l->monto_a_pagar], fn ($r) => $lc->pagar($r, $l->id));
foreach ([['Gaitán', 'Banco Galicia'], ['Ocampo', 'Banco Galicia'], ['Quintana', 'Mercado Pago'], ['Acosta', 'Mercado Pago']] as [$ap, $me]) { $l = $liq($ap); paso('54.7', "paga a $ap ".plata($l->monto_a_pagar)." por $me", $pagar($l, $me), 200); }
$lu = $liq('Gaitán');
paso('54.7b', 'pagar otra vez', $pagar($lu, 'Banco Galicia'));
paso('54.7c', 'LIQ-013: forzar eliminar la pagada', enviar($ad, 'DELETE', "/liquidaciones/{$lu->id}", [], fn ($r) => $lc->eliminar($lu->id)), 400);
paso('54.7d', 'LIQ-013: forzar cancelar la pagada', enviar($ad, 'POST', "/liquidaciones/{$lu->id}/cancelar", ['motivo' => 'Forzando'], fn ($r) => $lc->cancelar($r, $lu->id)), 400);
estado('fin 54 (antes de la noche)');
echo '   Sueldos de noviembre en Cashflow: '.CashflowMovimiento::whereDate('fecha', '2026-12-02')->where('referencia_tipo', 'LIQUIDACION')->get()->map(fn ($m) => plata($m->monto))->implode(' ')."\n";
