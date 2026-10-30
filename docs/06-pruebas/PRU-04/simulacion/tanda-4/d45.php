<?php
// Día 45, lunes 23/11, 11:00 — por pantalla: alta de Valentino al 70% con la inscripción nueva de $8.000 (paga todo y
// empieza en diciembre); el dueño, en el club, cobra diciembre a Morena sin caja, lo anula porque fue por otro medio
// y lo vuelve a cobrar; Sandra ve ese cobro; Renzo no puede cambiar la fecha de ingreso; Cashflow de hoy.
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController};
use App\Models\{Alumno, Grupo, DeudaCuota, Pago, CashflowMovimiento, CajaOperativa, User};
use Illuminate\Support\Str;
$admin = U('Admin Prueba'); $sandra = U('Sandra Vidal'); $caja = app(CajaWebController::class); $alumnos = app(AlumnoWebController::class);
estado('inicio 45 (después de la rutina)');
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $sandra->id)->firstOrFail();
$esp = fn () => app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
// 45.1 alta de Valentino Godoy, Patín Principiantes 2 por semana, ingreso 23/11
$g1 = Grupo::whereHas('deporte', fn ($d) => $d->where('nombre', 'Patín'))->whereHas('nivel', fn ($n) => $n->where('nombre', 'Principiantes'))->firstOrFail();
$p2 = DB::table('grupo_planes')->where('grupo_id', $g1->id)->where('clases_por_semana', 2)->first();
echo '   aviso del alta: '.json_encode(app(App\Services\PagoCuotaService::class)->previsualizarCuotaAlta('2026-11-23', $p2->id), JSON_UNESCAPED_UNICODE)."\n";
paso('45.1a', 'alta Valentino Godoy', enviar($sandra, 'POST', '/alumnos', ['nombre' => 'Valentino', 'apellido' => 'Godoy', 'dni' => '55444555', 'fecha_nacimiento' => '2017-07-02', 'celular' => '',
    'nombre_tutor' => 'Lorena Godoy', 'telefono_tutor' => '1133445566', 'deporte_id' => $g1->deporte_id, 'grupo_id' => $g1->id, 'plan_id' => $p2->id, 'fecha_alta' => '2026-11-23',
    'alta_token' => (string) Str::uuid()], fn ($r) => $alumnos->store($r)), 200);
$va = A('Godoy, Valentino');
echo '   Valentino: '.DeudaCuota::where('alumno_id', $va->id)->get()->map(fn ($d) => "$d->periodo:$d->estado ".plata($d->monto_original).' ('.$d->porcentaje_alta.'%)')->implode(' ')
    .' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $va->id)->where('tipo', 'INSCRIPCION')->value('monto_original'))."\n";
paso('45.1b', 'Valentino paga inscripción y noviembre en efectivo', enviar($sandra, 'POST', "/caja/cobrar/{$va->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-11'], 'fecha_pago' => '2026-11-23'], fn ($r) => $caja->pagar($r, $va->id), true), 200);
$p = Pago::where('alumno_id', $va->id)->latest('id')->first();
echo '   cobro '.plata($p->monto_final).' · recibo #'.$p->id.' · caja de Sandra esperado '.plata($esp())."\n";

// 45.2 el dueño cobra diciembre a Morena Rossi, sin caja, por Mercado Pago
$mo = A('Rossi, Morena');
$precio = app(App\Services\PagoCuotaService::class)->obtenerPlanParaPeriodo($mo->id, '2026-12')?->plan?->precioParaPeriodo('2026-12', $mo->fecha_alta);
$antesEsp = $esp();
$d = ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => (string) $precio], 'fecha_pago' => '2026-11-23', 'confirmar_pago_adelantado' => '1'];
paso('45.2', 'dueño cobra diciembre de Morena, '.plata($precio).' por MP', enviar($admin, 'POST', "/caja/cobrar/{$mo->id}", $d, fn ($r) => $caja->pagar($r, $mo->id), true), 200);
$pm = Pago::where('alumno_id', $mo->id)->latest('id')->first();
$cfm = CashflowMovimiento::where('referencia_tipo', 'PAGO_CUOTA')->where('referencia_id', $pm->id)->get();
echo '   cobro #'.$pm->id.' · Cashflow: '.$cfm->map(fn ($m) => $m->subrubro->nombre.' '.plata($m->monto).' '.$m->tipoCaja->nombre.' registró '.User::find($m->usuario_admin_id)?->name)->implode(' · ')
    .' · caja de Sandra '.plata($antesEsp).' → '.plata($esp())."\n";
