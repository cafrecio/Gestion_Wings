import os,sys,subprocess,json,hashlib,time
from pathlib import Path
app=Path(os.environ['CONTROL_APP']); out=Path(__file__).resolve().parent
source=app/'app/Services/PagoCuotaService.php'; original=source.read_bytes(); text=original.decode('utf-8')
custom=str(out/'ControlT100CodexTest.php')
mutations=[
 ('sin_origen','if ($deuda->pago_origen_id === null ||','if (false ||','AdelantoCanceladoT100Test.php','una_cuota_que_ya_existia'),
 ('sin_otro_pago',' || (float) $deuda->monto_pagado > 0','',custom,'caso_8_dos_cobros'),
 ('sin_perdida',' || (float) $deuda->monto_perdido > 0','',custom,'caso_9_recargo_y_perdida'),
 ('sin_recargo',"\\App\\Models\\CargoAlumno::where('deuda_cuota_id', $deuda->id)->exists()",'false',custom,'caso_9_recargo_y_perdida'),
 ('sin_liquidacion',"\\App\\Models\\LiquidacionDetalle::where('deuda_cuota_id', $deuda->id)->exists()",'false',custom,'caso_9_liquidacion_atada'),
 ('sin_mes_empezado',"$deuda->periodo > now()->format('Y-m') && !$enUso",'!$enUso',custom,'caso_5_caja_rechazada'),
 ('sin_borrado','$deuda->delete();','$deuda->save();',custom,'caso_1_operativa'),
 ('sin_precio_plan',"$deuda->monto_original = $plan->plan->precioParaPeriodo($deuda->periodo, Alumno::findOrFail($deuda->alumno_id)->fecha_alta);",'$deuda->monto_original = $deuda->monto_original;',custom,'caso_5_caja_rechazada'),
 ('sin_limpiar_origen','$deuda->pago_origen_id = null;','$deuda->pago_origen_id = $deuda->pago_origen_id;',custom,'caso_5_caja_rechazada'),
 ('sin_marca_origen',"$deuda->update(['pago_origen_id' => $pago->id]);",'// marca retirada', 'AdelantoCanceladoT100Test.php','el_adelanto_recuerda'),
 ('marca_cuota_preexistente','$deuda->wasRecentlyCreated && ', '',custom,'caso_6_multimes'),
 ('sin_cancelacion_operativa','$this->deshacerCuotaNacidaDeCobro($deuda);','// llamada retirada',custom,'caso_1_operativa'),
 ('sin_anulacion_duenio','$this->deshacerCuotaNacidaDeCobro($deuda);','// llamada retirada',custom,'caso_2_duenio'),
]
results=[]
try:
 for name,old,new,test,pattern in mutations:
  count=text.count(old)
  if count==0: raise RuntimeError('Mutacion no aplicable: '+name)
  if name=='sin_anulacion_duenio':
   head,tail=text.rsplit(old,1); changed=head+new+tail
  else: changed=text.replace(old,new,1)
  source.write_text(changed,encoding='utf-8',newline='')
  target=test if test==custom else 'tests/Feature/'+test
  started=time.monotonic()
  run=subprocess.run(['php','artisan','test',target,'--filter='+pattern,'--colors=never'],cwd=app,env=os.environ.copy(),stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
  log=run.stdout.decode('utf-8',errors='replace'); (out/('mutacion-'+name+'.txt')).write_text(log,encoding='utf-8')
  result={'regla':name,'exit':run.returncode,'detectada':run.returncode!=0 and ('Failed asserting' in log or 'Session is missing' in log),'segundos':round(time.monotonic()-started,2)}
  results.append(result); print(json.dumps(result),flush=True)
  source.write_bytes(original)
  (out/'mutaciones.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
finally:
 source.write_bytes(original)
 (out/'mutaciones-restauracion.json').write_text(json.dumps({'antes':hashlib.sha256(original).hexdigest(),'despues':hashlib.sha256(source.read_bytes()).hexdigest(),'iguales':source.read_bytes()==original},indent=2),encoding='utf-8')
