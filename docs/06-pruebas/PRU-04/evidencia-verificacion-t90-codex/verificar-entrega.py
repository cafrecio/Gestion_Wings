"""Revisa evidencia existente de T90 sin ejecutar Laravel ni conectar a una base."""
import hashlib
import json
import re
import subprocess
import sys
import xml.etree.ElementTree as ET
from collections import Counter
from pathlib import Path
from urllib.parse import unquote, urlsplit

folder = Path(__file__).resolve().parent
root = folder.parents[3]
report = folder.parent / 'VERIFICACION-T90-CODEX.md'
manifest = folder / 'INTEGRIDAD.json'
shared = [root / 'docs/00-estado' / n for n in ['LOG-CODEX.md', 'ESTADO-ACTUAL.md', 'RESUMEN-ARRANQUE.md', 'tareas.json', 'TABLERO.html', 'QUIEN-HACE-QUE.md']]
archives = sorted((root / 'docs/99-archivo/bitacoras/2026-10-27').glob('LOG-CODEX-T90-ANTECEDENTES.*'))

expected = {'independientes-final': (77, 528, 0), 'area': (160, 1160, 0), 'tecnica-dia23': (1, 7, 0), 'rapida': (939, 7465, 6)}
for name, (tests, assertions, skipped) in expected.items():
    suite = ET.parse(folder / (name + '.xml')).getroot().find('./testsuite')
    assert suite is not None
    assert (int(suite.get('tests')), int(suite.get('assertions')), int(suite.get('skipped'))) == (tests, assertions, skipped)
    assert int(suite.get('errors')) == int(suite.get('failures')) == 0
    assert (folder / (name + '-exit.txt')).read_text(encoding='utf-8-sig').strip() == '0'
cases = list(ET.parse(folder / 'independientes-final.xml').getroot().iter('testcase'))
assert sum(c.get('name', '').startswith('test_rechaza_') for c in cases) == 48
assert sum(c.get('name', '').startswith('test_cancelaciones_permitidas_') for c in cases) == 24
permanent = [s for s in ET.parse(folder / 'area.xml').getroot().iter('testsuite') if s.get('name', '').endswith('CancelarClaseLiquidadaT90Test')]
assert len(permanent) == 1 and permanent[0].get('tests') == '4' and permanent[0].get('assertions') == '21'

sources = json.loads((folder / 'fuentes-t90.json').read_text(encoding='utf-8'))
assert len(sources['fuentes']) == 14
for item in sources['fuentes']:
    raw = (root / item['ruta']).read_bytes()
    original = subprocess.check_output(['git', 'show', '563749b2:' + item['ruta']], cwd=root)
    assert raw == original
    assert hashlib.sha256(raw).hexdigest() == item['sha256']
source_copy = json.loads((folder / 'fuente-tecnica.json').read_text(encoding='utf-8'))
assert (root / source_copy['fuente']).read_bytes() == (folder / source_copy['copia']).read_bytes()
assert hashlib.sha256((folder / source_copy['copia']).read_bytes()).hexdigest() == source_copy['sha256']

environment = json.loads((folder / 'entorno-efectivo.json').read_text(encoding='utf-8-sig'))
assert environment['entorno'] == 'testing' and environment['base_efectiva'] == 'wings_testing_codex'
assert 'MariaDB' in environment['motor']
response = json.loads((folder / 'respuesta-rechazo.json').read_text(encoding='utf-8'))
assert response == {'success': False, 'message': 'No se puede cancelar: la clase está en una liquidación cerrada o pagada.'}
panel = json.loads((folder / 'resultado-panel.json').read_text(encoding='utf-8'))
assert len(panel['casos']) == 2
assert panel['js_sha256'] == hashlib.sha256((root / 'resources/js/clases-show.js').read_bytes()).hexdigest()
for case in panel['casos']:
    assert case['texto_panel'] == '✗ ' + response['message']
    assert case['panel_permanece_abierto'] and case['botones_restaurados']

