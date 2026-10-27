import hashlib
import json
from pathlib import Path

folder = Path(__file__).resolve().parent
root = folder.parents[3]
path = root / 'docs/00-estado/LOG-CODEX.md'
raw = path.read_bytes()
title = '## 2026-10-27 — Codex CAB — B3 devuelta: primera carga corregida'
if title in raw.decode('utf-8'):
    print('Entrada ya registrada.')
    raise SystemExit(0)
start = raw.index('## 2026-10-26 — Codex CyE — T83 verificada'.encode())
end = raw.index(b'\n[Dos entradas anteriores archivadas intactas al iniciar T88/B3]', start)
preserved = raw[start:end]
archive = root / 'docs/99-archivo/bitacoras/2026-10-27/LOG-CODEX-devolucion-B3.txt'
if archive.exists():
    assert archive.read_bytes() == preserved
else:
    archive.write_bytes(preserved)
archive.with_suffix('.json').write_text(json.dumps({'origen': 'docs/00-estado/LOG-CODEX.md', 'base_enlaces': 'docs/00-estado/',
    'bytes': len(preserved), 'sha256': hashlib.sha256(preserved).hexdigest(),
    'nota': 'Entrada T83 intacta; su cierre está en informe y bitácora. No es un bloqueo de B3.'}, ensure_ascii=False, indent=2)+'\n', encoding='utf-8', newline='\n')
content = (raw[:start]+raw[end:]).decode('utf-8')
entry = title+'\n\n'+'''[Devolución27/10](../06-pruebas/PRU-04/IMPLEMENTACION-B3-ASISTENCIA.md): ids de primera carga TERMINADA dan historia, no era pregunta pendiente. Dinero aprobado por Claude, sin tocarlo.
7 permanentes: rojo5/2 antes; área58/292, técnica día21 1/5 y rápida1028/6 omitidas/7983 aserciones/224,98s verdes; contador1153.
Capturas nuevas1366/375 del HTML real de Wings; sin Blade/CSS/JS. Alta posterior conserva Nuevo y margen; PENDIENTE/sin detalle no exime.
Otros usos: mes de alta web, API apagada y protección de borrados, sin tocar. Detalle en informe.
B3 a_verificar para Claude; aspecto pendiente de Carlos. Sin sitio/servidor/base del club, tabla, push manual ni despliegue.

'''
content = content.replace('\n\n', '\n\n'+entry, 1)
content += '\n[T83 archivada intacta al registrar la devolución B3](../99-archivo/bitacoras/2026-10-27/LOG-CODEX-devolucion-B3.txt).\n'
assert len(content) <= 12000 and len(content.splitlines()) <= 150 and content.count('\n## ') <= 10, len(content)
path.write_text(content, encoding='utf-8', newline='\n')
print(json.dumps({'caracteres': len(content), 'lineas': len(content.splitlines()), 'entradas': content.count('\n## ')}))
