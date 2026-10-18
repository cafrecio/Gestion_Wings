<?php

// Sirve respuestas literales de Laravel; el marco vacío solo contiene el iframe de medición.
if (getenv('DB_DATABASE') !== 'wings_testing_codex' || getenv('APP_ENV') !== 'testing') {
    http_response_code(403);
    exit('Solo evidencia local en testing/codex.');
}
$raiz = dirname(__DIR__, 4);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$assets = $raiz.'/storage/app/t75-consistencia-runtime/app/assets-build';
if (is_dir($assets) && preg_match('#^/build/(assets/[A-Za-z0-9._-]+|manifest\.json)$#', $ruta, $asset)) {
    $archivo = $assets.'/'.$asset[1];
    if (!is_file($archivo)) { http_response_code(404); exit; }
    $tipo = match (pathinfo($archivo, PATHINFO_EXTENSION)) {
        'css' => 'text/css', 'js' => 'text/javascript', 'json' => 'application/json',
        'woff2' => 'font/woff2', 'woff' => 'font/woff', 'svg' => 'image/svg+xml',
        'png' => 'image/png', default => 'application/octet-stream',
    };
    header('Content-Type: '.$tipo);
    readfile($archivo);
    return;
}
if (preg_match('#^/__t75/(antes|despues)/([a-zA-Z0-9_-]+)\.html$#', $ruta, $m)) {
    $archivo = $raiz.'/storage/app/t75-consistencia-runtime/app/html/'.$m[1].'/'.$m[2].'.html';
    if (!is_file($archivo)) { http_response_code(404); exit; }
    header('Content-Type: text/html; charset=utf-8');
    readfile($archivo);
    return;
}
if ($ruta === '/__t75/marco') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><title>Marco T75</title></head><body style="margin:0"></body></html>';
    return;
}
if (is_file($raiz.'/public'.$ruta)) { return false; }
http_response_code(404);
