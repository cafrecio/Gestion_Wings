<?php
// Día 55, noche: CAJ-040. El dueño valida por pantalla: sin todos los tildes no deja.
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
use App\Models\CajaOperativa;
$ad = U('Admin Prueba'); $sa = U('Sandra Vidal'); $cc = app(CajaWebController::class);
$k = CajaOperativa::where('estado', 'CERRADA')->where('usuario_operativo_id', $sa->id)->orderByDesc('id')->firstOrFail();
$movs = $k->movimientos()->get();
echo "   caja #{$k->id}: {$movs->count()} renglones\n";
$tilda = fn ($m) => enviar($ad, 'POST', "/cajas/{$k->id}/movimientos/{$m->id}/verificar", ['verificado' => true, 'huella' => $m->huellaVerificacion()], fn ($r) => $cc->verificarMovimiento($r, $k->id, $m->id));
paso('55.9a', 'valida sin tildar nada', enviar($ad, 'POST', "/cajas/{$k->id}/validar", [], fn ($r) => $cc->validar($k->id)), 400);
foreach ($movs->take(3) as $m) $tilda($m);
paso('55.9b', 'con tres tildes', enviar($ad, 'POST', "/cajas/{$k->id}/validar", [], fn ($r) => $cc->validar($k->id)), 400);
foreach ($movs->slice(3) as $m) $tilda($m);
paso('55.9c', 'con todos', enviar($ad, 'POST', "/cajas/{$k->id}/validar", [], fn ($r) => $cc->validar($k->id)), 200);
