<?php

/** Usa las tres clases originales del commit inicial, sin tocar el checkout compartido. */
require __DIR__.'/../evidencia-verificacion-t92-codex/bootstrap.php';
$originales = [
    'App\\Services\\CajaService' => __DIR__.'/antes/CajaService.php',
    'App\\Services\\PagoCuotaService' => __DIR__.'/antes/PagoCuotaService.php',
    'App\\Http\\Controllers\\CajaWebController' => __DIR__.'/antes/CajaWebController.php',
];
foreach (array_keys($originales) as $clase) {
    if (class_exists($clase, false)) throw new RuntimeException('La clase ya estaba cargada antes de la comparación.');
}
spl_autoload_register(function ($clase) use ($originales) {
    if (isset($originales[$clase])) require $originales[$clase];
}, true, true);
