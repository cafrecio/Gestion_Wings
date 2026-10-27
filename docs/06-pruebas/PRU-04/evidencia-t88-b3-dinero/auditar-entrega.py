"""Audita la evidencia propia de dinero; no conecta ni modifica ninguna base."""
import hashlib
import json
import re
import xml.etree.ElementTree as ET
from pathlib import Path
from PIL import Image

folder = Path(__file__).resolve().parent
root = folder.parents[3]
report = folder.parent / 'IMPLEMENTACION-T88-B3-DINERO.md'
gallery = folder.parent / 'APROBAR-T88-B3-DINERO.html'
assert 'PENDIENTE |' not in report.read_text(encoding='utf-8')
results = {}
for name in ['rojo-final', 'area-final', 'completa', 'rapida-cierre', 'tecnica-final', 'fixture-parcial', 'repeticion-ajenos']:
    tree = ET.parse(folder / f'{name}.xml')
    cases = tree.findall('.//testcase')
    results[name] = {
        'tests': len(cases),
        'failures': sum(c.find('failure') is not None for c in cases),
        'errors': sum(c.find('error') is not None for c in cases),
        'skipped': sum(c.find('skipped') is not None for c in cases),
        'assertions': sum(int(c.get('assertions', 0)) for c in cases),
        'exit': int((folder / f'{name}-exit.txt').read_text().strip()),
    }
assert results['rojo-final']['failures'] > 0
for name in ['area-final', 'rapida-cierre', 'tecnica-final', 'fixture-parcial', 'repeticion-ajenos']:
    assert results[name]['exit'] == 0, (name, results[name])
    assert results[name]['failures'] + results[name]['errors'] == 0
assert results['completa']['failures'] + results['completa']['errors'] == 40
assert results['repeticion-ajenos']['tests'] == 39
assert results['fixture-parcial']['tests'] == 1

images = {}
for path in sorted(folder.glob('*-final-*.png')):
    with Image.open(path) as im:
        images[path.name] = list(im.size)
        assert im.width == (600 if '-375' in path.name else 1366)
assert len(images) == 9
for source in [report, gallery]:
    content = source.read_text(encoding='utf-8')
    links = re.findall(r'(?:href|src)="([^"]+)"', content) if source == gallery else re.findall(r'\]\(([^)]+)\)', content)
    for link in links:
        if ':' in link or link.startswith('#'):
            continue
        assert (source.parent / link.split('#')[0]).exists(), (source.name, link)

source_paths = [
    'app/Exceptions/ExcepcionCobranzaRequerida.php', 'app/Models/ExcepcionCobranza.php',
    'app/Services/ExcepcionCobranzaService.php', 'app/Services/CajaService.php',
    'app/Services/PagoCuotaService.php', 'app/Services/AvisoAdminService.php',
    'app/Http/Controllers/CajaWebController.php', 'app/Http/Controllers/WebController.php',
    'database/migrations/2026_10_27_150000_crear_excepciones_cobranza.php',
    'resources/views/admin/dashboard.blade.php', 'resources/views/admin/_excepciones.blade.php',
    'resources/views/caja/cobrar.blade.php', 'resources/js/cobrar.js',
    'tests/Feature/T88B3DineroTest.php', 'tests/Feature/T88EfectivoConcurrenteTest.php',
    'docs/06-pruebas/PRU-04/prueba-tecnica/SeguridadDiasPruebaTest.php',
]
hashes = {p: hashlib.sha256((root / p).read_bytes()).hexdigest() for p in source_paths}
for path in folder.glob('*-dom.json'):
    content = path.read_text(encoding='utf-8')
    assert not re.search(r'name=\\"_token\\"[^>]*value=\\"(?!neutralizado)[A-Za-z0-9]{20}', content), path.name
data = {'alcance': 'Solo evidencia local T88/B3 dinero. Sin consultas a bases ni servidor.',
        'resultados': results, 'dimensiones': images, 'sha256_fuentes': hashes,
        'artefactos': {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(folder.iterdir())
                      if p.is_file() and p.name not in ['auditoria.json']}}
(folder / 'auditoria.json').write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print(json.dumps({'estado': 'PASS', 'resultados': results, 'imagenes': len(images), 'fuentes': len(hashes)}, ensure_ascii=False))
