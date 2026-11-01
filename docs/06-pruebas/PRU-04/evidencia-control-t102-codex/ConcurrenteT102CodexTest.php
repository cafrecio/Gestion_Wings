<?php
namespace Tests\Feature;
require_once __DIR__.'/ControlT102CodexTest.php';
use App\Models\{Grupo,Nivel};use Illuminate\Support\Facades\DB;
class ConcurrenteT102CodexTest extends ControlT102CodexTest {
 protected function setUp():void{\Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;parent::setUp();}
 private function cruzar(bool $crear,bool $inactivarPrimero):void {
  $nivel=$this->nivelDestino();DB::commit();$principal=config('database.default');config(['database.connections.t102_codex_otra'=>config('database.connections.'.$principal)]);$otra=DB::connection('t102_codex_otra');$ids=[DB::selectOne('SELECT CONNECTION_ID() id')->id,$otra->selectOne('SELECT CONNECTION_ID() id')->id];$this->assertNotSame($ids[0],$ids[1]);$this->assertSame('wings_testing_codex',$otra->getDatabaseName());
  $this->actingAs($this->admin);$this->withoutExceptionHandling();
  $inactive=fn()=>$this->patchJson(route('web.niveles.toggle-activo',$nivel->id));
  $assign=fn()=>$crear?$this->post(route('web.grupos.store'),['deporte_id'=>$this->alumno->deporte_id,'nivel_id'=>$nivel->id,'planes'=>[['clases_por_semana'=>2,'precio_mensual'=>30000]]]):$this->guardarNivel($nivel->id);
  $first=$inactivarPrimero?$inactive:$assign;$second=$inactivarPrimero?$assign:$inactive;$foto=fn()=>[DB::table('niveles')->orderBy('id')->get()->toJson(),DB::table('grupos')->orderBy('id')->get()->toJson(),DB::table('grupo_planes')->orderBy('id')->get()->toJson()];
  try {
   $otra->beginTransaction();DB::setDefaultConnection('t102_codex_otra');$r=$first();$this->assertLessThan(400,$r->getStatusCode());DB::setDefaultConnection($principal);$antes=$foto();DB::statement('SET SESSION innodb_lock_wait_timeout=1');DB::enableQueryLog();
   try{$second();$this->fail('No espero el bloqueo del otro pedido');}catch(\Illuminate\Database\QueryException|\Illuminate\Database\DeadlockException $error){$this->assertStringContainsString('Lock wait timeout',$error->getMessage());$sql=$error instanceof \Illuminate\Database\QueryException?$error:$error->getPrevious();$this->assertStringContainsString('for update',strtolower($sql->getSql()));}finally{$qs=DB::getQueryLog();DB::disableQueryLog();}
   $writes=array_values(array_filter($qs,fn($q)=>preg_match('/^\s*(insert|update|delete)\b/i',$q['query'])));$this->assertSame([],$writes);$this->assertSame($antes,$foto());$otra->commit();$retry=$second();
   if($inactivarPrimero)$retry->assertSessionHas('error');else $retry->assertStatus(422);
   $invalid=DB::table('grupos as g')->join('niveles as n','n.id','=','g.nivel_id')->where('g.activo',true)->where('n.activo',false)->count();$this->assertSame(0,$invalid);
   file_put_contents(getenv('CONTROL_EVIDENCE').'/concurrencia.jsonl',json_encode(['crear'=>$crear,'inactivar_primero'=>$inactivarPrimero,'conexiones'=>$ids,'escrituras_segundo'=>count($writes),'invariantes_invalidas'=>$invalid])."\n",FILE_APPEND);
  }finally{DB::setDefaultConnection($principal);while($otra->transactionLevel()>0)$otra->rollBack();DB::purge('t102_codex_otra');}
 }
 public function test_carrera_crear_asignar_primero():void{$this->cruzar(true,false);}
 public function test_carrera_crear_inactivar_primero():void{$this->cruzar(true,true);}
 public function test_carrera_editar_asignar_primero():void{$this->cruzar(false,false);}
 public function test_carrera_editar_inactivar_primero():void{$this->cruzar(false,true);}
}
