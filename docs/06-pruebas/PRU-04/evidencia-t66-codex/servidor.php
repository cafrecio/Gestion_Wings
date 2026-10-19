<?php
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') { http_response_code(403); exit; }
$repo = dirname(__DIR__, 4);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($ruta === '/__t66/marco') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><title>Marco T66</title></head><body style="margin:0"></body></html>'; return;
}
if (preg_match('#^/__t66/([a-z-]+)\.html$#', $ruta, $m)) {
    $archivo = __DIR__.'/html/'.$m[1].'.html';
    if (!is_file($archivo)) { http_response_code(404); exit; }
    header('Content-Type: text/html; charset=utf-8'); readfile($archivo); return;
}
if (str_starts_with($ruta, '/build/') && !str_contains($ruta, '..')) {
    $archivo = __DIR__.$ruta;
    if (is_file($archivo)) {
        $extension = pathinfo($archivo, PATHINFO_EXTENSION);
        header('Content-Type: '.(['css' => 'text/css', 'js' => 'application/javascript', 'woff' => 'font/woff', 'woff2' => 'font/woff2'][$extension] ?? 'application/octet-stream'));
        readfile($archivo); return;
    }
}
http_response_code(404);
