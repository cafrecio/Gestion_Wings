from pathlib import Path
import hashlib,json,re,subprocess
root=Path(__file__).resolve().parents[4]
e=Path(__file__).resolve().parent
app=root/'storage/app/control-gw-261101/app'
snap=json.loads((e/'fuentes-antes-suite.json').read_text(encoding='utf-8-sig'))
copy_changed=[];root_changed=[]
for x in snap['fuentes']:
 name=x['archivo'];expected=x['sha256']
 if hashlib.sha256((app/name).read_bytes()).hexdigest()!=expected:copy_changed.append(name)
 if hashlib.sha256((root/name).read_bytes()).hexdigest()!=expected:root_changed.append(name)
raw=[json.loads(x) for x in (e/'monitor-suite.jsonl').read_text(encoding='utf-8-sig').splitlines() if x.strip()]
tree=[json.loads(x) for x in (e/'monitor-arbol.jsonl').read_text(encoding='utf-8-sig').splitlines() if x.strip()]
known={28568,35648,57080}
for row in tree:known.update(x['ProcessId'] for x in row['arbol'])
unknown=[];flags={}
for row in raw:
 for x in row.get('ajenos',[]):
  pid=x['ProcessId'];flags[str(pid)]=x['CommandLine']
  if pid not in known:unknown.append(x)
real=[x for row in tree for x in row['ajenos']]
r={'fuentes':len(snap['fuentes']),'copia_cambiada':copy_changed,'checkout_cambiado':root_changed,'ventana_libre':len([x for x in raw if x['fase']=='espera' and not x['procesos']]),'muestras_arbol':len(tree),'falsos_positivos_monitor_inicial':flags,'procesos_sin_atribuir':unknown,'procesos_ajenos_arbol':real,'sin_otros_procesos_observados':not unknown and not real}
(e/'revision-final.json').write_text(json.dumps(r,indent=2,ensure_ascii=False)+'\n',encoding='utf-8')
assert not copy_changed,'Fuentes aisladas cambiadas '+str(copy_changed)
# No descartar el schedule observado: se informa la limitacion en el resultado.
for name in ['VERIFICACION-T100-CODEX.md','VERIFICACION-T106-CODEX.md','VERIFICACION-T102-CODEX.md']:
 f=e.parent/name
 for link in re.findall(r'\]\(([^)]+)\)',f.read_text(encoding='utf-8-sig')):
  if '://' not in link and not link.startswith('#'):assert (f.parent/link.split('#')[0]).exists(),link
for p in e.glob('*.php'):
 r=subprocess.run(['php','-l',str(p)],capture_output=True,text=True);assert r.returncode==0,r.stdout+r.stderr
print('Fuentes aisladas intactas; enlaces y sintaxis correctos. Monitor con incidencias:',bool(unknown or real))
