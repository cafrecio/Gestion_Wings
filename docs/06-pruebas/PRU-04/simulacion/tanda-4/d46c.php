<?php
// Día 46, martes 24/11, 21:00 — Pablo cierra con doble clic: el segundo envío no vuelve a cerrar.
require __DIR__.'/lib.php';
use App\Http\Controllers\CajaWebController;
use App\Models\CajaOperativa;
$pablo = U('Pablo Ledesma'); $caja = app(CajaWebController::class);
$k = CajaOperativa::where('estado', 'ABIERTA')->where('usuario_operativo_id', $pablo->id)->firstOrFail();
$esp = app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
echo '   esperado de Pablo: '.plata($esp)."\n";
$em = A('Medina, Emilia');
paso('46.5p', 'otra pestaña: diciembre de Emilia $44.000 en efectivo', enviar($pablo, 'POST', "/caja/cobrar/{$em->id}", ['tipo_caja_id' => M('Efectivo'), 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => '44000'], 'fecha_pago' => '2026-11-24', 'confirmar_pago_adelantado' => '1'], fn ($r) => $caja->pagar($r, $em->id), true), 200);
$esp2 = app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado']; echo '   esperado con el cobro: '.plata($esp2)."\n";
paso('46.5q', 'cierra con el contado de la pantalla vieja', enviar($pablo, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => '5000'], fn ($r) => $caja->cerrar($r, $k->id)));
$k->refresh(); echo '   caja: '.$k->estado.' esperado '.plata($k->efectivo_esperado).' contado '.plata($k->efectivo_contado).' diferencia '.plata($k->diferencia_efectivo)."\n";
$esp = $esp2;
if ($k->estado === 'ABIERTA') paso('46.5a', 'cierra', enviar($pablo, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => '5000'], fn ($r) => $caja->cerrar($r, $k->id)), 200);
paso('46.5b', 'el segundo clic', enviar($pablo, 'POST', "/caja/{$k->id}/cerrar", ['efectivo_contado' => (string) $esp, 'cambio_retenido' => '5000'], fn ($r) => $caja->cerrar($r, $k->id)), 400); // el segundo clic
$k->refresh();
echo '   caja #'.$k->id.': '.$k->estado.' cambio '.plata($k->cambio_retenido).' entrega '.plata($k->efectivo_retirado)."\n";
estado('fin 46');
