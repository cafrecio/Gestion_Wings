<?php
$base=getenv('CONTROL_APP');require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if(DB::selectOne('select database() db')->db!=='wings_testing_codex')throw new RuntimeException('BASE PROHIBIDA');
$clock=trim(file_get_contents($base.'/storage/control-clock.txt')); \Carbon\Carbon::setTestNow($clock);\Carbon\CarbonImmutable::setTestNow($clock);
$salida=['reloj'=>$clock,'pagos'=>DB::table('pagos')->select('id','alumno_id','monto_final','estado','detalle_anulacion')->orderBy('id')->get(),'cuotas'=>DB::table('deuda_cuotas')->select('id','alumno_id','periodo','monto_original','monto_pagado','estado','pago_origen_id')->orderBy('id')->get(),'movimientos'=>DB::table('movimientos_operativos')->select('id','alumno_id','pago_id','monto','estado')->orderBy('id')->get(),'cashflow'=>DB::table('cashflow_movimientos')->select('id','referencia_tipo','referencia_id','monto')->orderBy('id')->get(),'reporte'=>app(App\Services\ReporteMensualService::class)->obtener(substr($clock,0,7))];
$nombre=$argv[1]??'observacion';if(!preg_match('/^[a-z0-9-]+$/',$nombre))throw new RuntimeException('Nombre invalido');
file_put_contents(__DIR__.'/'.$nombre.'.json',json_encode($salida,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($salida,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
