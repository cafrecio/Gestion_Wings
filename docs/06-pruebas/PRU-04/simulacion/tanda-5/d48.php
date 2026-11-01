<?php
// Día 48, jueves 26/11, por pantalla: T94 y T99 en grupos, T102 en niveles, T96 en la configuración de Efectivo,
// T97 y T106 en Cashflow, un ajuste de saldo y un alta con fecha de un mes cerrado.
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController, CashflowWebController, CashflowCorreccionWebController, ClaseWebController, GrupoWebController, NivelWebController, TransferenciaCashflowWebController, TipoCajaWebController};
use App\Models\{Alumno, CajaOperativa, CashflowMovimiento, Clase, DeudaCuota, Grupo, GrupoPlan, Nivel, Pago, TipoCaja};
use App\Services\TransferenciaCashflowService;
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal'); $he = U('Hernán Quintana');
$gc = app(GrupoWebController::class); $nc = app(NivelWebController::class); $caja = app(CajaWebController::class); $al = app(AlumnoWebController::class);
$g = fn (string $dep, string $niv) => Grupo::whereHas('deporte', fn ($d) => $d->where('nombre', $dep))->whereHas('nivel', fn ($n) => $n->where('nombre', $niv))->firstOrFail();
$plan = fn (Grupo $gr, int $x) => GrupoPlan::where('grupo_id', $gr->id)->where('clases_por_semana', $x)->firstOrFail();
estado('inicio 48 (después de la rutina)');
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $sa->id)->firstOrFail();
$esp = fn () => app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];

// 48.1 T94: sacar frecuencias
$fi = $g('Fútbol', 'Infantiles'); $fed = $g('Patín', 'Federadas');
$quita = fn (GrupoPlan $p) => enviar($ad, 'DELETE', "/grupo-planes/{$p->id}", [], fn ($r) => $gc->destroyPlan($p->id));
paso('48.1a', 'Infantiles 1x, con Renzo', $quita($plan($fi, 1)), 400);
paso('48.1b', 'Infantiles 2x, nunca usada', $quita($plan($fi, 2)), 200);
echo '   filas de Infantiles: '.GrupoPlan::where('grupo_id', $fi->id)->count()."\n";
$f1 = $plan($fed, 1); $precioF1 = $f1->precio_mensual;
paso('48.1c', 'Federadas 1x, usada antes', $quita($f1), 200);
echo '   Federadas 1x: activo '.(int) $f1->fresh()->activo.' · historia alumno_planes '.DB::table('alumno_planes')->where('plan_id', $f1->id)->count()."\n";
paso('48.1d', 'reactiva Federadas 1x a $42.000', enviar($ad, 'POST', "/grupos/{$fed->id}/planes", ['clases_por_semana' => 1, 'precio_mensual' => '42000'], fn ($r) => $gc->storePlan($r, $fed->id)), 200);
echo '   Federadas 1x: id '.$f1->fresh()->id.' activo '.(int) $f1->fresh()->activo.' precio '.plata($f1->fresh()->precio_mensual).' · filas '.GrupoPlan::where('grupo_id', $fed->id)->count()."\n";

// 48.2 T99 y el nivel nuevo: Patín con dos filas iguales, sin página de error
$nivelInf = Nivel::where('nombre', 'Infantiles')->firstOrFail(); $pat = App\Models\Deporte::where('nombre', 'Patín')->firstOrFail();
$crear = fn (array $planes) => enviar($ad, 'POST', '/grupos', ['deporte_id' => $pat->id, 'nivel_id' => $nivelInf->id, 'planes' => $planes], fn ($r) => $gc->store($r));
paso('48.2a', 'dos filas de 1 por semana', $crear([['clases_por_semana' => 1, 'precio_mensual' => '22000'], ['clases_por_semana' => 1, 'precio_mensual' => '24000']]), 422);
paso('48.2b', 'una fila', $crear([['clases_por_semana' => 1, 'precio_mensual' => '22000']]), 200);
$pi = $g('Patín', 'Infantiles');
paso('48.2c', 'saca la única frecuencia', $quita($plan($pi, 1)), 400);

