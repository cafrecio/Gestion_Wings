<?php
$base=getenv('CONTROL_APP');require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Artisan,Hash};use App\Models\{User,TipoCaja,Subrubro,CashflowMovimiento};use Carbon\{Carbon,CarbonImmutable};
if(DB::connection()->getDatabaseName()!=='wings_testing_codex')throw new RuntimeException('BASE PROHIBIDA');
Carbon::setTestNow('2026-11-20 10:00:00');CarbonImmutable::setTestNow('2026-11-20 10:00:00');
$modo=$argv[1]??'preparar';
if($modo==='preparar') {
 Artisan::call('migrate:fresh',['--seed'=>true,'--force'=>true]);DB::table('primera_carga')->where('id',1)->update(['estado'=>'TERMINADA']);foreach(['reporte_cobertura','reporte_analitico_cobertura'] as $t)DB::table($t)->where('id',1)->update(['desde'=>'2026-01-01 00:00:00']);
 $u=User::factory()->create(['name'=>'Control T106','email'=>'admin.control@example.test','password'=>Hash::make('ControlLocal-261101!'),'rol'=>'ADMIN','activo'=>true]);
 $cash=TipoCaja::where('nombre','Efectivo')->sole();app(App\Services\CajaService::class)->configurarEfectivo($u->id,2000000);
 foreach([['Intereses Banco',421200],['Luz',-65200]] as [$name,$amount])CashflowMovimiento::create(['fecha'=>'2026-11-16','subrubro_id'=>Subrubro::where('nombre',$name)->sole()->id,'tipo_caja_id'=>$cash->id,'monto'=>$amount,'observaciones'=>'Negocio T106','usuario_admin_id'=>$u->id]);
 file_put_contents($base.'/storage/control-clock.txt','2026-11-20 10:00:00');
} elseif($modo==='historico') {
 $u=User::where('email','admin.control@example.test')->sole();$cash=TipoCaja::where('nombre','Efectivo')->sole();$bank=TipoCaja::where('nombre','Banco Galicia')->sole();
 foreach([['Salida a otro medio',-1500000,'2026-10-31',$cash->id],['Entrada desde otro medio',1500000,'2026-11-01',$bank->id]] as [$name,$amount,$date,$id])CashflowMovimiento::create(['fecha'=>$date,'subrubro_id'=>Subrubro::where('nombre',$name)->sole()->id,'tipo_caja_id'=>$id,'monto'=>$amount,'observaciones'=>'Historico suelto T106','usuario_admin_id'=>$u->id]);
} elseif($modo==='anterior') {
 $u=User::where('email','admin.control@example.test')->sole();app(App\Services\TransferenciaCashflowService::class)->registrar($u->id,['clave'=>(string)Illuminate\Support\Str::uuid(),'salida_id'=>TipoCaja::where('nombre','Efectivo')->sole()->id,'destino_id'=>TipoCaja::where('nombre','Banco Galicia')->sole()->id,'monto'=>'1500000','fecha'=>'2026-10-20']);
}
echo json_encode(['modo'=>$modo,'movimientos'=>CashflowMovimiento::orderBy('id')->get()->toArray(),'reporte_oct'=>app(App\Services\ReporteMensualService::class)->obtener('2026-10'),'reporte_nov'=>app(App\Services\ReporteMensualService::class)->obtener('2026-11')],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
