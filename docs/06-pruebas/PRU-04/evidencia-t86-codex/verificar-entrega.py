"""Verifica resultados y evidencia T86 existentes; no inicia Laravel ni modifica una base."""
import ast
import hashlib
import json
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from urllib.parse import unquote, urlsplit

folder = Path(__file__).resolve().parent
root = folder.parents[3]
manifest = folder / 'INTEGRIDAD.json'
report = folder.parent / 'IMPLEMENTACION-T86.md'
expect = {'rojo': (10, 18, 7, 0, 1), 'dirigidas': (24, 162, 0, 0, 0), 'observaciones-final': (1, 6, 0, 0, 0), 'rapida': (950, 7521, 0, 6, 0)}
results = {}
for name in list(expect) + ['completa']:
    suite = ET.parse(folder / (name + '.xml')).getroot().find('./testsuite')
    actual = tuple(int(suite.get(k)) for k in ('tests', 'assertions', 'failures', 'skipped'))
    assert int(suite.get('errors')) == 0, name
    rc = int((folder / (name + '-exit.txt')).read_text(encoding='utf-8-sig'))
    if name == 'completa':
        assert actual[0] == 1069 and actual[1] > 7521 and actual[2:] == (0, 6) and rc == 0, actual
    else:
        assert actual == expect[name][:4] and rc == expect[name][4], (name, actual, rc)
    results[name] = {**suite.attrib, 'exit': rc}
permanent = [s for s in ET.parse(folder / 'completa.xml').getroot().iter('testsuite') if s.get('name', '').endswith('SueldoOperativaT86Test')]
assert len(permanent) == 1 and permanent[0].get('tests') == '10' and permanent[0].get('failures') == '0'

sources = json.loads((folder / 'fuentes.json').read_text(encoding='utf-8'))
for f in sources['fuentes']:
    assert hashlib.sha256((root / f['ruta']).read_bytes()).hexdigest() == f['sha256'], f['ruta']
observation = json.loads((folder / 'observaciones.json').read_text(encoding='utf-8'))
assert observation['base']['nombre'] == 'wings_testing_codex'
assert observation['pago_registrado_antes_de_consultar_reportes']['monto'] == '-48000.75'
assert observation['reporte_sueldos']['profesores'] == [] and observation['reporte_sueldos']['incluye_operativas'] is False
env = json.loads((folder / 'entorno.json').read_text(encoding='utf-8-sig'))
assert env['entorno'] == 'testing' and env['base_efectiva'] == 'wings_testing_codex' and 'MariaDB' in env['motor']
board = json.loads((root / 'docs/00-estado/tareas.json').read_text(encoding='utf-8'))
t86 = next(t for t in board if t['id'] == 'T86')
assert (t86['estado'], t86['hizo'], t86['verifica'], t86['tiene']) == ('a_verificar', 'Codex', 'Claude', 'Claude')
assert next(t for t in board if t['id'] == 'T92')['estado'] == 'cerrado'
text = report.read_text(encoding='utf-8')
assert 'RESULTADO_' not in text and 'VERIFICACION_SINTAXIS_' not in text
assert 'No se considera cumplida su inclusión' in text
files = sorted([p for p in folder.rglob('*') if p.is_file() and p != manifest] + [report])
links = 0
for p in files:
    if p.suffix == '.json':
        json.loads(p.read_text(encoding='utf-8-sig'))
    elif p.suffix == '.py':
        ast.parse(p.read_text(encoding='utf-8'))
    elif p.suffix == '.md':
        for link in re.findall(r'\[[^\]]+\]\(([^)\n]+)\)', p.read_text(encoding='utf-8')):
            if urlsplit(link).scheme or link.startswith('#'):
                continue
            assert (p.parent / unquote(urlsplit(link).path)).resolve().exists(), (p, link)
            links += 1
data = {'estado': 'a_verificar', 'resultados': results, 'limites': ['Sueldos analitico no incluye operativas', 'Vinculos reservados anteriores al cambio de rol sin politica nueva'],
        'enlaces_revisados': links, 'archivos': [{'ruta': p.relative_to(root).as_posix(), 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()} for p in files],
        'alcance': 'Evidencia propia actual. Documentos compartidos se revisan por diff al entregar; este manifiesto se excluye de su huella.'}
if '--guardar' in sys.argv:
    manifest.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
else:
    assert json.loads(manifest.read_text(encoding='utf-8')) == data
print(json.dumps({'estado': data['estado'], 'archivos': len(files), 'enlaces': links, 'completa': results['completa']}, ensure_ascii=False))
