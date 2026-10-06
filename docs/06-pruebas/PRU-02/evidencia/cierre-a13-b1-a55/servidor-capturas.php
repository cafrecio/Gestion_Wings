<?php
// Servidor local documental. No modifica rutas ni headers de Wings.
$root = getenv('WINGS_PREVIEW_ROOT') ?: dirname(__DIR__, 5);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$carpeta = $root.'/docs/06-pruebas/PRU-02/capturas-a55';
if ($ruta === '/captura' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_GET['nombre'] ?? '';
    if (!preg_match('/^(login|ficha-futbol|ficha-patin|historial-anulado)-(375|escritorio)\.png$/D', $nombre)) {
        http_response_code(400); exit('Nombre no permitido');
    }
    $bytes = file_get_contents('php://input');
    if (strlen($bytes) > 5000000) { http_response_code(400); exit('Captura demasiado grande'); }
    $imagen = imagecreatefromstring($bytes);
    if (!$imagen) { http_response_code(400); exit('Imagen inválida'); }
    imagepng($imagen, $carpeta.'/'.$nombre);
    imagedestroy($imagen);
    exit('Captura guardada');
}
if (preg_match('#^/(login|ficha-futbol|ficha-patin)$#D', $ruta, $coincide)) {
    $html = file_get_contents($carpeta.'/'.$coincide[1].'.html');
    // Los assets vienen del mismo checkout que produjo la respuesta Laravel.
    $html = preg_replace('#https?://localhost(?=/)#', 'http://127.0.0.1:8790', $html);
    header('Content-Type: text/html; charset=utf-8');
    if (($_GET['marco'] ?? '') !== '375') { echo $html; return; }
    $marco = file_get_contents($root.'/docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html');
    echo str_replace('src="cashflow-devolucion.html"', 'srcdoc="'.htmlspecialchars($html, ENT_QUOTES, 'UTF-8').'"', $marco);
    return;
}
$archivo = $root.'/public'.$ruta;
if ($ruta !== '/' && is_file($archivo)) {
    $ext = pathinfo($archivo, PATHINFO_EXTENSION);
    $tipos = ['css'=>'text/css', 'js'=>'text/javascript', 'woff2'=>'font/woff2', 'png'=>'image/png', 'jpg'=>'image/jpeg', 'svg'=>'image/svg+xml'];
    header('Content-Type: '.($tipos[$ext] ?? 'application/octet-stream'));
    readfile($archivo); return;
}
http_response_code(404);
