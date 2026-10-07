from pathlib import Path
from html import escape

root = Path(__file__).parent
screens = sorted(file.name.removesuffix('-antes-375.jpg') for file in (root/'capturas').glob('*-antes-375.jpg'))
html = ['<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>A14/A27/A53 — Antes y después</title><style>body{font:16px system-ui;background:#f5f5f5;color:#222;margin:24px}section{background:white;padding:20px;margin:24px 0}h1,h2{margin-top:0}.par{display:flex;gap:20px;flex-wrap:wrap}.par figure{margin:0;max-width:48%}.par img{max-width:100%;height:auto;width:376px;border:1px solid #aaa}.desktop img{width:560px}figcaption{font-weight:bold;margin:8px 0}a{color:#185b85}@media(max-width:700px){.par figure{max-width:100%}}</style><h1>A14 / A27 / A53</h1><p>Preparación local — pendiente aprobación visual para publicar. Todas las imágenes provienen de Wings. Celular dentro del marco de 375 × 667; escritorio, archivos de 1280 × 613.</p><p>El cartel se actualiza al editar: los obligatorios completados salen, los nuevos errores entran; las comprobaciones contra la base quedan pendientes al guardar. Su JavaScript está en un archivo externo.</p>']
for screen in screens:
    html.append('<section><h2>'+escape(screen)+'</h2>')
    for size in ('375','desktop'):
        html.append('<div class="par '+('desktop' if size=='desktop' else '')+'">')
        for phase in ('antes','despues'):
            name = screen+'-'+phase+'-'+size+'.jpg'
            if not (root/'capturas'/name).exists(): continue
            html.append(f'<figure><figcaption>{phase.upper()} — {size}</figcaption><a href="capturas/{name}"><img loading="lazy" src="capturas/{name}" alt="{escape(name)}"></a></figure>')
        html.append('</div>')
    html.append('</section>')
extras = sorted(file for file in (root/'capturas').glob('*-despues-*.jpg') if not any(file.name.startswith(screen+'-despues-') for screen in screens))
html.append('<section><h2>Prueba del cartel y control PROFESOR</h2><div class="par">')
for file in extras:
    html.append(f'<figure><figcaption>{escape(file.stem)}</figcaption><a href="capturas/{file.name}"><img loading="lazy" src="capturas/{file.name}" alt="{escape(file.stem)}"></a></figure>')
html.append('</div></section></html>')
(root/'visor-comparacion.html').write_text('\n'.join(html), encoding='utf-8')
print(f'{len(screens)} casos comparados, {len(extras)} controles adicionales')
