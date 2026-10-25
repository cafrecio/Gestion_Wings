<?php
// Solo agrega el espia de diagnostico. Respuesta y CSP vienen de la aplicacion real.
if (getenv('DB_DATABASE') !== 'wings_testing_codex' || getenv('APP_ENV') !== 'testing') throw new RuntimeException('Solo testing/wings_testing_codex.');
$raiz = dirname(__DIR__, 4);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($ruta === '/__verificacion_t80.js') {
    header('Content-Type: application/javascript; charset=utf-8'); readfile(__DIR__.'/espia-csp.js'); return;
}
$archivo = realpath($raiz.'/public'.$ruta);
if ($archivo && str_starts_with($archivo, $raiz.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR) && is_file($archivo)) return false;
define('LARAVEL_START', microtime(true));
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$peticion = Illuminate\Http\Request::capture();
$respuesta = $kernel->handle($peticion);
$html = $respuesta->getContent();
if (stripos($html, '<head>') !== false) {
    $politica = $respuesta->headers->get('Content-Security-Policy');
    $soloReporte = $respuesta->headers->has('Content-Security-Policy-Report-Only');
    $meta = htmlspecialchars(json_encode(['http' => $respuesta->getStatusCode(), 'politica' => $politica, 'solo_reporte' => $soloReporte], JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
    $espia = '<meta name="verificacion-t80-respuesta" content="'.$meta.'"><script src="/__verificacion_t80.js"></script>';
    if ($ruta === '/login' && ($_GET['__verificacion_control'] ?? '') === '1') $espia .= '<script>document.documentElement.dataset.controlT80Ejecutado="si";</script>';
    $html = preg_replace('/<head>/i', '<head>'.$espia, $html, 1);
}
$respuesta->setContent($html)->send();
$kernel->terminate($peticion, $respuesta);
