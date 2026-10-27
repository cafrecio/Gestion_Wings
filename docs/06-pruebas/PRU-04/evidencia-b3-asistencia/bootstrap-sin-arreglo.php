<?php

require __DIR__.'/../evidencia-verificacion-t92-codex/bootstrap.php';
$originales = [
    'App\\Http\\Controllers\\ClaseWebController' => __DIR__.'/antes/ClaseWebController.php',
    'App\\Services\\ExcepcionCobranzaService' => __DIR__.'/antes/ExcepcionCobranzaService.php',
];
foreach (array_keys($originales) as $clase) {
    if (class_exists($clase, false)) throw new RuntimeException('La clase original debe cargarse antes que la actual.');
}
spl_autoload_register(function ($clase) use ($originales) {
    if (isset($originales[$clase])) require $originales[$clase];
}, true, true);
