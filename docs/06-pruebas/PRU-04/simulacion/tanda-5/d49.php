<?php
// Día 49, viernes 27/11, por pantalla: T100 otra vez, el saldo de Clara, la inscripción de Thiago, T108, T101, T107, T105 y T109.
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController};
use App\Models\{Alumno, CajaOperativa, DeudaCuota, Grupo, Pago, MovimientoOperativo, User};
use App\Services\{PagoCuotaService, InscripcionService};
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $ro = U('Rocío Paz'); $caja = app(CajaWebController::class); $al = app(AlumnoWebController::class); $cob = fn () => app(App\Services\CobranzaEstadoService::class);
estado('inicio 49 (después de la rutina)');
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $ro->id)->firstOrFail();
$esp = fn () => app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
$adelanto = fn (User $quien, Alumno $a, string $medio, string $monto, array $mas = []) => enviar($quien, 'POST', "/caja/cobrar/{$a->id}", ['tipo_caja_id' => M($medio), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => $monto], 'fecha_pago' => '2026-11-27', 'confirmar_pago_adelantado' => '1'] + $mas, fn ($r) => $caja->pagar($r, $a->id), true);

// 49.1 T100: un cero de más, ahora con la reparación
$ma = A('Castro, Mateo');
paso('49.1a', 'Mateo Castro: diciembre $280.000', $adelanto($ro, $ma, 'Efectivo', '280000'), 200);
$pm = Pago::where('alumno_id', $ma->id)->latest('id')->first(); $mm = MovimientoOperativo::where('pago_id', $pm->id)->first();
echo '   esperado con el error: '.plata($esp())."\n";
paso('49.1b', 'cancela con motivo', enviar($ro, 'POST', "/caja/{$k->id}/movimientos/{$mm->id}/cancelar", ['motivo' => 'Un cero de más: eran $28.000'], fn ($r) => $caja->cancelarMovimiento($r, $k->id, $mm->id)), 200);
echo '   diciembre de Mateo: '.(DeudaCuota::where('alumno_id', $ma->id)->where('periodo', '2026-12')->exists() ? 'EXISTE' : 'no existe').' · esperado '.plata($esp())."\n";
paso('49.1c', 'diciembre bien, $28.000', $adelanto($ro, $ma, 'Efectivo', '28000'), 200);
$d = DeudaCuota::where('alumno_id', $ma->id)->where('periodo', '2026-12')->first(); echo "   diciembre de Mateo: {$d->estado} ".plata($d->monto_original).' pagado '.plata($d->monto_pagado)."\n";

// 49.2 Clara: el saldo que dejó el defecto de la tanda 4
$cl = A('Iglesias, Clara'); $dc = DeudaCuota::where('alumno_id', $cl->id)->where('periodo', '2026-12')->firstOrFail();
$cond = fn (string $m) => enviar($ad, 'POST', "/deudas/{$dc->id}/condonar", ['motivo' => $m], fn ($r) => $al->condonarDeuda($r, $dc->id, app(PagoCuotaService::class)));
paso('49.2a', 'condona con motivo corto', $cond('Error'), 400);
paso('49.2b', 'condona el saldo', $cond('Un cero de más del 20/11: la cuota era de $30.000'), 200);
$dc->refresh(); echo "   diciembre de Clara: {$dc->estado} original ".plata($dc->monto_original).' pagado '.plata($dc->monto_pagado)."\n";
paso('49.2c', 'condona otra vez', $cond('Un cero de más del 20/11: segunda vez'), 400);

// 49.3 Thiago: la inscripción de la segunda ficha
$th = Alumno::where('apellido', 'Ruiz')->where('nombre', 'Thiago')->where('grupo_id', 1)->firstOrFail();
$fila = fn (Alumno $a) => $cob()->listadoCobranza('TODOS')->firstWhere('id', $a->id);
echo '   Thiago antes: '.$fila($th)?->estado_cobranza.' '.plata($fila($th)?->deuda_total)."\n";
$cargo = App\Models\CargoAlumno::where('alumno_id', $th->id)->where('tipo', 'INSCRIPCION')->firstOrFail();
paso('49.3', 'condona la inscripción de Thiago', enviar($ad, 'POST', "/inscripciones/{$cargo->id}/condonar", ['motivo' => 'Ya es del club: no paga otra inscripción'], fn ($r) => $al->condonarInscripcion($r, $cargo->id, app(InscripcionService::class))), 200);
echo '   Thiago después: '.$fila($th)?->estado_cobranza.' '.plata($fila($th)?->deuda_total).' · deuda total '.plata($cob()->resumenDashboard()['total_adeudado'])."\n";

