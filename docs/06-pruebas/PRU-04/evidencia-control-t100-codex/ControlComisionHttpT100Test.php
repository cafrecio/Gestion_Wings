<?php
namespace Tests\Feature;
use App\Models\{Liquidacion,Pago,User};
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
/** Control sobre fixture vivo de pantalla; no migra, no recrea la base. */
class ControlComisionHttpT100Test extends TestCase
{
 public function test_recalcular_por_post_retiro_cobro_anulado_y_conservo_cerrada():void {
  $this->assertSame('wings_testing_codex',DB::connection()->getDatabaseName());
  \Carbon\Carbon::setTestNow('2026-12-03 10:00:00');\Carbon\CarbonImmutable::setTestNow('2026-12-03 10:00:00');
  try {
   $antes=Liquidacion::findOrFail(2)->toArray();$cerrada=Liquidacion::findOrFail(1)->toArray();
   $this->assertSame('120000.00',$antes['total_calculado']);$this->assertSame('ANULADO',Pago::findOrFail(18)->estado);
   $this->actingAs(User::findOrFail(1))->post(route('web.liquidaciones.recalcular',2))->assertSessionHas('success','Liquidación recalculada correctamente.');
   $despues=Liquidacion::findOrFail(2)->toArray();$this->assertSame('0.00',$despues['total_calculado']);
   $this->assertSame(0,Liquidacion::findOrFail(2)->detalles()->count());$this->assertSame($cerrada,Liquidacion::findOrFail(1)->toArray());
   file_put_contents(__DIR__.'/comision-post-resultado.json',json_encode(['antes'=>$antes,'despues'=>$despues,'cerrada_intacta'=>$cerrada],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
  }finally{\Carbon\Carbon::setTestNow();\Carbon\CarbonImmutable::setTestNow();}
 }
}
