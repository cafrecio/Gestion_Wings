"""Solo normaliza tokens y rutas de assets del HTML devuelto por Wings."""
from pathlib import Path
from urllib.parse import urlparse
from lxml import html

folder = Path(__file__).resolve().parent
public = folder.parents[3] / 'public'
for name, height in [('lista', 1800), ('login', 800)]:
    path = folder / f'{name}-real.html'
    dom = html.fromstring(path.read_text(encoding='utf-8'))
    for meta in dom.xpath('//meta[@name="csrf-token"]'): meta.set('content', 'neutralizado')
    for token in dom.xpath('//input[@name="_token"]'): token.set('value', 'neutralizado')
    for element in dom.xpath('//*[@src or @href]'):
        for attr in ['src', 'href']:
            value = element.get(attr, '')
            parsed = urlparse(value)
            local = public / parsed.path.lstrip('/')
            if parsed.scheme in ['', 'http', 'https'] and local.is_file(): element.set(attr, local.as_uri())
    path.write_text('<!doctype html>\n'+html.tostring(dom, encoding='unicode', method='html')+'\n', encoding='utf-8', newline='\n')
    frame = f'<!doctype html><html lang="es"><meta charset="utf-8"><title>B3 primera carga · {name}</title><body style="margin:0;background:#ddd;font:14px Arial;text-align:center"><p id="medicion">Marco375</p><iframe id="pantalla" src="{name}-real.html" style="border:0;width:375px;height:{height}px"></iframe><script src="medir-marco.js"></script></body></html>\n'
    (folder / f'marco-{name}-375.html').write_text(frame, encoding='utf-8', newline='\n')
print('HTML reales preparados, sin tokens ni rutas de assets al servidor.')
