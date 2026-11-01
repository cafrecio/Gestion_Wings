<?php
// Día 57, sábado 5/12, 21:30: con la caja validada, el dueño paga al organizador, cierra Caja Torneo, retira y cierra la prueba.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CashflowWebController, TipoCajaWebController, TransferenciaCashflowWebController};
use App\Models\{CashflowMovimiento, TipoCaja};
use App\Services\TransferenciaCashflowService;
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $jp = U('Juan Pablo Ledesma'); $cf = app(CashflowWebController::class); $t = app(TransferenciaCashflowWebController::class); $tc = app(TipoCajaWebController::class);
$sal = fn (string $n) => DB::selectOne("select t.saldo_inicial + coalesce((select sum(m.monto) from cashflow_movimientos m where m.tipo_caja_id=t.id),0) s from tipos_caja t where t.nombre=?", [$n])->s;
echo '   Caja Torneo: '.plata($sal('Caja Torneo'))."\n";
paso('57.7a', 'paga al organizador $40.000 por Caja Torneo', enviar($ad, 'POST', '/cashflow/movimiento', ['subrubro_id' => S('Pago al organizador'), 'tipo_caja_id' => M('Caja Torneo'), 'monto' => '40000', 'fecha' => '2026-12-05', 'observaciones' => 'Torneo de fin de año: organizador'], fn ($r) => $cf->store($r)), 200);
$resto = $sal('Caja Torneo'); echo '   queda '.plata($resto)."\n";
paso('57.7b', 'pasa lo que queda a Galicia', enviar($ad, 'POST', '/cashflow/transferencia', ['clave' => (string) Str::uuid(), 'salida_id' => M('Caja Torneo'), 'destino_id' => M('Banco Galicia'), 'monto' => (string) $resto, 'fecha' => '2026-12-05', 'observaciones' => 'Cierre de Caja Torneo'], fn ($r) => $t->store($r, app(TransferenciaCashflowService::class))), 200);
$ct = TipoCaja::where('nombre', 'Caja Torneo')->firstOrFail();
paso('57.7c', 'desactiva Caja Torneo', enviar($ad, 'PATCH', "/tipos-caja/{$ct->id}/toggle-activo", [], fn ($r) => $tc->toggleActivo($r, $ct->id), true), 200);
echo '   activa: '.(int) $ct->fresh()->activo.' · saldo '.plata($sal('Caja Torneo'))."\n";
[$st,, $h] = http($jp, '/caja/movimiento'); echo "   Juan Pablo, gasto ($st): Caja Torneo ".(str_contains($h, 'Caja Torneo') ? 'SE OFRECE' : 'no se ofrece')."\n";
[$st,, $h] = http($ad, '/cashflow?tipo_caja_id='.$ct->id.'&desde=2026-11-29&hasta=2026-12-05'); echo "   Cashflow filtrado por Caja Torneo ($st): ".textoDe($h, 'organizador', 50)."\n";
paso('57.8', 'retiro del dueño $300.000 por Galicia', enviar($ad, 'POST', '/cashflow/movimiento', ['subrubro_id' => S('Retiro de dueños'), 'tipo_caja_id' => M('Banco Galicia'), 'monto' => '300000', 'fecha' => '2026-12-05', 'observaciones' => 'Retiro de diciembre'], fn ($r) => $cf->store($r)), 200);
$cob = app(App\Services\CobranzaEstadoService::class);
foreach ($cob->listadoCobranza('DEUDOR') as $f) echo "   Deudor: {$f->apellido}, {$f->nombre} ".plata($f->deuda_total)."\n";
foreach (['2026-10', '2026-11', '2026-12'] as $p) { $r = app(App\Services\ReporteMensualService::class)->obtener($p); echo "   $p: ingresos ".plata($r['ingresos'] / 100).' · egresos '.plata($r['egresos'] / 100).' · resultado '.plata(($r['ingresos'] - $r['egresos']) / 100)."\n"; }
$vol = fn ($t) => DB::table($t)->count();
echo '   volumen: movimientos de caja '.$vol('movimientos_operativos').' · cobros '.$vol('pagos').' · marcas '.$vol('asistencias').' · Cashflow '.$vol('cashflow_movimientos').' · alumnos '.$vol('alumnos').' · liquidaciones '.$vol('liquidaciones').' · clases '.$vol('clases')."\n";
