<?php
// Día 42, viernes 20/11, 11:00 — por pantalla: Rocío cobra un diciembre adelantado escribiendo mal el importe
// antes de escribirlo bien; doble Guardar de un gasto; corrige ese gasto y no puede tocar uno de una caja validada;
// Lucía mira la lista que tomó Mariela; el dueño carga Internet dos veces y elimina uno; Rocío cierra dejando
// más cambio que lo que recibió.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ClaseWebController, AlumnoWebController, CashflowWebController, CashflowCorreccionWebController};
use App\Models\{Alumno, Clase, DeudaCuota, Pago, MovimientoOperativo, CashflowMovimiento};
use Illuminate\Support\Str;
$admin = U('Admin Prueba'); $rocio = U('Rocío Paz'); $lucia = U('Lucía Gaitán'); $caja = app(CajaWebController::class);
estado('inicio 42 (después de la rutina de la mañana)');
$k = App\Models\CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $rocio->id)->firstOrFail();

// 42.1 diciembre adelantado de Clara Iglesias; el importe se escribe mal antes (lo frena la pantalla) y después bien
$cl = A('Iglesias, Clara');
$plan = app(App\Services\PagoCuotaService::class)->obtenerPlanParaPeriodo($cl->id, '2026-12')?->plan;
$precio = $plan?->precioParaPeriodo('2026-12', $cl->fecha_alta);
echo '   Clara: plan '.$plan?->clases_por_semana.'x · diciembre a '.plata($precio).' · cuotas '.DeudaCuota::where('alumno_id', $cl->id)->orderBy('periodo')->get()->map(fn ($d) => "$d->periodo:$d->estado")->implode(' ')."\n";
$d = ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'fecha_pago' => '2026-11-20', 'confirmar_pago_adelantado' => '1'];
paso('42.1a', 'forzando 44.505 (tres decimales)', enviar($rocio, 'POST', "/caja/cobrar/{$cl->id}", $d + ['montos_cuota' => ['2026-12' => '44.505']], fn ($r) => $caja->pagar($r, $cl->id), true), 422);
paso('42.1b', 'forzando cero', enviar($rocio, 'POST', "/caja/cobrar/{$cl->id}", $d + ['montos_cuota' => ['2026-12' => '0']], fn ($r) => $caja->pagar($r, $cl->id), true), 422);
paso('42.1c', 'forzando letras', enviar($rocio, 'POST', "/caja/cobrar/{$cl->id}", $d + ['montos_cuota' => ['2026-12' => 'cuarenta']], fn ($r) => $caja->pagar($r, $cl->id), true), 422);
echo '   pagos de Clara en la tanda: '.Pago::where('alumno_id', $cl->id)->where('fecha_pago', '>=', '2026-11-19')->count()."\n";
paso('42.1d', 'un cero de más: $300.000 en efectivo', enviar($rocio, 'POST', "/caja/cobrar/{$cl->id}", $d + ['montos_cuota' => ['2026-12' => '300000']], fn ($r) => $caja->pagar($r, $cl->id), true));
$pm = Pago::where('alumno_id', $cl->id)->latest('id')->first(); $mm = MovimientoOperativo::where('pago_id', $pm->id)->first();
echo '   pago #'.$pm->id.' '.plata($pm->monto_final).' · movimientos '.MovimientoOperativo::where('pago_id', $pm->id)->count().' · esperado '.plata(app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'])."\n";
paso('42.1e', 'cancela sin motivo', enviar($rocio, 'POST', "/caja/{$k->id}/movimientos/{$mm->id}/cancelar", [], fn ($r) => $caja->cancelarMovimiento($r, $k->id, $mm->id)), 422);
paso('42.1f', 'cancela con motivo', enviar($rocio, 'POST', "/caja/{$k->id}/movimientos/{$mm->id}/cancelar", ['motivo' => 'Un cero de más: eran $30.000'], fn ($r) => $caja->cancelarMovimiento($r, $k->id, $mm->id)), 200);
$dc = DeudaCuota::where('alumno_id', $cl->id)->where('periodo', '2026-12')->first();
echo '   tras cancelar: diciembre '.($dc ? "$dc->estado original ".plata($dc->monto_original).' pagado '.plata($dc->monto_pagado) : 'no existe').' · pago '.$pm->fresh()->estado.' · esperado '.plata(app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado']).' · deuda '.plata(app(App\Services\CobranzaEstadoService::class)->resumenDashboard()['total_adeudado'])."\n";
[$st,,$h] = http($rocio, "/caja/cobrar/{$cl->id}");
if (preg_match_all('/Diciembre.{0,120}/su', strip_tags($h), $mm2)) echo '   Cobrar ofrece: '.mb_substr(trim(preg_replace('/\s+/', ' ', $mm2[0][0])), 0, 150)."\n";
paso('42.1g', 'diciembre bien, '.plata($precio).' en efectivo', enviar($rocio, 'POST', "/caja/cobrar/{$cl->id}", $d + ['montos_cuota' => ['2026-12' => (string) $precio]], fn ($r) => $caja->pagar($r, $cl->id), true), 409);
paso('42.1h', 'indica el motivo que le pide', enviar($rocio, 'POST', "/caja/cobrar/{$cl->id}", $d + ['montos_cuota' => ['2026-12' => (string) $precio], 'motivo' => 'Corrijo el importe: eran $30.000'], fn ($r) => $caja->pagar($r, $cl->id), true), 200);
$dc = $dc->fresh(); echo '   DEFECTO: diciembre de Clara '.$dc->estado.' original '.plata($dc->monto_original).' pagado '.plata($dc->monto_pagado).' saldo '.plata($dc->monto_original - $dc->monto_pagado)."
";
echo '   Clara: '.DeudaCuota::where('alumno_id', $cl->id)->orderBy('periodo')->get()->map(fn ($x) => "$x->periodo:$x->estado ".plata($x->monto_original))->implode(' ')."\n";

// 42.2 resma: dos Guardar seguidos (la pantalla manda una sola vez; el segundo envío se reproduce igual)
$mov = ['subrubro_id' => S('Librería'), 'tipo_caja_id' => M('Efectivo'), 'monto' => '6500', 'observaciones' => 'Resma para las planillas del torneo'];
$antes = MovimientoOperativo::count();
paso('42.2a', 'resma $6.500', enviar($rocio, 'POST', '/caja/movimiento', $mov, fn ($r) => $caja->movimientoStore($r)), 200);
echo '   movimientos nuevos: '.(MovimientoOperativo::count() - $antes)."\n";
$resma = MovimientoOperativo::where('caja_operativa_id', $k->id)->where('observaciones', 'Resma para las planillas del torneo')->latest('id')->firstOrFail();

// 42.3 corrige la resma en su caja abierta; un movimiento de su caja validada del 18/11 no se toca
paso('42.3a', 'corrige la resma a $6.000', enviar($rocio, 'PUT', "/caja/{$k->id}/movimientos/{$resma->id}", ['tipo_caja_id' => M('Efectivo'), 'subrubro_id' => S('Librería'), 'monto' => '6000',
    'fecha' => '2026-11-20', 'observaciones' => 'Resma para las planillas del torneo'], fn ($r) => $caja->updateMovimiento($r, $k->id, $resma->id)), 200);
echo '   resma: '.plata($resma->fresh()->monto)."\n";
$k72 = App\Models\CajaOperativa::where('usuario_operativo_id', $rocio->id)->where('estado', 'VALIDADA')->orderByDesc('id')->firstOrFail();
$m72 = MovimientoOperativo::where('caja_operativa_id', $k72->id)->whereNull('pago_id')->firstOrFail();
paso('42.3b', "abre la edición de un movimiento de su caja validada #{$k72->id}", enviar($rocio, 'GET', "/caja/{$k72->id}/movimientos/{$m72->id}/editar", [], fn ($r) => $caja->editarMovimientoForm($k72->id, $m72->id)), 200); // se abre escribiendo la dirección; no hay botón y Guardar se rechaza
paso('42.3c', 'forzando el cambio de ese movimiento', enviar($rocio, 'PUT', "/caja/{$k72->id}/movimientos/{$m72->id}", ['tipo_caja_id' => $m72->tipo_caja_id, 'subrubro_id' => $m72->subrubro_id, 'monto' => '1',
    'fecha' => '2026-11-18', 'observaciones' => 'x'], fn ($r) => $caja->updateMovimiento($r, $k72->id, $m72->id)), 400);
echo '   movimiento validado: '.plata($m72->fresh()->monto)."\n";

// 42.4 Lucía mira la lista que tomó Mariela y fuerza la ficha de un alumno
$int = Clase::whereDate('fecha', '2026-11-20')->whereTime('hora_inicio', '16:00:00')->firstOrFail();
paso('42.4a', "Lucía abre la clase #{$int->id}", enviar($lucia, 'GET', "/clases/{$int->id}", [], fn ($r) => app(ClaseWebController::class)->show($int->id)), 200);
$lara = A('Quiroga, Lara');
$h = http($lucia, "/alumnos/{$lara->id}"); paso('42.4b', 'Lucía fuerza la ficha de Lara (con los filtros de la ruta)', [$h[0], $h[1]], 403);
echo '   profesores de la clase: '.$int->profesores()->pluck('nombre')->implode(', ').' · lista tomada por: '.DB::table('asistencias')->where('clase_id', $int->id)->count()." marcas\n";

// 42.5 Internet cargado dos veces; el segundo se elimina
$cf = fn () => enviar($admin, 'POST', '/cashflow/movimiento', ['subrubro_id' => S('Internet'), 'tipo_caja_id' => M('Banco Galicia'), 'monto' => '22000', 'fecha' => '2026-11-20',
    'observaciones' => 'Telecentro, noviembre'], fn ($r) => app(CashflowWebController::class)->store($r));
paso('42.5a', 'Internet $22.000 por Galicia', $cf(), 200);
paso('42.5b', 'Internet otra vez (desde la otra pestaña)', $cf(), 200);
$dos = CashflowMovimiento::where('observaciones', 'Telecentro, noviembre')->orderBy('id')->get();
echo '   Internet cargado: '.$dos->count().' veces · Galicia '.plata(DB::table('tipos_caja')->where('nombre', 'Banco Galicia')->value('saldo_inicial') + CashflowMovimiento::where('tipo_caja_id', M('Banco Galicia'))->sum('monto'))."\n";
$seg = $dos->last();
paso('42.5c', "elimina el segundo (#{$seg->id}) con motivo", enviar($admin, 'DELETE', "/cashflow/movimientos/{$seg->id}", ['clave' => (string) Str::uuid(), 'version' => (int) $seg->version_correccion, 'motivo' => 'Cargado dos veces'],
    fn ($r) => app(CashflowCorreccionWebController::class)->destroy($r, $seg->id)), 200);
$contra = CashflowMovimiento::where('contra_origen_id', $seg->id)->first();
echo '   anulado: '.($seg->fresh()->anulado_at ? 'sí' : 'no').' · contra #'.$contra?->id.' '.plata($contra?->monto).' '.$contra?->fecha?->format('d/m').' · Galicia '
    .plata(DB::table('tipos_caja')->where('nombre', 'Banco Galicia')->value('saldo_inicial') + CashflowMovimiento::where('tipo_caja_id', M('Banco Galicia'))->sum('monto'))."\n";
$rep = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
echo '   Reportes nov: egresos '.plata($rep['egresos'] / 100)."\n";

// 42.6 Rocío cierra dejando $15.000 de cambio (recibió $10.000)
$esp = app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
echo '   esperado de Rocío: '.plata($esp)."\n";
paso('42.6a', 'cierra dejando más de lo contado', enviar($rocio, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => (string) ($esp + 1000)], fn ($r) => $caja->cerrar($r, $k->id)), 400);
paso('42.6b', 'cierra dejando $15.000', enviar($rocio, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => '15000'], fn ($r) => $caja->cerrar($r, $k->id)), 200);
$k->refresh();
echo '   caja #'.$k->id.': '.$k->estado.' recibió '.plata($k->efectivo_inicial).' contado '.plata($k->efectivo_contado).' cambio '.plata($k->cambio_retenido).' entrega '.plata($k->efectivo_retirado)."\n";
estado('fin 42 (antes de la noche)');
