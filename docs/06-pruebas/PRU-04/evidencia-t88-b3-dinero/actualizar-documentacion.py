"""Integra el corte propio sin reemplazar aportes concurrentes."""
import json
import re
from pathlib import Path

folder = Path(__file__).resolve().parent
root = folder.parents[3]
count = sum(len(re.findall(r'public\s+function\s+test[A-Za-z0-9_]*\s*\(', p.read_text(encoding='utf-8-sig')))
            for p in (root / 'tests').rglob('*Test.php'))
own = sum(len(re.findall(r'public\s+function\s+test[A-Za-z0-9_]*\s*\(', (root / p).read_text(encoding='utf-8')))
          for p in ['tests/Feature/T88B3DineroTest.php', 'tests/Feature/T88EfectivoConcurrenteTest.php'])
(folder / 'contador-pruebas.json').write_text(json.dumps({'metodos_checkout': count, 'nuevos_propios': own,
    'nota': 'Incluye 18 métodos T65 concurrentes en el checkout; no se incorporan sus archivos al commit propio.'}, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
patterns = {
    'ESTADO-ACTUAL.md': r'(\*\*Tests\*\*.*?\*\*)\d+( pruebas)',
    'PLAN-PRODUCCION.md': r'(\| Suite \| \*\*)\d+( pruebas)',
    'CHECKLIST-CARLOS.md': r'(artisan test\s+#\s*)\d+( pruebas)',
}
for name, pattern in patterns.items():
    path = root / 'docs/00-estado' / name
    content = path.read_text(encoding='utf-8')
    content, hits = re.subn(pattern, lambda m: m[1]+str(count)+m[2], content, count=1)
    assert hits == 1, name
    if 'T88/B3 dinero Codex CAB 27/10:' not in content:
        # El relato histórico sigue íntegro; el encabezado distingue el corte actual.
        content = content.replace(str(count)+' pruebas', str(count)+' pruebas. T88/B3 dinero Codex CAB 27/10: 19 nuevas propias y 18 T65 concurrentes incluidas en el conteo del checkout; completa original 1070 aprobadas/6 omitidas/40 fallidas, los40 casos luego verdes al repetirlos; sin despliegue. Antes:', 1)
    content = re.sub(r'(\*\*\d+ pruebas)(\. T88/B3 dinero.*?Antes:)\*\*', r'\1**\2', content, count=1)
    path.write_text(content, encoding='utf-8', newline='\n')
print(json.dumps({'contador': count, 'propias': own}))
