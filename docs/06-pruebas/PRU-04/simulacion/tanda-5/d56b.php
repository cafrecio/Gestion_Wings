<?php
// Día 56, viernes 4/12, 14:00: cambio de turno con la pantalla vieja, rectificar, usuarios, alta, mes pasado sin cuota,
// quienes vuelven a clase, aumento de enero y una clase validada por error.
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController, ClaseWebController, GrupoWebController, UsuarioWebController};
use App\Models\{Alumno, CajaOperativa, Clase, DeudaCuota, Grupo, GrupoPlan, Pago, Profesor, User};
use App\Services\PagoCuotaService;
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $ro = U('Rocío Paz'); $pa = U('Pablo Ledesma'); $lu = U('Lucía Gaitán'); $caja = app(CajaWebController::class); $al = app(AlumnoWebController::class);
$cob = app(App\Services\CobranzaEstadoService::class);
$fila = fn (Alumno $a) => $cob->listadoCobranza('TODOS')->firstWhere('id', $a->id);
// 56.1 CAJ-011
$vieja = json_decode(file_get_contents(__DIR__.'/fotos/apertura-vieja.json'), true);
$abrir = fn (array $p) => enviar($ro, 'POST', '/caja/apertura', ['efectivo_inicial' => $p['efectivo_heredado'] ?? 0, 'confirmacion' => '1', 'caja_origen_id' => $p['caja_origen_id']], fn ($r) => $caja->abrir($r));
paso('56.1a', 'Rocío abre con la pantalla vieja', $abrir($vieja), 400);
$nueva = app(App\Services\CajaService::class)->propuestaApertura(); echo '   al recargar propone '.plata($nueva['efectivo_heredado'])."\n";
paso('56.1b', 'recarga y abre', $abrir($nueva), 200);
$kr = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $ro->id)->firstOrFail();
$kp = CajaOperativa::where('usuario_operativo_id', $pa->id)->orderByDesc('id')->firstOrFail();
// 56.2 CAJ-014
$rect = fn (User $q, CajaOperativa $k, string $mot) => enviar($q, 'POST', "/caja/{$k->id}/rectificar", ['efectivo_inicial' => '11000', 'motivo_rectificacion' => $mot], fn ($r) => $caja->guardarRectificacion($r, $k->id));
paso('56.2a', 'Pablo rectifica la caja de Rocío', $rect($pa, $kr, 'Conté mal el cambio'), 403);
paso('56.2b', 'Pablo rectifica la suya ya cerrada', $rect($pa, $kp, 'Conté mal el cambio'), 400);
paso('56.2c', 'Rocío con un motivo de dos letras', $rect($ro, $kr, 'Ya'), 422);
echo '   caja de Rocío: inicial '.plata($kr->fresh()->efectivo_inicial).' · caja de Pablo '.$kp->fresh()->estado."\n";
// 56.3 USU-004 y USU-008
$uc = app(UsuarioWebController::class);
$upd = fn (User $u, array $d) => enviar($ad, 'PUT', "/usuarios/{$u->id}", $d + ['name' => $u->name, 'email' => $u->email, 'rol' => $u->rol], fn ($r) => $uc->update($r, $u->id));
paso('56.3a', 'cambiarle el rol a Rocío, con la caja abierta', $upd($ro, ['rol' => 'PROFESOR', 'profesor_id' => Profesor::where('apellido', 'Directora')->value('id')]), 400);
paso('56.3b', 'Pablo pasa a llamarse Juan Pablo Ledesma', $upd($pa, ['name' => 'Juan Pablo Ledesma']), 200);
echo '   subrubro: '.DB::table('subrubros')->where('nombre', 'like', 'Op-%Ledesma')->value('nombre').' · cierres a su nombre '.CajaOperativa::where('usuario_operativo_id', $pa->id)->count()."\n";
$go = U('Gonzalo Ibarra');
paso('56.3c', 'Gonzalo pasa a operativa', $upd($go, ['rol' => 'OPERATIVO']), 200);
$go->refresh(); echo '   Gonzalo: rol '.$go->rol.' · profesor vinculado '.json_encode($go->profesor_id).' · subrubro '.json_encode(DB::table('subrubros')->where('nombre', 'like', '%Gonzalo%')->pluck('nombre'), JSON_UNESCAPED_UNICODE).' · clases futuras '.Clase::whereHas('profesores', fn ($q) => $q->where('apellido', 'Ibarra'))->whereDate('fecha', '>=', today())->where('cancelada', false)->count()."\n";
// 56.4 otra alta de Hockey
$gh = Grupo::whereHas('deporte', fn ($q) => $q->where('nombre', 'Hockey'))->firstOrFail(); $ph1 = GrupoPlan::where('grupo_id', $gh->id)->where('clases_por_semana', 1)->firstOrFail();
paso('56.4a', 'Ámbar Funes, Hockey', enviar($ro, 'POST', '/alumnos', ['apellido' => 'Funes', 'nombre' => 'Ámbar', 'dni' => '58234567', 'fecha_nacimiento' => '2012-09-21', 'celular' => '', 'nombre_tutor' => 'Graciela Funes', 'telefono_tutor' => '1144332211', 'deporte_id' => $gh->deporte_id, 'grupo_id' => $gh->id, 'plan_id' => $ph1->id, 'fecha_alta' => '2026-12-04', 'alta_token' => (string) Str::uuid()], fn ($r) => $al->store($r)), 200);
$am = A('Funes, Ámbar');
paso('56.4b', 'Ámbar paga en efectivo', enviar($ro, 'POST', "/caja/cobrar/{$am->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'fecha_pago' => '2026-12-04'], fn ($r) => $caja->pagar($r, $am->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $am->id)->latest('id')->value('monto_final'))."\n";
// 56.5 COB-029: Paulina empezó en septiembre y ese mes nunca se le generó
$pl = A('Silva, Paulina');
[$st,, $h] = http($ad, "/alumnos/{$pl->id}"); echo '   ficha de Paulina, dueño: '.textoDe($h, 'Meses sin cuota generada', 140)."\n";
[$st,, $h] = http($ro, "/alumnos/{$pl->id}"); echo '   ficha de Paulina, Rocío: '.(str_contains($h, 'Meses sin cuota generada') ? 'LO VE' : 'no lo ve')."\n";
$crear = fn (User $q, string $per) => enviar($q, 'POST', "/alumnos/{$pl->id}/cuotas-pasadas", ['periodo' => $per], fn ($r) => $al->crearCuotaPasada($r, $pl->id, app(PagoCuotaService::class)));
paso('56.5a', 'Rocío la crea, forzado', $crear($ro, '2026-09'), 403);
paso('56.5b', 'el dueño, un mes anterior al ingreso', $crear($ad, '2026-08'), 400);
paso('56.5c', 'el dueño, septiembre', $crear($ad, '2026-09'), 200);
paso('56.5d', 'otra vez septiembre', $crear($ad, '2026-09'), 400);
echo '   Paulina: '.$fila($pl)?->estado_cobranza.' '.plata($fila($pl)?->deuda_total)."\n";
paso('56.5e', 'Rocío cobra septiembre en efectivo', enviar($ro, 'POST', "/caja/cobrar/{$pl->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-09'], 'fecha_pago' => '2026-12-04'], fn ($r) => $caja->pagar($r, $pl->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $pl->id)->latest('id')->value('monto_final')).' · Paulina: '.$fila($pl)?->estado_cobranza.' '.plata($fila($pl)?->deuda_total)."\n";
$rep = app(App\Services\ReporteMensualService::class); foreach (['2026-09', '2026-12'] as $p) { $r = $rep->obtener($p); echo "   $p: ingresos ".plata($r['ingresos'] / 100)."\n"; }
// 56.6 Tobías paga lo que prometió
$to = A('Vera, Tobías'); echo '   Tobías: '.$fila($to)?->estado_cobranza.' '.plata($fila($to)?->deuda_total)."\n";
paso('56.6', 'la mamá de Tobías paga todo por MP', enviar($ro, 'POST', "/caja/cobrar/{$to->id}", ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => DeudaCuota::where('alumno_id', $to->id)->whereIn('estado', ['PENDIENTE'])->orderBy('periodo')->pluck('periodo')->all(), 'fecha_pago' => '2026-12-04'], fn ($r) => $caja->pagar($r, $to->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $to->id)->latest('id')->value('monto_final')).' · Tobías: '.$fila($to)?->estado_cobranza.' '.plata($fila($to)?->deuda_total)."\n";
// 56.7 aumento de enero
$gc = app(GrupoWebController::class); $pp = Grupo::find(1);
$fp = GrupoPlan::where('grupo_id', 1)->where('activo', true)->orderBy('clases_por_semana')->get()->map(fn ($x) => ['id' => $x->id, 'clases_por_semana' => $x->clases_por_semana, 'precio_mensual' => $x->clases_por_semana == 1 ? '33000' : '48000'])->all();
paso('56.7', 'Patín Principiantes: $33.000 y $48.000 desde enero', enviar($ad, 'PUT', '/grupos/1', ['planes' => $fp], fn ($r) => $gc->update($r, 1)), 200);
$mc = A('Castro, Martina');
echo '   diciembre ya generado de Martina Castro: '.plata(DeudaCuota::where('alumno_id', $mc->id)->where('periodo', '2026-12')->value('monto_original')).' · precio del plan para diciembre '.plata(GrupoPlan::where('grupo_id', 1)->where('clases_por_semana', 1)->first()->precioParaPeriodo('2026-12', $mc->fecha_alta)).' · para enero '.plata(GrupoPlan::where('grupo_id', 1)->where('clases_por_semana', 1)->first()->precioParaPeriodo('2027-01', $mc->fecha_alta))."\n";
// 56.8 CLA-024: una clase de más adelante, validada por error
$cc = app(ClaseWebController::class); $fut = Clase::whereDate('fecha', '2026-12-19')->where('grupo_id', $gh->id)->firstOrFail();
[$st,, $h] = http($ad, "/clases/{$fut->id}"); echo '   ficha de la clase del 19/12: botón Validar '.(preg_match('/>\s*Validar\s*</', $h) ? 'SE OFRECE' : 'no se ofrece')."\n";
paso('56.8', 'el dueño valida la clase del 19/12 por error', enviar($ad, 'PATCH', "/clases/{$fut->id}/validar", [], fn ($r) => $cc->toggleValidada($fut->id)));
[$st,, $h] = http($ad, "/clases/{$fut->id}"); echo '   validada: '.(int) $fut->fresh()->validada_para_liquidacion.' · botón Desvalidar '.(preg_match('/>\s*Desvalidar\s*</', $h) ? 'SE OFRECE' : 'no se ofrece')."\n";
estado('fin 56, tarde');
