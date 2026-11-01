<?php
// Día 51, domingo 29/11, 20:00, el dueño desde su casa: catálogos, segundo dueño, Cashflow.
require __DIR__.'/lib.php';
use App\Http\Controllers\{CashflowWebController, CashflowCorreccionWebController, DeporteWebController, ProfesorWebController, SubrubroWebController, TipoCajaWebController, TransferenciaCashflowWebController, UsuarioWebController, LiquidacionWebController};
use App\Models\{CashflowMovimiento, Deporte, Liquidacion, Pago, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\TransferenciaCashflowService;
use Illuminate\Support\Str;
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal');
estado('inicio 51');
// 51.1 CAT-001
$dc = app(DeporteWebController::class);
$dep = fn (string $n, string $t) => enviar($ad, 'POST', '/deportes', ['nombre' => $n, 'tipo_liquidacion' => $t], fn ($r) => $dc->store($r));
paso('51.1a', 'deporte Hockey, por hora', $dep('Hockey', 'HORA'), 200);
paso('51.1b', 'hockey con espacios', $dep(' hockey ', 'COMISION'), 422);
// 51.1c T95: un profesor sin tarifa avisa en el momento
$pc0 = app(ProfesorWebController::class); $nat = Deporte::where('nombre', 'Hockey')->firstOrFail();
$prof = fn (array $mas) => enviar($ad, 'POST', '/profesores', ['nombre' => 'Nora', 'apellido' => 'Vidal', 'deporte_id' => $nat->id, 'dni' => '30111222', 'fecha_nacimiento' => '1985-03-10', 'direccion' => 'Av. Rivadavia 1200', 'localidad' => 'CABA', 'telefono' => '1166554433'] + $mas, fn ($r) => $pc0->store($r));
paso('51.1c', 'Nora Vidal, Hockey, sin valor hora', $prof([]), 422);
paso('51.1d', 'con valor hora $14.000', $prof(['valor_hora' => '14000']), 200);
// 51.1e el grupo y las clases: hasta acá Hockey no le servía a nadie (T66: una clase necesita profesora)
$nivP = App\Models\Nivel::where('nombre', 'Principiantes')->firstOrFail(); $gcH = app(App\Http\Controllers\GrupoWebController::class);
paso('51.1e', 'grupo Hockey Principiantes, 1 por semana $30.000 y 2 por semana $42.000', enviar($ad, 'POST', '/grupos', ['deporte_id' => $nat->id, 'nivel_id' => $nivP->id, 'planes' => [['clases_por_semana' => 1, 'precio_mensual' => '30000'], ['clases_por_semana' => 2, 'precio_mensual' => '42000']]], fn ($r) => $gcH->store($r)), 200);
$gh = App\Models\Grupo::where('deporte_id', $nat->id)->firstOrFail(); $nora = Profesor::where('apellido', 'Vidal')->where('nombre', 'Nora')->firstOrFail(); $clc = app(App\Http\Controllers\ClaseWebController::class);
$serie = fn (array $profes) => enviar($ad, 'POST', '/clases', ['grupo_id' => $gh->id, 'profesores' => $profes, 'tipo_creacion' => 'recurrente', 'fecha_desde' => '2026-12-03', 'fecha_hasta' => '2026-12-19', 'dias_semana' => [4, 6], 'horarios' => [4 => ['hora_inicio' => '16:00', 'hora_fin' => '17:00'], 6 => ['hora_inicio' => '12:00', 'hora_fin' => '13:00']]], fn ($r) => $clc->store($r));
paso('51.1f', 'clases de Hockey sin profesora', $serie([]), 422);
paso('51.1g', 'jueves 16:00 y sábados 12:00, del 3 al 19/12, con Nora', $serie([$nora->id]), 200);
echo '   clases de Hockey: '.App\Models\Clase::where('grupo_id', $gh->id)->count().' · sesiones '.App\Models\Clase::where('grupo_id', $gh->id)->orderBy('fecha')->pluck('fecha')->map(fn ($f) => $f->format('d/m'))->implode(' ')."\n";
// 51.2 CAT-005
$tc = app(TipoCajaWebController::class);
$medio = fn (array $d) => enviar($ad, 'POST', '/tipos-caja', $d, fn ($r) => $tc->store($r));
paso('51.2a', 'medio Caja Torneo, abreviatura de seis letras', $medio(['nombre' => 'Caja Torneo', 'abreviatura' => 'TORNEO', 'saldo_inicial' => '50000']), 422);
paso('51.2b', 'saldo negativo', $medio(['nombre' => 'Caja Torneo', 'abreviatura' => 'TOR', 'saldo_inicial' => '-1']), 422);
paso('51.2c', 'nombre repetido', $medio(['nombre' => 'mercado pago', 'abreviatura' => 'MPX', 'saldo_inicial' => '0']), 422);
paso('51.2d', 'bien, $50.000', $medio(['nombre' => 'Caja Torneo', 'abreviatura' => 'TOR', 'saldo_inicial' => '50000']), 200);
estado('con Caja Torneo');
// 51.3 CAT-008
$sub = Subrubro::where('nombre', 'Reparaciones menores')->firstOrFail();
$sc = app(SubrubroWebController::class);
paso('51.3', 'Reparaciones menores pasa a solo del dueño', enviar($ad, 'PUT', "/rubros/{$sub->rubro_id}/subrubros/{$sub->id}", ['nombre' => $sub->nombre, 'permitido_para' => 'ADMIN', 'afecta_caja' => (int) $sub->afecta_caja, 'clasificacion_resultado' => $sub->clasificacion_resultado], fn ($r) => $sc->update($r, $sub->rubro_id, $sub->id)), 200);
echo '   permitido para: '.$sub->fresh()->permitido_para.' · clasificación '.$sub->fresh()->clasificacion_resultado."\n";
// 51.4 CAT-009
$res = Subrubro::where('es_reservado_sistema', true)->where('nombre', 'Cuota Mensual')->first() ?? Subrubro::where('es_reservado_sistema', true)->firstOrFail();
paso('51.4a', "editar un reservado ({$res->nombre})", enviar($ad, 'PUT', "/rubros/{$res->rubro_id}/subrubros/{$res->id}", ['nombre' => 'Otro nombre', 'permitido_para' => 'ADMIN'], fn ($r) => $sc->update($r, $res->rubro_id, $res->id)), 400);
echo '   sigue: '.$res->fresh()->nombre."\n";
// 51.5 SIS-014
$pc = app(ProfesorWebController::class); $va = Profesor::where('apellido', 'Directora')->firstOrFail();
paso('51.5a', 'interruptor de Vanina', enviar($ad, 'PATCH', "/profesores/{$va->id}/toggle-activo", [], fn ($r) => $pc->toggleActivo($r, $va->id), true));
echo '   Vanina activa: '.(int) $va->fresh()->activo."\n";
paso('51.5b', 'la vuelve a activar', enviar($ad, 'PATCH', "/profesores/{$va->id}/toggle-activo", [], fn ($r) => $pc->toggleActivo($r, $va->id), true));
echo '   Vanina activa: '.(int) $va->fresh()->activo."\n";
$uc = app(UsuarioWebController::class);
paso('51.5c', 'interruptor de la cuenta propia', enviar($ad, 'PATCH', "/usuarios/{$ad->id}/toggle-activo", [], fn ($r) => $uc->toggleActivo($r, $ad->id), true));
echo '   dueño activo: '.(int) $ad->fresh()->activo."\n";
// 51.6 el segundo dueño y CSH-011
paso('51.6a', 'usuario de la socia', enviar($ad, 'POST', '/usuarios', ['name' => 'Vanina Socia', 'email' => 'vanina.socia@example.com', 'password' => 'Socia-del-club-2026!', 'password_confirmation' => 'Socia-del-club-2026!', 'rol' => 'ADMIN'], fn ($r) => $uc->store($r)), 200);
$so = U('Vanina Socia');
paso('51.6b', 'gasto Eventos $15.000 por Galicia', enviar($ad, 'POST', '/cashflow/movimiento', ['subrubro_id' => S('Eventos'), 'tipo_caja_id' => M('Banco Galicia'), 'monto' => '15000', 'fecha' => '2026-11-29', 'observaciones' => 'Sonido del torneo'], fn ($r) => app(CashflowWebController::class)->store($r)), 200);
$m = CashflowMovimiento::where('observaciones', 'Sonido del torneo')->firstOrFail(); $cc = app(CashflowCorreccionWebController::class);
paso('51.6c', 'la socia lo corrige a $12.000', enviar($so, 'PUT', "/cashflow/movimientos/{$m->id}", ['clave' => (string) Str::uuid(), 'version' => (int) $m->version_correccion, 'tipo_caja_id' => $m->tipo_caja_id, 'subrubro_id' => $m->subrubro_id, 'monto' => '12000', 'fecha' => '2026-11-29', 'observaciones' => 'Sonido del torneo', 'motivo' => 'El presupuesto final fue $12.000'], fn ($r) => $cc->update($r, $m->id)), 200);
echo '   movimiento: '.plata($m->fresh()->monto).' · cargó '.User::find($m->usuario_admin_id)?->name.' · correcciones '.json_encode(DB::table('cashflow_correcciones')->where('movimiento_id', $m->id)->first(), JSON_UNESCAPED_UNICODE)."\n";
// 51.7 CSH-022: transferencia de un mes anterior, anulada
$repOct = fn () => app(App\Services\ReporteMensualService::class)->obtener('2026-10');
$antes = $repOct(); $t = app(TransferenciaCashflowWebController::class);
$datos = ['clave' => (string) Str::uuid(), 'salida_id' => M('Banco Galicia'), 'destino_id' => M('Banco Nación'), 'monto' => '50000', 'fecha' => '2026-10-20', 'observaciones' => 'Quedó sin cargar en octubre'];
paso('51.7a', 'Galicia a Nación $50.000 con fecha 20/10, sin confirmar', enviar($ad, 'POST', '/cashflow/transferencia', $datos, fn ($r) => $t->store($r, app(TransferenciaCashflowService::class))));
paso('51.7b', 'confirmando', enviar($ad, 'POST', '/cashflow/transferencia', $datos + ['confirmar_fecha_vieja' => '1'], fn ($r) => $t->store($r, app(TransferenciaCashflowService::class))), 200);
$fila = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_TRANSFERENCIA)->orderByDesc('id')->first();
paso('51.7c', 'la anula', enviar($ad, 'POST', "/cashflow/transferencias/{$fila->id}/anular", ['motivo' => 'Ya estaba cargada: me confundí'], fn ($r) => $t->anular($r, $fila->id, app(App\Services\AnulacionTransferenciaCashflowService::class))), 200);
$des = $repOct(); echo '   octubre ingresos/egresos: '.plata($antes['ingresos'] / 100).' / '.plata($antes['egresos'] / 100).' → '.plata($des['ingresos'] / 100).' / '.plata($des['egresos'] / 100)."\n";
// 51.8 CSH-009: lo que no se corrige
$val = CashflowMovimiento::where('referencia_tipo', 'CAJA_OPERATIVA')->first() ?? CashflowMovimiento::whereNotNull('referencia_tipo')->where('referencia_tipo', '!=', 'PAGO_CUOTA')->first();
$cob = CashflowMovimiento::where('referencia_tipo', 'PAGO_CUOTA')->first();
foreach (['de una caja validada' => $val, 'cobro del dueño' => $cob] as $que => $x) {
    if (!$x) { echo "   no hay movimiento $que\n"; continue; }
    paso('51.8', "forzar eliminar un movimiento $que (#{$x->id}, {$x->referencia_tipo})", enviar($ad, 'DELETE', "/cashflow/movimientos/{$x->id}", ['clave' => (string) Str::uuid(), 'version' => (int) $x->version_correccion, 'motivo' => 'Prueba de forzar'], fn ($r) => $cc->destroy($r, $x->id)), 400);
}
estado('fin 51');
