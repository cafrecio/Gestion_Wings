<?php
namespace Tests\Feature;
use App\Models\{CashflowMovimiento,Subrubro,TipoCaja,User};
use App\Services\{TransferenciaCashflowService,CashflowSaldoService,ReporteMensualService};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
class ControlT106CodexTest extends \Tests\TestCase {
 use RefreshDatabase;
 private $admin,$cash,$bank,$inc,$out;
 protected function setUp():void {
  parent::setUp();$this->assertSame('wings_testing_codex',DB::connection()->getDatabaseName());$this->travelTo('2026-11-20 10:00:00');$this->seed(\Database\Seeders\CatalogosSeeder::class);
  DB::table('primera_carga')->where('id',1)->update(['estado'=>'TERMINADA']);foreach(['reporte_cobertura','reporte_analitico_cobertura'] as $t)DB::table($t)->where('id',1)->update(['desde'=>'2026-01-01 00:00:00']);
  $this->admin=User::factory()->create(['rol'=>'ADMIN','activo'=>true]);$this->actingAs($this->admin);
  $this->cash=TipoCaja::where('nombre','Efectivo')->sole();$this->bank=TipoCaja::where('nombre','Banco Galicia')->sole();
  $this->inc=Subrubro::where('nombre','Intereses Banco')->sole();$this->out=Subrubro::where('nombre','Luz')->sole();
  $this->mov($this->inc,$this->cash,421200,'2026-11-16');$this->mov($this->out,$this->cash,-65200,'2026-11-16');
 }
 private function mov($sub,$medio,$monto,$date):void {CashflowMovimiento::create(['fecha'=>$date,'subrubro_id'=>$sub->id,'tipo_caja_id'=>$medio->id,'monto'=>$monto,'observaciones'=>'Control T106','usuario_admin_id'=>$this->admin->id]);}
 private function transfer($date='2026-11-17'):int{return app(TransferenciaCashflowService::class)->registrar($this->admin->id,['clave'=>(string)Str::uuid(),'salida_id'=>$this->cash->id,'destino_id'=>$this->bank->id,'monto'=>'1500000','fecha'=>$date]);}
 private function cf($q=[]){return $this->get('/cashflow?'.http_build_query($q+['periodo'=>'mes','fecha'=>'2026-11-20']))->assertOk();}
 private function hi($q=[]){return $this->get('/caja/historial?'.http_build_query($q+['desde'=>'2026-11-01','hasta'=>'2026-11-30']))->assertOk();}
 private function totals($r,$i,$e):void{$r->assertViewHas('totalIngresos',fn($v)=>(float)$v===$i)->assertViewHas('totalEgresos',fn($v)=>(float)$v===$e);}
 public function test_casos_17_a_21_transferencia_cuentas_anulacion_y_otros_reportes():void {
  $before=app(ReporteMensualService::class)->obtener('2026-11');$this->get('/admin/dashboard')->assertOk();$id=$this->transfer();
  $r=$this->cf();$this->totals($r,421200.0,65200.0);$r->assertViewHas('movimientos',fn($m)=>$m->where('referencia_tipo','TRANSFERENCIA')->count()===2);$this->totals($this->hi(),421200.0,65200.0);
  $this->totals($this->cf(['tipo_caja_id'=>$this->cash->id]),421200.0,1565200.0);$this->totals($this->cf(['tipo_caja_id'=>$this->bank->id]),1500000.0,0.0);
  $s=collect(app(CashflowSaldoService::class)->obtenerSaldosPorTipoCaja()['por_tipo_caja'])->keyBy('tipo_caja_id');$this->assertEquals(-1144000,$s[$this->cash->id]['saldo']);$this->assertEquals(1500000,$s[$this->bank->id]['saldo']);
  $salida=Subrubro::where('nombre','Salida a otro medio')->sole();$this->totals($this->hi(['subrubro_id'=>$salida->id]),0.0,1500000.0);
  $after=app(ReporteMensualService::class)->obtener('2026-11');foreach(['ingresos','egresos','resultado','resultado_global','disponible','deuda','por_pagar'] as $k)$this->assertEquals($before[$k],$after[$k],$k);$inicio=$this->get('/admin/dashboard')->assertOk()->viewData('reporte');foreach(['ingresos','egresos','resultado','disponible'] as $k)$this->assertEquals($before[$k],$inicio[$k],$k);$this->get('/reportes?mes=2026-11')->assertOk();
  $pata=CashflowMovimiento::where('referencia_tipo','TRANSFERENCIA')->where('referencia_id',$id)->firstOrFail();$this->post(route('web.cashflow.transferencia.anular.store',$pata->id),['motivo'=>'Control T106'])->assertSessionHas('success');
  $this->totals($this->cf(),421200.0,65200.0);$this->totals($this->hi(),421200.0,65200.0);$this->totals($this->cf(['tipo_caja_id'=>$this->cash->id]),421200.0,65200.0);$this->totals($this->hi(['subrubro_id'=>$salida->id]),0.0,0.0);
  $this->assertSame(4,CashflowMovimiento::where('reporte_clasificacion','TRASPASO')->count());
  $this->transfer('2026-10-20');$this->totals($this->cf(),421200.0,65200.0);$this->totals($this->hi(),421200.0,65200.0);$this->totals($this->cf(['fecha'=>'2026-10-20']),0.0,0.0);
 }
 public function test_historicos_sueltos_con_una_pata_en_cada_mes():void {
  $subOut=Subrubro::where('nombre','Salida a otro medio')->sole();$subIn=Subrubro::where('nombre','Entrada desde otro medio')->sole();
  $this->mov($subOut,$this->cash,-1500000,'2026-10-31');$this->mov($subIn,$this->bank,1500000,'2026-11-01');
  $this->totals($this->cf(),421200.0,65200.0);$this->totals($this->cf(['fecha'=>'2026-10-20']),0.0,0.0);$this->totals($this->hi(),421200.0,65200.0);
  $report=app(ReporteMensualService::class)->obtener('2026-11');$this->assertSame(185600000,$report['resultado_global']);$this->assertSame(35600000,$report['resultado']);
  $reportOct=app(ReporteMensualService::class)->obtener('2026-10');$this->assertSame(-150000000,$reportOct['resultado_global']);
  $this->totals($this->hi(['subrubro_id'=>$subIn->id]),1500000.0,0.0);
 }
}
