import json
import re
from pathlib import Path

folder = Path(__file__).resolve().parent
root = folder.parents[3]
count = sum(len(re.findall(r'public\s+function\s+test[A-Za-z0-9_]*\s*\(', p.read_text(encoding='utf-8-sig')))
            for p in (root / 'tests').rglob('*Test.php'))
patterns = {'ESTADO-ACTUAL.md': r'(\*\*Tests\*\*.*?\*\*)\d+( pruebas\*\*)',
            'PLAN-PRODUCCION.md': r'(\| Suite \| \*\*)\d+( pruebas\*\*)',
            'CHECKLIST-CARLOS.md': r'(artisan test\s+#\s*)\d+( pruebas)'}
for name, pattern in patterns.items():
    path = root / 'docs/00-estado' / name
    content = path.read_text(encoding='utf-8')
    def replace(m):
        label = m[1]+str(count)+m[2]
        return label if 'B3 asistencia Codex CAB 27/10:' in content else label+'. B3 asistencia Codex CAB 27/10: 20 nuevas; corte'+str(count)+'. Antes:'
    content, hits = re.subn(pattern, replace, content, count=1)
    assert hits == 1, name
    content = re.sub(r'B3 asistencia Codex CAB 27/10:.*? Antes:',
        'B3 asistencia Codex CAB 27/10: 20 nuevas; rapida1011 aprobadas/6 omitidas/7855 aserciones; completa1130 aprobadas/6 omitidas/9454 aserciones/962,45s, salida0, corte1136. El contador del checkout incluye las pruebas T54 concurrentes posteriores, sin certificarlas. Antes:', content, count=1)
    path.write_text(content, encoding='utf-8', newline='\n')
(folder / 'contador-pruebas.json').write_text(json.dumps({'metodos_checkout': count, 'nuevos_b3_asistencia': 20, 'corte_suite_completa': 1136,
    'nota': 'El checkout incluye pruebas T54 posteriores a la colección de la suite propia.'}, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
print(count)
