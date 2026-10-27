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
expected = {'rojo': (7, 5), 'area': (58, 0), 'tecnica-dia21': (1, 0), 'captura': (1, 0),
            'rapida': (1034, 0), 'documentacion': (7, 0)}
results = {}
for name, (count, faults) in expected.items():
    cases = ET.parse(folder / f'{name}.xml').findall('.//testcase')
    result = {'tests': len(cases), 'failures': sum(c.find('failure') is not None for c in cases),
        'errors': sum(c.find('error') is not None for c in cases), 'skipped': sum(c.find('skipped') is not None for c in cases),
        'assertions': sum(int(c.get('assertions', 0)) for c in cases), 'exit': int((folder / f'{name}-exit.txt').read_text().strip())}
    assert result['tests'] == count and result['failures'] + result['errors'] == faults, (name, result)
    assert (result['exit'] == 0) == (faults == 0), (name, result)
    results[name] = result
new_cases = [c for c in ET.parse(folder / 'area.xml').findall('.//testcase') if c.get('class', '').endswith('B3PrimeraCargaTest')]
assert len(new_cases) == 7
assert {c.get('name') for c in new_cases} == {c.get('name') for c in ET.parse(folder / 'rojo.xml').findall('.//testcase')}
for item in json.loads((folder / 'fuentes-antes-rapida.json').read_text(encoding='utf-8-sig')):
    assert hashlib.sha256((root / item['path']).read_bytes()).hexdigest().lower() == item['sha256'].lower(), item['path']
before = (folder / 'ExcepcionCobranzaService-antes.php').read_text(encoding='utf-8')
after = (root / 'app/Services/ExcepcionCobranzaService.php').read_text(encoding='utf-8')
assert before.split('public function validarAsistencia', 1)[1] == after.split('public function validarAsistencia', 1)[1]
data = json.loads((folder / 'datos-captura.json').read_text(encoding='utf-8'))
doc = html.parse(str(folder / 'lista-real.html'))
for aid in data['primera_carga']:
    assert not data['condiciones'][str(aid)]['nuevo']
    text = doc.xpath(f'//*[@id="card-{aid}"]//*[@data-condicion-cobranza]')[0].text_content()
    assert 'Nuevo' not in text
assert data['condiciones'][str(data['posterior'])]['nuevo']
assert 'Nuevo' in doc.xpath(f'//*[@id="card-{data["posterior"]}"]//*[@data-condicion-cobranza]')[0].text_content()
images = {}
for name in ['lista-1366.png', 'lista-375.png', 'login-375.png']:
    with Image.open(folder / name) as im:
        assert im.width == (1366 if '1366' in name else 600)
        images[name] = list(im.size)
for name in ['lista-real.html', 'login-real.html']:
    page = html.parse(str(folder / name))
    assert all(e.get('content') == 'neutralizado' for e in page.xpath('//meta[@name="csrf-token"]'))
    assert all(e.get('value') == 'neutralizado' for e in page.xpath('//input[@name="_token"]'))
for name in ['IMPLEMENTACION-B3-ASISTENCIA.md', 'APROBAR-B3-PRIMERA-CARGA.html']:
    p = folder.parent / name
    text = p.read_text(encoding='utf-8')
    assert 'RESULTADO_RAPIDA_DEVOLUCION' not in text
    links = re.findall(r'(?:src|href)="([^"]+)"', text) if name.endswith('.html') else re.findall(r'\]\(([^)]+)\)', text)
    for link in links:
        if ':' not in link and not link.startswith('#'): assert (p.parent / link.split('#')[0]).exists(), link
board = next(x for x in json.loads((root / 'docs/00-estado/tareas.json').read_text(encoding='utf-8')) if x['id'] == 'B3')
assert [board[k] for k in ['estado', 'tiene', 'hizo', 'verifica']] == ['a_verificar', 'Claude', 'Codex', 'Claude']
log = (root / 'docs/00-estado/LOG-CODEX.md').read_text(encoding='utf-8')
assert len(log) <= 12000 and len(log.splitlines()) <= 150 and log.count('\n## ') <= 10
assert len((root / 'docs/00-estado/RESUMEN-ARRANQUE.md').read_text(encoding='utf-8').splitlines()) <= 150
assert not subprocess.check_output(['git', 'diff', '--name-only', '--', 'resources/views', 'resources/css'], cwd=root).strip()
assert not subprocess.check_output(['git', 'diff', '--name-only', '--', 'docs/06-pruebas/PRU-04/tabla-de-pruebas'], cwd=root).strip()
result = {'estado': 'PASS', 'resultados': results, 'nuevos_verdes': len(new_cases), 'imagenes': images,
    'alcance': 'Solo condicion de asistencia. Sin cambiar cobros, Blade/CSS/JS ni usar datos del club/servidor.',
    'archivos': {str(p.relative_to(folder)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(folder.rglob('*'))
                 if p.is_file() and p.name != 'auditoria.json'}}
(folder / 'auditoria.json').write_text(json.dumps(result, ensure_ascii=False, indent=2)+'\n', encoding='utf-8', newline='\n')
print(json.dumps({'estado': 'PASS', 'resultados': results, 'nuevos_verdes': len(new_cases), 'imagenes': len(images)}, ensure_ascii=False))
