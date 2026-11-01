<?php
// Día 55, jueves 3/12, por pantalla: el mes se termina de pagar. Verónica, ya inactiva, cobra sus dos clases de noviembre;
// sueldos de las operativas y canchas; el dueño mira Reportes; arranca Hockey con dos altas.
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController, CashflowWebController, GrupoWebController, LiquidacionWebController, TipoCajaWebController};
use App\Models\{Alumno, CajaOperativa, Clase, DeudaCuota, Grupo, GrupoPlan, Liquidacion, Pago, Profesor, TipoCaja};
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal'); $lc = app(LiquidacionWebController::class); $al = app(AlumnoWebController::class); $caja = app(CajaWebController::class);
$rep = app(App\Services\ReporteMensualService::class);
estado('inicio 55 (después de la rutina)');
// 55.0 el generador volvió a correr a las 06:00: tiene que no hacer nada
$g = DB::table('generaciones_cuotas')->where('periodo', '2026-12')->first();
echo 'constancia de diciembre, sin cambios: creadas '.$g->creadas.' · a revisión '.$g->a_revision.' · cuotas de diciembre '.DeudaCuota::where('periodo', '2026-12')->count().' · revisiones pendientes '.DB::table('alumnos_revision_cobranza')->where('estado_revision', 'PENDIENTE')->count()."\n";

// 55.1 Verónica: inactiva, con dos clases de noviembre dictadas (T46)
$ve = Profesor::where('apellido', 'Salinas')->firstOrFail();
[$st,, $h] = http($ad, '/liquidaciones/crear?mes=11&anio=2026'); echo "   Nueva liquidación ($st): Verónica se ofrece: ".(str_contains($h, 'Salinas') ? 'SÍ' : 'NO').' · '.textoDe($h, 'Salinas', 80)."\n";
paso('55.1a', 'genera la de Verónica, noviembre', enviar($ad, 'POST', '/liquidaciones', ['profesor_id' => $ve->id, 'mes' => 11, 'anio' => 2026], fn ($r) => $lc->store($r)), 200);
$lv = Liquidacion::where('profesor_id', $ve->id)->where('mes', 11)->where('anio', 2026)->orderByDesc('id')->firstOrFail();
echo "   liquidación #{$lv->id}: total ".plata($lv->total_calculado).' · a pagar '.plata($lv->monto_a_pagar).' · detalles '.$lv->detalles()->count()."\n";
paso('55.1b', 'la cierra', enviar($ad, 'POST', "/liquidaciones/{$lv->id}/cerrar", [], fn ($r) => $lc->cerrar($lv->id)), 200);
paso('55.1c', 'la paga por Efectivo', enviar($ad, 'POST', "/liquidaciones/{$lv->id}/pagar", ['fecha_pago' => '2026-12-03', 'tipo_caja_id' => M('Efectivo'), 'monto_esperado' => (string) $lv->fresh()->monto_a_pagar], fn ($r) => $lc->pagar($r, $lv->id)), 200);

// 55.2 y 55.3 sueldos y canchas de noviembre
$cf = app(CashflowWebController::class);
$directo = fn (string $sub, string $medio, string $monto, string $obs) => enviar($ad, 'POST', '/cashflow/movimiento', ['subrubro_id' => S($sub), 'tipo_caja_id' => M($medio), 'monto' => $monto, 'fecha' => '2026-12-03', 'observaciones' => $obs], fn ($r) => $cf->store($r));
paso('55.2a', 'sueldo de Sandra $320.000, efectivo', $directo('Op-Sandra Vidal', 'Efectivo', '320000', 'Sueldo de noviembre'), 200);
paso('55.2b', 'sueldo de Pablo $320.000, efectivo', $directo('Op-Pablo Ledesma', 'Efectivo', '320000', 'Sueldo de noviembre'), 200);
paso('55.2c', 'sueldo de Rocío $280.000, MP', $directo('Op-Rocío Paz', 'Mercado Pago', '280000', 'Sueldo de noviembre'), 200);
paso('55.3a', 'cancha San Carlos $180.000, MP', $directo('San Carlos', 'Mercado Pago', '180000', 'Alquiler de cancha, noviembre'), 200);
paso('55.3b', 'cancha Centenera $125.000, efectivo', $directo('Centenera', 'Efectivo', '125000', 'Alquiler de cancha, noviembre'), 200);
$d = $rep->obtener('2026-12'); $n = $rep->obtener('2026-11');
echo '   diciembre: ingresos '.plata($d['ingresos'] / 100).' · egresos '.plata($d['egresos'] / 100).' · noviembre sigue '.plata($n['ingresos'] / 100).' / '.plata($n['egresos'] / 100)."\n";

