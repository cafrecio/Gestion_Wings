<?php
// Ensayo local de main integrado; ejecutar solo con APP_ENV=testing y DB_DATABASE=wings_testing_codex.
$root = dirname(__DIR__, 5);
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($ruta === '/captura' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_GET['nombre'] ?? '';
    if (!preg_match('/^[a-z0-9-]+\.jpg$/D', $nombre)) { http_response_code(400); exit('Nombre inválido'); }
    $bytes = file_get_contents('php://input');
    if (strlen($bytes)>5000000 || substr($bytes,0,2)!=="\xFF\xD8") { http_response_code(400); exit('Se requiere captura JPEG'); }
    if (!is_dir(__DIR__.'/capturas')) mkdir(__DIR__.'/capturas',0775,true);
    file_put_contents(__DIR__.'/capturas/'.$nombre,$bytes);
    exit('Captura guardada');
}
if (preg_match('#^/respuesta/([a-z-]+)$#D',$ruta,$m) && is_file(__DIR__.'/respuestas/'.$m[1].'.html')) {
$html=str_replace(['http://localhost','http://gestion-wings'],'http://127.0.0.1:8791',file_get_contents(__DIR__.'/respuestas/'.$m[1].'.html'));
    header('Content-Type: text/html; charset=utf-8');
    if (($_GET['marco']??'')!=='375') { echo $html; return; }
    $marco=file_get_contents($root.'/docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html');
    echo str_replace('src="cashflow-devolucion.html"','srcdoc="'.htmlspecialchars($html,ENT_QUOTES,'UTF-8').'"',$marco);
    return;
}
if ($ruta!=='/' && is_file($root.'/public'.$ruta)) return false;
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$kernel=$app->make(\Illuminate\Contracts\Http\Kernel::class);
$request=\Illuminate\Http\Request::capture();
$app->instance('request',$request);
$kernel->bootstrap();
if (!$app->environment('testing') || \Illuminate\Support\Facades\DB::connection()->getDatabaseName()!=='wings_testing_codex') {
    http_response_code(403); exit('Solo base descartable Codex');
}
$rol=$_GET['actor']??($_COOKIE['cierre_actor']??'admin');
if (!in_array($rol,['admin','operativo'],true)) { http_response_code(403); exit('Actor inválido'); }
if (isset($_GET['actor'])) setcookie('cierre_actor',$rol,0,'/');
if ($ruta!=='/login') {
    $usuario=\App\Models\User::where('email','cierre.'.$rol.'@example.test')->firstOrFail();
    \Illuminate\Support\Facades\Auth::setUser($usuario);
}
$respuesta=$kernel->handle($request);
$respuesta->send();
$kernel->terminate($request,$respuesta);
