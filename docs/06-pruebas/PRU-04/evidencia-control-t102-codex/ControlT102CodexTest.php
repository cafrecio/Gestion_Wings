<?php
namespace Tests\Feature;
require_once __DIR__.'/FixtureT102Codex.php';
use App\Models\{Alumno,AlumnoPlan,Asistencia,Clase,Deporte,DeudaCuota,Grupo,GrupoPlan,Nivel,Profesor};
use App\Services\{LiquidacionService,ReporteMensualService};
use Illuminate\Support\Facades\DB;
class ControlT102CodexTest extends FixtureT102Codex {
 protected function nivelDestino():Nivel{return Nivel::create(['nombre'=>'Destino T102 '.Nivel::count(),'activo'=>true]);}
 protected function guardarNivel(int $nivel,array $extra=[]){return $this->actingAs($this->admin)->from('/grupos/'.$this->plan->grupo_id.'/edit')->put(route('web.grupos.update',$this->plan->grupo_id),$extra+['nivel_id'=>$nivel,'planes'=>[['id'=>$this->plan->id,'clases_por_semana'=>2,'precio_mensual'=>30000]]]);}
 public function test_control_rechazos_y_catalogos_completos():void {
  $g=$this->plan->grupo;$orig=Nivel::findOrFail($g->nivel_id);$dest=$this->nivelDestino();$bad=Nivel::create(['nombre'=>'Inactivo T102','activo'=>false]);
  $d=Deporte::create(['nombre'=>'Otro deporte T102','tipo_liquidacion'=>'HORA','activo'=>true]);Grupo::create(['deporte_id'=>$d->id,'nivel_id'=>$orig->id,'activo'=>true]);
  $this->actingAs($this->admin)->patchJson(route('web.niveles.toggle-activo',$orig->id))->assertStatus(422)->assertJson(['error'=>'No se puede inactivar el nivel porque tiene 2 grupos activos. Pasalos a otro nivel o desactivalos.']);$this->assertTrue($orig->fresh()->activo);
  $this->get(route('web.grupos.create'))->assertOk()->assertDontSee('Inactivo T102');$this->get(route('web.grupos.edit',$g->id))->assertOk()->assertDontSee('Inactivo T102');
  $this->post(route('web.grupos.store'),['deporte_id'=>$g->deporte_id,'nivel_id'=>$bad->id,'planes'=>[['clases_por_semana'=>2,'precio_mensual'=>30000]]])->assertSessionHas('error');
  $this->guardarNivel($bad->id)->assertSessionHas('error');$this->assertSame($orig->id,$g->fresh()->nivel_id);
  $empty=Nivel::create(['nombre'=>'Vacio T102','activo'=>true]);$this->patchJson(route('web.niveles.toggle-activo',$empty->id))->assertOk();$this->assertFalse($empty->fresh()->activo);
  $inactive=Grupo::create(['deporte_id'=>$g->deporte_id,'nivel_id'=>$bad->id,'activo'=>false]);$this->patchJson(route('web.grupos.toggle-activo',$inactive->id))->assertStatus(422);$this->assertFalse($inactive->fresh()->activo);$this->assertSame($bad->id,$inactive->fresh()->nivel_id);
  $occupied=Grupo::create(['deporte_id'=>$g->deporte_id,'nivel_id'=>$dest->id,'activo'=>true]);$this->guardarNivel($dest->id)->assertSessionHas('error');$this->assertSame($orig->id,$g->fresh()->nivel_id);
  $occupied->update(['activo'=>false]);$this->guardarNivel($dest->id)->assertSessionHas('error');$this->assertSame($orig->id,$g->fresh()->nivel_id);
  $valid=$this->nivelDestino();$this->guardarNivel($valid->id,['planes'=>[['id'=>$this->plan->id,'clases_por_semana'=>2,'precio_mensual'=>0]]])->assertSessionHasErrors();$this->assertSame($orig->id,$g->fresh()->nivel_id);
 }
 private function foto():array {$a=[];foreach(['alumnos','alumno_planes','grupo_planes','clases','asistencias','pagos','deuda_cuotas','pago_deuda_cuota','cashflow_movimientos','reporte_eventos','liquidaciones','liquidacion_detalles'] as $t){$a[$t]=DB::table($t)->orderBy('id')->get()->map(fn($r)=>(array)$r)->all();}return $a;}
 public function test_control_historia_eventos_cierre_liquidacion_recibo_y_vistas():void {
  $this->reloj('2026-09-20 10:00:00');$this->alumno->update(['fecha_alta'=>'2026-01-10']);
  $q=DeudaCuota::create(['alumno_id'=>$this->alumno->id,'periodo'=>'2026-09','monto_original'=>60000,'monto_pagado'=>0,'estado'=>'PENDIENTE']);
  $p=$this->cobrar(['2026-09'=>30000],$this->admin,['motivo'=>'Parcial sintetico T102']);
  $prof=Profesor::create(['deporte_id'=>$this->alumno->deporte_id,'nombre'=>'Docente','apellido'=>'Historia T102','dni'=>'99000202','fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','porcentaje_comision'=>40,'activo'=>true]);
  $cl=Clase::create(['grupo_id'=>$this->plan->grupo_id,'fecha'=>'2026-09-20','hora_inicio'=>'17:00','hora_fin'=>'18:00','cancelada'=>false]);$cl->profesores()->attach($prof->id);Asistencia::create(['clase_id'=>$cl->id,'alumno_id'=>$this->alumno->id,'presente'=>true]);
  $liq=app(LiquidacionService::class)->generarLiquidacionMensual($prof->id,9,2026);$liq->update(['estado'=>'CERRADA']);
  $this->reloj('2026-10-05 10:00:00');$dest=$this->nivelDestino();$orig=$this->plan->grupo->nivel_id;
  $antes=$this->foto();$rep=$this->informe('2026-09');$this->assertSame(3000000,$rep['ingresos']);$this->assertSame('12000.00',$liq->fresh()->total_calculado);
  $pdfAntes=$this->actingAs($this->admin)->get(route('web.recibos.cuota',$p->id).'?inline=1')->assertOk()->getContent();$this->assertStringStartsWith('%PDF',$pdfAntes);
  $this->guardarNivel($dest->id)->assertSessionHas('success');$this->assertSame($dest->id,$this->plan->grupo->fresh()->nivel_id);
  $this->assertSame($antes,$this->foto());$post=$this->informe('2026-09');foreach(['ingresos','egresos','resultado','resultado_global','disponible','deuda','por_pagar'] as $k)$this->assertSame($rep[$k],$post[$k],$k);
  $pdfDespues=$this->get(route('web.recibos.cuota',$p->id).'?inline=1')->assertOk()->getContent();$this->assertSame(hash('sha256',$pdfAntes),hash('sha256',$pdfDespues));
  foreach(['/clases/'.$cl->id,'/liquidaciones/'.$liq->id,'/cobranza','/reportes/alumnos?mes=2026-09','/reportes?mes=2026-09','/alumnos/'.$this->alumno->id] as $url)$this->get($url)->assertOk();
  $this->get('/clases/'.$cl->id)->assertSee('Destino T102');$this->get('/liquidaciones/'.$liq->id)->assertSee('Destino T102');
  $q->refresh()->update(['monto_pagado'=>31000]);$events=DB::table('reporte_eventos')->where('deuda_cuota_id',$q->id)->orderBy('id')->get();$this->assertGreaterThanOrEqual(3,$events->count());$this->assertSame([$orig],$events->pluck('nivel_id')->unique()->all());
  $repAfter=$this->informe('2026-09');foreach(['ingresos','egresos','resultado','disponible','deuda','por_pagar'] as $k)$this->assertSame($rep[$k],$repAfter[$k],$k);
  file_put_contents(getenv('CONTROL_EVIDENCE').'/historia-ejecutada.json',json_encode(['nivel_antes'=>$orig,'nivel_despues'=>$dest->id,'tablas_identicas'=>array_keys($antes),'reporte_antes'=>$rep,'reporte_despues'=>$post,'reporte_tras_delta_octubre'=>$repAfter,'eventos'=>$events,'liquidacion'=>$liq->fresh()->toArray(),'pdf_sha256'=>hash('sha256',$pdfAntes)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));file_put_contents(getenv('CONTROL_EVIDENCE').'/recibo-conservado.pdf',$pdfAntes);
 }
}
