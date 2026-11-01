python3 - <<'PY'
from pathlib import Path
import re
p=Path('/root/wings-backup/restaurar.sh');print('FUENTE_LEGACY_SOLO_LECTURA')
for i,line in enumerate(p.read_text().splitlines(),1):
 if re.match(r'^\s*(?:[A-Z_]*(?:KEY|CLAVE|PASS|TOKEN|SECRET)[A-Z_]*)=',line):line=line.split('=',1)[0]+'=[valor omitido]'
 print(str(i)+': '+line)
PY
