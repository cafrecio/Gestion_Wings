"""Auditor documental de B3: fuentes, resultados, evidencia y tablero reales."""
import hashlib
import json
import re
import subprocess
import xml.etree.ElementTree as ET
from pathlib import Path
from lxml import html
from PIL import Image

folder = Path(__file__).resolve().parent
root = folder.parents[3]
expected = {'rojo': (15, 11), 'rojo-adicionales': (5, 3), 'verde-inicial': (15, 0),
            'area-inicial': (71, 0), 'rapida': (1017, 0), 'completa': (1136, 0), 'documentacion-final': (7, 0)}
results = {}
for name, (count, faults) in expected.items():
    cases = ET.parse(folder / f'{name}.xml').findall('.//testcase')
    result = {'tests': len(cases), 'failures': sum(c.find('failure') is not None for c in cases),
        'errors': sum(c.find('error') is not None for c in cases), 'skipped': sum(c.find('skipped') is not None for c in cases),
        'assertions': sum(int(c.get('assertions', 0)) for c in cases), 'exit': int((folder / f'{name}-exit.txt').read_text().strip())}
    assert result['tests'] == count and result['failures'] + result['errors'] == faults, (name, result)
    assert (result['exit'] == 0) == (faults == 0), (name, result)
    results[name] = result
frozen = json.loads((folder / 'fuentes-antes-suites.json').read_text(encoding='utf-8-sig'))
for item in frozen:
    actual = hashlib.sha256((root / item['path']).read_bytes()).hexdigest()
    assert item['sha256'].lower() == actual, item['path']
counter = sum(len(re.findall(r'public\s+function\s+test[A-Za-z0-9_]*\s*\(', p.read_text(encoding='utf-8-sig')))
              for p in (root / 'tests').rglob('*Test.php'))
assert json.loads((folder / 'contador-pruebas.json').read_text())['metodos_checkout'] == counter
board = {x['id']: x for x in json.loads((root / 'docs/00-estado/tareas.json').read_text())}
for task in ['T88', 'B3']:
    assert [board[task][k] for k in ['estado', 'hizo', 'verifica', 'tiene']] == ['a_verificar', 'Codex', 'Claude', 'Claude']
images = {}
for path in sorted(folder.glob('*-final-*.png')):
    with Image.open(path) as im:
        assert im.width == (600 if '-375' in path.name else 1366)
        images[path.name] = list(im.size)
assert len(images) == 9
for path in folder.glob('*-dom.json'):
    data = json.loads(path.read_text(encoding='utf-8'))
    dom = html.fromstring(data['html'])
    assert all(m.get('content') == 'neutralizado' for m in dom.xpath('//meta[@name="csrf-token"]'))
    assert all(x['value'] == 'neutralizado' for x in data['controles'] if x['name'] == '_token')
    assert all(not x['value'] for x in data['controles'] if x['tag'] == 'INPUT' and x['name'] == 'password')
for name in ['IMPLEMENTACION-B3-ASISTENCIA.md', 'APROBAR-B3-ASISTENCIA.html']:
    source = folder.parent / name
    content = source.read_text(encoding='utf-8')
    assert 'RESULTADO_' not in content
    links = re.findall(r'(?:href|src)="([^"]+)"', content) if name.endswith('.html') else re.findall(r'\]\(([^)]+)\)', content)
    for link in links:
        if ':' not in link and not link.startswith('#'):
            assert (source.parent / link.split('#')[0]).exists(), (name, link)
for name in ['LOG-CODEX.md', 'RESUMEN-ARRANQUE.md']:
    content = (root / 'docs/00-estado' / name).read_text(encoding='utf-8')
    assert len(content.splitlines()) <= 150, name
    if name.startswith('LOG'): assert len(content) <= 12000 and content.count('\n## ') <= 10
assert not subprocess.check_output(['git', 'diff', '--name-only', '--', 'resources/css'], cwd=root).strip()
assert not subprocess.check_output(['git', 'diff', '--name-only', '--', 'docs/06-pruebas/PRU-04/tabla-de-pruebas'], cwd=root).strip()
data = {'estado': 'PASS', 'resultados': results, 'fuentes_congeladas': frozen,
        'contador_checkout': counter, 'corte_completa': 1136, 'imagenes': images,
        'alcance': 'Solo B3/T88 propios; T54 concurrente no certificado. Sin servidor, despliegue ni datos del club.',
        'archivos': {str(p.relative_to(folder)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(folder.rglob('*'))
                     if p.is_file() and p.name != 'auditoria.json'}}
(folder / 'auditoria.json').write_text(json.dumps(data, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
print(json.dumps({'estado': 'PASS', 'resultados': results, 'fuentes_congeladas': len(frozen), 'imagenes': len(images), 'contador_checkout': counter}, ensure_ascii=False))
