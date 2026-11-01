<?php
// Día 56, viernes 4/12, 09:00: Rocío deja abierta la apertura con lo propuesto. Se guarda lo que veía, para 56.1.
require __DIR__.'/lib.php';
$p = app(App\Services\CajaService::class)->propuestaApertura();
echo '   Rocío ve: propone '.plata($p['efectivo_heredado'] ?? 0).' · origen '.$p['caja_origen_id']."\n";
file_put_contents(__DIR__.'/fotos/apertura-vieja.json', json_encode($p));
