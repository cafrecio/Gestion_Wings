<?php
// Día 47: Tobías paga y le anulan el cobro (COB-020); Facundo paga noviembre y diciembre sin venir; 15 cobros seguidos (COB-030); baja de Lautaro; serie doble clic (CLA-019); usuario de Gonzalo otra vez (USU-011).
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController, ClaseWebController, UsuarioWebController};
use App\Models\{Alumno, Grupo, Pago, DeudaCuota, CajaOperativa, MovimientoOperativo, Profesor, Clase, User};
use Illuminate\Support\Str;
$ro=U('Rocío Paz'); $adm=U('Admin Prueba'); $c=app(CajaWebController::class);
$k=CajaOperativa::where('estado','ABIERTA')->where('usuario_operativo_id',$ro->id)->firstOrFail();
$esp=fn()=>app(App\Services\CajaService::class)->arqueoCaja($k->id)['efectivo_esperado'];
$g=Grupo::whereHas('deporte',fn($d)=>$d->where('nombre','Fútbol'))->whereHas('nivel',fn($n)=>$n->where('nombre','Principiantes'))->firstOrFail();
$pl=DB::table('grupo_planes')->where('grupo_id',$g->id)->where('clases_por_semana',1)->first();
paso('47.6a','alta de Tobías Vera, ingreso 25/11', enviar($ro,'POST','/alumnos',['nombre'=>'Tobías','apellido'=>'Vera','dni'=>'53777888','fecha_nacimiento'=>'2015-08-30','celular'=>'','nombre_tutor'=>'Andrea Vera','telefono_tutor'=>'1166778899','deporte_id'=>$g->deporte_id,'grupo_id'=>$g->id,'plan_id'=>$pl->id,'fecha_alta'=>'2026-11-25','alta_token'=>(string)Str::uuid()],fn($r)=>app(AlumnoWebController::class)->store($r)),200);
$to=A('Vera, Tobías'); $a0=$esp();
paso('47.6b','paga todo en efectivo', enviar($ro,'POST',"/caja/cobrar/{$to->id}",['tipo_caja_id'=>M('Efectivo'),'periodos'=>['2026-11'],'fecha_pago'=>'2026-11-25'],fn($r)=>$c->pagar($r,$to->id),true),200);
$pt=Pago::where('alumno_id',$to->id)->latest('id')->first(); $movs=MovimientoOperativo::where('pago_id',$pt->id)->get();
echo '   cobro '.plata($pt->monto_final).' · movimientos '.$movs->count().' · esperado '.plata($a0).' → '.plata($esp())."\n";
paso('47.6c','cancela con motivo', enviar($ro,'POST',"/caja/{$k->id}/movimientos/{$movs->first()->id}/cancelar",['motivo'=>'La mamá se arrepintió: no hay cupo en su horario'],fn($r)=>$c->cancelarMovimiento($r,$k->id,$movs->first()->id)),200);
$fila=app(App\Services\CobranzaEstadoService::class)->listadoCobranza('TODOS')->firstWhere('id',$to->id);
echo '   Tobías: pago '.$pt->fresh()->estado.' · movimientos activos '.MovimientoOperativo::where('pago_id',$pt->id)->where('estado','ACTIVO')->count().' · '.$fila?->estado_cobranza.' '.plata($fila?->deuda_total).' · esperado '.plata($esp()).' · inscripción '.DB::table('cargos_alumno')->where('alumno_id',$to->id)->where('tipo','INSCRIPCION')->value('estado')."\n";
$fa=A('Rios, Facundo');
paso('47.7','Facundo Ríos: noviembre con recargo y diciembre, MP', enviar($ro,'POST',"/caja/cobrar/{$fa->id}",['tipo_caja_id'=>M('Mercado Pago'),'periodos'=>['2026-11','2026-12'],'montos_cuota'=>['2026-12'=>'28000'],'fecha_pago'=>'2026-11-25','confirmar_pago_adelantado'=>'1'],fn($r)=>$c->pagar($r,$fa->id),true),200);
echo '   Facundo: '.plata(Pago::where('alumno_id',$fa->id)->latest('id')->value('monto_final')).' · '.DeudaCuota::where('alumno_id',$fa->id)->where('periodo','>=','2026-11')->get()->map(fn($x)=>"$x->periodo:$x->estado")->implode(' ')."\n";
$lista=[['Rios, Lola',30000,'Efectivo'],['Silva, Abril',30000,'Efectivo'],['Flores, Zoe',30000,'Efectivo'],['Gomez, Sofia',30000,'Efectivo'],['Pereyra, Bianca',44000,'Efectivo'],['Perez, Mia',44000,'Efectivo'],['Ledesma, Paula',43000,'Efectivo'],['Ruiz, Sol',43000,'Efectivo'],['Sanchez, Berenice',43000,'Mercado Pago'],['Benitez, Paulina',43000,'Mercado Pago'],['Ferrari, Bautista',35000,'Mercado Pago'],['Acosta, Agustin',35000,'Mercado Pago'],['Navarro, Julian',52000,'Mercado Pago'],['Gimenez, Tomas',52000,'Banco Galicia'],['Iglesias, Helena',50000,'Banco Galicia']];
$t0=microtime(true); $ok=0; foreach($lista as [$n,$mon,$me]){ $a=A($n); $pr=app(App\Services\PagoCuotaService::class)->obtenerPlanParaPeriodo($a->id,'2026-12')?->plan?->precioParaPeriodo('2026-12',$a->fecha_alta);
  if((float)$pr!==(float)$mon) echo "   <<< NO ESPERADO $n: precio ".plata($pr)." y calculé ".plata($mon)."\n";
  $r=enviar($ro,'POST',"/caja/cobrar/{$a->id}",['tipo_caja_id'=>M($me),'periodos'=>['2026-12'],'montos_cuota'=>['2026-12'=>(string)$mon],'fecha_pago'=>'2026-11-25','confirmar_pago_adelantado'=>'1'],fn($q)=>$c->pagar($q,$a->id),true); if($r[0]<400)$ok++; else paso('47.8',$n,$r,200);}