// 45.3 lo anula: fue una transferencia al Galicia
paso('45.3a', 'anula con motivo corto', enviar($admin, 'POST', "/pagos/{$pm->id}/anular", ['motivo' => 'MP'], fn ($r) => app()->call([$alumnos, 'anularPago'], ['id' => $pm->id])), 422);
paso('45.3b', 'anula con motivo', enviar($admin, 'POST', "/pagos/{$pm->id}/anular", ['motivo' => 'Era transferencia al Galicia, no Mercado Pago'], fn ($r) => app()->call([$alumnos, 'anularPago'], ['id' => $pm->id])), 200);
$dic = DeudaCuota::where('alumno_id', $mo->id)->where('periodo', '2026-12')->first();
echo '   anulado: '.$pm->fresh()->estado.' · diciembre de Morena: '.($dic ? "$dic->estado ".plata($dic->monto_original).' pagado '.plata($dic->monto_pagado) : 'no existe')
    .' · Cashflow del cobro: '.CashflowMovimiento::where('referencia_id', $pm->id)->where('referencia_tipo', 'PAGO_CUOTA')->get()->map(fn ($m) => plata($m->monto).' '.$m->fecha->format('d/m'))->implode(' y ')."\n";
$fila = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id', $mo->id);
echo '   Morena en Cobranza: '.$fila?->estado_cobranza.' '.plata($fila?->deuda_total)."\n";
$d['tipo_caja_id'] = M('Banco Galicia');
paso('45.3c', 'vuelve a cobrar diciembre por Galicia', enviar($admin, 'POST', "/caja/cobrar/{$mo->id}", $d, fn ($r) => $caja->pagar($r, $mo->id), true), 200);
$pm2 = Pago::where('alumno_id', $mo->id)->latest('id')->first();
$dic = DeudaCuota::where('alumno_id', $mo->id)->where('periodo', '2026-12')->first();
echo '   cobro #'.$pm2->id.' · diciembre: '.$dic->estado.' '.plata($dic->monto_original)."\n";
// 45.4 Sandra abre la ficha de Morena: ve los cobros del dueño, abre el recibo, no tiene Anular
[$st, $t, $h] = http($sandra, "/alumnos/{$mo->id}");
echo "   ficha de Morena para Sandra ($st): Anular ".(str_contains($h, 'Anular') ? 'SÍ aparece' : 'no aparece').' · anulado '.(stripos($h, 'anulad') !== false ? 'se ve' : 'no se ve')."\n";
[$st, $t, $h] = http($admin, "/alumnos/{$mo->id}");
echo "   ficha de Morena para el dueño ($st): Anular ".(str_contains($h, 'Anular') ? 'aparece' : 'no aparece')."\n";
$h = http($sandra, "/recibos/cuota/{$pm2->id}"); echo '   Sandra abre el recibo del dueño: '.$h[0]."\n";
$h = http($sandra, "/pagos/{$pm2->id}/anular", 'POST', ['motivo' => 'Prueba de la operativa forzando']); echo '   Sandra fuerza Anular: '.$h[0].' '.$h[1]."\n";
// 45.5 Renzo: cambiar la fecha de ingreso al 5/12
$re = A('Acuña, Renzo');
$datos = ['nombre' => 'Renzo', 'apellido' => 'Acuña', 'dni' => '56123456', 'fecha_nacimiento' => '2019-03-14', 'celular' => '1155667788', 'nombre_tutor' => 'Marcela Acuña', 'telefono_tutor' => '1155667788',
    'deporte_id' => $re->deporte_id, 'grupo_id' => $re->grupo_id, 'fecha_alta' => '2026-12-05'];
paso('45.5a', 'Renzo: ingreso al 5/12 sin motivo', enviar($sandra, 'PUT', "/alumnos/{$re->id}", $datos, fn ($r) => $alumnos->update($r, $re->id)), 422);
paso('45.5b', 'Renzo: ingreso al 5/12 con motivo', enviar($sandra, 'PUT', "/alumnos/{$re->id}", $datos + ['motivo_fecha_alta' => 'La mamá avisa que empieza el sábado 5/12'], fn ($r) => $alumnos->update($r, $re->id)), 422);
echo '   Renzo ingreso: '.$re->fresh()->fecha_alta->format('d/m/Y')."\n";
// 45.6 Cashflow de hoy: quién registró y quién validó
[$st, $t, $h] = http($admin, '/cashflow?periodo=dia&fecha=2026-11-23');
echo "   Cashflow ($st): columna Val. ".(str_contains($h, 'Val.') ? 'sí' : 'no').' · Morena '.(str_contains($h, 'Morena') ? 'sí' : 'no')."\n";
estado('fin 45 (antes de la noche)');
