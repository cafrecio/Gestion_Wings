python3 - <<'PY'
from pathlib import Path
import re,json,subprocess,ipaddress
p=Path('/etc/csf/csf.conf');s=p.read_text();m=re.search(r'^IPV6\s*=\s*"?([01])',s,re.M)
allow=Path('/etc/csf/csf.allow').read_text();code=Path('/home/wingstest/app/bootstrap/app.php').read_text();nets=[]
for n in set(re.findall(r'[0-9a-fA-F:.]+/[0-9]{1,3}',code)):
 try:
  a=ipaddress.ip_network(n,strict=False)
  if ('.' in n or ':' in n) and not a.is_loopback:nets.append((a,n))
 except ValueError:pass
sys=Path('/proc/sys/net/ipv6/conf/all/disable_ipv6')
print(json.dumps({'control':'contexto_firewall_sin_cambios','csf_ipv6_habilitado':m.group(1)=='1' if m else None,'ipv6_sistema_deshabilitado':sys.read_text().strip()=='1' if sys.exists() else None,'rangos_ipv4_codigo':sum(a.version==4 for a,n in nets),'rangos_ipv4_en_csf':sum(a.version==4 and n in allow for a,n in nets),'rangos_ipv6_codigo':sum(a.version==6 for a,n in nets),'rangos_ipv6_en_csf':sum(a.version==6 and n in allow for a,n in nets)}))
PY
