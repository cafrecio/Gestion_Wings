<?php
// Día 43, sábado 21/11, 13:30 — Lucía ve la clase entre las suyas; Pablo mira la caja de Rocío (consultar sí, cerrar
// no); los menús de cada rol; Pablo cierra sin haber tenido ningún movimiento.
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
use App\Models\{Clase, CajaOperativa, MovimientoOperativo};
$admin = U('Admin Prueba'); $pablo = U('Pablo Ledesma'); $lucia = U('Lucía Gaitán'); $caja = app(CajaWebController::class);
estado('inicio 43b');
$fed = Clase::whereDate('fecha', '2026-11-21')->whereTime('hora_inicio', '10:00:00')->firstOrFail();
echo '   Federadas: '.DB::table('asistencias')->where('clase_id', $fed->id)->where('presente', 1)->count().' presentes de '.DB::table('asistencias')->where('clase_id', $fed->id)->count()."\n";
[$st, $t, $h] = http($lucia, '/clases?fecha_desde=2026-11-21&fecha_hasta=2026-11-21');
echo '   Clases de Lucía el 21/11: '.$st.' · aparece Federadas: '.(str_contains($h, 'Federadas') ? 'sí' : 'no').' · aparece Fútbol: '.(str_contains($h, 'Fútbol') ? 'sí' : 'no')."\n";
[$st, $t, $h] = http($U = U('Mariela Ocampo'), '/clases?fecha_desde=2026-11-21&fecha_hasta=2026-11-21');
echo '   Clases de Mariela el 21/11: '.$st.' · aparece Federadas: '.(str_contains($h, 'Federadas') ? 'sí' : 'no')."\n";
$k74 = CajaOperativa::where('usuario_operativo_id', U('Rocío Paz')->id)->orderByDesc('id')->firstOrFail();
foreach (["/caja/{$k74->id}/detalle" => 200, "/caja/{$k74->id}/resumen" => 403, "/caja/{$k74->id}/cerrar" => 403] as $u => $esp) {
    $h = http($pablo, $u); paso('43.5', "Pablo abre $u", [$h[0], $h[1]], $esp);
}
$m74 = MovimientoOperativo::where('caja_operativa_id', $k74->id)->whereNull('pago_id')->firstOrFail();
$h = http($pablo, "/caja/{$k74->id}/movimientos/{$m74->id}/editar"); paso('43.5', 'Pablo escribe la edición de un movimiento de Rocío', [$h[0], $h[1]], 403);
foreach ([[$lucia, '/clases'], [$pablo, '/caja'], [$admin, '/caja']] as [$u, $uri]) {
    [$st, $t, $h] = http($u, $uri);
    echo "   menú de {$u->name} ($uri → $st): ".menu($h)."\n";
}
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $pablo->id)->firstOrFail();
$esp = app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
echo '   movimientos de Pablo hoy: '.MovimientoOperativo::where('caja_operativa_id', $k->id)->count().' · esperado '.plata($esp)."\n";
paso('43.7', 'Pablo cierra: contó $15.000 y los deja de cambio', enviar($pablo, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => '15000', 'cambio_retenido' => '15000'], fn ($r) => $caja->cerrar($r, $k->id)), 200);
$k->refresh();
echo '   caja #'.$k->id.': '.$k->estado.' diferencia '.plata($k->diferencia_efectivo).' entrega '.plata($k->efectivo_retirado)."\n";
estado('fin 43');
