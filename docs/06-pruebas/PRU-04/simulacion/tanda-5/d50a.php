<?php
// Día 50, sábado 28/11, 09:30: Pablo abre con doble clic; Sandra intenta abrir al mismo tiempo.
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
use App\Models\CajaOperativa;
$pa = U('Pablo Ledesma'); $sa = U('Sandra Vidal'); $caja = app(CajaWebController::class);
$p = app(App\Services\CajaService::class)->propuestaApertura();
$abrir = fn ($quien) => enviar($quien, 'POST', '/caja/apertura', ['efectivo_inicial' => $p['efectivo_heredado'] ?? 0, 'confirmacion' => '1', 'caja_origen_id' => $p['caja_origen_id']], fn ($r) => $caja->abrir($r));
echo '   propone '.plata($p['efectivo_heredado'] ?? 0)."\n";
paso('50.1a', 'Pablo abre', $abrir($pa), 200);
paso('50.1b', 'el segundo clic de Pablo', $abrir($pa));
paso('50.1c', 'Sandra abre al mismo tiempo', $abrir($sa), 400);
echo '   cajas abiertas: '.CajaOperativa::where('estado', 'ABIERTA')->count()."\n";
