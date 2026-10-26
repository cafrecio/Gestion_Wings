"""Audita y sella la entrega documental. No ejecuta Wings ni consulta el servidor."""
import hashlib
import json
import re
import runpy
import subprocess
import sys
from collections import Counter
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlsplit

from PIL import Image
from pypdf import PdfReader

base = Path(__file__).resolve().parent.parent
root = base.parents[2]
manifest = base / 'INTEGRIDAD-DIAS-13-16.json'
shared = [root / 'docs/00-estado' / name for name in ['ESTADO-ACTUAL.md', 'LOG-CODEX.md', 'RESUMEN-ARRANQUE.md', 'tareas.json', 'TABLERO.html', 'QUIEN-HACE-QUE.md']]
archives = list((root / 'docs/99-archivo/bitacoras/2026-10-26').glob('LOG-CODEX-DIAS-13-16-ANTECEDENTES.*'))
files = [base / f'DIA-{day}.md' for day in (13, 14, 15, 16)] + [base / 'EVIDENCIA-DIAS-13-16.html'] + shared + archives
for day in (13, 14, 15, 16):
    files.extend(p for p in (base / f'evidencia-dia-{day}').rglob('*') if p.is_file())
files = sorted(set(files))

old_args = sys.argv
sys.argv = ['verificar-entrega.py', '--completo']
runpy.run_path(str(base / 'evidencia-dia-16/verificar-entrega.py'), run_name='__main__')
sys.argv = old_args

class Links(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self.ids = set()
        self.images = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if attrs.get('id'):
            self.ids.add(attrs['id'])
        for key in ('href', 'src'):
            if attrs.get(key):
                self.links.append(attrs[key])
        if tag == 'img' and attrs.get('src'):
            self.images.append(attrs['src'])

def verify_link(link, parent):
    if urlsplit(link).scheme or link.startswith('//'):
        return
    parsed = urlsplit(link)
    if not parsed.path:
        return
    target = (parent / unquote(parsed.path)).resolve()
    assert target.exists(), (parent, link)
    if parsed.fragment and target.suffix == '.html':
        doc = Links()
        doc.feed(target.read_text(encoding='utf-8-sig'))
        assert unquote(parsed.fragment) in doc.ids, (target, parsed.fragment)

images = pdfs = link_count = 0
for path in files:
    if path.suffix.lower() in {'.jpg', '.jpeg', '.png'}:
        with Image.open(path) as img:
            img.verify()
        images += 1
    elif path.suffix == '.pdf':
        assert len(PdfReader(path).pages) > 0
        pdfs += 1
    elif path.suffix == '.json':
        json.loads(path.read_text(encoding='utf-8-sig'))
    elif path.suffix == '.md':
        text = path.read_text(encoding='utf-8-sig')
        if path in archives:
            meta = json.loads(path.with_suffix('.json').read_text(encoding='utf-8'))
            raw = path.read_bytes()
            start, size = meta['bloque_offset'], meta['bloque_bytes']
            block = raw[start:start + size]
            assert hashlib.sha256(block).hexdigest() == meta['sha256_bloque']
            segments = [(block.decode('utf-8'), root / meta['base_enlaces_originales']), ((raw[:start] + raw[start + size:]).decode('utf-8'), path.parent)]
        else:
            links = re.findall(r'\[[^\]]+\]\(([^)\n]+)\)', text)
            if path in shared:
                old = subprocess.check_output(['git', 'show', f'78d5b0a2:{path.relative_to(root).as_posix()}'], cwd=root).decode('utf-8-sig')
                links = list((Counter(links) - Counter(re.findall(r'\[[^\]]+\]\(([^)\n]+)\)', old))).elements())
            segments = [('', path.parent)]
            for link in links:
                verify_link(link, path.parent)
                link_count += 1
        for segment, parent in segments:
            for link in re.findall(r'\[[^\]]+\]\(([^)\n]+)\)', segment):
                verify_link(link, parent)
                link_count += 1

gallery = Links()
gallery.feed((base / 'EVIDENCIA-DIAS-13-16.html').read_text(encoding='utf-8'))
for link in gallery.links:
    verify_link(link, base)
    link_count += 1
invalid = {x['archivo'] for x in json.loads((base / 'evidencia-dia-14/incidentes-evidencia.json').read_text(encoding='utf-8'))}
assert len(invalid) == 40
assert not invalid.intersection(gallery.images)
assert len([x for x in gallery.ids if x.startswith('caso-')]) == 39

events = []
for day in (13, 14, 15, 16):
    folder = base / f'evidencia-dia-{day}'
    report = (base / f'DIA-{day}.md').read_text(encoding='utf-8')
    for name in ('reloj-inicio.txt', 'rutina-manana.txt', 'rutina-noche.txt'):
        path = folder / name
        if path.exists():
            assert path.read_text(encoding='utf-8-sig').replace('\r\n', '\n') in report, (day, name)
    log = folder / 'cambios-usuario.jsonl'
    events.extend(json.loads(line) for line in log.read_text(encoding='utf-8').splitlines() if line)
pauses = [e['segundosDesdeSalir'] for e in events if e.get('evento') == 'intento_ingreso' and e.get('segundosDesdeSalir') is not None]
assert pauses and min(pauses) >= 60

product = subprocess.check_output(['git', 'diff', '78d5b0a2', '--name-only', '--', 'app', 'resources', 'routes', 'tests', 'scripts', 'database', 'config', 'bootstrap'], cwd=root).decode().strip()
assert not product, product
rows = [{'ruta': p.relative_to(root).as_posix(), 'bytes': p.stat().st_size, 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()} for p in files]
checks = {'casos': 39, 'si': 25, 'parcial': 12, 'no': 2, 'imagenes_decodificadas': images, 'pdfs_legibles': pdfs, 'enlaces_controlados': link_count, 'archivos_incidentales_excluidos_de_imagenes': 40, 'pausa_minima_registrada_segundos': min(pauses), 'salidas_consola_literales': True, 'producto_sin_cambios': True, 'galeria_renderizada_en_navegador': False, 'limite_galeria': 'Chrome rechazó el protocolo file; no se buscó otro acceso para eludirlo.'}
data = {'controles': checks, 'archivos': rows, 'excluido_del_autosellado': manifest.relative_to(root).as_posix()}
if '--guardar' in old_args:
    manifest.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
else:
    assert json.loads(manifest.read_text(encoding='utf-8')) == data, 'Huellas cambiadas'
print(json.dumps(checks, ensure_ascii=False))
print(f'{len(rows)} archivos propios sellados; manifiesto excluido de su propia huella.')
