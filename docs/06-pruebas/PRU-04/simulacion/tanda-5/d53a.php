<?php
// Día 53, martes 1/12, 08:30: lo que dejó la generación de las 06:00. El dueño valida la caja del 30/11 (REP-013) y
// cancela los feriados de diciembre. Cada persona preparada, contra lo que tiene que dar.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ClaseWebController, AlumnoWebController};
use App\Models\{Alumno, CajaOperativa, Clase, DeudaCuota};
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal'); $pa = U('Pablo Ledesma'); $lu = U('Lucía Gaitán');
$g = DB::table('generaciones_cuotas')->where('periodo', '2026-12')->first();
echo 'constancia: creadas '.$g->creadas.' · a revisión '.$g->a_revision.' · ya existían '.$g->ya_existian.' · sin plan '.$g->sin_plan.' · activos '.Alumno::where('activo', 1)->count()."\n";
$esta = function (Alumno $a) {
    if (DB::table('alumnos_revision_cobranza')->where('alumno_id', $a->id)->where('periodo_objetivo', '2026-12')->exists()) return 'REVISION';
    $d = DeudaCuota::where('alumno_id', $a->id)->where('periodo', '2026-12')->first();
    return $d ? "CUOTA {$d->estado} ".plata($d->monto_original).' pagado '.plata($d->monto_pagado) : 'SIN CUOTA';
};
foreach (DB::table('alumnos_revision_cobranza')->where('periodo_objetivo', '2026-12')->get() as $r) { $a = Alumno::find($r->alumno_id); echo "   Revisión: {$a->apellido}, {$a->nombre} (grupo {$a->grupo_id}) · {$r->estado_revision}\n"; }
foreach ([315, 357, 319] as $id) { $a = Alumno::find($id); echo "   {$a->apellido}, {$a->nombre} #{$id} activo ".(int) $a->activo.': '.$esta($a)."\n"; }
foreach (['Iglesias, Clara', 'Godoy, Valentino', 'Cabrera, Bruno', 'Acuña, Renzo', 'Rios, Facundo', 'Pereyra, Liam', 'Quiroga, Lara', 'Castro, Mateo', 'Paz, Ciro', 'Arias, Nina', 'Vera, Tobías'] as $n) { echo "   $n: ".$esta(A($n))."\n"; }
$c = app(App\Services\CobranzaEstadoService::class);
$res = $c->resumenDashboard(); echo '   Cobranza: '.plata($res['total_adeudado']).' '.json_encode($res['por_estado'])."\n";
foreach ($c->listadoCobranza('DEUDOR') as $f) echo "   Deudor: {$f->apellido}, {$f->nombre} ".plata($f->deuda_total)."\n";
echo '   Revisión pendientes: '.DB::table('alumnos_revision_cobranza')->where('estado_revision', 'PENDIENTE')->count()."\n";
foreach (['dueño' => [$ad, '/admin/dashboard'], 'Sandra' => [$sa, '/operativo']] as $q => [$u, $url]) { [$st, $t, $h] = http($u, $url); echo "   Inicio de $q ($st $t): ".textoDe($h, 'revisi[oó]n', 80)."\n"; }
[$st, $t] = http($lu, '/revision-cobranza'); echo "   Lucía escribe Revisión: $st\n";
// 53.1 REP-013: la caja del 30/11 se valida hoy
$nov0 = app(App\Services\ReporteMensualService::class)->obtener('2026-11'); $dic0 = app(App\Services\ReporteMensualService::class)->obtener('2026-12');
$k = CajaOperativa::where('estado', 'CERRADA')->where('usuario_operativo_id', $sa->id)->orderByDesc('id')->firstOrFail(); $cc = app(CajaWebController::class);
foreach ($k->movimientos()->get() as $m) enviar($ad, 'POST', "/cajas/{$k->id}/movimientos/{$m->id}/verificar", ['verificado' => true, 'huella' => $m->huellaVerificacion()], fn ($r) => $cc->verificarMovimiento($r, $k->id, $m->id));
paso('53.1', "valida la caja #{$k->id} del 30/11", enviar($ad, 'POST', "/cajas/{$k->id}/validar", [], fn ($r) => $cc->validar($k->id)), 200);
$nov1 = app(App\Services\ReporteMensualService::class)->obtener('2026-11'); $dic1 = app(App\Services\ReporteMensualService::class)->obtener('2026-12');
echo '   noviembre: ingresos '.plata($nov0['ingresos'] / 100).' → '.plata($nov1['ingresos'] / 100).' · egresos '.plata($nov0['egresos'] / 100).' → '.plata($nov1['egresos'] / 100).' · diciembre: ingresos '.plata($dic0['ingresos'] / 100).' → '.plata($dic1['ingresos'] / 100)."\n";
// 53.2 feriados
$cl = app(ClaseWebController::class);
foreach ([['2026-12-08', 'Feriado: Inmaculada Concepción'], ['2026-12-25', 'Feriado: Navidad']] as [$f, $m]) {
    $n = 0; foreach (Clase::whereDate('fecha', $f)->where('cancelada', false)->get() as $x) { [$st] = enviar($ad, 'PATCH', "/clases/{$x->id}/cancelar", ['motivo_cancelacion' => $m], fn ($r) => $cl->toggleCancelada($r, $x->id)); $n += $st < 400; }
    echo "   $f: $n clases canceladas\n";
}
// 53.3 ALU-018: reactivar a Lautaro
$la = A('Medina, Lautaro');
paso('53.3', 'reactiva a Lautaro', enviar($ad, 'PATCH', "/alumnos/{$la->id}/toggle-activo", [], fn ($r) => app(AlumnoWebController::class)->toggleActivo($r, $la->id), true), 200);
echo '   Lautaro: activo '.(int) $la->fresh()->activo.' · '.$esta($la->fresh()).' · '.json_encode($c->listadoCobranza('TODOS')->firstWhere('id', $la->id)?->estado_cobranza).' '.plata($c->listadoCobranza('TODOS')->firstWhere('id', $la->id)?->deuda_total)."\n";
estado('fin de la mañana del 53');
