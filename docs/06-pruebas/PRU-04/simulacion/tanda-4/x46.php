<?php
// Día 46: doble cobro de Milagros (COB-023), lista atrasada de Hernán (por teléfono), Thiago Ruiz se anota en un segundo deporte sin pagar (ficha que irá a Revisión).
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController, ClaseWebController};
use App\Models\{Alumno, Clase, Grupo, DeudaCuota, Pago};
use Illuminate\Support\Str;
$p=U('Pablo Ledesma'); $her=U('Hernán Quintana'); $c=app(CajaWebController::class);
$mi=A('Ferreyra, Milagros');
paso('46.6','otra pestaña: vuelve a cobrar a Milagros', enviar($p,'POST',"/caja/cobrar/{$mi->id}",['tipo_caja_id'=>M('Mercado Pago'),'periodos'=>['2026-11'],'fecha_pago'=>'2026-11-24'],fn($r)=>$c->pagar($r,$mi->id),true),422);
echo '   pagos de Milagros: '.Pago::where('alumno_id',$mi->id)->count()."\n";
$cl=Clase::whereDate('fecha','2026-11-23')->whereTime('hora_inicio','16:00:00')->whereHas('grupo',fn($g)=>$g->whereHas('deporte',fn($d)=>$d->where('nombre','Fútbol')))->firstOrFail();
$fa=A('Rios, Facundo')->id;
$items=Alumno::enGrupoElDia($cl->grupo_id,$cl->fecha)->where('activo',true)->pluck('id')->map(fn($id)=>['alumno_id'=>$id,'presente'=>$id===$fa?0:1,'motivo_exceso'=>'EXTRA'])->all();
paso('46.7','Hernán carga la lista del lunes', enviar($her,'POST',"/clases/{$cl->id}/asistencias",['items'=>$items],fn($r)=>app(ClaseWebController::class)->storeAsistencias($r,$cl->id),true),200);
echo '   lista #'.$cl->id.': '.count($items).' alumnos · sin lista ahora: '.Clase::where('cancelada',false)->whereDate('fecha','<=',today())->where('validada_para_liquidacion',false)->doesntHave('asistencias')->count()."\n";
$th=A('Ruiz, Thiago'); $g=Grupo::whereHas('deporte',fn($d)=>$d->where('nombre','Patín'))->whereHas('nivel',fn($n)=>$n->where('nombre','Principiantes'))->firstOrFail();
$pl=DB::table('grupo_planes')->where('grupo_id',$g->id)->where('clases_por_semana',1)->first();
echo '   Thiago: DNI '.$th->dni.' tutor '.$th->nombre_tutor.' · nace '.$th->fecha_nacimiento?->format('Y-m-d')."\n";
paso('46.8','Thiago también en Patín, ingreso 24/11', enviar($p,'POST','/alumnos',['nombre'=>'Thiago','apellido'=>'Ruiz','dni'=>$th->dni,'fecha_nacimiento'=>$th->fecha_nacimiento?->format('Y-m-d'),'celular'=>$th->celular ?? '','nombre_tutor'=>$th->nombre_tutor ?: 'Marta Ruiz','telefono_tutor'=>$th->telefono_tutor ?: '1122334455','deporte_id'=>$g->deporte_id,'grupo_id'=>$g->id,'plan_id'=>$pl->id,'fecha_alta'=>'2026-11-24','alta_token'=>(string)Str::uuid()],fn($r)=>app(AlumnoWebController::class)->store($r)),200);
$f=Alumno::where('apellido','Ruiz')->where('nombre','Thiago')->where('grupo_id',$g->id)->first();
echo '   ficha de Patín #'.$f?->id.': '.DeudaCuota::where('alumno_id',$f?->id)->get()->map(fn($x)=>"$x->periodo:$x->estado ".plata($x->monto_original))->implode(' ').' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id',$f?->id)->where('tipo','INSCRIPCION')->value('monto_original') ?? 0)."\n";
estado('fin x46');
