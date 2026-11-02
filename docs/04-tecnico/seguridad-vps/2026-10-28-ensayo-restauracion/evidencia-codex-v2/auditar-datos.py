import sys,json,subprocess,hashlib
from pathlib import Path

def q(s):return '`'+s.replace('`','``')+'`'
def sql(s):
 p=subprocess.run(['mysql','-N','-B','--raw','-e',s],capture_output=True)
 if p.returncode:raise RuntimeError('Consulta fallo: '+p.stderr.decode(errors='replace')[:300])
 return p.stdout.decode('utf-8')
def meta(db):
 return [json.loads(x) for x in sql("SELECT JSON_ARRAY(TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,DATA_TYPE) FROM information_schema.columns WHERE table_schema='"+db+"' ORDER BY TABLE_NAME,ORDINAL_POSITION").splitlines()]
def scan(db):
 tables={}
 for t,c,typ,null,dt in meta(db):tables.setdefault(t,[]).append([c,typ,null,dt])
 out={}
 for t,cols in tables.items():
  expr=','.join('IF('+q(c)+' IS NULL,NULL,HEX(CAST('+q(c)+' AS BINARY)))' for c,*_ in cols)
  rows=sorted(sql('SELECT JSON_ARRAY('+expr+') FROM '+q(db)+'.'+q(t)).splitlines())
  digest=hashlib.sha256(('\n'.join(rows)).encode()).hexdigest()
  out[t]={'columnas':cols,'filas':len(rows),'privado_contenido':digest}
 return out

def baseline():
 data={db:scan(db) for db in ('wings','wingstest')}
 Path('/root/control-t82-codex-v2/antes-privado.json').write_text(json.dumps(data))
 print(json.dumps({'control':'estado_vivo_inicial','tablas':{k:len(v) for k,v in data.items()}}))
def liveafter():
 before=json.loads(Path('/root/control-t82-codex-v2/antes-privado.json').read_text());results={};bad=False
 for db,old in before.items():
  now=scan(db);changed=[t for t in sorted(set(old)|set(now)) if old.get(t)!=now.get(t)]
  results[db]={'tablas':len(now),'tablas_cambiadas':changed,'sin_cambios':not changed};bad|=bool(changed)
 print(json.dumps({'control':'bases_vivas_despues','resultado':results}));return int(bad)
def compare(src,dst):
 a,b=scan(src),scan(dst);result=[];bad=False
 for t in sorted(set(a)|set(b)):
  x,y=a.get(t),b.get(t);same=x==y;bad|=not same
  result.append({'tabla':t,'filas_viva':x['filas'] if x else None,'filas_copia':y['filas'] if y else None,'columnas_identicas':bool(x and y and x['columnas']==y['columnas']),'contenido_completo_identico':bool(x and y and x['privado_contenido']==y['privado_contenido'])})
 print(json.dumps({'control':'comparacion_independiente_todas_las_tablas','viva':src,'copia':dst,'tablas':result,'diferencias':sum(not(x.get('columnas_identicas') and x.get('contenido_completo_identico')) for x in result)}))
 important={'pagos','pago_deuda_cuota','deuda_cuotas','movimientos_operativos','cashflow_movimientos','liquidaciones','liquidacion_detalles','cargos_alumno','cajas_operativas'}
 money=[]
 for t in sorted(important & set(a) & set(b)):
  for c,typ,null,dt in a[t]['columnas']:
   if dt not in ('decimal','double','float'):continue
   query='SELECT JSON_ARRAY(COUNT('+q(c)+'),CAST(SUM(CAST('+q(c)+' AS DECIMAL(40,6))) AS CHAR)) FROM {db}.'+q(t)
   x=json.loads(sql(query.format(db=q(src))));y=json.loads(sql(query.format(db=q(dst))));same=x==y;bad|=not same
   money.append({'tabla':t,'columna':c,'valores_no_nulos':int(x[0]),'suma_viva':x[1],'suma_copia':y[1],'igual':same,'evidencia_monetaria':int(x[0])>0})
 print(json.dumps({'control':'plata_independiente_decimal_sin_floats_python','comparaciones':money}))
 return int(bad)
def files(src,dst):
 import os
 def inventory(base):
  result={}
  for p in Path(base).rglob('*'):
   if p.is_file():result[str(p.relative_to(base))]=hashlib.sha256(p.read_bytes()).hexdigest()
  return result
 a,b=inventory(src),inventory(dst);diff=[x for x in set(a)|set(b) if a.get(x)!=b.get(x)]
 print(json.dumps({'control':'archivos_subidos_completos','archivos_vivos':len(a),'archivos_restaurados':len(b),'contenidos_iguales':not diff,'diferencias':len(diff)}));return int(bool(diff))
if __name__=='__main__':
 try:
  action=sys.argv[1];code=baseline() if action=='baseline' else liveafter() if action=='liveafter' else files(sys.argv[2],sys.argv[3]) if action=='files' else compare(sys.argv[2],sys.argv[3]);sys.exit(code or 0)
 except Exception as exc:print(json.dumps({'control':'error','tipo':type(exc).__name__,'detalle':str(exc)}));sys.exit(1)
