<?php
// Día 46, martes 24/11, 21:00 — Pablo cierra con doble clic: el segundo envío no vuelve a cerrar.
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
use App\Models\CajaOperativa;
$pablo = U('Pablo Ledesma'); $caja = app(CajaWebController::class);
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $pablo->id)->firstOrFail();
$esp = app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
echo '   esperado de Pablo: '.plata($esp)."\n";
paso('46.5a', 'cierra', enviar($pablo, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => '5000'], fn ($r) => $caja->cerrar($r, $k->id)), 200);
paso('46.5b', 'el segundo clic', enviar($pablo, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => '5000'], fn ($r) => $caja->cerrar($r, $k->id)), 400); // el segundo clic
$k->refresh();
echo '   caja #'.$k->id.': '.$k->estado.' cambio '.plata($k->cambio_retenido).' entrega '.plata($k->efectivo_retirado)."\n";
estado('fin 46');
