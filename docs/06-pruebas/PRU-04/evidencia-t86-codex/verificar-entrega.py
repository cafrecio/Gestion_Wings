"""Audita el corte inicial T86 f257fd6f; no exige conservar su limitación ya resuelta."""
import hashlib
import json
import subprocess
import xml.etree.ElementTree as ET
from pathlib import Path

folder = Path(__file__).resolve().parent
root = folder.parents[3]
revision = 'f257fd6f'

def historic(path):
    return subprocess.check_output(['git', 'show', f'{revision}:{path}'], cwd=root)

prefix = folder.relative_to(root).as_posix()
manifest = json.loads(historic(f'{prefix}/INTEGRIDAD.json'))
# Solo estos archivos evolucionaron con la ampliación autorizada.
evolved = {f'{prefix}/ALCANCE-PENDIENTE.md', f'{prefix}/ObservacionesT86Test.php',
           f'{prefix}/verificar-entrega.py', 'docs/06-pruebas/PRU-04/IMPLEMENTACION-T86.md'}
for item in manifest['archivos']:
    assert hashlib.sha256(historic(item['ruta'])).hexdigest() == item['sha256'], item['ruta']
    if item['ruta'] not in evolved:
        assert hashlib.sha256((root / item['ruta']).read_bytes()).hexdigest() == item['sha256'], item['ruta']
sources = json.loads(historic(f'{prefix}/fuentes.json'))
for item in sources['fuentes']:
    assert hashlib.sha256(historic(item['ruta'])).hexdigest() == item['sha256'], item['ruta']
expected = {'rojo': (10, 18, 7, 0), 'dirigidas': (24, 162, 0, 0),
            'observaciones-final': (1, 6, 0, 0), 'rapida': (950, 7521, 0, 6),
            'completa': (1069, 9120, 0, 6)}
for name, values in expected.items():
    suite = ET.parse(folder / f'{name}.xml').getroot().find('./testsuite')
    assert tuple(int(suite.get(k)) for k in ('tests', 'assertions', 'failures', 'skipped')) == values
    assert suite.get('errors') == '0'
    assert int((folder / f'{name}-exit.txt').read_text(encoding='utf-8-sig')) == (1 if name == 'rojo' else 0)
print(json.dumps({'corte_historico': revision, 'archivos': len(manifest['archivos']),
                  'vigencia': 'La ampliacion actual se audita en evidencia-t86-reportes-codex.'}))
