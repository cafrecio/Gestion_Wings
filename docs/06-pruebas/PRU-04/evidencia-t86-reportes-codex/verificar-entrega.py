"""Comprueba evidencia ya generada; no ejecuta PHP ni modifica ninguna base."""
import ast
import hashlib
import json
import re
import struct
import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from urllib.parse import unquote, urlsplit

folder = Path(__file__).resolve().parent
root = folder.parents[3]
report = folder.parent / 'IMPLEMENTACION-T86.md'
gallery = folder.parent / 'APROBAR-T86-SUELDOS.html'
manifest = folder / 'INTEGRIDAD.json'
expected = {'rojo': (8, 9, 8, 0, 1), 'dirigidas': (28, 131, 0, 0, 0),
            'dirigidas-final': (30, 133, 0, 0, 0), 'roles': (1, 8, 0, 0, 0),
            'captura': (1, 4, 0, 0, 0), 'rapida': (958, 7558, 0, 6, 0),
            'completa': (1077, 9157, 0, 6, 0)}
results = {}
for name, values in expected.items():
    suite = ET.parse(folder / f'{name}.xml').getroot().find('./testsuite')
    actual = tuple(int(suite.get(k)) for k in ('tests', 'assertions', 'failures', 'skipped'))
    rc = int((folder / f'{name}-exit.txt').read_text(encoding='utf-8-sig'))
    assert actual == values[:4] and rc == values[4] and suite.get('errors') == '0', (name, actual, rc)
    results[name] = {**suite.attrib, 'exit': rc}
sources = json.loads((folder / 'fuentes.json').read_text(encoding='utf-8'))
for item in sources['fuentes']:
    assert hashlib.sha256((root / item['ruta']).read_bytes()).hexdigest() == item['sha256'], item['ruta']
capture = json.loads((folder / 'datos-captura.json').read_text(encoding='utf-8'))
assert capture['base'] == 'wings_testing_codex' and capture['datos_sinteticos'] is True
payments = capture['reporte']['pagos_operativas']
assert payments['total_centavos'] == 13600125 and len(payments['filas']) == 3
assert sorted((f['centavos'], f['movimientos']) for f in payments['filas']) == [(3800050, 1), (4800075, 2), (5000000, 1)]
roles = json.loads((folder / 'observaciones-actualizadas.json').read_text(encoding='utf-8'))
assert roles['base']['nombre'] == 'wings_testing_codex'
assert roles['reporte_sueldos']['pagos_operativas']['total_centavos'] == 4800075
assert roles['admin_con_fk_legacy_reservada_vuelve_a_operativo']['reserva'] is True
assert roles['operativa_nueva_promovida_a_admin']['reserva'] is False
images = {}
for name, size in {'con-pagos-1366.png': (1366, 1800), 'sin-pagos-1366.png': (1366, 1800),
                   'con-pagos-375.png': (600, 2480), 'sin-pagos-375.png': (600, 2480),
                   'enfoque-sueldos-con-pagos-375.png': (600, 920), 'enfoque-login-375.png': (600, 920)}.items():
    data = (folder / name).read_bytes()
    assert data[:8] == b'\x89PNG\r\n\x1a\n' and struct.unpack('>II', data[16:24]) == size, name
    images[name] = list(size)
for name in ['marco-sueldos-con-pagos-375.html', 'marco-sueldos-sin-pagos-375.html',
             'enfoque-sueldos-con-pagos-375.html', 'enfoque-login-375.html']:
    assert '375' in (folder / name).read_text(encoding='utf-8-sig')
view = (root / 'resources/views/reportes/sueldos.blade.php').read_text(encoding='utf-8')
assert 'Pagos de operativas' in view and 'pagos_operativas' in view
assert not re.search(r'<script\b(?![^>]*\bsrc=)|\bon(?:click|submit|change)\s*=', view, re.I)
text = report.read_text(encoding='utf-8')
assert 'EN_CURSO_' not in text and 'aprobación visual de Carlos aún pendiente' in text
board = json.loads((root / 'docs/00-estado/tareas.json').read_text(encoding='utf-8'))
t86 = next(t for t in board if t['id'] == 'T86')
assert tuple(t86[k] for k in ('estado', 'hizo', 'verifica', 'tiene')) == ('a_verificar', 'Codex', 'Claude', 'Claude')
assert next(t for t in board if t['id'] == 'T92')['estado'] == 'cerrado'
files = sorted([p for p in folder.iterdir() if p.is_file() and p != manifest] + [report, gallery,
    root / 'docs/06-pruebas/PRU-04/evidencia-t86-codex/ALCANCE-PENDIENTE.md',
    root / 'docs/06-pruebas/PRU-04/evidencia-t86-codex/ObservacionesT86Test.php',
    root / 'docs/06-pruebas/PRU-04/evidencia-t86-codex/verificar-entrega.py'])
links = 0
for path in files:
    if path.suffix == '.json':
        json.loads(path.read_text(encoding='utf-8-sig'))
    elif path.suffix == '.py':
        ast.parse(path.read_text(encoding='utf-8'))
    if path.suffix == '.md' or path == gallery:
        content = path.read_text(encoding='utf-8')
        refs = re.findall(r'\[[^\]]+\]\(([^)\n]+)\)', content) if path.suffix == '.md' else re.findall(r'(?:src|href)="([^"]+)"', content)
        for ref in refs:
            if urlsplit(ref).scheme or ref.startswith('#'):
                continue
            assert (path.parent / unquote(urlsplit(ref).path)).resolve().exists(), (path, ref)
            links += 1
data = {'estado': 'a_verificar', 'resultados': results, 'imagenes': images, 'enlaces_revisados': links,
        'pendiente': ['Verificacion independiente de Claude', 'Aprobacion visual de Carlos', 'Definir reservas antiguas al cambiar de rol'],
        'archivos': [{'ruta': p.relative_to(root).as_posix(), 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()} for p in files],
        'alcance': 'Ampliacion autorizada de T86. Evidencia inicial sellada aparte en f257fd6f. Documentos compartidos revisados por diff, no congelados aqui.'}
if '--guardar' in sys.argv:
    manifest.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
else:
    assert json.loads(manifest.read_text(encoding='utf-8')) == data
print(json.dumps({'archivos': len(files), 'enlaces': links, 'completa': results['completa'], 'estado': data['estado']}))
