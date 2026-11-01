<?php
namespace Tests\Feature;
require_once __DIR__.'/ControlT100CodexTest.php';
use App\Models\MovimientoOperativo;
use Illuminate\Support\Facades\DB;
class ManualRechazosT100CodexTest extends ControlT100CodexTest
{
 public function test_manual_rechazos_por_motivo_estado_y_cobro_ya_cancelado():void {
  $p=$this->cobrar(['2026-12'=>300000]);$m=MovimientoOperativo::where('pago_id',$p->id)->sole();
  $ruta=route('web.caja.movimientos.cancelar.store',[$this->caja->id,$m->id]);
  $foto=fn()=>[DB::table('pagos')->orderBy('id')->get()->toJson(),DB::table('deuda_cuotas')->orderBy('id')->get()->toJson(),DB::table('movimientos_operativos')->orderBy('id')->get()->toJson()];
  $antes=$foto();$this->actingAs($this->op)->post($ruta,[])->assertSessionHasErrors('motivo');$this->assertSame($antes,$foto());
  foreach(['CERRADA','VALIDADA'] as $estado){DB::table('cajas_operativas')->where('id',$this->caja->id)->update(['estado'=>$estado]);$antes=$foto();
   $this->actingAs($this->op)->get($ruta)->assertSessionHas('error');
   $this->actingAs($this->op)->post($ruta,['motivo'=>'Control de estado prohibido'])->assertSessionHas('error');$this->assertSame($antes,$foto());
  }
  DB::table('cajas_operativas')->where('id',$this->caja->id)->update(['estado'=>'ABIERTA']);$this->revertir($p);$antes=$foto();
  $this->actingAs($this->op)->post($ruta,['motivo'=>'Control de segundo intento'])->assertSessionHas('error');$this->assertSame($antes,$foto());
 }
}