// 48.3 T102: inactivar un nivel y pasar un grupo a otro
$niv = fn (Nivel $n) => enviar($ad, 'PATCH', "/niveles/{$n->id}/toggle-activo", [], fn ($r) => $nc->toggleActivo($r, $n->id), true);
paso('48.3a', 'inactiva Infantiles, con dos grupos activos', $niv($nivelInf), 400);
paso('48.3b', 'crea el nivel Mini', enviar($ad, 'POST', '/niveles', ['nombre' => 'Mini', 'descripcion' => ''], fn ($r) => $nc->store($r)), 200);
$mini = Nivel::where('nombre', 'Mini')->firstOrFail(); $pplan = $plan($pi, 1);
$upd = fn (int $nivelId) => enviar($ad, 'PUT', "/grupos/{$pi->id}", ['nivel_id' => $nivelId, 'planes' => [['id' => $pplan->id, 'clases_por_semana' => 1, 'precio_mensual' => '22000']]], fn ($r) => $gc->update($r, $pi->id));
paso('48.3c', 'pasa Patín Infantiles a Principiantes, ya ocupado', $upd(Nivel::where('nombre', 'Principiantes')->value('id')), 400);
paso('48.3d', 'pasa Patín Infantiles a Mini', $upd($mini->id), 200);
echo '   grupo #'.$pi->id.' nivel '.$pi->fresh()->nivel->nombre."\n";
paso('48.3e', 'inactiva Mini, con el grupo activo', $niv($mini), 400);
paso('48.3f', 'desactiva el grupo', enviar($ad, 'PATCH', "/grupos/{$pi->id}/toggle-activo", [], fn ($r) => $gc->toggleActivo($r, $pi->id), true), 200);
paso('48.3g', 'inactiva Mini', $niv($mini), 200);
paso('48.3h', 'activa el grupo con el nivel inactivo', enviar($ad, 'PATCH', "/grupos/{$pi->id}/toggle-activo", [], fn ($r) => $gc->toggleActivo($r, $pi->id), true), 400);
echo '   Mini activo '.(int) $mini->fresh()->activo.' · grupo activo '.(int) $pi->fresh()->activo."\n";

// 48.4 GRU-005: pantalla vieja
$adu = $g('Patín', 'Adultos'); $a1 = $plan($adu, 1); $a2 = $plan($adu, 2);
$fviejo = [['id' => $a1->id, 'clases_por_semana' => 1, 'precio_mensual' => '33000'], ['id' => $a2->id, 'clases_por_semana' => 2, 'precio_mensual' => '43000']];
paso('48.4a', 'otra pestaña: suma 3 por semana', enviar($ad, 'POST', "/grupos/{$adu->id}/planes", ['clases_por_semana' => 3, 'precio_mensual' => '52000'], fn ($r) => $gc->storePlan($r, $adu->id)), 200);
// GRU-005 pide rechazarlo. Hoy guarda y se lleva puesta la frecuencia que agregó la otra pestaña: T110.
paso('48.4b', 'pantalla vieja: cambia precios', enviar($ad, 'PUT', "/grupos/{$adu->id}", ['planes' => $fviejo], fn ($r) => $gc->update($r, $adu->id)), 200);
echo '   Adultos: '.GrupoPlan::where('grupo_id', $adu->id)->orderBy('clases_por_semana')->get()->map(fn ($p) => $p->clases_por_semana.'x '.plata($p->precio_mensual))->implode(' · ')."\n";

// 48.5 T96: Efectivo ya configurado
paso('48.5a', 'pedido directo a la configuración', enviar($ad, 'POST', '/caja/configuracion', ['saldo_inicial' => '999'], fn ($r) => $caja->configurarMostrador($r)), 400);
[$st, $t] = http($ad, '/caja/configuracion'); echo "   /caja/configuracion: $st $t\n";
$ef = TipoCaja::where('nombre', 'Efectivo')->firstOrFail(); echo '   Efectivo saldo inicial '.plata($ef->saldo_inicial)."\n";
// 48.6 Ajuste de saldo: cambia el saldo y no el resultado
$antes = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
paso('48.6', 'Ajuste de saldo: salida, $1.200', enviar($ad, 'POST', '/cashflow/movimiento', ['subrubro_id' => S('Ajuste de saldo: salida'), 'tipo_caja_id' => M('Efectivo'), 'monto' => '1200', 'fecha' => '2026-11-26', 'observaciones' => 'Arqueo de la caja fuerte: faltan $1.200'], fn ($r) => app(CashflowWebController::class)->store($r)), 200);
$desp = app(App\Services\ReporteMensualService::class)->obtener('2026-11');
echo '   resultado antes/después: '.plata(($antes['ingresos'] - $antes['egresos']) / 100).' / '.plata(($desp['ingresos'] - $desp['egresos']) / 100).' · disponible '.plata($antes['disponible'] / 100).' → '.plata($desp['disponible'] / 100)."\n";

