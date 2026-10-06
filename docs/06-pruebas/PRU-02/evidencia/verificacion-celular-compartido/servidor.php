<?php
// Laboratorio HTTP local: respuestas reales de Laravel, base descartable y actores ficticios.
$root=dirname(__DIR__,5);
$ruta=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (getenv('APP_ENV')!=='testing' || getenv('DB_DATABASE')!=='wings_testing_codex') { http_response_code(403); exit('Solo laboratorio Codex'); }
if ($ruta==='/captura' && $_SERVER['REQUEST_METHOD']==='POST') {
    $nombre=$_GET['nombre']??'';
    if (!preg_match('/^[a-z0-9-]+\.(jpg|json)$/D',$nombre)) { http_response_code(400); exit; }
    $bytes=file_get_contents('php://input');
    if (strlen($bytes)>5000000 || (str_ends_with($nombre,'.jpg') && substr($bytes,0,2)!=="\xFF\xD8")) { http_response_code(400); exit; }
    if(!is_dir(__DIR__.'/capturas')) mkdir(__DIR__.'/capturas',0775,true);
    file_put_contents(__DIR__.'/capturas/'.$nombre,$bytes); exit('Guardado');
}
if ($ruta!=='/' && is_file($root.'/public'.$ruta)) return false;
$marco=$ruta==='/marco' || isset($_GET['marco']);
$pagina=$ruta==='/marco'?($_GET['pagina']??'/login'):$ruta;
$actor=$_GET['actor']??($_COOKIE['celular_actor']??'admin');
if(!in_array($actor,['admin','operativo','profesor'],true) || !str_starts_with($pagina,'/') || str_starts_with($pagina,'//')) { http_response_code(400); exit; }
setcookie('celular_actor',$actor,0,'/');
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$request=$ruta==='/marco'?\Illuminate\Http\Request::create('http://127.0.0.1:8792'.$pagina,'GET',[],$_COOKIE): \Illuminate\Http\Request::capture();
$app->instance('request',$request);
$kernel=$app->make(\Illuminate\Contracts\Http\Kernel::class); $kernel->bootstrap();
if(!$app->environment('testing') || \Illuminate\Support\Facades\DB::connection()->getDatabaseName()!=='wings_testing_codex') { http_response_code(403); exit; }
if($pagina!=='/login') \Illuminate\Support\Facades\Auth::setUser(\App\Models\User::where('email','celular.'.$actor.'@example.test')->firstOrFail());
if(preg_match('#^/respuesta/([a-z-]+)$#D',$pagina,$m) && is_file(__DIR__.'/respuestas/'.$m[1].'.html')) {
    $html=str_replace(['http://localhost','http://gestion-wings'],'http://127.0.0.1:8792',file_get_contents(__DIR__.'/respuestas/'.$m[1].'.html'));
} else {
    $respuesta=$kernel->handle($request);
    if(!$marco) { $respuesta->send(); $kernel->terminate($request,$respuesta); return; }
    $html=$respuesta->getContent();
    foreach($respuesta->headers->getCookies() as $cookie) header('Set-Cookie: '.$cookie,false);
    header('X-Laboratorio-Estado: '.$respuesta->getStatusCode());
    $kernel->terminate($request,$respuesta);
}
header('Content-Type: text/html; charset=utf-8');
if(!$marco) { echo $html; return; }
$plantilla=file_get_contents($root.'/docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html');
if(($_GET['alto']??'')==='667') $plantilla=str_replace('height:1000px','height:667px',$plantilla);
echo str_replace('src="cashflow-devolucion.html"','srcdoc="'.htmlspecialchars($html,ENT_QUOTES,'UTF-8').'"',$plantilla);
