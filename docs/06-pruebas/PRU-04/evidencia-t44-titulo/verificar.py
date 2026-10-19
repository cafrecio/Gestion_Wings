from pathlib import Path
import hashlib,json,re
r=Path(__file__).resolve().parent
repo=r.parents[3]
m=json.loads((r/'mediciones.json').read_text(encoding='utf8'))['resultados']
assert len(m)==9
fotos=[]
for x in m:
 assert x['ancho'] in (360,375,1366) and x['contenido']<=x['ancho'] and x['css']=='16px'
 if x['slug']!='login':
  c=x['separacion'];assert c['padresSeparados'] and c['tituloSinBotones'] and c['debajo']
  assert abs(c['derechaNuevo']-c['derechaGuardar'])<1 and c['despues']==c['antes']+1
 fotos+=x['fotos']
for f in fotos:
 assert hashlib.sha256((r/f['archivo']).read_bytes()).hexdigest()==f['sha256']
for x in json.loads((r/'respuestas.json').read_text(encoding='utf8')):
 assert x['estado']==200
 assert hashlib.sha256((r/'html'/f"{x['slug']}.html").read_bytes()).hexdigest()==x['sha256']
for f,h in json.loads((r/'fuentes.json').read_text(encoding='utf8'))['fuentes'].items():
 assert hashlib.sha256((repo/f).read_bytes().replace(b'\r\n',b'\n')).hexdigest()==h
for g in [r/'GALERIA.html',r.parent/'evidencia-t44/GALERIA.html']:
 s=g.read_text(encoding='utf8');assert s.count('<section id=')==2
 for link in re.findall(r'(?:src|href)="([^"#]+)',s):
  if '://' not in link:assert (g.parent/link.split('#')[0]).exists(),link
for n in ['LOG-CODEX.md','RESUMEN-ARRANQUE.md']:
 s=(repo/'docs/00-estado'/n).read_text(encoding='utf8');assert len(s.splitlines())<=150
 if n.startswith('LOG'):assert len(s)<=12000
for f,h in json.loads((r/'fuentes-suite.json').read_text(encoding='utf8'))['fuentes'].items():
 assert hashlib.sha256((repo/f).read_bytes().replace(b'\r\n',b'\n')).hexdigest()==h
s=json.loads((r/'suite-final.json').read_text(encoding='utf8'))
assert s['salida']==0 and s['fallidas']==0 and s['base']=='wings_testing_codex'
assert hashlib.sha256((r/'suite-final.txt').read_bytes()).hexdigest()==s['sha256']
salida={'mediciones':len(m),'formularios_verificados':6,'capturas':len(fotos),'desbordes':0,'titulo_separado':True,'nuevo_agrega_fila':True,'enlaces':True}
(r/'integridad.json').write_text(json.dumps(salida,indent=2)+'\n',encoding='utf8')
print(json.dumps(salida))
