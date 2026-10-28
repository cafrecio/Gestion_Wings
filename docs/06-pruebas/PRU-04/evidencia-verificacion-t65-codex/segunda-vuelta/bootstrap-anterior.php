<?php

require dirname(__DIR__, 2).'/evidencia-verificacion-t92-codex/bootstrap.php';
$modoT65 = getenv('T65_ANTERIOR');
if (!in_array($modoT65, ['app', 'controller'], true)) throw new RuntimeException('Elegir app o controller anteriores.');
$anteriorT65 = $raiz.'/storage/app/t65-segunda-vuelta/13b85f68/';
if (!is_file($anteriorT65.'app/Http/Controllers/AlumnoWebController.php')) throw new RuntimeException('Falta preparar la copia anterior.');
spl_autoload_register(function ($clase) use ($modoT65, $anteriorT65) {
    if (!str_starts_with($clase, 'App\\')) return;
    if ($modoT65 === 'controller' && $clase !== App\Http\Controllers\AlumnoWebController::class) return;
    $archivo = $anteriorT65.'app/'.str_replace('\\', '/', substr($clase, 4)).'.php';
    if (!is_file($archivo)) throw new RuntimeException('Clase App ausente en 13b85f68: '.$clase);
    require $archivo;
}, true, true);
register_shutdown_function(function () use ($modoT65) {
    $cargadas = [];
    foreach (get_declared_classes() as $clase) {
        if (str_starts_with($clase, 'App\\')) $cargadas[$clase] = (new ReflectionClass($clase))->getFileName();
    }
    file_put_contents(__DIR__.'/cargas-'.$modoT65.'.json', json_encode($cargadas, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
});
