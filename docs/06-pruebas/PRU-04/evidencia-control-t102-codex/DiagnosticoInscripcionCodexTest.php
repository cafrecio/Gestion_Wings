<?php
namespace Tests\Feature;
require_once getenv('CONTROL_APP').'/tests/Feature/InscripcionConcurrenteTest.php';
use Illuminate\Support\Facades\DB;
class DiagnosticoInscripcionCodexTest extends InscripcionConcurrenteTest {
 public function test_diagnostico_primera_carga_del_fixture():void {
  $this->assertSame('wings_testing_codex',DB::connection()->getDatabaseName());$estado=DB::table('primera_carga')->where('id',1)->value('estado');$this->assertSame('PENDIENTE',$estado);
  $prop=new \ReflectionProperty(InscripcionConcurrenteTest::class,'datos');$datos=$prop->getValue($this);$r=$this->post('/alumnos',$datos);
  $r->assertRedirect(route('web.primera-carga.index'))->assertSessionHas('error');$this->assertDatabaseCount('alumnos',0);
  file_put_contents(getenv('CONTROL_EVIDENCE').'/diagnostico-inscripcion.json',json_encode(['estado'=>$estado,'destino'=>$r->headers->get('Location'),'alumnos'=>DB::table('alumnos')->count(),'conclusion'=>'El POST sale por primera carga antes de llegar a la inscripcion; no comprueba el candado.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 }
}
