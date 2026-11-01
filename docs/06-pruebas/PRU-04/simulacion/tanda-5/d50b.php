<?php
// Día 50, sábado 28/11, 12:30, por pantalla: dos personas guardan la misma lista; Hernán carga la suya tarde; el dueño
// desactiva a Pablo con la sesión abierta y lo reactiva; adelantos; Pablo cancela una clase de la serie.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ClaseWebController, UsuarioWebController};
use App\Models\{Alumno, CajaOperativa, Clase, Grupo, MovimientoOperativo, User};
$ad = U('Admin Prueba'); $pa = U('Pablo Ledesma'); $ma = U('Mariela Ocampo'); $he = U('Hernán Quintana'); $caja = app(CajaWebController::class); $cc = app(ClaseWebController::class);
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $pa->id)->firstOrFail();
$esp = fn () => app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
estado('inicio 50 (después de la rutina)');
$sinLista = fn () => Clase::where('cancelada', false)->whereDate('fecha', '<=', today())->where('validada_para_liquidacion', false)->doesntHave('asistencias')->where(fn ($q) => $q->whereDate('fecha', '<', today())->orWhereTime('hora_fin', '<=', now()->format('H:i:s')))->count();
echo '   sin lista: '.$sinLista()."\n";
$lista = function (User $q, Clase $c, array $faltan) use ($cc) {
    $items = Alumno::enGrupoElDia($c->grupo_id, $c->fecha)->where('activo', true)->get()->map(fn ($a) => ['alumno_id' => $a->id, 'presente' => in_array("{$a->apellido}, {$a->nombre}", $faltan, true) ? 0 : 1, 'motivo_exceso' => 'EXTRA'])->all();
    return enviar($q, 'POST', "/clases/{$c->id}/asistencias", ['items' => $items], fn ($r) => $cc->storeAsistencias($r, $c->id));
};
$fed = Clase::whereDate('fecha', '2026-11-28')->whereTime('hora_inicio', '10:00:00')->firstOrFail();
$fut = Clase::whereDate('fecha', '2026-11-28')->whereTime('hora_inicio', '11:00:00')->firstOrFail();
// 50.2 ASI-012: Mariela y Pablo guardan la misma lista
paso('50.2a', 'Mariela guarda Federadas, faltan Lucía Ramos y Berenice Rossi', $lista($ma, $fed, ['Ramos, Lucia', 'Rossi, Berenice']), 200);
paso('50.2b', 'Pablo, con la pantalla vieja, guarda: falta Lucía Ramos', $lista($pa, $fed, ['Ramos, Lucia']), 200);
echo '   marcas de Federadas: '.DB::table('asistencias')->where('clase_id', $fed->id)->count().' · presentes '.DB::table('asistencias')->where('clase_id', $fed->id)->where('presente', 1)->count()."\n";
// 50.3 Hernán carga tarde
paso('50.3', 'Hernán toma Fútbol Avanzadas, falta Lucas Gómez', $lista($he, $fut, ['Gomez, Lucas']), 200);
echo '   sin lista: '.$sinLista()."\n";
// 50.4 ACC-005 y USU-010. Con caja abierta no se puede desactivar: se prueba con Hernán, que tiene la sesión abierta.
$uc = app(UsuarioWebController::class);
paso('50.4a', 'el dueño desactiva a Pablo, con la caja abierta', enviar($ad, 'PATCH', "/usuarios/{$pa->id}/toggle-activo", [], fn ($r) => $uc->toggleActivo($r, $pa->id), true), 400);
echo '   Pablo activo '.(int) $pa->fresh()->activo."\n";
paso('50.4b', 'desactiva a Hernán', enviar($ad, 'PATCH', "/usuarios/{$he->id}/toggle-activo", [], fn ($r) => $uc->toggleActivo($r, $he->id), true), 200);
$he->refresh(); $antes = DB::table('asistencias')->count(); [$st, $d] = http($he, '/clases'); echo "   Hernán abre Clases: $st $d\n";
$c2 = Clase::whereDate('fecha', '2026-11-28')->whereTime('hora_inicio', '11:00:00')->firstOrFail();
[$st, $d] = http($he, "/clases/{$c2->id}/asistencias", 'POST', ['items' => []]); echo "   Hernán guarda la lista que tenía en pantalla: $st $d · marcas nuevas ".(DB::table('asistencias')->count() - $antes)."\n";
paso('50.4c', 'el dueño lo reactiva', enviar($ad, 'PATCH', "/usuarios/{$he->id}/toggle-activo", [], fn ($r) => $uc->toggleActivo($r, $he->id), true), 200);
$he->refresh(); echo '   Hernán activo '.(int) $he->activo.' · sus marcas siguen '.DB::table('asistencias')->where('clase_id', $c2->id)->count()."\n";
// 50.5 adelantos
$adelanto = fn (Alumno $a, string $medio, string $monto) => enviar($pa, 'POST', "/caja/cobrar/{$a->id}", ['tipo_caja_id' => M($medio), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => $monto], 'fecha_pago' => '2026-11-28', 'confirmar_pago_adelantado' => '1'], fn ($r) => $caja->pagar($r, $a->id), true);
paso('50.5a', 'Franco Molina diciembre $38.000 en efectivo', $adelanto(A('Molina, Franco'), 'Efectivo', '38000'), 200);
paso('50.5b', 'Felipe Sosa diciembre $38.000 por MP', $adelanto(A('Sosa, Felipe'), 'Mercado Pago', '38000'), 200);
// 50.6 CLA-025: Pablo cancela una sola clase de la serie
$adu = Clase::whereDate('fecha', '2026-12-03')->whereTime('hora_inicio', '20:30:00')->firstOrFail();
paso('50.6', "Pablo cancela Adultos del 3/12 (#{$adu->id})", enviar($pa, 'PATCH', "/clases/{$adu->id}/cancelar", ['motivo_cancelacion' => 'Mariela avisó que ese jueves no puede'], fn ($r) => $cc->toggleCancelada($r, $adu->id)), 200);
echo '   canceladas de Adultos en diciembre: '.Clase::where('grupo_id', $adu->grupo_id)->where('cancelada', 1)->whereBetween('fecha', ['2026-12-01', '2026-12-31'])->count().' · esperado de Pablo '.plata($esp())."\n";
estado('fin 50 (antes de cerrar)');
