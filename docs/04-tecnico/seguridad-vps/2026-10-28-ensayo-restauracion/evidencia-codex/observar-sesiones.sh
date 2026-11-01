set -u
printf 'UTC ';date -u +%FT%TZ
mysql -N -B <<'SQL'
SELECT 'sesiones_produccion_ventana_1404_1409',COUNT(*),SUM(user_id IS NOT NULL),SUM(user_agent LIKE 'curl/%'),SUM(user_agent LIKE '%bot%'),FROM_UNIXTIME(MIN(last_activity)),FROM_UNIXTIME(MAX(last_activity)) FROM wings.sessions WHERE last_activity BETWEEN UNIX_TIMESTAMP('2026-11-01 14:04:00') AND UNIX_TIMESTAMP('2026-11-01 14:09:00');
SELECT 'sesiones_prueba_en_10m_reales',COUNT(*) FROM wingstest.sessions WHERE last_activity BETWEEN UNIX_TIMESTAMP()-600 AND UNIX_TIMESTAMP();
SQL
python3 - <<'PY'
from pathlib import Path
import re,json,datetime
p=Path('/usr/local/apache/domlogs/wings.gestionar-te.com.ar.ssl.log');counts={}
for line in p.read_text(errors='replace').splitlines():
 m=re.search(r'\[(01/Nov/2026:14:0[4-9]:[0-9]{2}) [+-][0-9]{4}\]',line)
 if m:
  minute=m[1][:-3];row=counts.setdefault(minute,{'solicitudes':0,'agente_contiene_bot':0,'agente_curl':0});row['solicitudes']+=1;row['agente_contiene_bot']+=int('bot' in line.lower());row['agente_curl']+=int('curl/' in line.lower())
print(json.dumps({'control':'accesos_produccion_sin_IP_sin_personas','por_minuto':counts}))
PY
