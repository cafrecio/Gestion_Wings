<?php
$base=getenv('CONTROL_APP');require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if(DB::selectOne('select database() db')->db!=='wings_testing_codex')throw new RuntimeException('BASE PROHIBIDA');
App\Models\CajaOperativa::findOrFail(1)->update(['estado'=>'RECHAZADA']);
file_put_contents($base.'/storage/control-clock.txt','2026-12-03 10:00:00');
$antes=app(App\Services\CajaService::class)->arqueoCaja(1);
file_put_contents(__DIR__.'/caso5-arqueo-antes.json',json_encode($antes,JSON_PRETTY_PRINT));echo json_encode($antes);
