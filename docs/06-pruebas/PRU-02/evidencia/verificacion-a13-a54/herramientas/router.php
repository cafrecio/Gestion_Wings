<?php

// Servidor de ensayo local con login real, nunca accesible desde otra computadora.
if (PHP_SAPI !== 'cli-server' || getenv('APP_ENV') !== 'testing'
    || getenv('DB_DATABASE') !== 'wings_testing_codex'
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit('Solo ensayo local de Codex.');
}
$path=parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path,'/build/') || str_starts_with($path,'/images/') || str_starts_with($path,'/favicon')) return false;
$root=dirname(__DIR__,6);
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$request=Illuminate\Http\Request::capture();
$app->instance('request',$request);
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') throw new RuntimeException('Base ajena.');
config(['filesystems.default'=>'local','filesystems.disks.local.root'=>storage_path('app/verificacion-a13-a54'),'mail.default'=>'array']);
Illuminate\Support\Carbon::setTestNow('2026-10-05 10:00:00');
$response=$kernel->handle($request);
$response->send();
$kernel->terminate($request,$response);