// 49.4 T108: segunda ficha de quien vino de la primera carga
$mr = A('Rodriguez, Mateo'); $pa = Grupo::find(1); $p1 = DB::table('grupo_planes')->where('grupo_id', 1)->where('clases_por_semana', 1)->first();
[$st,, $h] = http($ro, '/alumnos/inscripcion-preview?dni='.$mr->dni.'&fecha_alta=2026-11-27'); echo "   aviso previo ($st): ".mb_substr($h, 0, 300)."\n";
paso('49.4a', 'segunda ficha de Mateo Rodríguez, Patín Principiantes', enviar($ro, 'POST', '/alumnos', ['nombre' => $mr->nombre, 'apellido' => $mr->apellido, 'dni' => $mr->dni, 'fecha_nacimiento' => $mr->fecha_nacimiento->format('Y-m-d'), 'celular' => (string) $mr->celular, 'nombre_tutor' => (string) ($mr->nombre_tutor ?: 'Mónica Rodríguez'), 'telefono_tutor' => (string) ($mr->telefono_tutor ?: '1155001122'), 'deporte_id' => $pa->deporte_id, 'grupo_id' => 1, 'plan_id' => $p1->id, 'fecha_alta' => '2026-11-27', 'alta_token' => (string) Str::uuid()], fn ($r) => $al->store($r)), 200);
$mr2 = Alumno::where('dni', $mr->dni)->where('grupo_id', 1)->firstOrFail();
echo '   ficha nueva: '.DeudaCuota::where('alumno_id', $mr2->id)->get()->map(fn ($d) => "$d->periodo:$d->estado ".plata($d->monto_original))->implode(' ').' · inscripciones de esa persona '.DB::table('cargos_alumno')->where('dni', $mr->dni)->where('tipo', 'INSCRIPCION')->count().' · deuda total '.plata($cob()->resumenDashboard()['total_adeudado'])."\n";
paso('49.4b', 'paga noviembre por MP', enviar($ro, 'POST', "/caja/cobrar/{$mr2->id}", ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => ['2026-11'], 'fecha_pago' => '2026-11-27'], fn ($r) => $caja->pagar($r, $mr2->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $mr2->id)->latest('id')->value('monto_final'))."\n";

// 49.5 T101: el gasto del dueño en el turno, a su nombre
paso('49.5', 'dueño: Insumos $2.500 en el turno de Rocío', enviar($ad, 'POST', "/caja/{$k->id}/editar", ['tipo_caja_id' => M('Efectivo'), 'subrubro_id' => S('Insumos Varios'), 'monto' => '2500', 'fecha' => '2026-11-27', 'observaciones' => 'Lamparita del vestuario: la pagó la caja'], fn ($r) => $caja->editarStore($r, $k->id)), 200);
$g = MovimientoOperativo::where('observaciones', 'like', 'Lamparita%')->firstOrFail(); echo '   a nombre de: '.User::find($g->usuario_id)->name.' · esperado '.plata($esp())."\n";
// 49.6 adelantos del día
paso('49.6a', 'Catalina Castro diciembre $45.000 por MP', $adelanto($ro, A('Castro, Catalina'), 'Mercado Pago', '45000'), 200);
// 49.7 T105 y T109
$t0 = microtime(true); [$st,, $h] = http($ro, '/recibos/cuota/'.Pago::where('alumno_id', $ma->id)->latest('id')->value('id')); echo "   recibo de Mateo: $st ".round(strlen($h) / 1024).' KB, '.round(microtime(true) - $t0, 1)." s\n";
[$st, $t] = http($ro, '/recibos/cuota/9999999'); echo "   recibo inexistente: $st $t\n";
[$st, $t] = http($ro, '/alumnos?estado=basura&page=xx&deporte_id=abc'); echo "   filtros con basura: $st $t\n";
// 49.8 T107: se cierra con la pantalla vieja
$vista = $esp(); echo '   pantalla de cierre: '.plata($vista)."\n";
paso('49.8a', 'otra pestaña: Melina Castro diciembre $45.000 en efectivo', $adelanto($ro, A('Castro, Melina'), 'Efectivo', '45000'), 200);
paso('49.8b', 'cierra con la pantalla vieja', enviar($ro, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $vista, 'cambio_retenido' => '10000', 'esperado_visto' => (string) $vista], fn ($r) => $caja->cerrar($r, $k->id)), 400);
$k->refresh(); echo "   caja: {$k->estado}\n"; $nuevo = $esp(); echo '   esperado al día: '.plata($nuevo)."\n";
paso('49.8c', 'cierra con lo contado', enviar($ro, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $nuevo, 'cambio_retenido' => '10000', 'esperado_visto' => (string) $nuevo], fn ($r) => $caja->cerrar($r, $k->id)), 200);
paso('49.8d', 'el segundo clic', enviar($ro, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $nuevo, 'cambio_retenido' => '10000', 'esperado_visto' => (string) $nuevo], fn ($r) => $caja->cerrar($r, $k->id)), 400);
$k->refresh(); echo "   caja #{$k->id}: {$k->estado} diferencia ".plata($k->diferencia_efectivo)."\n";
estado('fin 49 (antes de la noche)');
