<?php
// No usa la base: prueba el emparejamiento del router ya registrado, sin ejecutar acciones.
$raiz = dirname(__DIR__, 4);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex') throw new RuntimeException('Solo base autorizada.');
$numericos = ['id', 'movId', 'rubroId', 'cajaId', 'alumnoId', 'planId', 'pagoId', 'liquidacionId', 'detalleId'];
$control = []; $errores = [];
foreach (app('router')->getRoutes() as $ruta) {
    $parametros = array_values(array_intersect($ruta->parameterNames(), $numericos));
    foreach ($parametros as $parametro) {
        $url = preg_replace('/\{[^}]+\}/', '1', $ruta->uri());
        $urlTexto = str_replace('{'.$parametro.'}', 'abc', $ruta->uri());
        $urlTexto = preg_replace('/\{[^}]+\}/', '1', $urlTexto);
        $numeroAceptado = $ruta->matches(Illuminate\Http\Request::create('/'.$url, $ruta->methods()[0]));
        $textoRechazado = !$ruta->matches(Illuminate\Http\Request::create('/'.$urlTexto, $ruta->methods()[0]));
        $fila = ['ruta' => $ruta->uri(), 'metodo' => $ruta->methods()[0], 'parametro' => $parametro,
            'numero_aceptado' => $numeroAceptado, 'texto_rechazado' => $textoRechazado];
        $control[] = $fila;
        if (!$numeroAceptado || !$textoRechazado) $errores[] = $fila;
    }
}
$clave = app('router')->getRoutes()->match(Illuminate\Http\Request::create('/configuraciones/mora_dia_desde', 'PATCH'));
$salida = ['solo_enrutamiento_sin_ejecutar_controladores' => true, 'parametros_revisados' => count($control),
    'errores' => $errores, 'clave_textual' => ['accion' => $clave->getActionName(), 'parametro' => $clave->parameter('clave'),
        'patron_clave' => $clave->wheres['clave'] ?? null], 'casos' => $control];
file_put_contents(__DIR__.'/rutas-comprobadas.json', json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode(['parametros' => count($control), 'errores' => count($errores), 'clave' => $clave->parameter('clave')]).PHP_EOL;
if ($errores || $clave->parameter('clave') !== 'mora_dia_desde') exit(1);
