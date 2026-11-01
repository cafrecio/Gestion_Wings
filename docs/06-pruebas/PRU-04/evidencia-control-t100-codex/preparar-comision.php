<?php
$base=getenv('CONTROL_APP');require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Mail,Notification};
use App\Models\{Alumno,AlumnoPlan,Asistencia,Clase,DeudaCuota,Profesor};
if(DB::selectOne('select database() db')->db!=='wings_testing_codex')throw new RuntimeException('BASE PROHIBIDA');Mail::fake();Notification::fake();
\Carbon\Carbon::setTestNow('2026-10-20 10:00:00');\Carbon\CarbonImmutable::setTestNow('2026-10-20 10:00:00');
$a=Alumno::findOrFail(3)->replicate();$a->nombre='Comisión';$a->dni='99001013';$a->save();AlumnoPlan::create(['alumno_id'=>$a->id,'plan_id'=>1,'fecha_desde'=>'2026-01-10','activo'=>true]);DeudaCuota::create(['alumno_id'=>$a->id,'periodo'=>'2026-10','monto_original'=>30000,'monto_pagado'=>30000,'estado'=>'PAGADA']);
$prof=Profesor::create(['deporte_id'=>$a->deporte_id,'nombre'=>'Docente Comisión','apellido'=>'Control T100','dni'=>'99001101','fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','porcentaje_comision'=>40,'activo'=>true]);
$cl=Clase::create(['grupo_id'=>1,'fecha'=>'2026-12-02','hora_inicio'=>'17:00','hora_fin'=>'18:00','cancelada'=>false]);$cl->profesores()->attach($prof->id);Asistencia::create(['clase_id'=>$cl->id,'alumno_id'=>$a->id,'presente'=>true]);
$p=app(App\Services\PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id'=>$a->id,'tipo_caja_id'=>1,'usuario_admin_id'=>1,'fecha_pago'=>'2026-10-20','items'=>[['periodo'=>'2026-12','monto'=>300000]],'montos_adelantados'=>['2026-12'=>300000]])['pago'];
$preview=app(App\Services\LiquidacionService::class)->previsualizarLiquidacion($prof->id,12,2026);if((float)$preview['total_estimado']!==120000.0)throw new RuntimeException('Sin control positivo');
\Carbon\Carbon::setTestNow('2026-12-03 10:00:00');\Carbon\CarbonImmutable::setTestNow('2026-12-03 10:00:00');
$liq=app(App\Services\LiquidacionService::class)->generarLiquidacionMensual($prof->id,12,2026);if($liq->total_calculado!=='120000.00')throw new RuntimeException('Comision inicial distinta');
$r=['alumno'=>$a->id,'pago'=>$p->id,'profesor'=>$prof->id,'liquidacion'=>$liq->id,'antes'=>$liq->total_calculado,'preview_antes'=>$preview];file_put_contents(__DIR__.'/comision-antes.json',json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($r);