// 55.4 Reportes: sueldos y alumnos (B12)
foreach (['/reportes/sueldos?mes=2026-11', '/reportes/alumnos?mes=2026-11', '/reportes?mes=2026-11', '/reportes/sueldos?mes=2026-12'] as $u) { [$st, $t] = http($ad, $u); echo "   $u: $st $t\n"; }
$rs = app(App\Services\ReporteSueldosService::class)->obtener('2026-11', null);
echo '   sueldos noviembre (claves): '.implode(',', array_keys($rs))."\n";
echo '   '.mb_substr(json_encode(array_map(fn ($v) => is_scalar($v) ? $v : null, $rs), JSON_UNESCAPED_UNICODE), 0, 500)."\n";

// 55.5 T98 y T65: un alumno con historia no se elimina; uno mal cargado, sí
$li = A('Pereyra, Liam');
paso('55.5a', 'el dueño intenta eliminar a Liam', enviar($ad, 'DELETE', "/alumnos/{$li->id}", [], fn ($r) => $al->destroy($r, $li->id)), 400);
[$st,, $h] = http($ad, "/alumnos/{$li->id}"); echo '   ficha de Liam: '.textoDe($h, 'No se puede eliminar', 180)."\n";
// 55.6 CAT-006 y GRU-006
$tc = app(TipoCajaWebController::class); $gc = app(GrupoWebController::class);
$ef = TipoCaja::where('nombre', 'Efectivo')->firstOrFail(); $ah = TipoCaja::where('nombre', 'Banco Nacion ahorro')->firstOrFail();
paso('55.6a', 'desactivar Efectivo', enviar($ad, 'PATCH', "/tipos-caja/{$ef->id}/toggle-activo", [], fn ($r) => $tc->toggleActivo($r, $ef->id), true), 400);
paso('55.6b', 'desactivar Banco Nacion ahorro', enviar($ad, 'PATCH', "/tipos-caja/{$ah->id}/toggle-activo", [], fn ($r) => $tc->toggleActivo($r, $ah->id), true), 200);
echo '   ahorro activo: '.(int) $ah->fresh()->activo."\n";
$adu = Grupo::whereHas('nivel', fn ($q) => $q->where('nombre', 'Adultos'))->firstOrFail();
paso('55.6c', 'desactivar Patín Adultos', enviar($ad, 'PATCH', "/grupos/{$adu->id}/toggle-activo", [], fn ($r) => $gc->toggleActivo($r, $adu->id), true), 400);

