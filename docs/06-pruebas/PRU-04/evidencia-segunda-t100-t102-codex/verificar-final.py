from pathlib import Path
import json,hashlib,subprocess,re
r=Path.cwd();e=r/'docs/06-pruebas/PRU-04/evidencia-segunda-t100-t102-codex';a=r/'storage/app/control-gw-261101/app'
before=json.loads((e/'fuentes-antes-completa.json').read_text(encoding='utf-8'))
changes=[]
for p,h in before['hashes'].items():
 for scope,base in [('copia',a),('raiz',r)]:
  f=base/p
  actual=hashlib.sha256(f.read_bytes()).hexdigest() if f.exists() else None
  if actual!=h:changes.append({'ambito':scope,'archivo':p,'antes':h,'despues':actual})
raw=(e/'suite-completa.txt').read_text(encoding='utf-8-sig');m=re.search(r'Tests:\s*(.*?)\n\s*Duration:\s*(.*?)\n',raw,re.S)
mon=[json.loads(x) for x in (e/'monitor-completa.jsonl').read_text(encoding='utf-8-sig').splitlines() if x.strip()]
others=[{'hora':x['hora'],'otras_suites':x.get('otras_suites',[])} for x in mon if x.get('otras_suites')]
otherphp=[x for x in mon if x.get('otros_php')]
new=subprocess.check_output(['git','ls-files','--others','--exclude-standard','app','bootstrap','config','database','routes','resources','tests'],text=True).splitlines()
out={'head_antes':before['head'],'head_despues':subprocess.check_output(['git','rev-parse','HEAD'],text=True).strip(),'fuentes_comparadas':len(before['hashes']),'cambios':changes,'fuentes_nuevas_ajenas_fuera_del_corte':new,'muestras_monitor':len(mon),'otras_suites_observadas':others,'muestras_con_otros_php':otherphp,'resumen':m.group(1).strip() if m else None,'duracion_resumen':m.group(2).strip() if m else None,'runner':json.loads((e/'suite-resultado.json').read_text(encoding='utf-8-sig'))}
(e/'verificacion-final.json').write_text(json.dumps(out,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps({k:v for k,v in out.items() if k not in ['muestras_con_otros_php']},ensure_ascii=True))
if changes or others:raise SystemExit('Revisar diferencias o concurrencia detectada')
