<?php
if (getenv('DB_DATABASE') !== 'wings_testing_codex' || getenv('APP_ENV') !== 'testing') { http_response_code(403); exit; }
$repo = dirname(__DIR__, 4);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/__t44/([a-z-]+)\.html$#', $ruta, $m)) {
    $archivo = __DIR__.'/html/'.$m[1].'.html';
    if (!is_file($archivo)) { http_response_code(404); exit; }
    header('Content-Type: text/html; charset=utf-8'); readfile($archivo); return;
}
if ($ruta === '/__t44/marco') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><title>Marco de captura T44</title></head><body style="margin:0"></body></html>'; return;
}
if (is_file($repo.'/public'.$ruta)) return false;
http_response_code(404);
