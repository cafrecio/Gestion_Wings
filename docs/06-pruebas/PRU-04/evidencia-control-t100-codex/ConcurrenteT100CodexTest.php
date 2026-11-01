<?php
namespace Tests\Feature;
require_once __DIR__.'/ControlT100CodexTest.php';
use App\Models\{DeudaCuota,MovimientoOperativo};
use App\Services\PagoCuotaService;
use Illuminate\Support\Facades\DB;
class ConcurrenteT100CodexTest extends ControlT100CodexTest
{
 protected function setUp():void { \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false; parent::setUp(); }
 private function carrera(bool $admin,bool $cancelarPrimero):void {
  $p=$this->cobrar(['2026-12'=>300000],$admin?$this->admin:$this->op);
  if(!$cancelarPrimero) $this->cuota()->update(['monto_original'=>330000,'estado'=>'PENDIENTE']);
  DB::commit(); // Fixture confirmado para que la segunda conexión lo vea.
  $principal=config('database.default'); config(['database.connections.control_t100_otra'=>config('database.connections.'.$principal)]);
  $otra=DB::connection('control_t100_otra');
  $ids=[DB::selectOne('SELECT CONNECTION_ID() AS id')->id,$otra->selectOne('SELECT CONNECTION_ID() AS id')->id];
  $this->assertNotSame($ids[0],$ids[1]); $this->assertSame('wings_testing_codex',$otra->getDatabaseName());
  $data=['alumno_id'=>$this->alumno->id,'tipo_caja_id'=>$this->medio->id,'usuario_operativo_id'=>$this->op->id,'usuario_admin_id'=>$this->admin->id,'items'=>[['periodo'=>'2026-12','monto'=>30000]],'montos_adelantados'=>['2026-12'=>30000],'fecha_pago'=>'2026-10-05','motivo'=>'Control concurrente'];
  $cobrar=fn()=> $admin?app(PagoCuotaService::class)->registrarPagoCuotaAdmin($data):app(PagoCuotaService::class)->registrarPagoCuotaOperativo($data);
  $mov=MovimientoOperativo::where('pago_id',$p->id)->value('id');
  $cancelar=fn()=> $admin?app(PagoCuotaService::class)->anularCobroAdmin($p->id,'Control simultáneo',$this->admin->id):app(PagoCuotaService::class)->cancelarCobroOperativo($mov,'Control simultáneo',$this->op->id);
  $primera=$cancelarPrimero?$cancelar:$cobrar; $segunda=$cancelarPrimero?$cobrar:$cancelar;
  $foto=function(){ $f=[]; foreach(['pagos','deuda_cuotas','pago_deuda_cuota','movimientos_operativos','cashflow_movimientos','reporte_eventos'] as $t) $f[$t]=DB::table($t)->orderBy('id')->get()->toJson(); return $f; };
  try {
   $otra->beginTransaction(); DB::setDefaultConnection('control_t100_otra'); $primera(); DB::setDefaultConnection($principal);
   $antes=$foto(); DB::statement('SET SESSION innodb_lock_wait_timeout=1'); DB::enableQueryLog();
   $bloqueado=false;
   try { $segunda(); $this->fail('El segundo pedido escribió sin esperar.'); }
   catch(\Illuminate\Database\QueryException|\Illuminate\Database\DeadlockException $err) {
    $this->assertStringContainsString('Lock wait timeout',$err->getMessage()); $sql=$err instanceof \Illuminate\Database\QueryException?$err:$err->getPrevious();
    $this->assertStringContainsString('for update',strtolower($sql->getSql())); $bloqueado=true;
   } finally { $queries=DB::getQueryLog(); DB::disableQueryLog(); }
   $escrituras=array_values(array_filter($queries,fn($q)=>preg_match('/^\s*(insert|update|delete)\b/i',$q['query'])));
   $this->assertSame([],$escrituras); $this->assertSame($antes,$foto());
   $otra->commit(); $segunda();
   $q=$this->cuota(); $this->assertNotNull($q); $this->assertSame('30000.00',$q->monto_pagado);
   $this->assertSame($cancelarPrimero?'30000.00':'330000.00',$q->monto_original);
   $row=['admin'=>$admin,'cancelar_primero'=>$cancelarPrimero,'conexiones'=>$ids,'espera_for_update'=>$bloqueado,'escrituras_segundo'=>count($escrituras),'original'=>$q->monto_original,'pagado'=>$q->monto_pagado,'estado'=>$q->estado];
   file_put_contents(getenv('CONTROL_EVIDENCE').'/concurrencia.jsonl',json_encode($row)."\n",FILE_APPEND);
  } finally { DB::setDefaultConnection($principal); while($otra->transactionLevel()>0)$otra->rollBack(); DB::purge('control_t100_otra'); }
 }
 public function test_concurrencia_operativa_cancela_primero():void {$this->carrera(false,true);}
 public function test_concurrencia_operativa_cobra_primero():void {$this->carrera(false,false);}
 public function test_concurrencia_admin_anula_primero():void {$this->carrera(true,true);}
 public function test_concurrencia_admin_cobra_primero():void {$this->carrera(true,false);}
}
