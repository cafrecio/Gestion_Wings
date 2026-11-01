<?php
// Día 52, lunes 30/11, por pantalla: alta con la inscripción en cero y con el importe cambiado (ALU-010, ALU-009); el
// medio nuevo se usa para cobrar; adelantos, un cobro del dueño y un gasto directo; cuadre del día (REP-010).
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController, CashflowWebController, ConfiguracionWebController, TipoCajaWebController};
use App\Models\{Alumno, CajaOperativa, DeudaCuota, Pago, TipoCaja, User};
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal'); $caja = app(CajaWebController::class); $al = app(AlumnoWebController::class); $cfg = app(ConfiguracionWebController::class);
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $sa->id)->firstOrFail();
$esp = fn () => app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
estado('inicio 52 (después de la rutina)');
$rep0 = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
$p1 = DB::table('grupo_planes')->where('grupo_id', 1)->where('clases_por_semana', 1)->first();
$alta = fn (array $mas = []) => enviar($sa, 'POST', '/alumnos', ['nombre' => 'Nina', 'apellido' => 'Arias', 'dni' => '57222333', 'fecha_nacimiento' => '2018-02-20', 'celular' => '', 'nombre_tutor' => 'Pablo Arias', 'telefono_tutor' => '1177889900', 'deporte_id' => 1, 'grupo_id' => 1, 'plan_id' => $p1->id, 'fecha_alta' => '2026-11-30', 'alta_token' => (string) Str::uuid()] + $mas, fn ($r) => $al->store($r));
// 52.1 ALU-010: el importe no se puede dejar en cero desde Configuración; se comprueba y se sigue
$inscr = fn (string $v) => enviar($ad, 'PUT', '/configuraciones/inscripcion_importe', ['valor' => $v], fn ($r) => $cfg->update($r, 'inscripcion_importe'));
paso('52.1', 'el dueño deja la inscripción en $0', $inscr('0'), 422);
// 52.2 ALU-009: cambia el importe con el alta a la vista
$vista = app(App\Services\PagoCuotaService::class)->previsualizarCuotaAlta('2026-11-30', $p1->id);
echo '   aviso del alta: '.json_encode($vista, JSON_UNESCAPED_UNICODE)."\n";
paso('52.2a', 'el dueño sube la inscripción a $9.000', $inscr('9000'), 200);
paso('52.2b', 'Sandra guarda lo que veía: $8.000', $alta(['inscripcion_importe_visto' => '8000', 'cuota_periodo_visto' => $vista['periodo'], 'cuota_importe_visto' => $vista['importe'] ?? $vista['monto'] ?? '']), 422);
echo '   Nina creada: '.(Alumno::where('dni', '57222333')->exists() ? 'SÍ' : 'no').' · deuda '.plata(app(App\Services\CobranzaEstadoService::class)->resumenDashboard()['total_adeudado'])."\n";
paso('52.2c', 'el dueño la vuelve a $8.000', $inscr('8000'), 200);
paso('52.2d', 'Sandra guarda de nuevo', $alta(['inscripcion_importe_visto' => '8000', 'cuota_periodo_visto' => $vista['periodo'], 'cuota_importe_visto' => $vista['importe'] ?? $vista['monto'] ?? '']), 200);
$ni = A('Arias, Nina');
echo '   Nina: '.DeudaCuota::where('alumno_id', $ni->id)->get()->map(fn ($d) => "$d->periodo:$d->estado ".plata($d->monto_original))->implode(' ').' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $ni->id)->where('tipo', 'INSCRIPCION')->value('monto_original'))."\n";
// 52.3 CAT-005: el medio nuevo se usa y ya no se le cambia el saldo inicial
paso('52.3a', 'Nina paga todo en efectivo', enviar($sa, 'POST', "/caja/cobrar/{$ni->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-11'], 'fecha_pago' => '2026-11-30'], fn ($r) => $caja->pagar($r, $ni->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $ni->id)->latest('id')->value('monto_final')).' · esperado '.plata($esp())."\n";
$torneoCT = fn (string $f) => enviar($sa, 'POST', '/caja/movimiento', ['subrubro_id' => S('Inscripciones'), 'tipo_caja_id' => M('Caja Torneo'), 'monto' => '3000', 'observaciones' => "Torneo de diciembre, Patín Principiantes: familia $f"], fn ($r) => $caja->movimientoStore($r));
paso('52.3b', 'familia Torres paga el torneo por Caja Torneo', $torneoCT('Torres'), 200);
paso('52.3c', 'familia Suarez paga el torneo por Caja Torneo', $torneoCT('Suarez'), 200);
$ct = TipoCaja::where('nombre', 'Caja Torneo')->firstOrFail(); $tcC = app(TipoCajaWebController::class);
paso('52.3d', 'el dueño intenta cambiarle el saldo inicial', enviar($ad, 'PUT', "/tipos-caja/{$ct->id}", ['nombre' => 'Caja Torneo', 'abreviatura' => 'TOR', 'saldo_inicial' => '60000'], fn ($r) => $tcC->update($r, $ct->id)));
echo '   Caja Torneo, saldo inicial: '.plata($ct->fresh()->saldo_inicial)."\n";
// 52.3e la profesora nueva necesita usuario antes de su primera clase
$noraP = App\Models\Profesor::where('apellido', 'Vidal')->where('nombre', 'Nora')->firstOrFail();
paso('52.3e', 'usuario de Nora', enviar($ad, 'POST', '/usuarios', ['name' => 'Nora Vidal', 'email' => 'nora.vidal@example.com', 'password' => 'Hockey-del-club-2026!', 'password_confirmation' => 'Hockey-del-club-2026!', 'rol' => 'PROFESOR', 'profesor_id' => $noraP->id], fn ($r) => app(App\Http\Controllers\UsuarioWebController::class)->store($r)), 200);
// 52.4 adelantos y cobro del dueño
$adelanto = fn (User $q, Alumno $a, string $medio, string $monto) => enviar($q, 'POST', "/caja/cobrar/{$a->id}", ['tipo_caja_id' => M($medio), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => $monto], 'fecha_pago' => '2026-11-30', 'confirmar_pago_adelantado' => '1'], fn ($r) => $caja->pagar($r, $a->id), true);
paso('52.4a', 'Sandra: Delfina Giménez diciembre $35.000 en efectivo', $adelanto($sa, A('Gimenez, Delfina'), 'Efectivo', '35000'), 200);
paso('52.4b', 'Sandra: Lorenzo Morales diciembre $38.000 por MP', $adelanto($sa, A('Morales, Lorenzo'), 'Mercado Pago', '38000'), 200);
paso('52.4c', 'el dueño: Benjamín Fernández diciembre $52.000 por Galicia', $adelanto($ad, A('Fernandez, Benjamin'), 'Banco Galicia', '52000'), 200);
paso('52.5', 'gasto directo: Luz $9.800 por Galicia', enviar($ad, 'POST', '/cashflow/movimiento', ['subrubro_id' => S('Luz'), 'tipo_caja_id' => M('Banco Galicia'), 'monto' => '9800', 'fecha' => '2026-11-30', 'observaciones' => 'Edesur, noviembre'], fn ($r) => app(CashflowWebController::class)->store($r)), 200);
$rep1 = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
echo '   noviembre, mañana a tarde: ingresos '.plata($rep0['ingresos'] / 100).' → '.plata($rep1['ingresos'] / 100).' · egresos '.plata($rep0['egresos'] / 100).' → '.plata($rep1['egresos'] / 100)."\n";
// 52.5b T99: el aviso de la fecha de ingreso, sin nombres internos
$re = A('Acuña, Renzo');
paso('52.5b', 'Renzo: ingreso al 5/12, sin motivo', enviar($sa, 'PUT', "/alumnos/{$re->id}", ['nombre' => 'Renzo', 'apellido' => 'Acuña', 'dni' => '56123456', 'fecha_nacimiento' => '2019-03-14', 'celular' => '1155667788', 'nombre_tutor' => 'Marcela Acuña', 'telefono_tutor' => '1155667788', 'deporte_id' => $re->deporte_id, 'grupo_id' => $re->grupo_id, 'fecha_alta' => '2026-12-05'], fn ($r) => $al->update($r, $re->id)), 422);
// 52.6 Sandra no ve Reparaciones menores
[$st,, $h] = http($sa, '/caja/movimiento'); echo "   Sandra, gasto ($st): Reparaciones menores ".(str_contains($h, 'Reparaciones menores') ? 'SE OFRECE' : 'no se ofrece')."\n";
estado('fin 52 (antes de la noche)');
