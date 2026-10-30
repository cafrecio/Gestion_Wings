<?php
// Día 43, sábado 21/11, 09:30 — por pantalla, antes de la rutina: Pablo abre y ve la propuesta de $15.000 que dejó
// Rocío; el dueño suma a Lucía a la clase de Federadas (clínica antes del torneo); Pablo cancela Fútbol Avanzadas por lluvia.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ClaseWebController};
use App\Models\{Clase, Profesor};
$admin = U('Admin Prueba'); $pablo = U('Pablo Ledesma'); $caja = app(CajaWebController::class);
estado('inicio 43');
Illuminate\Support\Facades\Auth::guard('web')->setUser($pablo);
$p = app(App\Services\CajaService::class)->propuestaApertura();
echo '   propuesta de apertura para Pablo: '.json_encode($p, JSON_UNESCAPED_UNICODE)."\n";
$ap = ['efectivo_inicial' => (string) $p['efectivo_heredado'], 'confirmacion' => '1', 'caja_origen_id' => $p['caja_origen_id']];
paso('43.1a', 'dueño: a su nombre, sin elegir operativa', enviar($admin, 'POST', '/caja/apertura', $ap, fn ($r) => $caja->abrir($r)), 422);
paso('43.1b', 'dueño: a nombre de Pablo', enviar($admin, 'POST', '/caja/apertura', $ap + ['operativo_id' => $pablo->id], fn ($r) => $caja->abrir($r)), 200);
$kp = App\Models\CajaOperativa::where('estado', 'ABIERTA')->first(); echo '   caja #'.$kp->id.' de '.$kp->usuarioOperativo?->name."\n";
$gon = Profesor::where('apellido', 'Ibarra')->first(); $fedc = Clase::whereDate('fecha', '2026-11-21')->whereTime('hora_inicio', '10:00:00')->first();
paso('43.1c', 'dueño: Gonzalo (Fútbol) en una clase de Patín', enviar($admin, 'PATCH', "/clases/{$fedc->id}/profesores", ['profesores' => [$gon->id]], fn ($r) => app(ClaseWebController::class)->actualizarProfesores($r, $fedc->id), true), 422);
foreach ([U('Lucía Gaitán'), $pablo] as $u) { [$st,,$h] = http($u, '/clases'); echo '   '.$u->name.' clave en el menú: '.(stripos(menu($h), 'clave') !== false ? 'sí' : 'no')."\n"; }
$fed = Clase::whereDate('fecha', '2026-11-21')->whereTime('hora_inicio', '10:00:00')->firstOrFail();
$mar = Profesor::where('nombre', 'Mariela')->firstOrFail(); $luc = Profesor::where('nombre', 'Lucía')->firstOrFail();
paso('43.2', "dueño suma a Lucía a Federadas (#{$fed->id})", enviar($admin, 'PATCH', "/clases/{$fed->id}/profesores", ['profesores' => [$mar->id, $luc->id]], fn ($r) => app(ClaseWebController::class)->actualizarProfesores($r, $fed->id), true), 200);
echo '   profesoras: '.$fed->profesores()->pluck('nombre')->implode(' y ')."\n";
$fut = Clase::whereDate('fecha', '2026-11-21')->whereTime('hora_inicio', '11:00:00')->firstOrFail();
paso('43.3', "Pablo cancela Fútbol Avanzadas (#{$fut->id}) por lluvia", enviar($pablo, 'PATCH', "/clases/{$fut->id}/cancelar", ['motivo_cancelacion' => 'Lluvia: la cancha está anegada'], fn ($r) => app(ClaseWebController::class)->toggleCancelada($r, $fut->id), true), 200);
estado('fin 43a');
