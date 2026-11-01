<?php
// Instrumentación exclusivamente local de la copia de control; nunca se instala en Wings.
$base=getenv('CONTROL_APP');
if (!$base || !is_file($base.'/artisan')) throw new RuntimeException('Falta CONTROL_APP');
require $base.'/vendor/autoload.php';
$app=require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Artisan,Mail,Notification,Hash};
use App\Models\{Alumno,AlumnoPlan,CajaOperativa,Deporte,DeudaCuota,Grupo,GrupoPlan,Nivel,TipoCaja,User};
use Carbon\{Carbon,CarbonImmutable};
if(DB::connection()->getDatabaseName()!=='wings_testing_codex' || DB::selectOne('select database() as db')->db!=='wings_testing_codex') throw new RuntimeException('BASE PROHIBIDA');
$fecha='2026-10-05 10:00:00'; Carbon::setTestNow($fecha); CarbonImmutable::setTestNow($fecha);
Mail::fake(); Notification::fake();
Artisan::call('migrate:fresh',['--seed'=>true,'--force'=>true]);
DB::table('primera_carga')->where('id',1)->update(['estado'=>'TERMINADA']);
foreach(['reporte_cobertura','reporte_analitico_cobertura'] as $t) DB::table($t)->where('id',1)->update(['desde'=>'2026-01-01 00:00:00']);
$admin=User::factory()->create(['name'=>'Dueño Control Codex','email'=>'admin.control@example.test','password'=>Hash::make('ControlLocal-261101!'),'rol'=>'ADMIN','activo'=>true]);
$op=User::factory()->create(['name'=>'Operativa Control Codex','email'=>'operativa.control@example.test','password'=>Hash::make('ControlLocal-261101!'),'rol'=>'OPERATIVO','activo'=>true]);
$medio=TipoCaja::where('nombre','Efectivo')->sole();
app(App\Services\CajaService::class)->configurarEfectivo($admin->id,0);
$caja=Tests\Support\CajaDeclarada::crear($op->id,$medio->id);
$d=Deporte::create(['nombre'=>'Control T100 Codex','tipo_liquidacion'=>'COMISION','activo'=>true]);
$n=Nivel::create(['nombre'=>'Inicial Control Codex','activo'=>true]);
$g=Grupo::create(['deporte_id'=>$d->id,'nivel_id'=>$n->id,'activo'=>true]);
$plan=GrupoPlan::create(['grupo_id'=>$g->id,'clases_por_semana'=>2,'precio_mensual'=>30000,'activo'=>true]);
$filas=[];
foreach(['Operativa','Dueño','Asistencia','Revisión','Caja rechazada','Multimes','Preexistente','Dos cobros','Recargo','Descuento','Meses seguidos'] as $i=>$nombre) {
 $a=Alumno::create(['nombre'=>$nombre,'apellido'=>'Control T100','dni'=>(string)(99001000+$i),'fecha_nacimiento'=>'2000-01-01','celular'=>'1100000000','fecha_alta'=>'2026-01-10','deporte_id'=>$d->id,'grupo_id'=>$g->id,'activo'=>true]);
 AlumnoPlan::create(['alumno_id'=>$a->id,'plan_id'=>$plan->id,'fecha_desde'=>'2026-01-10','activo'=>true]);
 DeudaCuota::create(['alumno_id'=>$a->id,'periodo'=>'2026-10','monto_original'=>30000,'monto_pagado'=>30000,'estado'=>'PAGADA']);
 $filas[$nombre]=$a->id;
}
file_put_contents($base.'/storage/control-clock.txt',$fecha);
echo json_encode(['base'=>DB::connection()->getDatabaseName(),'admin'=>$admin->id,'operativa'=>$op->id,'caja'=>$caja->id,'medio'=>$medio->id,'plan'=>$plan->id,'alumnos'=>$filas],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
