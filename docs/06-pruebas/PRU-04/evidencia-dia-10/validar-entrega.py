"""Controles documentales de esta entrega; no opera Wings ni su base."""
from pathlib import Path
from collections import Counter
from datetime import datetime, timezone
import hashlib
import html
import json
import re
from PIL import Image
from pypdf import PdfReader

BASE = Path(__file__).resolve().parent.parent
SHA = lambda p: hashlib.sha256(p.read_bytes()).hexdigest()
day_results = {}
all_events = []
sections = []

for day in (10, 11, 12):
    folder = BASE / f'evidencia-dia-{day}'
    report = BASE / f'DIA-{day}.md'
    frozen = json.loads((folder / 'esperados-originales.json').read_text(encoding='utf-8-sig'))
    rows = {}
    for line in report.read_text(encoding='utf-8-sig').splitlines():
        if re.match(r'\| \d+\.\d+', line):
            cells = [v.strip() for v in line.split('|')[1:-1]]
            rows[cells[0]] = cells
    assert len(rows) == len(frozen['casos'])
    for case in frozen['casos']:
        row = rows[case['id']]
        assert row[:4] == [case['id'], case['quien'], case['tarea'], case['esperado']], case['id']
        assert row[5] in ('Sí', 'No', 'Parcial'), case['id']
        assert row[4] and 'Pendiente de ejecutar' not in row[4], case['id']
    counts = dict(Counter(row[5] for row in rows.values()))
    images = []
    pdfs = []
    for file in sorted(folder.iterdir()):
        if file.suffix.lower() in ('.jpg', '.jpeg', '.png'):
            with Image.open(file) as im:
                im.verify()
            images.append(file.name)
        elif file.suffix.lower() == '.pdf':
            reader = PdfReader(file)
            assert len(reader.pages) >= 1
            assert sum(len(p.extract_text() or '') for p in reader.pages) > 100
            pdfs.append({'archivo': file.name, 'paginas': len(reader.pages)})
    events = [json.loads(s) for s in (folder / 'cambios-usuario.jsonl').read_text(encoding='utf-8').splitlines() if s]
    all_events.extend(events)
    minimum = min(e['segundosDesdeSalir'] for e in events if e.get('segundosDesdeSalir') is not None)
    assert minimum >= 60
    data = {'dia': day, 'situaciones': len(rows), 'resultados': counts,
            'esperados_literales': True, 'fuente_congelada_sha256': frozen['fuente_sha256'],
            'imagenes_decodificadas': len(images), 'recibos': pdfs,
            'pausa_minima_registrada_segundos': minimum,
            'producto_y_tabla': 'Sin cambios; ver diff del commit',
            'limites': 'Día 12: reactivación/pago de Julieta bloqueados en Chrome; cierre parcial. Revisión independiente pendiente.'}
    (folder / 'control-entrega.json').write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    day_results[str(day)] = data
    cards = []
    for ident, row in rows.items():
        matching = [file for file in sorted(folder.iterdir()) if file.name.startswith(ident + '-')]
        imgs = [file for file in matching if file.suffix.lower() in ('.jpg', '.png')]
        links = ' · '.join(f'<a href="evidencia-dia-{day}/{html.escape(file.name)}">{html.escape(file.name)}</a>' for file in matching if file.suffix.lower() not in ('.jpg', '.png'))
        pictures = ''.join(f'<a href="evidencia-dia-{day}/{html.escape(file.name)}"><img loading="lazy" src="evidencia-dia-{day}/{html.escape(file.name)}" alt="{html.escape(file.name)}"><span>{html.escape(file.name)}</span></a>' for file in imgs)
        cards.append(f'<details><summary>{html.escape(ident)} · {html.escape(row[1])} · {html.escape(row[5])}</summary><p>{html.escape(row[4])}</p><div class="fotos">{pictures}</div><p>{links or "Ver límites en el informe y registro de bloqueo."}</p></details>')
    sections.append(f'<section id="dia-{day}"><h2>Día {day}</h2><p>{len(rows)} situaciones · {html.escape(str(counts))}. <a href="DIA-{day}.md">Informe completo con esperados</a> · <a href="evidencia-dia-{day}/LEEME.md">Índice de archivos</a></p>{"".join(cards)}</section>')

