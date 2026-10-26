"""Arma la galería documental con archivos reales; no modifica Wings ni sus datos."""
import html
import json
import re
from collections import Counter
from pathlib import Path

base = Path(__file__).resolve().parent.parent
incidents = json.loads((base / 'evidencia-dia-14/incidentes-evidencia.json').read_text(encoding='utf-8'))
invalid = {x['archivo'] for x in incidents}
escape = html.escape
sections = []
all_counts = Counter()
for day in (13, 14, 15, 16):
    folder = base / f'evidencia-dia-{day}'
    path = base / f'DIA-{day}.md'
    text = path.read_text(encoding='utf-8-sig')
    console = '## Consola y rutina\n\n'
    for name, title in [('reloj-inicio.txt', 'Reloj: una ejecución'), ('rutina-manana.txt', 'Rutina de mañana: una ejecución'), ('rutina-noche.txt', 'Rutina de noche: una ejecución')]:
        source = folder / name
        if source.exists():
            raw = source.read_text(encoding='utf-8-sig').replace('\r\n', '\n')
            console += f'### {title}\n\n```text\n{raw}' + ('' if raw.endswith('\n') else '\n') + '```\n\n'
    if day == 16:
        console += 'El domingo no lleva rutina de mañana. Solo se operó como Dueño.\n\n'
    if day == 14:
        console += 'La lista #21 tuvo 18 alumnos y 11 presentes, frente a 19 y 12 esperados. No se repararon datos. El Resumen mostró esperado $156.000 antes de ejecutar la rutina nocturna.\n\n'
        text = re.sub(r'\n### Noche \(una ejecución\)[\s\S]*$', '\n', text)
    text = re.sub(r'## Consola y rutina\n[\s\S]*?(?=## Cierre y diferencias)', lambda _: console, text, count=1)
    rows = []
    for line in text.splitlines():
        if re.match(rf'^\| {day}\.\d+ \|', line):
            rows.append([x.strip() for x in line.strip('|').split('|')])
    counts = Counter(row[5] for row in rows)
    all_counts.update(counts)
    summary = ' · '.join(f'{n} {status}' for status, n in counts.items())
    appendix = f'\n## Evidencia y revisión\n\nResultado de esta jornada: {summary}. [Índice de evidencia](evidencia-dia-{day}/LEEME.md) · [Galería de las cuatro jornadas](EVIDENCIA-DIAS-13-16.html#dia-{day}).\n\nT14 continúa en curso. Esta entrega requiere verificación independiente; no constituye cierre de la tarea ni aprobación de todos los casos. La herramienta de Chrome bloqueó el protocolo file al abrir la galería local: se controlaron estructura y enlaces, sin afirmar render de esa galería. Las capturas de Wings sí provienen del sitio en marcha.\n'
    text = re.sub(r'\n## Evidencia y revisión[\s\S]*$', '', text) + appendix
    path.write_text(text, encoding='utf-8', newline='\n')
    media = sorted(p for p in folder.iterdir() if p.suffix.lower() in {'.png', '.jpg', '.jpeg', '.pdf'} and f'{folder.name}/{p.name}' not in invalid)
    case_html = []
    for row in rows:
        identifier, actor, task, expected, actual, status = row
        assets = [p for p in media if p.name.startswith(identifier + '-')]
        pictures = ''.join(f'<figure><a href="{escape(folder.name + "/" + p.name)}"><img loading="lazy" src="{escape(folder.name + "/" + p.name)}" alt="{escape(p.name)}"></a><figcaption>{escape(p.name)}</figcaption></figure>' for p in assets if p.suffix.lower() != '.pdf')
        pdfs = ''.join(f'<p><a href="{escape(folder.name + "/" + p.name)}">{escape(p.name)}</a></p>' for p in assets if p.suffix.lower() == '.pdf')
        if not assets:
            pictures = '<p>Sin captura acreditada de este caso. Consultar la observación directa y sus límites en el informe.</p>'
        case_html.append(f'<article id="caso-{identifier}"><h3>{identifier} · {escape(actor)} · {escape(status)}</h3><p><b>Tarea:</b> {escape(task)}</p><p><b>Esperado original:</b> {escape(expected)}</p><p><b>Resultado:</b> {escape(actual)}</p><details><summary>Evidencia acreditada ({len(assets)} archivos)</summary>{pdfs}{pictures}</details></article>')
    incident_links = [x for x in incidents if x['archivo'].startswith(folder.name + '/')]
    readme = f'# Evidencia del día {day}\n\n{summary}. [Informe](../DIA-{day}.md) · [Galería](../EVIDENCIA-DIAS-13-16.html#dia-{day}).\n\nLos esperados se congelaron antes de operar en [esperados-originales.json](esperados-originales.json). Los estados Sí/No/Parcial corresponden al criterio del guion; no sustituyen la revisión independiente. La herramienta de Chrome bloqueó el protocolo file al abrir la galería local: se verifican estructura y enlaces, sin afirmar render de esa galería. Las capturas de Wings sí provienen del sitio en marcha.\n\n## Archivos acreditados\n\n'
    readme += '\n'.join(f'- [{p.name}]({p.name})' for p in media) + '\n'
    for p in sorted(folder.glob('*.txt')):
        if f'{folder.name}/{p.name}' not in invalid:
            readme += f'- [{p.name}]({p.name})\n'
    if incident_links:
        readme += '\n## Archivos que no acreditan su caso\n\nLos auxiliares iniciales conservaron una pestaña anterior al recuperarla. Se preservan estos archivos como incidentes, sin usarlos en la galería. Las recapturas posteriores solo acreditan el estado vigente, no recrean una pantalla histórica.\n\n'
        readme += '\n'.join(f'- [{Path(x["archivo"]).name}]({Path(x["archivo"]).name})' for x in incident_links) + '\n'
    if day == 16:
        readme += '\n## Verificación documental\n\n[Verificador de esperados](verificar-entrega.py) · [Generador de galería](preparar-entrega.py). No acceden a la aplicación ni a la base.\n'
    (folder / 'LEEME.md').write_text(readme, encoding='utf-8', newline='\n')
    sections.append(f'<section id="dia-{day}"><h2>Día {day} · {escape(summary)}</h2><p><a href="DIA-{day}.md">Informe completo</a> · <a href="{folder.name}/LEEME.md">Índice de evidencia</a></p>{"".join(case_html)}</section>')

