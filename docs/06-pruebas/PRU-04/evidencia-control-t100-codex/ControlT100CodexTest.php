<?php
namespace Tests\Feature;
use App\Models\{Alumno,AlumnoPlan,AlumnoRevisionCobranza,Asistencia,CajaOperativa,CargoAlumno,CashflowMovimiento,Clase,Deporte,DeudaCuota,Grupo,GrupoPlan,Liquidacion,LiquidacionDetalle,MovimientoOperativo,Nivel,Pago,Profesor,ReglaPrimerPago,Rubro,Subrubro,TipoCaja,User};
use App\Services\{CajaService,LiquidacionService,PagoCuotaService,ReporteMensualService};
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Mail,Notification};
use Tests\TestCase;

/** Control independiente. Se ejecuta fuera del catálogo permanente, sobre fuentes aisladas. */
class ControlT100CodexTest extends TestCase
{
 use RefreshDatabase;
 protected User $admin; protected User $op; protected Alumno $alumno; protected TipoCaja $medio;
 protected CajaOperativa $caja; protected GrupoPlan $plan;
 protected function reloj(string $fecha):void { Carbon::setTestNow($fecha); CarbonImmutable::setTestNow($fecha); }
 protected function setUp():void {
  parent::setUp(); $this->assertSame('wings_testing_codex',DB::connection()->getDatabaseName());
  $this->reloj('2026-10-05 10:00:00'); Mail::fake(); Notification::fake();
  foreach(['reporte_cobertura','reporte_analitico_cobertura'] as $tabla) DB::table($tabla)->where('id',1)->update(['desde'=>'2026-01-01 00:00:00']);
  $r=Rubro::create(['nombre'=>'Cuotas','tipo'=>'INGRESO']);
  Subrubro::create(['rubro_id'=>$r->id,'nombre'=>'Cuota Mensual','permitido_para'=>'OPERATIVO','afecta_caja'=>true,'es_reservado_sistema'=>true,'activo'=>true]);
  $this->medio=TipoCaja::create(['nombre'=>'Efectivo','activo'=>true,'saldo_inicial'=>0]);
  $this->admin=User::factory()->create(['name'=>'Control Dueño','rol'=>'ADMIN','activo'=>true]);
  $this->op=User::factory()->create(['name'=>'Control Operativa','rol'=>'OPERATIVO','activo'=>true]);
  $this->caja=\Tests\Support\CajaDeclarada::crear($this->op->id,$this->medio->id);
  $d=Deporte::create(['nombre'=>'Control T100','tipo_liquidacion'=>'COMISION','activo'=>true]);
  $n=Nivel::create(['nombre'=>'Control inicial','activo'=>true]);
  $g=Grupo::create(['deporte_id'=>$d->id,'nivel_id'=>$n->id,'activo'=>true]);
  $this->plan=GrupoPlan::create(['grupo_id'=>$g->id,'clases_por_semana'=>2,'precio_mensual'=>30000,'activo'=>true]);
  $this->alumno=Alumno::create(['nombre'=>'Clara Sintética','apellido'=>'Control T100','dni'=>'99000100','fecha_nacimiento'=>'2000-01-01','celular'=>'1100000100','fecha_alta'=>'2026-01-10','grupo_id'=>$g->id,'deporte_id'=>$d->id,'activo'=>true]);
  AlumnoPlan::create(['alumno_id'=>$this->alumno->id,'plan_id'=>$this->plan->id,'fecha_desde'=>'2026-01-10','activo'=>true]);
  DeudaCuota::create(['alumno_id'=>$this->alumno->id,'periodo'=>'2026-10','monto_original'=>30000,'monto_pagado'=>30000,'estado'=>'PAGADA']);
 }
 protected function tearDown():void { Carbon::setTestNow(); CarbonImmutable::setTestNow(); parent::tearDown(); }
 protected function cuota(string $mes='2026-12'):?DeudaCuota {return DeudaCuota::where('alumno_id',$this->alumno->id)->where('periodo',$mes)->first();}
 protected function cobrar(array $partes,?User $user=null,array $extra=[]):Pago {
  $this->actingAs($user??$this->op)->post(route('web.caja.pagar',$this->alumno->id),array_replace([
   'tipo_caja_id'=>$this->medio->id,'periodos'=>array_keys($partes),'montos_cuota'=>$partes,'fecha_pago'=>now()->toDateString(),'confirmar_pago_adelantado'=>1],$extra))->assertSessionHas('success');
  return Pago::latest('id')->firstOrFail();
 }
 protected function revertir(Pago $pago,bool $admin=false):void {
  if($admin) $this->actingAs($this->admin)->post(route('web.pagos.anular',$pago->id),['motivo'=>'Control: cero de más'])->assertSessionHas('success');
  else { $m=MovimientoOperativo::where('pago_id',$pago->id)->sole(); $this->actingAs($this->op)->post(route('web.caja.movimientos.cancelar.store',[$this->caja->id,$m->id]),['motivo'=>'Control: cero de más'])->assertSessionHas('success'); }
 }
 protected function informe(string $mes):array {return app(ReporteMensualService::class)->obtener($mes);}
 public function test_caso_1_operativa_diciembre_y_caja_al_peso():void {
  $antes=app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado'];
  $p=$this->cobrar(['2026-12'=>300000]); $this->assertSame('300000.00',$this->cuota()->monto_original);
  $this->assertEquals($antes+300000,app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
  $this->revertir($p); $this->assertNull($this->cuota());
  $this->assertEquals($antes,app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
  $this->cobrar(['2026-12'=>30000]); $q=$this->cuota();
  $this->assertSame(['30000.00','30000.00','PAGADA',0.0],[$q->monto_original,$q->monto_pagado,$q->estado,$q->saldo_pendiente]);
 }
 public function test_caso_2_duenio_cashflow_y_ficha_sin_cuota():void {
  $p=$this->cobrar(['2026-12'=>300000],$this->admin);
  $this->assertEquals(300000,CashflowMovimiento::sum('monto')); $this->revertir($p,true);
  $this->assertNull($this->cuota()); $this->assertEquals(0,CashflowMovimiento::sum('monto'));
  $this->assertSame('ANULADO',$p->fresh()->estado); $this->assertSame('2026-12',$p->fresh()->detalle_anulacion['periodos'][0]['periodo']);
  $this->actingAs($this->admin)->get(route('web.alumnos.show',$this->alumno->id))->assertOk()->assertSee('Anulado');
  $this->cobrar(['2026-12'=>30000],$this->admin); $this->assertEquals(30000,CashflowMovimiento::sum('monto'));
 }
 public function test_casos_3_4_generacion_con_y_sin_asistencia():void {
  $this->revertir($this->cobrar(['2026-11'=>300000]));
  $cl=Clase::create(['grupo_id'=>$this->alumno->grupo_id,'fecha'=>'2026-10-20','hora_inicio'=>'17:00','hora_fin'=>'18:00','cancelada'=>false]);
  Asistencia::create(['clase_id'=>$cl->id,'alumno_id'=>$this->alumno->id,'presente'=>true]);
  $this->reloj('2026-11-01 06:00:00'); $this->artisan('cobranza:generar-deudas')->assertSuccessful();
  $this->assertSame('30000.00',$this->cuota('2026-11')->monto_original);
  $this->assertSame('PENDIENTE',$this->cuota('2026-11')->estado);
  $this->reloj('2026-11-05 10:00:00'); $this->revertir($this->cobrar(['2026-12'=>300000],$this->admin,['motivo'=>'Control aislado de diciembre con noviembre pendiente']),true);
  $this->reloj('2026-12-01 06:00:00'); $this->artisan('cobranza:generar-deudas')->assertSuccessful();
  $this->assertNull($this->cuota()); $this->assertTrue(AlumnoRevisionCobranza::where('alumno_id',$this->alumno->id)->where('periodo_objetivo','2026-12')->exists());
 }
 public function test_caso_5_caja_rechazada_mes_empezado():void {
  $p=$this->cobrar(['2026-12'=>300000]); $this->caja->update(['estado'=>'RECHAZADA']);
  $this->reloj('2026-12-03 10:00:00'); $this->revertir($p);
  $q=$this->cuota(); $this->assertSame(['30000.00','0.00','PENDIENTE',null],[$q->monto_original,$q->monto_pagado,$q->estado,$q->pago_origen_id]);
 }
 public function test_caso_6_multimes_y_7_cuota_preexistente():void {
  $this->cuota('2026-10')->update(['monto_pagado'=>0,'estado'=>'PENDIENTE']);
  $this->revertir($this->cobrar(['2026-10'=>30000,'2026-12'=>30000]));
  $this->assertSame('PENDIENTE',$this->cuota('2026-10')->estado); $this->assertNull($this->cuota());
  DeudaCuota::create(['alumno_id'=>$this->alumno->id,'periodo'=>'2026-12','monto_original'=>25000,'monto_pagado'=>0,'estado'=>'PENDIENTE']);
  $this->revertir($this->cobrar(['2026-12'=>25000],null,['motivo'=>'Control de cuota preexistente con octubre pendiente'])); $this->assertSame('25000.00',$this->cuota()->monto_original);
 }
 public function test_caso_8_dos_cobros_ambos_ordenes():void {
  foreach([false,true] as $inverso) {
   $p=$this->cobrar(['2026-12'=>30000],$this->admin); $this->cuota()->update(['monto_original'=>50000,'estado'=>'PENDIENTE']);
   $p2=$this->cobrar(['2026-12'=>20000],$this->admin,['motivo'=>'Control de saldo restante']);
   $this->revertir($inverso?$p2:$p,true); $this->assertSame($inverso?'30000.00':'20000.00',$this->cuota()->monto_pagado);
   $this->revertir($inverso?$p:$p2,true); $this->assertNull($this->cuota());
  }
 }
 public function test_caso_9_recargo_y_perdida_impiden_borrar():void {
  $p=$this->cobrar(['2026-12'=>300000],$this->admin);
  CargoAlumno::create(['alumno_id'=>$this->alumno->id,'deuda_cuota_id'=>$this->cuota()->id,'tipo'=>'PUNITORIO','clave_origen'=>'control-t100','monto_original'=>100,'estado'=>'VIGENTE','calculo'=>[],'subrubro_id'=>Subrubro::first()->id]);
  $this->revertir($p,true); $this->assertSame('30000.00',$this->cuota()->monto_original);
  $p2=$this->cobrar(['2026-11'=>300000],$this->admin); $this->cuota('2026-11')->forceFill(['monto_perdido'=>100])->save();
  $this->revertir($p2,true); $this->assertSame('300000.00',$this->cuota('2026-11')->monto_original);
 }
 public function test_caso_10_primera_cuota_futura_con_descuento():void {
  $this->cuota('2026-10')->delete(); $this->alumno->update(['fecha_alta'=>'2026-12-20']);
  AlumnoPlan::where('alumno_id',$this->alumno->id)->update(['fecha_desde'=>'2026-12-20']);
  ReglaPrimerPago::create(['nombre'=>'Setenta','dia_desde'=>16,'dia_hasta'=>23,'porcentaje'=>70,'activo'=>true]);
  $p=$this->cobrar(['2026-12'=>30000],$this->admin); $this->assertSame('21000.00',$this->cuota()->monto_original);
  $this->revertir($p,true); $this->assertNull($this->cuota());
  $p2=$this->cobrar(['2026-12'=>30000],$this->admin); $this->assertSame('21000.00',$p2->monto_final);
  $this->assertSame('21000.00',$this->cuota()->monto_original);
 }
 public function test_caso_11_orden_tras_cancelar_noviembre():void {
  $p=$this->cobrar(['2026-11'=>30000],$this->admin); $p2=$this->cobrar(['2026-12'=>30000],$this->admin);
  $diciembre=$this->cuota()->toArray(); $this->revertir($p,true);
  $this->assertNull($this->cuota('2026-11')); $this->assertSame($diciembre,$this->cuota()->toArray());
  $this->assertSame('COMPLETADO',$p2->fresh()->estado);
  $this->cobrar(['2026-11'=>30000],$this->admin); $this->assertSame('PAGADA',$this->cuota('2026-11')->estado);
 }
 public function test_reportes_antes_despues_y_cierre_octubre_intacto():void {
  $base=$this->informe('2026-10'); $p=$this->cobrar(['2026-12'=>300000],$this->admin);
  $this->assertSame(0,$this->informe('2026-10')['ingresos']); $this->assertSame(30000000,$this->informe('2026-10')['disponible']);
  $this->revertir($p,true); $fin=$this->informe('2026-10');
  foreach(['ingresos','egresos','deuda','disponible'] as $campo) $this->assertSame($base[$campo],$fin[$campo],$campo);
  $this->assertSame(0,DB::table('reporte_eventos')->where('tipo','CUOTA')->where('periodo','2026-12')->count());
  $this->reloj('2026-11-05 10:00:00'); $cerrado=$this->informe('2026-10'); foreach(['ingresos','egresos','deuda','disponible','resultado'] as $campo) $this->assertSame($fin[$campo],$cerrado[$campo],$campo);
 }
 public function test_caso_9_liquidacion_atada_no_borra_ni_reescribe_cierre():void {
  $p=$this->cobrar(['2026-12'=>300000],$this->admin);
  $prof=Profesor::create(['deporte_id'=>$this->alumno->deporte_id,'nombre'=>'Docente','apellido'=>'Sintética','dni'=>'99000101','fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','porcentaje_comision'=>40,'activo'=>true]);
  $liq=Liquidacion::create(['profesor_id'=>$prof->id,'mes'=>12,'anio'=>2026,'tipo'=>'COMISION','total_calculado'=>120000,'estado'=>'ABIERTA','porcentaje_comision_aplicado'=>40]);
  $detalle=LiquidacionDetalle::create(['liquidacion_id'=>$liq->id,'tipo_referencia'=>'alumno','referencia_id'=>$this->alumno->id,'deuda_cuota_id'=>$this->cuota()->id,'pago_id'=>$p->id,'monto'=>120000,'descripcion'=>'Control cierre congelado','base_cuota'=>300000,'origen_cuota'=>'adelantado','periodo_cuota'=>'2026-12']);
  $liq->update(['estado'=>'CERRADA']); $antes=[$liq->fresh()->toArray(),$detalle->fresh()->toArray()];
  $this->revertir($p,true); $this->assertSame('30000.00',$this->cuota()->monto_original);
  $this->assertSame($antes,[$liq->fresh()->toArray(),$detalle->fresh()->toArray()]);
 }
 public function test_comision_t43_y_t42_sin_comision_residual_del_anulado():void {
  $p=$this->cobrar(['2026-12'=>300000],$this->admin); $this->revertir($p,true);
  $prof=Profesor::create(['deporte_id'=>$this->alumno->deporte_id,'nombre'=>'Docente','apellido'=>'Comisión','dni'=>'99000101','fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','porcentaje_comision'=>40,'activo'=>true]);
  $cl=Clase::create(['grupo_id'=>$this->alumno->grupo_id,'fecha'=>'2026-12-05','hora_inicio'=>'17:00','hora_fin'=>'18:00','cancelada'=>false]); $cl->profesores()->attach($prof->id);
  Asistencia::create(['clase_id'=>$cl->id,'alumno_id'=>$this->alumno->id,'presente'=>true]);
  $this->reloj('2026-12-06 10:00:00');
  $liq=app(LiquidacionService::class)->generarLiquidacionMensual($prof->id,12,2026);
  $this->assertSame('0.00',$liq->total_calculado); $this->assertSame(0,$this->informe('2026-12')['ingresos']);
 }
 public function test_adelanto_anterior_a_t100_no_se_infiere_origen():void {
  $p=$this->cobrar(['2026-12'=>300000],$this->admin); $this->cuota()->update(['pago_origen_id'=>null]);
  $this->revertir($p,true); $q=$this->cuota();
  $this->assertSame(['300000.00','0.00','PENDIENTE'],[$q->monto_original,$q->monto_pagado,$q->estado]);
 }
 public function test_recibo_original_y_regenerado_tras_borrado():void {
  $p=$this->cobrar(['2026-12'=>300000],$this->admin); $this->revertir($p,true); $this->assertNull($this->cuota());
  foreach([0,1] as $regenerar) {
   $r=$this->actingAs($this->admin)->get(route('web.recibos.cuota',$p->id).'?inline=1&regenerar='.$regenerar)->assertOk();
   $this->assertStringStartsWith('%PDF',$r->getContent());
   file_put_contents(getenv('CONTROL_EVIDENCE').'/recibo-anulado-'.$regenerar.'.pdf',$r->getContent());
  }
 }
}
