<?php
// Día 57, sábado 5/12, 13:30: el torneo de fin de año. Se cancela Fútbol Avanzadas, Caja Torneo cobra, paga al organizador
// y se cierra. Enero se cobra adelantado, ya con el año nuevo. Último día de la prueba.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, CashflowWebController, ClaseWebController, TipoCajaWebController, TransferenciaCashflowWebController};
use App\Models\{Alumno, CashflowMovimiento, Clase, DeudaCuota, Grupo, Pago, TipoCaja, User};
use App\Services\TransferenciaCashflowService;
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $jp = U('Juan Pablo Ledesma'); $no = U('Nora Vidal'); $caja = app(CajaWebController::class); $cc = app(ClaseWebController::class); $cf = app(CashflowWebController::class);
$cob = app(App\Services\CobranzaEstadoService::class); $rep = app(App\Services\ReporteMensualService::class);
$fila = fn (Alumno $a) => $cob->listadoCobranza('TODOS')->firstWhere('id', $a->id);
estado('inicio 57 (después de la rutina)');
// 57.1 el torneo: Hernán lleva al equipo
$fa = Clase::whereDate('fecha', '2026-12-05')->whereTime('hora_inicio', '11:00:00')->where('grupo_id', 6)->firstOrFail();
paso('57.1', 'cancela Fútbol Avanzadas de las 11', enviar($ad, 'PATCH', "/clases/{$fa->id}/cancelar", ['motivo_cancelacion' => 'Torneo de fin de año: Hernán lleva al equipo'], fn ($r) => $cc->toggleCancelada($r, $fa->id)), 200);
// 57.2 inscripciones de último momento por Caja Torneo
foreach (['Herrera', 'Molina', 'Romero', 'Lopez'] as $f) {
    paso('57.2', "familia $f paga el torneo", enviar($jp, 'POST', '/caja/movimiento', ['subrubro_id' => S('Inscripciones'), 'tipo_caja_id' => M('Caja Torneo'), 'monto' => '3000', 'observaciones' => "Torneo de diciembre, último momento: familia $f"], fn ($r) => $caja->movimientoStore($r)), 200);
}
$sal = fn (string $n) => DB::selectOne("select t.saldo_inicial + coalesce((select sum(m.monto) from cashflow_movimientos m where m.tipo_caja_id=t.id),0) s from tipos_caja t where t.nombre=?", [$n])->s;
echo '   Caja Torneo, antes de validar la caja: '.plata($sal('Caja Torneo'))."\n";
// 57.3 Nora y Hockey
$gh = Grupo::whereHas('deporte', fn ($q) => $q->where('nombre', 'Hockey'))->firstOrFail(); $ch = Clase::whereDate('fecha', '2026-12-05')->where('grupo_id', $gh->id)->firstOrFail();
$items = Alumno::enGrupoElDia($ch->grupo_id, $ch->fecha)->where('activo', true)->get()->map(fn ($a) => ['alumno_id' => $a->id, 'presente' => $a->apellido === 'Luna' ? 0 : 1, 'motivo_exceso' => 'EXTRA'])->all();
echo '   lista de Hockey: '.count($items)."\n";
paso('57.3', 'Nora toma Hockey', enviar($no, 'POST', "/clases/{$ch->id}/asistencias", ['items' => $items], fn ($r) => $cc->storeAsistencias($r, $ch->id)), 200);
// 57.4 Alma paga lo prometido
$al = A('Navarro, Alma'); echo '   Alma: '.$fila($al)?->estado_cobranza.' '.plata($fila($al)?->deuda_total)."\n";
paso('57.4', 'Alma paga todo en efectivo', enviar($jp, 'POST', "/caja/cobrar/{$al->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => DeudaCuota::where('alumno_id', $al->id)->where('estado', 'PENDIENTE')->orderBy('periodo')->pluck('periodo')->all(), 'fecha_pago' => '2026-12-05'], fn ($r) => $caja->pagar($r, $al->id), true), 200);
echo '   cobro '.plata(Pago::where('alumno_id', $al->id)->latest('id')->value('monto_final')).' · Alma: '.$fila($al)?->estado_cobranza.' '.plata($fila($al)?->deuda_total)."\n";
// 57.5 COB-027: el dueño y Juan Pablo cobran el saldo de Carla al mismo tiempo
$ca = A('Morales, Carla'); echo '   Carla debe '.plata($fila($ca)?->deuda_total)."\n";
$cobraCa = fn (User $q, string $medio) => enviar($q, 'POST', "/caja/cobrar/{$ca->id}", ['tipo_caja_id' => M($medio), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => '15000'], 'fecha_pago' => '2026-12-05'], fn ($r) => $caja->pagar($r, $ca->id), true);
paso('57.5a', 'Juan Pablo cobra los $15.000 de Carla', $cobraCa($jp, 'Efectivo'), 200);
paso('57.5b', 'el dueño, con la pantalla vieja', $cobraCa($ad, 'Banco Galicia'), 400);
echo '   cobros de diciembre de Carla: '.Pago::where('alumno_id', $ca->id)->count().' · diciembre '.DeudaCuota::where('alumno_id', $ca->id)->where('periodo', '2026-12')->value('estado')."\n";
// 57.6 enero, con el precio nuevo y el año nuevo
$va = A('Lopez, Valentina'); $dicAntes = $rep->obtener('2026-12');
[$st,, $h] = http($jp, "/caja/cobrar/{$va->id}"); echo "   Cobrar de Valentina ($st): ".textoDe($h, 'Enero', 60)."\n";
paso('57.6a', 'Valentina López paga enero $48.000 por MP', enviar($jp, 'POST', "/caja/cobrar/{$va->id}", ['tipo_caja_id' => M('Mercado Pago'), 'periodos' => ['2027-01'], 'montos_cuota' => ['2027-01' => '48000'], 'fecha_pago' => '2026-12-05', 'confirmar_pago_adelantado' => '1'], fn ($r) => $caja->pagar($r, $va->id), true), 200);
$de = DeudaCuota::where('alumno_id', $va->id)->where('periodo', '2027-01')->first(); echo "   enero de Valentina: {$de?->estado} ".plata($de?->monto_original).' pagado '.plata($de?->monto_pagado)."\n";
$la = A('Morales, Julieta');
paso('57.6b', 'Julieta Morales paga enero con el precio viejo: $30.000', enviar($jp, 'POST', "/caja/cobrar/{$la->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2027-01'], 'montos_cuota' => ['2027-01' => '30000'], 'fecha_pago' => '2026-12-05', 'confirmar_pago_adelantado' => '1'], fn ($r) => $caja->pagar($r, $la->id), true));
$dj = DeudaCuota::where('alumno_id', $la->id)->where('periodo', '2027-01')->first(); echo "   enero de Julieta: {$dj?->estado} ".plata($dj?->monto_original).' pagado '.plata($dj?->monto_pagado)."\n";
foreach (['/reportes?mes=2027-01', '/cashflow?anio=2027'] as $u) { [$st, $t] = http($ad, $u); echo "   $u: $st $t\n"; }
estado('fin 57, antes de la noche');
