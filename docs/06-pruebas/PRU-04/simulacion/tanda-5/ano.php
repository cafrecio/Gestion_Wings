<?php
// Paso de año, en la copia: lo que hace Wings el 1 y el 2 de enero. Solo para responderle a Carlos; no es parte del guion.
require __DIR__.'/lib.php';
use App\Http\Controllers\LiquidacionWebController;
use App\Models\{Liquidacion, Profesor, Alumno, DeudaCuota};
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal'); $lc = app(LiquidacionWebController::class);
echo 'hoy '.now()->toDateString()."\n";
$g = DB::table('generaciones_cuotas')->where('periodo', '2027-01')->first();
echo $g ? "enero: creadas {$g->creadas} · revisión {$g->a_revision} · ya existían {$g->ya_existian}\n" : "sin constancia de enero\n";
echo 'enero de Valentina (adelantada): '.DeudaCuota::where('alumno_id', A('Lopez, Valentina')->id)->where('periodo', '2027-01')->count().' cuota(s) · '.DeudaCuota::where('alumno_id', A('Lopez, Valentina')->id)->where('periodo', '2027-01')->value('monto_original')."\n";
$c = app(App\Services\CobranzaEstadoService::class)->resumenDashboard(); echo 'Cobranza: '.plata($c['total_adeudado']).' '.json_encode($c['por_estado'])."\n";
foreach (['/liquidaciones/crear', '/liquidaciones', '/reportes', '/reportes?mes=2026-12', '/reportes?mes=2027-01', '/reportes/alumnos', '/reportes/sueldos?mes=2026-12', '/cashflow', '/cashflow?anio=2026&mes=12', '/caja/historial', '/cobranza', '/revision-cobranza', '/admin/dashboard', '/clases'] as $u) { [$st, $t, $h] = http($ad, $u); echo "   $u: $st $t\n"; }
[$st,, $h] = http($ad, '/liquidaciones/crear');
preg_match('/<select id="mes".*?<\/select>/s', $h, $m); preg_match('/<option value="(\d+)"[^>]*selected/s', $m[0] ?? '', $sel); preg_match('/id="anio"[^>]*value="(\d+)"/s', $h, $an);
echo '   Nueva liquidación propone: mes '.($sel[1] ?? '?').' · año '.($an[1] ?? (preg_match('/value="(\d{4})"/', $h, $x) ? $x[1] : '?'))."\n";
$hp = Profesor::where('apellido', 'Ocampo')->firstOrFail();
paso('año-a', 'dueño cambia solo el mes: diciembre, con el año propuesto (2027)', enviar($ad, 'POST', '/liquidaciones', ['profesor_id' => $hp->id, 'mes' => 12, 'anio' => 2027], fn ($r) => $lc->store($r)));
$l = Liquidacion::where('profesor_id', $hp->id)->where('mes', 12)->where('anio', 2027)->first(); echo '   liquidación de diciembre de 2027: '.($l ? "#{$l->id} total ".plata($l->total_calculado).' detalles '.$l->detalles()->count() : 'no se creó')."\n";
if ($l) enviar($ad, 'DELETE', "/liquidaciones/{$l->id}", [], fn ($r) => $lc->eliminar($l->id));
paso('año-b', 'diciembre de 2026, bien', enviar($ad, 'POST', '/liquidaciones', ['profesor_id' => $hp->id, 'mes' => 12, 'anio' => 2026], fn ($r) => $lc->store($r)));
$l2 = Liquidacion::where('profesor_id', $hp->id)->where('mes', 12)->where('anio', 2026)->first(); echo '   diciembre de 2026: '.($l2 ? "total ".plata($l2->total_calculado).' detalles '.$l2->detalles()->count() : 'no se creó')."\n";
$li = A('Pereyra, Liam'); [$st,, $h] = http($ad, "/alumnos/{$li->id}"); preg_match_all('/(Ene|Dic|Nov)[a-z]* (20\d\d)/', $h, $m2); echo "   ficha de Liam ($st): ".implode(' · ', array_slice(array_unique($m2[0]), 0, 8))."\n";
[$st,, $h] = http($sa, "/caja/cobrar/{$li->id}"); preg_match_all('/(Enero|Diciembre|Noviembre|enero|diciembre|noviembre)( de)? 20\d\d/', $h, $m3); echo "   Cobrar de Liam ($st): ".implode(' · ', array_slice(array_unique($m3[0]), 0, 8))."\n";
