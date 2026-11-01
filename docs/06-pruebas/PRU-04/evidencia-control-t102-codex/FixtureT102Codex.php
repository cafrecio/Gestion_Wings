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
class FixtureT102Codex extends TestCase
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
  Subrubro::create(['rubro_id'=>$r->id,'nombre'=>'Cuota Mensual','clasificacion_resultado'=>'NEGOCIO','permitido_para'=>'OPERATIVO','afecta_caja'=>true,'es_reservado_sistema'=>true,'activo'=>true]);
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
}
