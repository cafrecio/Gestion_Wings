from pathlib import Path
import hashlib,json,re,struct
r=Path(__file__).resolve().parent
repo=r.parents[3]
source=json.loads((r/'fuentes-iniciales.json').read_text(encoding='utf8'))['fuentes']
for f,h in source.items():assert hashlib.sha256((repo/f).read_bytes().replace(b'\r\n',b'\n')).hexdigest()==h,f
for p in json.loads((r/'respuestas.json').read_text(encoding='utf8')):
 assert p['estado']==200
 assert hashlib.sha256((r/'html'/f"{p['slug']}.html").read_bytes()).hexdigest()==p['sha256']
m=json.loads((r/'mediciones.json').read_text(encoding='utf8'))['resultados'];assert len(m)==9
fotos=[]
for x in m:
 assert x['ancho'] in (360,375,1366) and x['contenido']<=x['ancho'] and x['css']=='16px'
 if x['slug']=='asistencia-profesor':
  v=x['funcional'];assert [v['antes'],v['uno'],v['dos'],v['final']]==[0,1,2,0]
  assert v['sufijos'] and not v['dniCompletoEnHtml'] and v['guardar'] and v['motivo']
 for f in x['fotos']:
  b=(r/f['archivo']).read_bytes();assert hashlib.sha256(b).hexdigest()==f['sha256']
  assert struct.unpack('>II',b[16:24])==(x['ancho'],1100)
  fotos.append(f)
a=json.loads((r/'auditoria.json').read_text(encoding='utf8'));assert (a['pruebas'],a['aprobadas'],a['fallidas'],a['salida'])==(24,21,3,1)
assert hashlib.sha256((r/'auditoria-definitiva.txt').read_bytes()).hexdigest()==a['sha256']
assert hashlib.sha256((r/'VerificacionAmpliadaT34T67Test.php').read_bytes().replace(b'\r\n',b'\n')).hexdigest()==a['instrumental_sha256']
s=json.loads((r/'suite-completa.json').read_text(encoding='utf8'));assert s['salida']==0
assert hashlib.sha256((r/'suite-completa.txt').read_bytes()).hexdigest()==s['sha256']
d=json.loads((r/'dominio.json').read_text(encoding='utf8'));assert (d['aprobadas'],d['aserciones'],d['salida'])==(2,19,0)
assert hashlib.sha256((r/'dominio-final.txt').read_bytes()).hexdigest()==d['sha256']
assert hashlib.sha256((r/'DominioClasesT34T67Test.php').read_bytes().replace(b'\r\n',b'\n')).hexdigest()==d['fuente_sha256']
tareas=json.loads((repo/'docs/00-estado/tareas.json').read_text(encoding='utf8'))
t34=next(t for t in tareas if t['id']=='T34');t67=next(t for t in tareas if t['id']=='T67')
assert (t34['estado'],t34['tiene'])==('devuelto','Gemini')
assert (t67['estado'],t67['verifica'])==('cerrado','Codex')
report=repo/'docs/06-pruebas/PRU-04/VERIFICACION-T34-T67-CODEX.md'
for link in re.findall(r'\]\(([^)]+)\)',report.read_text(encoding='utf8')):
 if not re.match(r'https?://|#',link):assert (report.parent/link.split('#')[0]).exists(),link
for name in ['LOG-CODEX.md','RESUMEN-ARRANQUE.md']:
 text=(repo/'docs/00-estado'/name).read_text(encoding='utf8');assert len(text.splitlines())<=150
 if name.startswith('LOG'):assert len(text)<=12000
result={'fuentes_intactas':len(source),'archivos_producto':12,'prueba_original':1,'respuestas_reales':3,'mediciones':9,'capturas':len(fotos),'interacciones':3,'desbordes':0,'auditoria_fallos_reproducidos':3,'suite_salida':s['salida'],'enlaces':True,'T34':'devuelto','T67':'cerrado'}
(r/'integridad.json').write_text(json.dumps(result,indent=2)+'\n',encoding='utf8')
print(json.dumps(result))
