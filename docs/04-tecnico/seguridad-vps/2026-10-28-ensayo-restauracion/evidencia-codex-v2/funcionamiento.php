<?php
use Illuminate\Support\Facades\{DB,Auth,Storage};
require '/root/ensayo-t82/app/vendor/autoload.php';
$app=require '/root/ensayo-t82/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$out=['control'=>'funcionamiento_independiente','fuente'=>getenv('FUENTE_CONTROL')];
try {
 if(DB::connection()->getDatabaseName()!=='wings_ensayo_t82'||DB::selectOne('select database() as db')->db!=='wings_ensayo_t82')throw new RuntimeException('Base fuera del ensayo');
 $backup=Dotenv\Dotenv::parse(file_get_contents('/root/ensayo-t82/env.txt'));
 $live=Dotenv\Dotenv::parse(file_get_contents('/home/'.getenv('FUENTE_CONTROL').'/app/.env'));
 $key=$live['APP_KEY'];$bytes=str_starts_with($key,'base64:')?base64_decode(substr($key,7)):$key;
 $original=new Illuminate\Encryption\Encrypter($bytes,config('app.cipher'));
 $cipher=$original->encryptString('control-t82-recuperacion');
 $out['app_key_del_respaldo_cargada']=config('app.key')===$backup['APP_KEY'];
 $actual=Dotenv\Dotenv::parse(file_get_contents('/root/ensayo-t82/app/.env'));
 $esperado=$backup;
 foreach(['DB_DATABASE','DB_USERNAME','DB_PASSWORD','APP_URL'] as $campo){unset($actual[$campo],$esperado[$campo]);}
 $out['configuracion_salvo_aislamiento_igual']=$actual===$esperado;
 unset($actual,$esperado);
 $out['descifra_con_clave_recuperada']=$app['encrypter']->decryptString($cipher)==='control-t82-recuperacion';
 unset($backup,$live,$key,$bytes,$cipher);
 $alumno=App\Models\Alumno::query()->first();$pago=App\Models\Pago::query()->first();$liq=App\Models\Liquidacion::with('profesor')->first();
 $out['modelos']=['alumno_leido'=>(bool)$alumno,'pago_leido'=>(bool)$pago,'liquidacion_leida'=>(bool)$liq,'pago_con_alumno'=>$pago?(bool)App\Models\Alumno::find($pago->alumno_id):null,'liquidacion_con_profesor'=>$liq?(bool)$liq->profesor:null];
 $admin=App\Models\User::where('rol','ADMIN')->where('activo',true)->firstOrFail();
 Auth::guard('web')->setUser($admin);Auth::shouldUse('web');
 $kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
 $get=function($uri)use($kernel,$admin){$req=Illuminate\Http\Request::create('http://127.0.0.1:8099'.$uri,'GET');$req->setUserResolver(fn()=>$admin);$res=$kernel->handle($req);$kernel->terminate($req,$res);return $res;};
 $out['peticiones_internas_con_admin_copiado']=[];
 if($alumno){$res=$get('/alumnos/'.$alumno->id);$out['peticiones_internas_con_admin_copiado']['alumno']=['http'=>$res->getStatusCode(),'muestra_alumno_leido'=>str_contains((string)$res->getContent(),e($alumno->nombre))];}
 if($liq){$res=$get('/liquidaciones/'.$liq->id);$out['peticiones_internas_con_admin_copiado']['liquidacion']=['http'=>$res->getStatusCode(),'muestra_profesor_leido'=>$liq->profesor?str_contains((string)$res->getContent(),e($liq->profesor->apellido)):false];}
 $service=app(App\Services\ReciboService::class);$stored=null;
 foreach(App\Models\Pago::where('estado','COMPLETADO')->cursor() as $candidate){$path=$service->obtenerRutaReciboCuota($candidate->id);if(Storage::exists($path)){$stored=['pago'=>$candidate,'path'=>$path,'bytes'=>Storage::get($path)];break;}}
 $out['recibo_guardado']=['encontrado'=>(bool)$stored];
 if($stored){$res=$get('/recibos/cuota/'.$stored['pago']->id.'?inline=1');$bytes=$res->getContent();$out['recibo_guardado']+=['http'=>$res->getStatusCode(),'tipo_pdf'=>str_starts_with((string)$res->headers->get('Content-Type'),'application/pdf'),'bytes_iguales_al_guardado'=>$bytes===$stored['bytes'],'cabecera_pdf'=>str_starts_with($bytes,'%PDF-'),'cierre_pdf'=>str_contains(substr($bytes,-1024),'%%EOF'),'cantidad_bytes'=>strlen($bytes)];}
 $ok=$out['app_key_del_respaldo_cargada']&&$out['descifra_con_clave_recuperada']&&$out['configuracion_salvo_aislamiento_igual'];
 if(getenv('FUENTE_CONTROL')==='wingstest'){
  $ok=$ok&&$out['modelos']['alumno_leido']&&$out['modelos']['pago_leido']&&$out['modelos']['liquidacion_leida'];
  foreach($out['peticiones_internas_con_admin_copiado'] as $v)$ok=$ok&&$v['http']===200;
  $ok=$ok&&$stored&&$out['recibo_guardado']['http']===200&&$out['recibo_guardado']['tipo_pdf']&&$out['recibo_guardado']['bytes_iguales_al_guardado']&&$out['recibo_guardado']['cabecera_pdf']&&$out['recibo_guardado']['cierre_pdf'];
 }
 $out['aprobado']=$ok;echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;exit($ok?0:1);
}catch(Throwable $ex){$out['error']=['tipo'=>get_class($ex),'archivo'=>str_replace('/root/ensayo-t82/app/','<copia>/',$ex->getFile()),'linea'=>$ex->getLine()];echo json_encode($out).PHP_EOL;exit(1);}
