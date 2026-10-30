<?php
// Día 45: el dueño intenta validar la caja abierta (CAJ-026), Sandra fuerza validar (CAJ-028), el dueño carga un gasto en el turno de Sandra (CAJ-041), el dueño va a Nuevo movimiento (MOV-006) y cancela la clase de Intermedias con lista (CLA-012 c: Alma).
require __DIR__.'/lib.php';
use App\Http\Controllers\{CajaWebController, ClaseWebController};
use App\Models\{Clase, CajaOperativa, MovimientoOperativo};
$adm=U('Admin Prueba'); $s=U('Sandra Vidal'); $c=app(CajaWebController::class);
$k=CajaOperativa::where('estado','ABIERTA')->where('usuario_operativo_id',$s->id)->firstOrFail();
$esp=fn()=>app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
paso('45.7a','dueño valida la caja abierta', enviar($adm,'POST',"/cajas/{$k->id}/validar",[],fn($r)=>$c->validar($k->id)),400);
echo '   caja: '.$k->fresh()->estado."\n";
$h=http($s,"/cajas/{$k->id}/validar",'POST'); echo '   Sandra fuerza Validar: '.$h[0].' '.$h[1]."\n";
$h=http($adm,'/caja/movimiento'); echo '   dueño en Nuevo movimiento: '.$h[0].' '.$h[1]."\n";
$a=$esp();
paso('45.8','dueño: Insumos $3.000 en el turno de Sandra', enviar($adm,'POST',"/caja/{$k->id}/editar",['tipo_caja_id'=>M('Efectivo'),'subrubro_id'=>S('Insumos Varios'),'monto'=>'3000','fecha'=>'2026-11-23','observaciones'=>'Bombitas de luz: las pagó la caja'],fn($r)=>$c->editarStore($r,$k->id)),200);
$m=MovimientoOperativo::where('caja_operativa_id',$k->id)->latest('id')->first();
echo '   esperado '.plata($a).' → '.plata($esp()).' · movimiento: '.json_encode($m->only(['usuario_id','usuario_admin_id','created_by','observaciones']), JSON_UNESCAPED_UNICODE)."\n";
$int=Clase::whereDate('fecha','2026-11-23')->whereTime('hora_inicio','17:00:00')->whereHas('grupo',fn($g)=>$g->whereHas('nivel',fn($n)=>$n->where('nombre','Intermedias')))->firstOrFail();
$alma=A('Navarro, Alma');
$pres=fn()=>DB::table('asistencias as s')->join('clases as c','c.id','=','s.clase_id')->where('s.alumno_id',$alma->id)->where('s.presente',1)->whereBetween('c.fecha',['2026-11-01','2026-11-30'])->where('c.cancelada',0)->count();
echo '   Alma presente en #'.$int->id.': '.DB::table('asistencias')->where('clase_id',$int->id)->where('alumno_id',$alma->id)->value('presente').' · presentes de noviembre en clases vigentes: '.$pres()."\n";
$cc=app(ClaseWebController::class);
paso('45.9a','dueño cancela sin confirmar', enviar($adm,'PATCH',"/clases/{$int->id}/cancelar",['motivo_cancelacion'=>'Se cortó la luz a los 15 minutos'],fn($r)=>$cc->toggleCancelada($r,$int->id),true));
paso('45.9b','confirma', enviar($adm,'PATCH',"/clases/{$int->id}/cancelar",['motivo_cancelacion'=>'Se cortó la luz a los 15 minutos','confirmar'=>'1'],fn($r)=>$cc->toggleCancelada($r,$int->id),true),200);
echo '   cancelada: '.$int->fresh()->cancelada.' · marcas conservadas '.DB::table('asistencias')->where('clase_id',$int->id)->count().' · presentes de noviembre de Alma en clases vigentes: '.$pres()."\n";
$sl=Clase::where('cancelada',false)->whereDate('fecha','<=',today())->where('validada_para_liquidacion',false)->doesntHave('asistencias')->get(); echo '   sin lista: '.$sl->count().' #'.$sl->pluck('id')->implode(',')."\n";
