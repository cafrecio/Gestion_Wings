from pathlib import Path
import hashlib,json,re,struct
r=Path(__file__).resolve().parent
repo=r.parents[3]
fuentes=json.loads((r/'fuentes-finales.json').read_text(encoding='utf8'))['fuentes']
suite=json.loads((r/'fuentes-suite.json').read_text(encoding='utf8'))['fuentes']
assert fuentes==suite,'Las fuentes finales deben coincidir con la suite definitiva'
for f,h in fuentes.items():
 assert hashlib.sha256((repo/f).read_bytes().replace(b'\r\n',b'\n')).hexdigest()==h,f
medidas=json.loads((r/'mediciones.json').read_text(encoding='utf8'))['resultados']
assert len(medidas)==33
fotos=[]
for m in medidas:
 assert m['ancho'] in [360,375,1366] and m['contenido']<=m['ancho'] and m['css']=='16px'
 fotos+=m['fotos']
inter=json.loads((r/'interaccion.json').read_text(encoding='utf8'))['resultados']
assert len(inter)==3
for m in inter:
 assert (m['antes'],m['despues'],m['saldo'],m['pagado'],m['precio'])==(30000,50000,50000,10000,60000)
 assert m['botonHabilitado'] and m['totalVisible']=='$50,000.00'
 fotos.append({'archivo':m['archivo'],'sha256':m['sha256']})
for f in fotos:
 b=(r/f['archivo']).read_bytes();assert hashlib.sha256(b).hexdigest()==f['sha256'],f
 w,h=struct.unpack('>II',b[16:24]);assert w in [360,375,1366] and h==1100
for e in json.loads((r/'respuestas.json').read_text(encoding='utf8')):
 assert e['estado']==200
 assert hashlib.sha256((r/'html'/f"{e['slug']}.html").read_bytes()).hexdigest()==e['sha256']
m=json.loads((r/'migracion.json').read_text(encoding='utf8'))
assert m['base']=='wings_testing_codex' and m['fecha_inicial'] is None and m['rollback_y_up']
assert m['precio_2000_01']==m['precio_2027_01']==40000.5
s=json.loads((r/'suite-final.json').read_text(encoding='utf8'))
assert s['fallidas']==0 and s['salida']==0 and s['base']=='wings_testing_codex'
assert hashlib.sha256((r/'suite-final.txt').read_bytes()).hexdigest()==s['sha256']
for doc in [repo/'docs/06-pruebas/PRU-04/IMPLEMENTACION-T44.md',repo/'docs/02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v5.md',repo/'docs/02-contratos/Wings-contrato-cuotas-deudas-pagos-V2.md']:
 for link in re.findall(r'\]\(([^)]+)\)',doc.read_text(encoding='utf8')):
  if not re.match('https?://|#',link):assert (doc.parent/link.split('#')[0]).exists(),(doc,link)
t=next(t for t in json.loads((repo/'docs/00-estado/tareas.json').read_text(encoding='utf8')) if t['id']=='T44')
assert t['estado']=='a_verificar' and t['tiene']=='Claude' and t['hizo']=='Codex'
assert len(re.findall('<section id=',(r/'GALERIA.html').read_text(encoding='utf8')))==2
for name in ['LOG-CODEX.md','RESUMEN-ARRANQUE.md']:
 text=(repo/'docs/00-estado'/name).read_text(encoding='utf8');assert len(text.splitlines())<=150
 if name.startswith('LOG'):assert len(text)<=12000
(r/'capturas-manifiesto.json').write_text(json.dumps(fotos,indent=2)+'\n',encoding='utf8')
resultado={'fuentes_identicas_a_suite':len(fuentes),'respuestas_reales':11,'mediciones':33,'interacciones':3,'capturas':len(fotos),'desbordes':0,'enlaces':True,'entrega_a_claude':True,'salida_suite':s['salida']}
(r/'integridad.json').write_text(json.dumps(resultado,indent=2)+'\n',encoding='utf8');print(json.dumps(resultado))