all_events.sort(key=lambda e: e['fecha'])
last_exit = None
for event in all_events:
    when = datetime.fromisoformat(event['fecha'].replace('Z', '+00:00'))
    if event['evento'] == 'salir':
        last_exit = when
    elif event['evento'] == 'intento_ingreso' and last_exit:
        assert (when - last_exit).total_seconds() >= 60, event

gallery = '''<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PRU-04 · Días 10 a 12</title>
<style>body{font:16px system-ui;max-width:1200px;margin:32px auto;padding:0 18px;color:#222;background:#f5f5f5}a{color:#24566c}details{background:white;border:1px solid #ccc;border-radius:8px;margin:12px 0;padding:14px}summary{cursor:pointer;font-weight:650}p{line-height:1.5}.fotos{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px}.fotos img{width:100%;height:220px;object-fit:contain;background:#eee}.fotos span{display:block;font-size:12px;overflow-wrap:anywhere}nav{display:flex;gap:24px}.aviso{background:#fff2d5;padding:16px;border-left:4px solid #a57116}</style>
<h1>PRU-04 · Días 10 a 12</h1><p>Recorrido real en Chrome sobre test/wingstest. Codex CAB, 25/10/2026. Capturas del sistema y recibos descargados; esta página solo organiza la evidencia.</p>
<p class="aviso">Entrega parcial: días 10 y 11 recorridos; día 12 ejecutado hasta 12.21. La reactivación y el cobro de Julieta quedaron bloqueados por el control de Chrome. Cierre final parcial. No es aprobación integral ni cierre de T14. Los esperados congelados al iniciar se conservan en los informes.</p>
<nav><a href="#dia-10">Día 10</a><a href="#dia-11">Día 11</a><a href="#dia-12">Día 12</a></nav>'''
(BASE / 'EVIDENCIA-DIAS-10-12.html').write_text(gallery + ''.join(sections) + '</html>\n', encoding='utf-8')

for day in (10, 11, 12):
    folder = BASE / f'evidencia-dia-{day}'
    files = sorted(p for p in folder.iterdir() if p.is_file() and p.name != 'LEEME.md')
    text = f'# Evidencia del día {day}\n\n[Informe](../DIA-{day}.md) · [Galería](../EVIDENCIA-DIAS-10-12.html)\n\n'
    text += 'La evidencia describe lo observado; los casos bloqueados no se acreditan. Los JSON de esperados se guardaron antes de operar. La tabla externa permanece intacta.\n\n'
    if day == 12:
        text += '[Bloqueo de Chrome y último cierre observado](12.22-12.25-bloqueo-chrome.md). No hay recibo de Julieta ni nueva captura del cierre; ver límite explícito.\n\n'
    text += '\n'.join(f'- [{p.name}]({p.name})' for p in files) + '\n'
    (folder / 'LEEME.md').write_text(text, encoding='utf-8')

# Normalizar solo los artefactos propios de esta entrega antes de calcular huellas.
own = [BASE / f'DIA-{d}.md' for d in (10, 11, 12)] + [BASE / 'EVIDENCIA-DIAS-10-12.html']
for day in (10, 11, 12):
    own.extend((BASE / f'evidencia-dia-{day}').iterdir())
for file in own:
    if file.suffix.lower() in ('.md', '.txt', '.json', '.jsonl', '.html', '.py', '.patch'):
        text = file.read_text(encoding='utf-8-sig')
        normalized = text.replace('\r\n', '\n') if file.suffix == '.patch' else '\n'.join(line.rstrip() for line in text.splitlines()) + '\n'
        file.write_text(normalized, encoding='utf-8', newline='\n')

for day in (10, 11, 12):
    folder = BASE / f'evidencia-dia-{day}'
    manifest = folder / 'integridad.json'
    paths = [BASE / f'DIA-{day}.md', BASE / 'EVIDENCIA-DIAS-10-12.html']
    paths.extend(p for p in sorted(folder.iterdir()) if p.is_file() and p != manifest)
    records = [{'archivo': p.relative_to(BASE).as_posix(), 'bytes': p.stat().st_size, 'sha256': SHA(p)} for p in paths]
    manifest.write_text(json.dumps({'archivos': records}, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
    for record in records:
        assert SHA(BASE / record['archivo']) == record['sha256']

print(json.dumps({day: {'situaciones': v['situaciones'], 'resultados': v['resultados'], 'imagenes': v['imagenes_decodificadas'], 'pdf': len(v['recibos'])} for day, v in day_results.items()}, ensure_ascii=False))
