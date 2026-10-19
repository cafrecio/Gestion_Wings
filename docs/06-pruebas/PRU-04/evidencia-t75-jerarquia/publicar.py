"""Publica evidencia real; la galería solo contiene las cinco observaciones."""
from pathlib import Path
import hashlib
import html
import json
import shutil

root = Path(__file__).resolve().parent
repo = root.parents[3]
runtime = repo / 'storage/app/t75-jerarquia-runtime/app/capturas'
seleccion = {
    'admin-cashflow-movimientos-id-eliminar': 'Eliminar movimiento',
    'admin-clases-create': 'Nueva clase',
    'admin-liquidaciones-id': 'Liquidación',
    'operativo-0': 'Inicio operativo',
    'admin-revision-cobranza': 'Revisión',
}
antes = json.loads((root/'mediciones-antes.json').read_text(encoding='utf8'))['casos']
despues = json.loads((root/'mediciones-despues.json').read_text(encoding='utf8'))['casos']
extras = json.loads((root/'mediciones-comision.json').read_text(encoding='utf8'))['casos'] if (root/'mediciones-comision.json').exists() else []
manifiesto = []
for fase, casos in [('antes', antes), ('despues', despues + extras)]:
    for c in casos:
        # Guardar internamente las pantallas afectadas por CSS/markup compartidos.
        afectados = c['slug'] in seleccion or bool(c.get('cifras'))
        if not afectados:
            continue
        for foto in c['fotos'] + [v['foto'] for v in c.get('variantes', [])]:
            src = runtime/fase/foto['nombre']
            assert hashlib.sha256(src.read_bytes()).hexdigest() == foto['sha256']
            dst = root/'capturas'/fase/foto['nombre']
            dst.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(src, dst)
            manifiesto.append({'archivo': dst.relative_to(root).as_posix(), 'sha256': foto['sha256']})

for archivo in root.glob('revision-interruptor-*.png'):
    manifiesto.append({'archivo': archivo.name, 'sha256': hashlib.sha256(archivo.read_bytes()).hexdigest()})
cards = []
for slug, nombre in seleccion.items():
    cards.append(f'<section id="{slug}"><h2>{html.escape(nombre)}</h2><div class="par"><figure><figcaption>Antes</figcaption><img loading="lazy" data-fase="antes" data-slug="{slug}" alt="Antes: {html.escape(nombre)}"></figure><figure><figcaption>Después</figcaption><img loading="lazy" data-fase="despues" data-slug="{slug}" alt="Después: {html.escape(nombre)}"></figure></div></section>')
catalogo = {fase: {f"{c['slug']}:{c['ancho']}": [f['nombre'] for f in c['fotos']] for c in casos if c['slug'] in seleccion} for fase, casos in [('antes', antes), ('despues', despues)]}
pagina = '''<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>T75 — Jerarquía fija</title>
<style>body{font:16px system-ui;background:#f4f6f8;color:#111827;margin:0;padding:24px}header{position:sticky;top:0;background:#fff;padding:12px;z-index:1;border:1px solid #d8e0ea;border-radius:8px}h1{font-size:22px;margin:0 0 12px}nav{display:flex;gap:12px;flex-wrap:wrap;margin-top:12px}a{color:#496c85}section{scroll-margin-top:155px;margin:32px 0}h2{font-size:20px}.par{display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap}figure{margin:0;min-width:0}figcaption{margin-bottom:8px}img{display:block;max-width:100%;border:1px solid #d8e0ea}select{padding:6px}</style>
<header><h1>Cinco pantallas observadas · jerarquía fija</h1><label>Ancho <select id="ancho"><option>375</option><option>360</option><option>1366</option></select></label> <label>Zona <select id="zona"><option value="principal">Inicio</option><option value="final">Pie</option></select></label><nav>__NAV__</nav></header>
__CARDS__
<script>const catalogo=__CATALOGO__;function mostrar(){const a=document.querySelector('#ancho').value,z=document.querySelector('#zona').value;document.querySelectorAll('img[data-slug]').forEach(i=>{const fotos=catalogo[i.dataset.fase][i.dataset.slug+':'+a];let f=fotos.find(f=>f.endsWith('--'+z+'.png'))||fotos[0];i.src='capturas/'+i.dataset.fase+'/'+f;i.style.width=a+'px';});}document.querySelectorAll('select').forEach(s=>s.addEventListener('change',mostrar));mostrar();</script></html>'''
pagina = pagina.replace('__NAV__', ''.join(f'<a href="#{s}">{html.escape(n)}</a>' for s,n in seleccion.items())).replace('__CARDS__', '\n'.join(cards)).replace('__CATALOGO__', json.dumps(catalogo))
(root/'GALERIA.html').write_text(pagina, encoding='utf8')
(root/'capturas-manifiesto.json').write_text(json.dumps(manifiesto, indent=2), encoding='utf8')
print(f'Galería: {len(seleccion)} pantallas. Evidencia interna: {len(manifiesto)} PNG con huella.')