echo "   COB-030: $ok de ".count($lista).' · '.round(microtime(true)-$t0,1).'s · esperado '.plata($esp())."\n";
$la=A('Medina, Lautaro');
paso('47.9','baja de Lautaro Medina', enviar($ro,'PATCH',"/alumnos/{$la->id}/toggle-activo",[],fn($r)=>app(AlumnoWebController::class)->toggleActivo($r,$la->id),true),200);
$gi=Grupo::whereHas('nivel',fn($n)=>$n->where('nombre','Infantiles'))->firstOrFail(); $gon=Profesor::where('apellido','Ibarra')->firstOrFail();
paso('47.10','serie de Infantiles otra vez (doble clic)', enviar($adm,'POST','/clases',['grupo_id'=>$gi->id,'profesores'=>[$gon->id],'tipo_creacion'=>'recurrente','fecha_desde'=>'2026-12-05','fecha_hasta'=>'2026-12-26','dias_semana'=>[6],'horarios'=>[6=>['hora_inicio'=>'10:00','hora_fin'=>'11:00']]],fn($r)=>app(ClaseWebController::class)->store($r)),422);
echo '   clases de Infantiles: '.Clase::where('grupo_id',$gi->id)->count()."\n";
$b=['name'=>'Gonzalo Ibarra','email'=>'gonzalo.ibarra@example.com','password'=>'Infantiles-2026!','password_confirmation'=>'Infantiles-2026!','rol'=>'PROFESOR','profesor_id'=>$gon->id];
paso('47.11','usuario de Gonzalo otra vez (atrás y reenvía)', enviar($adm,'POST','/usuarios',$b,fn($r)=>app(UsuarioWebController::class)->store($r)),422);
echo '   usuarios Gonzalo: '.User::where('email',$b['email'])->count().' · subrubros '.DB::table('subrubros')->where('nombre','like','%Ibarra%')->count()."\n";
[$st,,$h]=http(User::where('email',$b['email'])->first(),'/clases'); echo '   Gonzalo entra: '.$st.' · menú: '.menu($h)."\n";
[$st,,$h]=http($ro,'/alumnos'); echo '   listado de alumnos: '.$st.' · Inactivo visible: '.(stripos($h,'Inactivo')!==false?'sí':'no')."\n";
estado('fin x47');