page = '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>PRU-04 · Días 13 a 16</title><style>body{font:16px/1.5 system-ui,sans-serif;max-width:1100px;margin:auto;padding:24px;background:#f6f6f6;color:#222}article{background:white;border:1px solid #bbb;padding:18px;margin:20px 0}a{color:#164a76}img{max-width:100%;height:auto}figure{margin:20px 0}figcaption{overflow-wrap:anywhere}summary{cursor:pointer}nav{display:flex;gap:20px;flex-wrap:wrap}h2{margin-top:48px}</style><h1>PRU-04 · Días 13 a 16</h1><p><b>25 Sí · 12 Parcial · 2 No.</b> Los 39 casos tienen resultado registrado; no se declara conformidad total.</p><p>Sitio de prueba, staging/wingstest, datos ficticios. Se preservan diferencias y pasos salteados. T14 sigue en curso, pendiente de verificación independiente. Capturas tomadas del sistema; ninguna maqueta de Wings.</p><p><b>Límite de evidencia:</b> 40 archivos de los días 13/14 fueron capturados por un auxiliar que conservó la pestaña anterior. Sus nombres no acreditan el caso y se excluyen de las imágenes de esta galería. Permanecen identificados en los índices como incidentes. Las recapturas se rotulan como posteriores.</p><nav>' + ''.join(f'<a href="#dia-{d}">Día {d}</a>' for d in (13,14,15,16)) + '</nav>' + ''.join(sections) + '<p>Reloj final iniciado una sola vez: 25/10/2026 15:00. No repetir cobros, rutinas ni cambios de fecha de estas jornadas.</p></html>\n'
(base / 'EVIDENCIA-DIAS-13-16.html').write_text(page, encoding='utf-8', newline='\n')
assert sum(all_counts.values()) == 39
print(dict(all_counts))
print('Galería e índices generados; 40 archivos incidentales excluidos de las imágenes.')
