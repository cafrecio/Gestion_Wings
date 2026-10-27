"""Bitácora propia: archiva una entrada íntegra y agrega el corte nuevo."""
import hashlib
import json
from pathlib import Path

folder = Path(__file__).resolve().parent
root = folder.parents[3]
log = root / 'docs/00-estado/LOG-CODEX.md'
raw = log.read_bytes()
title = '## 2026-10-27 — Codex CAB — B3 asistencia entregada; T88/B3 a verificar'
if title in raw.decode('utf-8'):
    print('Entrada ya registrada.')
    raise SystemExit(0)
start = raw.index('## 2026-10-26 — Codex CyE — Días 17–18: requisito previo bloqueado'.encode())
end = raw.index(b'\n[Dos entradas anteriores archivadas intactas al iniciar T88/B3]', start)
preserved = raw[start:end]
archive = root / 'docs/99-archivo/bitacoras/2026-10-27/LOG-CODEX-cierre-B3.txt'
assert not archive.exists()
archive.write_bytes(preserved)
archive.with_suffix('.json').write_text(json.dumps({'origen': 'docs/00-estado/LOG-CODEX.md',
    'base_de_enlaces_originales': 'docs/00-estado/', 'bytes': len(preserved),
    'sha256': hashlib.sha256(preserved).hexdigest(),
    'nota': 'Entrada histórica intacta; T83 y el avance posterior de T14 siguen en bitácora/resumen e informes.'}, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
content = (raw[:start] + raw[end:]).decode('utf-8')
entry = title+'\n\n'+'''[Entrega asistencia](../06-pruebas/PRU-04/IMPLEMENTACION-B3-ASISTENCIA.md), después de dinero17f99d13: condición en lista, margen interno, Nuevo/DEUDOR con motivo; MOROSO sin justificar, EXTRA/RECUPERA fuera de B3.
20 permanentes:15 iniciales11 rojas/4 verdes, cinco adicionales3 rojas/2 verdes; área71/344. Rápida1011/6 omitidas/7855 aserciones/190,04s y completa1130/6 omitidas/9454 aserciones/962,45s, salida0, corte1136. Seis fuentes propias congeladas.
Navegador profesora: cuatro presentes, dos motivos, reenvío sin duplicar; dueño ve el origen y abre la clase. [Capturas reales1366/375](../06-pruebas/PRU-04/APROBAR-B3-ASISTENCIA.html), aspecto pendiente de Carlos. Sintaxis PHP/JS, Blade y build correctos.
B3 y T88 a_verificar,hizoCodex,verificaClaude,tieneClaude; sin autocierre. Registro común atómico; readmisión conserva decisiones y avisos agrupados, no individuales.
Preguntas conservadas: primera carga, corte de correcciones históricas y avisos de orígenes anulados/corregidos. T54 posterior fuera del corte de suite y del producto/pruebas propios; contador del checkout calificado.
Solo wings_testing_codex; localhost detenido antes de suites. Sin sitio/servidor/base del club, tabla externa/copia, push manual ni despliegue.

'''
content = content.replace('\n\n', '\n\n'+entry, 1)
content += '\n[Entrada histórica previa de días17–18 archivada intacta al cerrar B3](../99-archivo/bitacoras/2026-10-27/LOG-CODEX-cierre-B3.txt).\n'
assert len(content) <= 12000 and len(content.splitlines()) <= 150 and content.count('\n## ') <= 10
log.write_text(content, encoding='utf-8', newline='\n')
print(json.dumps({'caracteres': len(content), 'lineas': len(content.splitlines()), 'entradas': content.count('\n## ')}))
