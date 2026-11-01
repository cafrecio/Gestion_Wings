from pathlib import Path
import subprocess,os,json,time
root=Path(__file__).resolve().parents[4];e=Path(__file__).resolve().parent;app=root/'storage/app/control-gw-261101/app'
pwsh=r'C:/Users/cafre/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/powershell/pwsh.exe'
def procesos():
 code="@(Get-CimInstance Win32_Process | Select-Object ProcessId,ParentProcessId,Name,CommandLine) | ConvertTo-Json -Compress"
 s=subprocess.check_output([pwsh,'-NoProfile','-Command',code],text=True,encoding='utf-8-sig');return json.loads(s)
def php_real(x):return x['Name'].lower()=='php.exe' and not any(v in (x.get('CommandLine') or '') for v in ['artisan serve','-S 127.0.0.1:8111'])
def correr(args,nombre):
 assert not [x for x in procesos() if php_real(x)],'Otra prueba activa'
 env=os.environ.copy();env['DB_DATABASE']='wings_testing_codex';otros=[]
 with (e/(nombre+'.txt')).open('wb') as f:
  p=subprocess.Popen(['php','artisan','test',*args,'--no-ansi'],cwd=app,env=env,stdout=f,stderr=subprocess.STDOUT)
  while p.poll() is None:
   ps=procesos();ids={p.pid};n=-1
   while n!=len(ids):
    n=len(ids);ids.update(x['ProcessId'] for x in ps if x['ParentProcessId'] in ids)
   extra=[x for x in ps if php_real(x) and x['ProcessId'] not in ids];otros+=extra
   with (e/'monitor-comprobaciones.jsonl').open('a',encoding='utf-8') as mon:mon.write(json.dumps({'caso':nombre,'propios':list(ids),'ajenos':extra})+'\n')
   time.sleep(2)
  p.wait()
 return {'caso':nombre,'codigo':p.returncode,'ajenos':otros}
filtro='test_el_adelanto_recuerda_el_cobro_del_que_nacio|test_dos_altas_del_mismo_dni_en_deportes_distintos_se_serializan'
r=[correr(['tests/Feature/AdelantoCanceladoT100Test.php','tests/Feature/InscripcionConcurrenteTest.php','--filter',filtro],'inscripcion-con-preparacion-previa')]
r.append(correr(['tests/Feature/InactivosT70T71Test.php'],'inactivos-repetida-sin-proceso-no-identificado'))
(e/'resultado-comprobaciones.json').write_text(json.dumps(r,indent=2)+'\n',encoding='utf-8');print(json.dumps(r))
