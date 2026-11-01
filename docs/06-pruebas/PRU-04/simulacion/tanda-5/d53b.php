<?php
// Día 53, martes 1/12, 11:00, por pantalla: Hernán carga tarde la lista del lunes por teléfono (choque de Mora, Tobías
// sale de Revisión); las liquidaciones de noviembre con todo lo que les puede pasar.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ClaseWebController, LiquidacionWebController, ProfesorWebController};
use App\Models\{Alumno, CajaOperativa, Clase, DeudaCuota, Liquidacion, Pago, Profesor, User};
$ad = U('Admin Prueba'); $pa = U('Pablo Ledesma'); $lu = U('Lucía Gaitán'); $he = U('Hernán Quintana'); $cc = app(ClaseWebController::class); $lc = app(LiquidacionWebController::class); $caja = app(CajaWebController::class);
estado('inicio 53 (después de la rutina)');
$lista = function (User $q, Clase $c, array $faltan, array $motivos = [], ?string $correccion = null) use ($cc) {
    $items = Alumno::enGrupoElDia($c->grupo_id, $c->fecha)->where('activo', true)->get()->map(function ($a) use ($faltan, $motivos) {
        $n = "{$a->apellido}, {$a->nombre}"; return ['alumno_id' => $a->id, 'presente' => in_array($n, $faltan, true) ? 0 : 1, 'motivo_exceso' => 'EXTRA'] + (isset($motivos[$n]) ? ['motivo_excepcion' => $motivos[$n]] : []);
    })->all();
    return enviar($q, 'POST', "/clases/{$c->id}/asistencias", ['items' => $items] + ($correccion ? ['motivo' => $correccion] : []), fn ($r) => $cc->storeAsistencias($r, $c->id));
};
// 53.4 Hernán, Fútbol Principiantes del 30/11 a las 16: ASI-008, excepción y CBZ-016 a
$fp = Clase::whereDate('fecha', '2026-11-30')->whereTime('hora_inicio', '16:00:00')->where('grupo_id', 5)->firstOrFail();
$mot = ['Vera, Tobías' => 'Vino a probar: la mamá paga el miércoles', 'Pereyra, Liam' => 'El papá avisó que paga esta semana', 'Ruiz, Thiago' => 'Viene a Fútbol: arregla lo de Patín'];
paso('53.4a', 'Hernán marca todos, sin motivos', $lista($he, $fp, ['Rios, Facundo']), 422);
// ASI-008 pide rechazarlo, pero Mora tiene una ficha por deporte y el control mira la ficha: T111.
paso('53.4b', 'con los motivos; Mora presente, con su lista de Patín a la misma hora', $lista($he, $fp, ['Rios, Facundo'], $mot), 200);
echo '   marcas de la clase: '.DB::table('asistencias')->where('clase_id', $fp->id)->count().' · Mora presente el 30/11 a las 16: '.DB::table('asistencias')->join('clases', 'clases.id', '=', 'asistencias.clase_id')->whereDate('clases.fecha', '2026-11-30')->whereIn('asistencias.alumno_id', [341, 345])->where('presente', 1)->count()." fichas\n";
$to = A('Vera, Tobías');
echo '   Tobías: revisión '.(DB::table('alumnos_revision_cobranza')->where('alumno_id', $to->id)->where('periodo_objetivo', '2026-12')->value('estado_revision') ?? 'sin').' · diciembre '.(DeudaCuota::where('alumno_id', $to->id)->where('periodo', '2026-12')->first()?->estado ?? 'sin cuota').' '.plata(DeudaCuota::where('alumno_id', $to->id)->where('periodo', '2026-12')->value('monto_original')).' · pendientes '.DB::table('alumnos_revision_cobranza')->where('estado_revision', 'PENDIENTE')->count()."\n";

