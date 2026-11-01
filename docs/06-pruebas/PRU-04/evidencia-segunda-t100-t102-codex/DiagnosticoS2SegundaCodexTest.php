<?php
namespace Tests\Feature;
require_once getenv('CONTROL_APP').'/tests/Feature/InscripcionConcurrenteTest.php';
use Illuminate\Support\Facades\DB;
class DiagnosticoS2SegundaCodexTest extends InscripcionConcurrenteTest {
 public function test_diagnostico_s2_ya_llega_al_alta():void {
  $this->assertSame('wings_testing_codex',DB::connection()->getDatabaseName());$estado=DB::table('primera_carga')->where('id',1)->value('estado');$this->assertSame('TERMINADA',$estado);
  $prop=new \ReflectionProperty(InscripcionConcurrenteTest::class,'datos');$datos=$prop->getValue($this);$r=$this->post('/alumnos',$datos);$r->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');$this->assertNotSame(route('web.primera-carga.index'),$r->headers->get('Location'));$this->assertDatabaseCount('alumnos',1);
  file_put_contents(getenv('CONTROL_EVIDENCE').'/s2-diagnostico.json',json_encode(['estado'=>$estado,'destino'=>$r->headers->get('Location'),'alumnos'=>DB::table('alumnos')->get(['id','dni'])->toArray()],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 }
}
