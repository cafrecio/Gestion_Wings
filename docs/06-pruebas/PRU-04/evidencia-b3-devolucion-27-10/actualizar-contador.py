import json
import re
from pathlib import Path

folder = Path(__file__).resolve().parent
root = folder.parents[3]
count = sum(len(re.findall(r'public\s+function\s+test[A-Za-z0-9_]*\s*\(', p.read_text(encoding='utf-8-sig')))
            for p in (root / 'tests').rglob('*Test.php'))
patterns = {'ESTADO-ACTUAL.md': r'(\*\*Tests\*\*.*?\*\*)\d+( pruebas)',
            'PLAN-PRODUCCION.md': r'(\| Suite \| \*\*)\d+( pruebas)',
            'CHECKLIST-CARLOS.md': r'(artisan test\s+#\s*)\d+( pruebas)'}
for name, pattern in patterns.items():
    path = root / 'docs/00-estado' / name
    text = path.read_text(encoding='utf-8')
    text, hits = re.subn(pattern, lambda m: m[1]+str(count)+m[2], text, count=1)
    assert hits == 1, name
    path.write_text(text, encoding='utf-8', newline='\n')
(folder / 'contador.json').write_text(json.dumps({'metodos': count, 'nuevos_b3_primera_carga': 7}, indent=2)+'\n', encoding='utf-8', newline='\n')
print(count)
