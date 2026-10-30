<?php
// Día 41: dos altas cargadas tarde, a un lado y otro del límite de 15 días del 1/12 (CBZ-017); doble Guardar (ALU-008).
require __DIR__.'/lib.php';
use App\Http\Controllers\{AlumnoWebController, CajaWebController};
use App\Models\{Grupo, Pago, DeudaCuota};
use Illuminate\Support\Str;
$s = U('Sandra Vidal'); $c = app(CajaWebController::class); $al = app(AlumnoWebController::class);
$g = Grupo::whereHas('nivel', fn ($n) => $n->where('nombre', 'Adultos'))->firstOrFail();
$pl = DB::table('grupo_planes')->where('grupo_id', $g->id)->where('clases_por_semana', 1)->first();
foreach ([['Joaquina','Luna','28111222','1991-05-20','2026-11-15','Efectivo'],['Bruno','Cabrera','27333444','1988-09-09','2026-11-16','Mercado Pago']] as [$no,$ap,$dni,$na,$fe,$me]) {
  $tok = (string) Str::uuid();
  $d = ['nombre'=>$no,'apellido'=>$ap,'dni'=>$dni,'fecha_nacimiento'=>$na,'celular'=>'1144556677','nombre_tutor'=>'','telefono_tutor'=>'','deporte_id'=>$g->deporte_id,'grupo_id'=>$g->id,'plan_id'=>$pl->id,'fecha_alta'=>$fe,'alta_token'=>$tok];
  echo "   aviso $fe: ".json_encode(app(App\Services\PagoCuotaService::class)->previsualizarCuotaAlta($fe, $pl->id), JSON_UNESCAPED_UNICODE)."\n";
  paso("41.7 $no", "alta ingreso $fe", enviar($s, 'POST', '/alumnos', $d, fn ($r) => $al->store($r)), 200);
  if ($no === 'Bruno') {
    paso('41.8a', 'doble Guardar: el mismo formulario', enviar($s, 'POST', '/alumnos', $d, fn ($r) => $al->store($r)));
    paso('41.8b', 'atrás, cambia el apellido y reenvía', enviar($s, 'POST', '/alumnos', array_replace($d, ['apellido'=>'Cabreras']), fn ($r) => $al->store($r)), 422);
    echo '   alumnos Cabrera Bruno: '.App\Models\Alumno::where('nombre','Bruno')->whereIn('apellido',['Cabrera','Cabreras'])->count()."\n";
  }
  $a = A("$ap, $no");
  echo "   $no: ".DeudaCuota::where('alumno_id',$a->id)->get()->map(fn($x)=>"$x->periodo:$x->estado ".plata($x->monto_original))->implode(' ').' · inscripción '.plata(DB::table('cargos_alumno')->where('alumno_id',$a->id)->where('tipo','INSCRIPCION')->value('monto_original'))."\n";
  paso("41.9 $no", "paga todo por $me", enviar($s, 'POST', "/caja/cobrar/{$a->id}", ['tipo_caja_id'=>M($me),'periodos'=>['2026-11'],'fecha_pago'=>'2026-11-19'], fn ($r) => $c->pagar($r,$a->id), true), 200);
  echo '   cobro '.plata(Pago::where('alumno_id',$a->id)->latest('id')->value('monto_final'))."\n";
}
estado('fin x41');
