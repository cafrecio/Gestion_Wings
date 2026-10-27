"""Exporta el DOM observado en Wings local; no inventa contenido de aplicación."""
import json
import re
from pathlib import Path
from lxml import html

folder = Path(__file__).resolve().parent
root = folder.parents[3]
public = (root / 'public').as_uri() + '/'
for name in ['parcial', 'anteriores', 'gasto-rechazado', 'inicio', 'login']:
    path = folder / f'{name}-dom.json'
    data = json.loads(path.read_text(encoding='utf-8'))
    # Neutralizar tokens antes de conservar el snapshot, incluidos valores de formulario.
    data['html'] = re.sub(r'(<meta name="csrf-token" content=")[^"]*', r'\1FICTICIO', data['html'])
    data['html'] = re.sub(r'(name="_token"[^>]*value=")[^"]*', r'\1FICTICIO', data['html'])
    for field in data['campos']:
        if field['name'] == '_token' or field['type'] == 'password': field['value'] = 'FICTICIO' if field['name'] == '_token' else ''
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
    doc = html.document_fromstring(data['html'])
    controls = doc.xpath('//*[self::input or self::select or self::textarea][not(ancestor::template)]')
    assert len(controls) == len(data['campos']), name
    for element, field in zip(controls, data['campos']):
        assert element.get('name', '') == field['name'], (name, field)
        # outerHTML no serializa propiedades editadas; copiar solo lo observado.
        if element.tag == 'input':
            element.set('value', field['value'])
            if field['checked']: element.set('checked', 'checked')
            else: element.attrib.pop('checked', None)
        elif element.tag == 'textarea': element.text = field['value']
        else:
            for i, option in enumerate(element.xpath('.//option')):
                if i == field['selectedIndex']: option.set('selected', 'selected')
                else: option.attrib.pop('selected', None)
    for element in doc.xpath('//*[@src or @href]'):
        for attribute in ['src', 'href']:
            value = element.get(attribute, '')
            for prefix in ['http://127.0.0.1:8878/', 'http://localhost:8878/']:
                if value.startswith(prefix) and value[len(prefix):].startswith(('build/', 'img/', 'favicon')):
                    element.set(attribute, public + value[len(prefix):])
    (folder / f'{name}-real.html').write_text('<!doctype html>\n'+html.tostring(doc, encoding='unicode'), encoding='utf-8', newline='\n')
    alto = {'login': 844, 'gasto-rechazado': 1200, 'parcial': 1900, 'anteriores': 2000, 'inicio': 2500}[name]
    (folder / f'marco-{name}-375.html').write_text(f'''<!doctype html><html lang="es"><meta charset="utf-8"><title>Marco375 · {name}</title><style>body{{margin:0;padding:20px;background:#ddd;font:14px Arial}}p{{text-align:center}}iframe{{display:block;width:375px;height:{alto}px;margin:auto;border:0;background:white}}</style><p id="medida">Marco375</p><iframe id="pantalla" src="{name}-real.html"></iframe><script src="medir-marco.js"></script></html>''', encoding='utf-8', newline='\n')
print('Cinco DOM reales exportados con valores observados, assets locales y tokens neutralizados.')
