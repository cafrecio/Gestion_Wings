import hashlib,json,subprocess,shutil
from pathlib import Path
root=Path.cwd(); dest=root/'storage/app/control-gw-261101/app'; ev=root/'docs/06-pruebas/PRU-04/evidencia-segunda-t100-t102-codex'
head=subprocess.check_output(['git','rev-parse','HEAD'],text=True).strip()
files=subprocess.check_output(['git','ls-files','app','bootstrap','config','database','routes','resources','tests'],text=True).splitlines()
files=[p for p in files if not p.startswith('bootstrap/cache/')]
if subprocess.check_output(['git','diff','HEAD','--','app','bootstrap','config','database','routes','resources','tests']).strip(): raise RuntimeError('Fuentes raiz con cambios sin publicar')
for p in files:
 target=dest/p;target.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(root/p,target)
changed=subprocess.check_output(['git','diff','--name-only','e3301f75',head,'--','docs','scripts'],text=True).splitlines()
for p in changed:
 r=subprocess.run(['git','show',f'{head}:{p}'],capture_output=True)
 if r.returncode==0:
  target=dest/p;target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(r.stdout)
hashes={p:hashlib.sha256((dest/p).read_bytes()).hexdigest() for p in files}
(ev/'fuentes-antes-completa.json').write_text(json.dumps({'head':head,'base':'wings_testing_codex','archivos':len(files),'hashes':hashes,'docs_scripts_refrescados':changed},indent=2),encoding='utf-8')
print(json.dumps({'head':head,'archivos':len(files),'docs_scripts':len(changed)}))
