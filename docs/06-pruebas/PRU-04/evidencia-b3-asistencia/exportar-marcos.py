"""Serializa únicamente DOM y controles observados en Wings funcionando."""
import json
from pathlib import Path
from lxml import html

folder = Path(__file__).resolve().parent
public_path = folder.parents[3] / 'public'
public = public_path.as_uri() + '/'
for name in ['lista', 'motivos', 'guardada', 'guardada-profesora', 'inicio', 'login']:
    path = folder / f'{name}-dom.json'
    data = json.loads(path.read_text(encoding='utf-8'))
    dom = html.fromstring(data['html'])
    for meta in dom.xpath('//meta[@name="csrf-token"]'):
        meta.set('content', 'neutralizado')
    controls = dom.xpath('//input[not(ancestor::template)] | //select[not(ancestor::template)] | //textarea[not(ancestor::template)]')
    assert len(controls) == len(data['controles']), (name, len(controls), len(data['controles']))
    for element, observed in zip(controls, data['controles']):
        assert element.tag.upper() == observed['tag']
        value = observed['value']
        if observed['name'] == '_token' or element.get('type') == 'password':
            value = 'neutralizado' if observed['name'] == '_token' else ''
            observed['value'] = value
        if element.tag == 'textarea':
            element.text = value
        elif element.tag == 'select':
            for option in element.xpath('.//option'):
                option.attrib.pop('selected', None)
                if option.get('value', option.text or '') == value: option.set('selected', 'selected')
        else:
            element.set('value', value)
            if element.get('type') in ['checkbox', 'radio']:
                element.attrib.pop('checked', None)
                if observed['checked']: element.set('checked', 'checked')
    # Conservar el snapshot con tokens neutralizados, incluso dentro de formularios.
    data['html'] = html.tostring(dom, encoding='unicode', method='html')
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
    for element in dom.xpath('//*[@href or @src]'):
        for attr in ['href', 'src']:
            url = element.get(attr, '')
            prefix = 'http://127.0.0.1:8878/'
            if url.startswith(prefix) and (public_path / url[len(prefix):]).is_file():
                element.set(attr, public + url[len(prefix):])
    (folder / f'{name}-real.html').write_text('<!DOCTYPE html>\n'+html.tostring(dom, encoding='unicode', method='html')+'\n', encoding='utf-8')
    height = 800 if name == 'login' else (2500 if name == 'inicio' else 2000)
    frame = f'<!doctype html><html lang="es"><meta charset="utf-8"><title>B3 · {name} · marco375</title><body style="margin:0;background:#ddd;font:14px Arial;text-align:center"><p id="medicion">Marco375</p><iframe id="pantalla" src="{name}-real.html" style="width:375px;height:{height}px;border:0;background:white"></iframe><script src="medir-marco.js"></script></body></html>\n'
    (folder / f'marco-{name}-375.html').write_text(frame, encoding='utf-8')
print('DOM reales exportados; valores conservados y tokens neutralizados.')
