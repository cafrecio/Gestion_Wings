<?php
$base=getenv('CONTROL_APP'); require $base.'/vendor/autoload.php'; $app=require $base.'/bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Schema};
use App\Models\{DeudaCuota,Pago,MovimientoOperativo,User};
use App\Services\PagoCuotaService;
if(DB::selectOne('select database() db')->db!=='wings_testing_codex')throw new RuntimeException('BASE PROHIBIDA');
\Carbon\Carbon::setTestNow('2026-11-25 12:00:00'); \Carbon\CarbonImmutable::setTestNow('2026-11-25 12:00:00');
if(Schema::hasColumn('deuda_cuotas','pago_origen_id'))throw new RuntimeException('La copia ya tenia T100; revisar');
$foto=function(){ $hashes=[]; foreach(DB::select('SHOW TABLES') as $row){$t=array_values((array)$row)[0]; if($t==='users')continue; $filas=DB::table($t)->get()->map(function($r){$r=(array)$r;unset($r['pago_origen_id']);ksort($r);return json_encode($r);})->sort()->values()->all();$hashes[$t]=['filas'=>count($filas),'sha256'=>hash('sha256',json_encode($filas))];}return $hashes;};
$antes=$foto(); $migration=require $base.'/database/migrations/2026_11_01_130000_add_pago_origen_to_deuda_cuotas.php'; $migration->up(); $despues=$foto();
if($antes!==$despues)throw new RuntimeException('La migracion cambio datos');
$nulls=DeudaCuota::whereNotNull('pago_origen_id')->count(); if($nulls!==0)throw new RuntimeException('Se invento origen');
// Selección de filas concretas antes de concluir qué representan los registros.
$adelantos=DB::table('pago_deuda_cuota as pd')->join('pagos as p','p.id','=','pd.pago_id')->join('deuda_cuotas as d','d.id','=','pd.deuda_cuota_id')->where('p.estado','COMPLETADO')->whereColumn('d.periodo','>',DB::raw("DATE_FORMAT(p.fecha_pago,'%Y-%m')"))->select('p.id as pago_id','p.alumno_id','p.fecha_pago','d.id as deuda_id','d.periodo','d.monto_original','d.monto_pagado','d.pago_origen_id')->orderBy('p.id')->limit(5)->get();
if($adelantos->isEmpty())throw new RuntimeException('No hay adelantos existentes');
$admin=User::factory()->create(['name'=>'Control Migración Codex','rol'=>'ADMIN','activo'=>true]);
$elegido=null;
foreach($adelantos as $row){if(!MovimientoOperativo::where('pago_id',$row->pago_id)->exists()){$elegido=$row;break;}}
// Para tener un camino operativo o admin comprobable, buscar uno fuera de los primeros cinco si hiciera falta.
if(!$elegido){$elegido=DB::table('pago_deuda_cuota as pd')->join('pagos as p','p.id','=','pd.pago_id')->join('deuda_cuotas as d','d.id','=','pd.deuda_cuota_id')->where('p.estado','COMPLETADO')->where('d.periodo','>','2026-11')->whereNotExists(fn($q)=>$q->selectRaw('1')->from('movimientos_operativos as m')->whereColumn('m.pago_id','p.id'))->select('p.id as pago_id','d.id as deuda_id','d.periodo','d.monto_original','d.monto_pagado')->first();}
$legacy=null;
if($elegido){$q=DeudaCuota::findOrFail($elegido->deuda_id); $original=$q->monto_original; $aplicado=DB::table('pago_deuda_cuota')->where('pago_id',$elegido->pago_id)->where('deuda_cuota_id',$q->id)->value('monto_aplicado');$pagado=$q->monto_pagado;app(PagoCuotaService::class)->anularCobroAdmin($elegido->pago_id,'Control local de adelanto anterior a T100',$admin->id);$q->refresh();if($q->monto_original!==$original || (float)$q->monto_pagado!==max(0.0,(float)$pagado-(float)$aplicado))throw new RuntimeException('Se cambio cuota anterior');$legacy=['pago_id'=>$elegido->pago_id,'deuda_id'=>$q->id,'periodo'=>$q->periodo,'original'=>$q->monto_original,'pagado_antes'=>$pagado,'pagado_despues'=>$q->monto_pagado,'estado'=>$q->estado,'pago_origen_id'=>$q->pago_origen_id];}
$output=['base'=>DB::connection()->getDatabaseName(),'fuente_sitio_commit'=>'6214fd4a8e2e3bfe493171a2edbb27abc2d8dbfb','tablas_antes'=>$antes,'tablas_despues'=>$despues,'datos_identicos'=>$antes===$despues,'cuotas_origen_no_nulo'=>$nulls,'adelantos_muestra'=>$adelantos,'anulacion_legado'=>$legacy];
file_put_contents(getenv('CONTROL_EVIDENCE').'/migracion-copia-sitio.json',json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); echo json_encode(['tablas'=>count($antes),'datos_identicos'=>true,'muestra'=>$adelantos,'legado'=>$legacy],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
