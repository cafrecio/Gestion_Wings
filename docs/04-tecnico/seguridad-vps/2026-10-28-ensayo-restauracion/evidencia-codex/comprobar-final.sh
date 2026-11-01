set -u
python3 - <<'PY'
from pathlib import Path
import os,json,re,ipaddress,datetime,subprocess
roots=['/root/ensayo-t82','/root/control-t82'];procs=[]
for p in Path('/proc').glob('[0-9]*'):
 try:
  cwd=os.readlink(str(p/'cwd'));cmd=(p/'cmdline').read_bytes()
  if any(cwd==d or cwd.startswith(d+'/') or cwd.startswith(d+' (deleted)') or (d+'/').encode() in cmd for d in roots):procs.append(int(p.name))
 except OSError:pass
cfg=Path('/root/.config/rclone/rclone.conf')
code=Path('/home/wingstest/app/bootstrap/app.php').read_text();allow=Path('/etc/csf/csf.allow').read_text();valid=[]
for n in set(re.findall(r'[0-9a-fA-F:.]+/[0-9]{1,3}',code)):
 try:
  net=ipaddress.ip_network(n,strict=False)
  if ('.' in n or ':' in n) and not net.is_loopback:valid.append(n)
 except ValueError:pass
print(json.dumps({'control':'limpieza_final','carpetas':{d:Path(d).exists() for d in roots},'procesos_relacionados':procs,'temporales_restaurar':len(list(Path('/tmp').glob('restaurar-wings-*'))),'rclone_conf_modificacion_UTC':datetime.datetime.fromtimestamp(cfg.stat().st_mtime,datetime.timezone.utc).isoformat(),'rangos_codigo_validos_sin_loopback':len(valid),'rangos_codigo_presentes_en_csf':sum(n in allow for n in valid),'rangos_codigo_ausentes_en_csf':len([n for n in valid if n not in allow])}))
PY
mysql -N -B <<'SQL'
SELECT 'bases_ensayo_residuales',COUNT(*) FROM information_schema.schemata WHERE schema_name IN ('wings_ensayo_t82','wings_ensayo');
SELECT 'usuario_mysql_residual',COUNT(*) FROM mysql.user WHERE User='ensayo_t82';
SELECT 'prod_alumnos_pagos',(SELECT COUNT(*) FROM wings.alumnos),(SELECT COUNT(*) FROM wings.pagos);
SELECT 'test_alumnos_pagos',(SELECT COUNT(*) FROM wingstest.alumnos),(SELECT COUNT(*) FROM wingstest.pagos);
SQL
if getent passwd ensayo_t82 >/dev/null;then echo USUARIO_SISTEMA_PRESENTE;else echo USUARIO_SISTEMA_AUSENTE;fi
printf 'PUERTO_8099 ';ss -ltnH '( sport = :8099 )' | wc -l