board = json.loads((root / 'docs/00-estado/tareas.json').read_text(encoding='utf-8-sig'))
t90 = next(x for x in board if x['id'] == 'T90')
assert (t90['estado'], t90['hizo'], t90['verifica'], t90['tiene']) == ('cerrado', 'Claude', 'Codex', 'nadie')
t62 = next(x for x in board if x['id'] == 'T62')
assert 'CLA-012' in t62['paso'] and 'CLA-015' in t62['paso']
side_effect = json.loads((folder / 'salida-lateral-t34.json').read_text(encoding='utf-8'))
assert hashlib.sha256((root / side_effect['archivo']).read_bytes()).hexdigest() == side_effect['sha256_original']
assert not subprocess.check_output(['git', 'diff', '--name-only', '--', 'app', 'resources', 'routes', 'tests', 'scripts', 'database', 'bootstrap', 'config'], cwd=root).strip()
assert not subprocess.check_output(['git', 'diff', '--name-only', '--', 'docs/06-pruebas/PRU-04/tabla-de-pruebas'], cwd=root).strip()

files = sorted(set([p for p in folder.rglob('*') if p.is_file() and p != manifest] + [report] + shared + archives))
links_count = 0

def check_links(text, parent, previous=''):
    global links_count
    pattern = r'\[[^\]]+\]\(([^)\n]+)\)'
    links = Counter(re.findall(pattern, text)) - Counter(re.findall(pattern, previous))
    for link in links.elements():
        if urlsplit(link).scheme or link.startswith('#'):
            continue
        target = (parent / unquote(urlsplit(link).path)).resolve()
        assert target.exists(), (parent, link)
        links_count += 1

for file in files:
    if file.suffix == '.json':
        json.loads(file.read_text(encoding='utf-8-sig'))
    if file.suffix != '.md':
        continue
    text = file.read_text(encoding='utf-8-sig')
    if file in archives:
        metadata = json.loads(file.with_suffix('.json').read_text(encoding='utf-8'))
        raw = file.read_bytes()
        start, count = metadata['bloque_offset'], metadata['bloque_bytes']
        block = raw[start:start + count]
        assert hashlib.sha256(block).hexdigest() == metadata['sha256_bloque']
        assert block in subprocess.check_output(['git', 'show', metadata['origen']], cwd=root)
        check_links(block.decode('utf-8'), root / metadata['base_enlaces_originales'])
        check_links((raw[:start] + raw[start + count:]).decode('utf-8'), file.parent)
    else:
        previous = ''
        if file in shared:
            previous = subprocess.check_output(['git', 'show', 'c30bb7c2:' + file.relative_to(root).as_posix()], cwd=root).decode('utf-8-sig')
        check_links(text, file.parent, previous)

data = {'controles': {'propias': '77/528', 'rechazos_protegidos': 48, 'permitidas': 24, 'area': '160/1160', 't90_en_area': '4/21', 'tecnica_dia23': '1/7', 'rapida': '933 aprobadas,6 omitidas,7465 aserciones', 'js': '2/2', 'base_efectiva': environment['base_efectiva'], 'fuentes_identicas_563749b2': 14, 'tabla_solo_lectura': True, 'salida_t34_restaurada': True, 'tablero_t90': 'cerrado,verifica=Codex', 'enlaces_nuevos_revisados': links_count},
        'archivos': [{'ruta': p.relative_to(root).as_posix(), 'bytes': p.stat().st_size, 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()} for p in files],
        'manifiesto_excluido_de_autohash': manifest.relative_to(root).as_posix()}
if '--guardar' in sys.argv:
    manifest.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
else:
    assert json.loads(manifest.read_text(encoding='utf-8')) == data, 'Cambió un archivo sellado'
print(json.dumps(data['controles'], ensure_ascii=False))
print(len(files), 'archivos propios verificados y sellados; manifiesto excluido de su propia huella.')
