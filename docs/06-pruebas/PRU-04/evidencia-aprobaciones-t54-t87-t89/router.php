<?php

/** Marco de captura; Wings se sirve intacto con su index.php y sus rutas normales. */
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    http_response_code(503); exit('Solo base descartable Codex.');
}
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Vista web pública de los documentos creados para esta tarea: tres páginas e imágenes.
// No es un lector de rutas del disco ni expone archivos ajenos a la galería.
if (preg_match('~^/__galeria/revision/(APROBAR-T54\.html|APROBAR-T87\.html|DECIDIR-T89\.html)$~', $ruta, $m)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(dirname(__DIR__).'/'.$m[1]);
    return;
}
if (preg_match('~^/__galeria/revision/evidencia-aprobaciones-t54-t87-t89/([a-z0-9-]+\.jpg)$~', $ruta, $m)) {
    $archivo = __DIR__.'/'.$m[1];
    if (!is_file($archivo)) { http_response_code(404); exit; }
    header('Content-Type: image/jpeg');
    readfile($archivo);
    return;
}
if (preg_match('~^/__galeria/vista/([a-z0-9-]+\.html)$~', $ruta, $m)) {
    $archivo = __DIR__.'/dom/'.$m[1];
    if (!is_file($archivo)) { http_response_code(404); exit; }
    header('Content-Type: text/html; charset=utf-8');
    readfile($archivo);
    return;
}
if ($ruta === '/__galeria/marco') {
    $destino = (string)($_GET['ruta'] ?? '/login');
    if (!str_starts_with($destino, '/') || str_starts_with($destino, '//')) { http_response_code(400); exit; }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Wings · Marco real 375</title><style>html{background:#f4f6f8}body{margin:0;padding:16px;width:407px;box-sizing:border-box;font:13px Arial;color:#374151}p{margin:0 0 12px}iframe{display:block;width:375px;height:1200px;border:1px solid #ccd3da;box-sizing:content-box;background:white}</style><p>Wings real · contenido a 375 px</p><iframe id="telefono" title="Wings a 375 px" src="'.htmlspecialchars($destino, ENT_QUOTES).'" scrolling="no"></iframe></html>';
    return;
}
$publico = dirname(__DIR__, 4).'/public';
if ($ruta !== '/' && is_file($publico.$ruta)) return false;
require_once dirname(__DIR__, 4).'/vendor/autoload.php';
Carbon\Carbon::setTestNow('2026-11-02 19:00:00');
Carbon\CarbonImmutable::setTestNow('2026-11-02 19:00:00');
require $publico.'/index.php';