// 55.7 Hockey arranca: un alta mal escrita, la de bien, y una segunda ficha
$gh = Grupo::whereHas('deporte', fn ($q) => $q->where('nombre', 'Hockey'))->firstOrFail(); $ph1 = GrupoPlan::where('grupo_id', $gh->id)->where('clases_por_semana', 1)->firstOrFail();
$alta = fn (array $d) => enviar($sa, 'POST', '/alumnos', $d + ['celular' => '', 'deporte_id' => $gh->deporte_id, 'grupo_id' => $gh->id, 'plan_id' => $ph1->id, 'fecha_alta' => '2026-12-03', 'alta_token' => (string) Str::uuid()], fn ($r) => $al->store($r));
$beni = ['apellido' => 'Luna', 'nombre' => 'Benicio', 'dni' => '58123456', 'fecha_nacimiento' => '2014-06-02', 'nombre_tutor' => 'Marta Luna', 'telefono_tutor' => '1166112233'];
paso('55.7a', 'Sandra carga a Benicio «Lunna»', $alta(['apellido' => 'Lunna'] + $beni), 200);
$malo = Alumno::where('apellido', 'Lunna')->firstOrFail();
echo '   Lunna: '.DeudaCuota::where('alumno_id', $malo->id)->get()->map(fn ($x) => "$x->periodo ".plata($x->monto_original))->implode(' ').' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $malo->id)->where('tipo', 'INSCRIPCION')->value('monto_original')).' · eliminable: '.json_encode($malo->motivoNoEliminable(), JSON_UNESCAPED_UNICODE)."\n";
paso('55.7b', 'Sandra no ve Eliminar: forzado', enviar($sa, 'DELETE', "/alumnos/{$malo->id}", [], fn ($r) => $al->destroy($r, $malo->id)), 403);
paso('55.7c', 'el dueño lo elimina', enviar($ad, 'DELETE', "/alumnos/{$malo->id}", [], fn ($r) => $al->destroy($r, $malo->id)), 200);
echo '   Lunna existe: '.(Alumno::where('apellido', 'Lunna')->exists() ? 'SÍ' : 'no')."\n";
paso('55.7d', 'Benicio Luna, bien', $alta($beni), 200);
$be = A('Luna, Benicio');
echo '   Benicio: '.DeudaCuota::where('alumno_id', $be->id)->get()->map(fn ($x) => "$x->periodo $x->estado ".plata($x->monto_original))->implode(' ').' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $be->id)->where('tipo', 'INSCRIPCION')->value('monto_original'))."\n";
paso('55.7e', 'Benicio paga todo en efectivo', enviar($sa, 'POST', "/caja/cobrar/{$be->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'fecha_pago' => '2026-12-03'], fn ($r) => $caja->pagar($r, $be->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $be->id)->latest('id')->value('monto_final'))."\n";
$mia = A('Rodriguez, Mia');
paso('55.7f', 'segunda ficha de Mia Rodríguez, Hockey', enviar($sa, 'POST', '/alumnos', ['nombre' => $mia->nombre, 'apellido' => $mia->apellido, 'dni' => $mia->dni, 'fecha_nacimiento' => $mia->fecha_nacimiento->format('Y-m-d'), 'celular' => (string) $mia->celular, 'nombre_tutor' => (string) ($mia->nombre_tutor ?: 'Mónica Rodríguez'), 'telefono_tutor' => (string) ($mia->telefono_tutor ?: '1155001122'), 'deporte_id' => $gh->deporte_id, 'grupo_id' => $gh->id, 'plan_id' => $ph1->id, 'fecha_alta' => '2026-12-03', 'alta_token' => (string) Str::uuid()], fn ($r) => $al->store($r)), 200);
$mh = Alumno::where('dni', $mia->dni)->where('grupo_id', $gh->id)->firstOrFail();
echo '   Mia en Hockey: '.DeudaCuota::where('alumno_id', $mh->id)->get()->map(fn ($x) => "$x->periodo $x->estado ".plata($x->monto_original))->implode(' ').' · inscripciones de la persona '.DB::table('cargos_alumno')->where('dni', $mia->dni)->where('tipo', 'INSCRIPCION')->count()."\n";
paso('55.7g', 'Mia paga Hockey por MP', enviar($sa, 'POST', "/caja/cobrar/{$mh->id}", ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => ['2026-12'], 'fecha_pago' => '2026-12-03'], fn ($r) => $caja->pagar($r, $mh->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $mh->id)->latest('id')->value('monto_final'))."\n";
estado('fin 55, mediodía');
