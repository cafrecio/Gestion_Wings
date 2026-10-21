<?php
if(PHP_SAPI!=='cli-server') exit;
$root=dirname(__DIR__,4);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath($root.'/public'.rawurldecode($path));
if($file&&str_starts_with($file,realpath($root.'/public').DIRECTORY_SEPARATOR)&&is_file($file)&&pathinfo($file,PATHINFO_EXTENSION)!=='php') return false;
define('LARAVEL_START',microtime(true));
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$request=Illuminate\Http\Request::capture();$app->instance('request',$request);
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$kernel->bootstrap();
if(config('database.connections.mysql.database')!=='wings_testing_codex'||config('database.connections.mysql.host')!=='127.0.0.1'){http_response_code(503);exit('Solo base propia');}
$response=$kernel->handle($request);$response->send();$kernel->terminate($request,$response);