$pr = fn (string $ap) => Profesor::where('apellido', $ap)->firstOrFail();
$gen = fn (Profesor $p) => enviar($ad, 'POST', '/liquidaciones', ['profesor_id' => $p->id, 'mes' => 11, 'anio' => 2026], fn ($r) => $lc->store($r));
$liq = fn (Profesor $p) => Liquidacion::where('profesor_id', $p->id)->where('mes', 11)->where('anio', 2026)->orderByDesc('id')->firstOrFail();
$ver = fn (Liquidacion $l) => "#{$l->id} {$l->fresh()->estado}/{$l->fresh()->estado_pago} total ".plata($l->fresh()->total_calculado).' a pagar '.plata($l->fresh()->monto_a_pagar).' detalles '.$l->fresh()->detalles()->count();
// 53.5 LIQ-003 y LIQ-007: Lucía, con Intermedias del 30/11 sin lista
$pl = $pr('Gaitán'); $int = Clase::whereDate('fecha', '2026-11-30')->whereTime('hora_inicio', '17:00:00')->firstOrFail();
[$st,, $h] = http($ad, '/liquidaciones/crear'); echo "   pantalla de generar ($st): ".textoDe($h, 'sin asistencia', 120)."\n";
paso('53.5a', 'genera la de Lucía', $gen($pl), 200); $l = $liq($pl); echo '   '.$ver($l)."\n";
paso('53.5b', 'Lucía carga Intermedias del 30/11', $lista($lu, $int, ['Navarro, Alma', 'Villalba, Emma']), 200);
paso('53.5c', 'recalcula', enviar($ad, 'POST', "/liquidaciones/{$l->id}/recalcular", [], fn ($r) => $lc->recalcular($l->id)), 200); echo '   '.$ver($l)."\n";
paso('53.5d', 'cierra', enviar($ad, 'POST', "/liquidaciones/{$l->id}/cerrar", [], fn ($r) => $lc->cerrar($l->id)), 200);
paso('53.5e', 'LIQ-004: genera otra vez', $gen($pl), 400);
// 53.6 ASI-006 y LIQ-012
paso('53.6a', 'Lucía corrige la lista: liquidación cerrada', $lista($lu, $int, ['Navarro, Alma', 'Villalba, Emma', 'Sosa, Rocio'], [], 'Rocío Sosa no vino: la marqué mal'), 422);
$can = fn (string $m) => enviar($ad, 'POST', "/liquidaciones/{$l->id}/cancelar", ['motivo' => $m], fn ($r) => $lc->cancelar($r, $l->id));
paso('53.6b', 'cancela sin motivo', $can(''), 422); paso('53.6c', 'con tres letras', $can('Mal'), 422);
paso('53.6d', 'con motivo', $can('Asistencia mal cargada del 30/11'), 200); echo '   '.$ver($l)."\n";
paso('53.6e', 'Lucía corrige sin motivo', $lista($lu, $int, ['Navarro, Alma', 'Villalba, Emma', 'Sosa, Rocio']), 400);
paso('53.6e2', 'Lucía corrige con motivo', $lista($lu, $int, ['Navarro, Alma', 'Villalba, Emma', 'Sosa, Rocio'], [], 'Rocío Sosa no vino: la marqué mal'), 200);
paso('53.6f', 'genera de nuevo', $gen($pl), 200); $l2 = $liq($pl); echo '   '.$ver($l2).' · la cancelada la reemplaza #'.$l->fresh()->reemplazada_por_id."\n";
paso('53.6g', 'cierra', enviar($ad, 'POST', "/liquidaciones/{$l2->id}/cerrar", [], fn ($r) => $lc->cerrar($l2->id)), 200);
// 53.7 LIQ-014 y LIQ-017: Mariela
$pm = $pr('Ocampo'); $octM = Liquidacion::where('profesor_id', $pm->id)->where('mes', 10)->first();
paso('53.7a', 'genera la de Mariela por error', $gen($pm), 200); $m1 = $liq($pm); echo '   '.$ver($m1)."\n";
paso('53.7b', 'la elimina', enviar($ad, 'DELETE', "/liquidaciones/{$m1->id}", [], fn ($r) => $lc->eliminar($m1->id)), 200);
paso('53.7c', 'intenta eliminar la cerrada de Lucía', enviar($ad, 'DELETE', "/liquidaciones/{$l2->id}", [], fn ($r) => $lc->eliminar($l2->id)), 400);
$pm->valor_hora = 19000; $pm->save(); // Por pantalla: Profesores → Editar. Acá directo: el cálculo no depende del formulario.
paso('53.7d', 'con el valor hora en $19.000, genera y cierra', $gen($pm), 200); $m2 = $liq($pm); echo '   '.$ver($m2).' · octubre sigue a '.plata($octM?->valor_hora_aplicado)."\n";
enviar($ad, 'POST', "/liquidaciones/{$m2->id}/cerrar", [], fn ($r) => $lc->cerrar($m2->id));
// 53.8 LIQ-015: Hernán
$ph = $pr('Quintana'); paso('53.8a', 'genera la de Hernán', $gen($ph), 200); $h1 = $liq($ph); echo '   '.$ver($h1)."\n";
$aj = fn (array $d) => enviar($ad, 'POST', "/liquidaciones/{$h1->id}/ajustar", $d, fn ($r) => $lc->ajustar($r, $h1->id));
foreach ($h1->fresh()->detalles()->where('decision_atrasada', 'PENDIENTE')->get() as $det) {
    $a = Alumno::find($det->referencia_id); echo "   cuota atrasada: {$a?->apellido}, {$a?->nombre} ".plata($det->monto_comision ?? $det->monto ?? 0).' · '.json_encode($det->only(['id', 'decision_atrasada']), JSON_UNESCAPED_UNICODE)."\n";
    paso('53.8a2', 'el dueño la aprueba', enviar($ad, 'POST', "/liquidaciones/{$h1->id}/detalles/{$det->id}/decision", ['decision' => 'APROBADA', 'decision_esperada' => 'PENDIENTE'], fn ($r) => $lc->decidirAtrasada($r, $h1->id, $det->id)), 200);
}
echo '   '.$ver($h1)."\n";
$mf = (float) $h1->fresh()->monto_a_pagar; $redondo = (string) (floor($mf / 100) * 100 + 100);
paso('53.8b', 'ajusta sin motivo', $aj(['monto_final' => $redondo, 'monto_anterior' => (string) $mf, 'motivo' => '']), 422);
paso('53.8c', 'monto negativo', $aj(['monto_final' => '-1', 'monto_anterior' => (string) $mf, 'motivo' => 'Redondeo acordado']), 422);
paso('53.8d', 'con la pantalla vieja', $aj(['monto_final' => $redondo, 'monto_anterior' => '1', 'motivo' => 'Redondeo acordado']), 400);
paso('53.8e', "redondea a $redondo", $aj(['monto_final' => $redondo, 'monto_anterior' => (string) $mf, 'motivo' => 'Redondeo acordado con Hernán']), 200); echo '   '.$ver($h1)."\n";
paso('53.8f', 'cierra la de Hernán', enviar($ad, 'POST', "/liquidaciones/{$h1->id}/cerrar", [], fn ($r) => $lc->cerrar($h1->id)), 200);
echo '   detalle de Hernán: atrasadas excluidas '.plata($h1->fresh()->total_atrasadas_excluidas).' · pendientes de decidir '.DB::table('liquidacion_decisiones_atrasadas')->where('liquidacion_id', $h1->id)->count()."\n";
// 53.9 Martín y Gonzalo
$pt = $pr('Acosta'); paso('53.9a', 'genera y cierra la de Martín', $gen($pt), 200); $t1 = $liq($pt); echo '   '.$ver($t1)."\n"; enviar($ad, 'POST', "/liquidaciones/{$t1->id}/cerrar", [], fn ($r) => $lc->cerrar($t1->id));
paso('53.9b', 'Gonzalo, sin nada en noviembre', $gen($pr('Ibarra'))); $g1 = $liq($pr('Ibarra')); echo '   '.$ver($g1)."\n";
paso('53.9c', 'la elimina', enviar($ad, 'DELETE', "/liquidaciones/{$g1->id}", [], fn ($r) => $lc->eliminar($g1->id)), 200);
// 53.10 COB-032: un pago parcial
$ca = A('Morales, Carla'); $k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $pa->id)->firstOrFail();
paso('53.10', 'Carla Morales paga $20.000 de diciembre', enviar($pa, 'POST', "/caja/cobrar/{$ca->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => '20000'], 'motivo' => 'Paga el resto el viernes', 'fecha_pago' => '2026-12-01'], fn ($r) => $caja->pagar($r, $ca->id), true), 200);
$d = DeudaCuota::where('alumno_id', $ca->id)->where('periodo', '2026-12')->first(); echo "   Carla: {$d->estado} ".plata($d->monto_original).' pagado '.plata($d->monto_pagado)."\n";
estado('fin 53 (antes de la noche)');