// 48.7 T97: transferencia equivocada, anulada; 48.8 el depósito real se queda
$tc = app(TransferenciaCashflowWebController::class); $ts = app(TransferenciaCashflowService::class);
$tr = fn (string $de, string $a, string $monto, string $fecha, string $obs, array $mas = []) => enviar($ad, 'POST', '/cashflow/transferencia', ['clave' => (string) Str::uuid(), 'salida_id' => M($de), 'destino_id' => M($a), 'monto' => $monto, 'fecha' => $fecha, 'observaciones' => $obs] + $mas, fn ($r) => $tc->store($r, $ts));
$antesRep = $desp;
paso('48.7a', 'MP a Galicia, $400.000', $tr('Mercado Pago', 'Banco Galicia', '400000', '2026-11-26', 'Era a Nación, no a Galicia'), 200);
$ult = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->orderByDesc('id')->take(2)->get()->sortBy('id')->values();
$anul = fn (int $id, ?string $m) => enviar($ad, 'POST', "/cashflow/transferencias/{$id}/anular", ['motivo' => $m], fn ($r) => $tc->anular($r, $id, app(App\Services\AnulacionTransferenciaCashflowService::class)));
paso('48.7b', 'anula sin motivo', $anul($ult[1]->id, ''), 422);
paso('48.7c', 'anula desde la fila de entrada', $anul($ult[1]->id, 'Cuenta de destino equivocada'), 200);
paso('48.7d', 'la otra pestaña anula otra vez', $anul($ult[0]->id, 'Otro motivo cualquiera'), 400);
paso('48.7e', 'forzar corregir una fila', enviar($ad, 'DELETE', "/cashflow/movimientos/{$ult[0]->id}", ['clave' => (string) Str::uuid(), 'version' => (int) $ult[0]->version_correccion, 'motivo' => 'Corregir una sola fila'], fn ($r) => app(CashflowCorreccionWebController::class)->destroy($r, $ult[0]->id)), 400);
paso('48.8', 'depósito real: Efectivo a Galicia, $800.000', $tr('Efectivo', 'Banco Galicia', '800000', '2026-11-26', 'Depósito del efectivo de la semana'), 200);
estado('después de las transferencias');
// 48.9 T106: las tarjetas
echo '   Cashflow semana sin filtro: '.tarjetas($ad, '/cashflow?periodo=semana&fecha=2026-11-26')."\n";
echo '   Cashflow semana con Galicia: '.tarjetas($ad, '/cashflow?periodo=semana&fecha=2026-11-26&tipo_caja_id='.M('Banco Galicia'))."\n";
echo '   Historial 23 al 26/11: '.tarjetas($ad, '/caja/historial?desde=2026-11-23&hasta=2026-11-26')."\n";

// 48.10 ALU-006: alta con fecha de un mes cerrado
$fp = $g('Fútbol', 'Principiantes'); $p1 = $plan($fp, 1);
$alta = fn (array $mas) => enviar($sa, 'POST', '/alumnos', ['nombre' => 'Ciro', 'apellido' => 'Paz', 'dni' => '49111222', 'fecha_nacimiento' => '2015-04-11', 'celular' => '', 'nombre_tutor' => 'Laura Paz', 'telefono_tutor' => '1144556600', 'deporte_id' => $fp->deporte_id, 'grupo_id' => $fp->id, 'plan_id' => $p1->id, 'fecha_alta' => '2026-08-10', 'alta_token' => (string) Str::uuid()] + $mas, fn ($r) => $al->store($r));
paso('48.10a', 'ingreso 10/8, sin elegir', $alta([]), 422);
paso('48.10b', 'ingreso 10/8, elige generar', $alta(['generar_cuota_actual' => '1']), 200);
$ci = A('Paz, Ciro');
echo '   Ciro: '.DeudaCuota::where('alumno_id', $ci->id)->get()->map(fn ($d) => "$d->periodo:$d->estado ".plata($d->monto_original))->implode(' ').' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id', $ci->id)->where('tipo', 'INSCRIPCION')->value('monto_original')).' · ingreso '.$ci->fecha_alta->format('d/m/Y');
$fila = app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id', $ci->id); echo ' · '.$fila?->estado_cobranza.' '.plata($fila?->deuda_total)."\n";
paso('48.10c', 'Ciro paga todo en efectivo', enviar($sa, 'POST', "/caja/cobrar/{$ci->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-11'], 'fecha_pago' => '2026-11-26'], fn ($r) => $caja->pagar($r, $ci->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $ci->id)->latest('id')->value('monto_final')).' · esperado '.plata($esp())."\n";
// 48.11 Hernán toma Fútbol Avanzadas por pantalla
$cl = Clase::whereDate('fecha', '2026-11-26')->whereTime('hora_inicio', '19:00:00')->firstOrFail();
$items = Alumno::enGrupoElDia($cl->grupo_id, $cl->fecha)->where('activo', true)->get()->map(fn ($a) => ['alumno_id' => $a->id, 'presente' => $a->apellido === 'Silva' ? 0 : 1, 'motivo_exceso' => 'EXTRA'])->all();
paso('48.11', 'Hernán toma Fútbol Avanzadas, falta Simón Silva', enviar($he, 'POST', "/clases/{$cl->id}/asistencias", ['items' => $items], fn ($r) => app(ClaseWebController::class)->storeAsistencias($r, $cl->id)), 200);
estado('fin 48 (antes de la noche)');
