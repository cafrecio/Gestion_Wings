<?php
// Día 47, miércoles 25/11, 11:00 — por pantalla: Emma Villalba morosa con su recargo; su mamá pregunta cuánto debe;
// el dueño arma la serie de Fútbol Infantiles (primero con Hernán, que choca); Rocío abre diez veces el mismo recibo;
// el dueño crea el usuario de Gonzalo con datos mal cargados antes de cargarlos bien.
require __DIR__.'/lib.php';
use App\Http\Controllers\{ClaseWebController, ReciboController, UsuarioWebController};
use App\Models\{Clase, Grupo, Pago, Profesor, User};
$admin = U('Admin Prueba'); $rocio = U('Rocío Paz');
estado('inicio 47 (después de la rutina)');
echo '   clases de diciembre: '.Clase::whereBetween('fecha', ['2026-12-01', '2026-12-31'])->count().' · el 1/12: '.Clase::whereDate('fecha', '2026-12-01')->with('grupo.nivel', 'grupo.deporte')->get()->map(fn ($c) => $c->grupo->deporte->nombre.' '.$c->grupo->nivel->nombre.' '.substr($c->hora_inicio, 0, 5))->implode(', ')
    .' · el 8/12 (feriado): '.Clase::whereDate('fecha', '2026-12-08')->count()." · el 25/12 (feriado): ".Clase::whereDate('fecha', '2026-12-25')->count()."\n";
// 47.1 y 47.2 Emma Villalba
$em = A('Villalba, Emma');
$fila = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id', $em->id);
echo '   Emma: '.$fila?->estado_cobranza.' '.plata($fila?->deuda_total)."\n";
[$st, $t, $h] = http($rocio, "/alumnos/{$em->id}");
if (preg_match('/Total adeudado.{0,200}/s', strip_tags($h), $m)) echo '   ficha de Emma para Rocío: '.trim(preg_replace('/\s+/', ' ', $m[0]))."\n";
$morosos = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('MOROSO');
echo '   Morosos: '.$morosos->map(fn ($f) => "{$f->apellido}, {$f->nombre} ".plata($f->deuda_total))->implode(' · ')."\n";
// 47.3 serie de Fútbol Infantiles, sábados de diciembre
$gi = Grupo::whereHas('nivel', fn ($n) => $n->where('nombre', 'Infantiles'))->firstOrFail();
$gon = Profesor::where('apellido', 'Ibarra')->firstOrFail(); $her = Profesor::where('apellido', 'Quintana')->firstOrFail();
$serie = fn (array $profes, string $ini, string $fin) => enviar($admin, 'POST', '/clases', ['grupo_id' => $gi->id, 'profesores' => $profes, 'tipo_creacion' => 'recurrente',
    'fecha_desde' => '2026-12-05', 'fecha_hasta' => '2026-12-26', 'dias_semana' => [6], 'horarios' => [6 => ['hora_inicio' => $ini, 'hora_fin' => $fin]]], fn ($r) => app(ClaseWebController::class)->store($r));
paso('47.3a', 'Fútbol Infantiles sábados 11:00 con Gonzalo y Hernán', $serie([$gon->id, $her->id], '11:00', '12:00'), 422);
echo '   clases de Fútbol Infantiles: '.Clase::where('grupo_id', $gi->id)->count()."\n";
paso('47.3b', 'sábados 10:00 solo con Gonzalo', $serie([$gon->id], '10:00', '11:00'), 200);
echo '   clases de Fútbol Infantiles: '.Clase::where('grupo_id', $gi->id)->orderBy('fecha')->get()->map(fn ($c) => $c->fecha->format('d/m'))->implode(', ')."\n";
// 47.3c Patín Adultos de diciembre: el aviso de cancha y la confirmación
$ga = Grupo::whereHas('nivel', fn ($n) => $n->where('nombre', 'Adultos'))->firstOrFail(); $mar = Profesor::where('apellido', 'Ocampo')->firstOrFail();
$da = ['grupo_id' => $ga->id, 'profesores' => [$mar->id], 'tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-12-01', 'fecha_hasta' => '2026-12-31', 'dias_semana' => [2, 4],
    'horarios' => [2 => ['hora_inicio' => '20:30', 'hora_fin' => '21:30'], 4 => ['hora_inicio' => '20:30', 'hora_fin' => '21:30']]];
$r1 = enviar($admin, 'POST', '/clases', $da, fn ($r) => app(ClaseWebController::class)->store($r));
global $app; $aviso = $app['session']->driver()->get('aviso_cancha');
paso('47.3c', 'Patín Adultos de diciembre: aviso de cancha', [$aviso ? 409 : $r1[0], $aviso ? implode(' | ', array_column($aviso['horarios'], 'detalle')) : $r1[1]], 409);
paso('47.3d', 'confirma', enviar($admin, 'POST', '/clases', $da + ['confirmar_cancha' => $aviso['firma'] ?? ''], fn ($r) => app(ClaseWebController::class)->store($r)), 200);
echo '   Adultos en diciembre: '.Clase::where('grupo_id', $ga->id)->whereBetween('fecha', ['2026-12-01', '2026-12-31'])->count()."\n";
// 47.4 el mismo recibo diez veces
$va = A('Godoy, Valentino'); $pv = Pago::where('alumno_id', $va->id)->latest('id')->firstOrFail();
$antes = [Pago::count(), DB::table('movimientos_operativos')->count()]; $codigos = [];
for ($i = 0; $i < 10; $i++) { [$st] = http($rocio, "/recibos/cuota/{$pv->id}"); $codigos[] = $st; }
echo '   recibo #'.$pv->id.' diez veces: '.implode(',', array_unique($codigos)).' · pagos y movimientos '.json_encode($antes).' → '.json_encode([Pago::count(), DB::table('movimientos_operativos')->count()])."\n";
// 47.5 usuario de Gonzalo
$u = fn (array $d) => enviar($admin, 'POST', '/usuarios', $d, fn ($r) => app(UsuarioWebController::class)->store($r));
$base = ['name' => 'Gonzalo Ibarra', 'email' => 'gonzalo.ibarra@example.com', 'password' => 'Infantiles-2026!', 'password_confirmation' => 'Infantiles-2026!', 'rol' => 'PROFESOR', 'profesor_id' => $gon->id];
paso('47.5a', 'mail de Hernán', $u(array_replace($base, ['email' => 'hernan.quintana@wings.test'])), 422);
paso('47.5b', 'mail sin arroba', $u(array_replace($base, ['email' => 'gonzalo.ibarra.example.com'])), 422);
paso('47.5c', 'clave de 8', $u(array_replace($base, ['password' => 'Futbol26', 'password_confirmation' => 'Futbol26'])), 422);
paso('47.5d', 'claves distintas', $u(array_replace($base, ['password_confirmation' => 'Infantiles-2027!'])), 422);
paso('47.5e', 'Profesor sin elegir profesor', $u(array_replace($base, ['profesor_id' => ''])), 422);
paso('47.5f', 'sin nombre', $u(array_replace($base, ['name' => ''])), 422);
echo '   usuarios: '.User::count().' · subrubros de sueldo de Gonzalo: '.DB::table('subrubros')->where('nombre', 'like', '%Ibarra%')->count()."\n";
paso('47.5g', 'bien', $u($base), 200);
echo '   usuario de Gonzalo: '.json_encode(User::where('email', 'gonzalo.ibarra@example.com')->first(['name', 'rol', 'profesor_id', 'activo']))."\n";
estado('fin 47 (antes de la noche)');
