<?php
// Servidor de ensayo de Claude: la aplicación real de Laravel, sin cambiar rutas, vistas ni controladores.
//   DB_DATABASE=wings_testing_claude php -S 127.0.0.1:8811 -t public <este archivo>
// Único desvío: se quita X-Frame-Options para poder medir el celular dentro de un <iframe> de 375 (AGENTS §1).
$root = dirname(__DIR__, 5);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (getenv('DB_DATABASE') !== 'wings_testing_claude') {
    http_response_code(403);
    exit('Solo base de Claude');
}
if ($ruta === '/__marco375') {
    $pagina = $_GET['pagina'] ?? '/login';
    if (!str_starts_with($pagina, '/') || str_starts_with($pagina, '//')) {
        http_response_code(400);
        exit;
    }
    echo '<!doctype html><meta charset="utf-8"><body style="margin:0;background:#888;display:flex;justify-content:center">'
        .'<iframe src="'.htmlspecialchars($pagina).'" style="width:375px;height:667px;border:0;background:#fff"></iframe></body>';
    exit;
}
if ($ruta !== '/' && is_file($root.'/public'.$ruta)) {
    return false;
}
header_register_callback(fn () => header_remove('X-Frame-Options'));
require $root.'/public/index.php';
