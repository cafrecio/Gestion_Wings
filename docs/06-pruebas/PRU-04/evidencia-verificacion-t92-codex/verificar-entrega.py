"""Audita evidencia T92 ya guardada; no ejecuta Laravel ni conecta a una base."""
import ast
import hashlib
import json
import re
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from urllib.parse import unquote, urlsplit

folder = Path(__file__).resolve().parent
root = folder.parents[3]
manifest = folder / 'INTEGRIDAD.json'
report = folder.parent / 'VERIFICACION-T92-CODEX.md'
expected = {'propias-final': (8, 44, 2, 0, 1), 'area': (91, 461, 0, 0, 0), 'rapida': (939, 7465, 0, 6, 0)}
results = {}
for name, values in expected.items():
    s = ET.parse(folder / (name + '.xml')).getroot().find('./testsuite')
    actual = tuple(int(s.get(k)) for k in ('tests', 'assertions', 'failures', 'skipped'))
    assert actual == values[:4] and int(s.get('errors')) == 0, (name, actual)
    assert int((folder / (name + '-exit.txt')).read_text(encoding='utf-8-sig')) == values[4]
    results[name] = {**s.attrib, 'exit': values[4]}
failures = [c.get('name') for c in ET.parse(folder / 'propias-final.xml').getroot().iter('testcase') if c.find('failure') is not None]
assert failures == ['test_repetir_no_suma_otra_vez_la_misma_revision', 'test_tercera_corrida_suma_solo_la_revision_nueva']
permanent = [s for s in ET.parse(folder / 'area.xml').getroot().iter('testsuite') if s.get('name', '').endswith('GeneracionCuotasReintentoT40Test')]
assert len(permanent) == 1 and permanent[0].get('tests') == '10' and permanent[0].get('assertions') == '47'

source = json.loads((folder / 'fuentes.json').read_text(encoding='utf-8'))
assert len(source['fuentes']) == 19
for item in source['fuentes']:
    b = (root / item['ruta']).read_bytes()
    assert hashlib.sha256(b).hexdigest() == item['sha256'], item['ruta']
    original = subprocess.check_output(['git', 'show', 'c30bb7c2:' + item['ruta']], cwd=root)
    assert b.replace(b'\r\n', b'\n') == original.replace(b'\r\n', b'\n')
repeated = json.loads((folder / 'revision-repetida.json').read_text(encoding='utf-8'))
assert repeated['primera']['a_revision'] == 1 and repeated['segunda']['a_revision'] == 2
assert len(repeated['revisiones']) == 1
added = json.loads((folder / 'revision-nueva.json').read_text(encoding='utf-8'))
assert [r['a_revision'] for r in added['constancias']] == [1, 2, 4] and len(added['revisiones']) == 2
debts = json.loads((folder / 'tres-corridas-cuotas.json').read_text(encoding='utf-8'))
assert [r['creadas'] for r in debts['constancias']] == [2, 2, 3]
assert {r['terminada_at'] for r in debts['constancias']} == {'2026-11-01 06:00:00'}
assert len(debts['deudas']) == 3
schema = json.loads((folder / 'columnas-on-update.json').read_text(encoding='utf-8'))
assert schema['base']['nombre'] == 'wings_testing_codex' and 'MariaDB' in schema['base']['motor']
assert [(c['TABLE_NAME'], c['COLUMN_NAME']) for c in schema['columnas']] == [('cargo_alumno_eventos', 'created_at'), ('generaciones_cuotas', 'terminada_at'), ('reporte_eventos', 'registrado_en')]
assert all('on update' in c['EXTRA'].lower() for c in schema['columnas'])
text = report.read_text(encoding='utf-8')
assert 'Dictamen: DEVUELTA' in text and 'PENDIENTE_RESULTADO' not in text

# Los documentos compartidos siguen evolucionando; sus bytes completos no son evidencia propia congelada.
shared = [root / 'docs/00-estado' / f for f in ['LOG-CODEX.md', 'ESTADO-ACTUAL.md', 'RESUMEN-ARRANQUE.md', 'tareas.json', 'TABLERO.html', 'QUIEN-HACE-QUE.md']]
shared.append(folder.parent / 'ESTADO-PRUEBA.md')
if '--tablero' in sys.argv:
    board = json.loads((root / 'docs/00-estado/tareas.json').read_text(encoding='utf-8'))
    t = next(t for t in board if t['id'] == 'T92')
    assert (t['estado'], t['tiene'], t['hizo'], t['verifica']) == ('devuelto', 'Claude', 'Claude', 'Codex')
    assert next(t for t in board if t['id'] == 'T90')['estado'] == 'cerrado'

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
data = {'dictamen': 'devuelto', 'resultados': results, 'fallos': failures, 'fuentes_estables': 19,
        'enlaces_revisados': links, 'archivos': [{'ruta': p.relative_to(root).as_posix(), 'sha256': hashlib.sha256(p.read_bytes()).hexdigest(), 'bytes': p.stat().st_size} for p in files],
        'compartidos_actualizados': [p.relative_to(root).as_posix() for p in shared],
        'limite': 'Los compartidos se revisan por diff al entregar; no se congelan sus bytes para otras tareas. --tablero comprueba el estado al entregar. El manifiesto se excluye de su propia huella.'}
if '--guardar' in sys.argv:
    manifest.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
else:
    assert json.loads(manifest.read_text(encoding='utf-8')) == data, 'Cambio un artefacto sellado'
print(json.dumps({'dictamen': data['dictamen'], 'fallos': failures, 'archivos_sellados': len(files), 'enlaces': links, 'fuentes': 19}, ensure_ascii=False))
