"""Integridad documental de la propuesta; no prueba la anulación financiera."""
from hashlib import sha256
from html.parser import HTMLParser
from pathlib import Path
import json
from PIL import Image

PROPUESTA = Path(__file__).resolve().parent
REPO = PROPUESTA.parents[4]
PRU = PROPUESTA.parents[1]


class Enlaces(HTMLParser):
    def __init__(self):
        super().__init__()
        self.rutas = []

    def handle_starttag(self, tag, attrs):
        for clave, valor in attrs:
            if clave in {'href', 'src'} and valor and not valor.startswith(('#', 'http:', 'https:')):
                self.rutas.append(valor)


origen = json.loads((PROPUESTA / 'origen.json').read_text(encoding='utf-8'))
for nombre, esperado in origen['fuentes'].items():
    assert sha256((REPO / nombre).read_bytes()).hexdigest() == esperado, nombre
enlaces = Enlaces()
enlaces.feed((PRU / 'APROBAR-T97.html').read_text(encoding='utf-8'))
for ruta in enlaces.rutas:
    assert (PRU / ruta).is_file(), ruta
capturas = []
for archivo in sorted((PROPUESTA.parent / 'capturas').glob('*.jpg')):
    with Image.open(archivo) as imagen:
        ancho, alto = imagen.size
        assert imagen.format == 'JPEG'
        imagen.verify()
    # Chrome omite los 15 px del scrollbar al capturar una página completa.
    assert ancho in ({1351, 1366} if '-1366.' in archivo.name else {375}), archivo.name
    capturas.append({'archivo': archivo.relative_to(PRU).as_posix(), 'ancho': ancho, 'alto': alto,
                     'sha256': sha256(archivo.read_bytes()).hexdigest()})
assert len(capturas) == 8
mediciones = json.loads((PROPUESTA / 'medicion-barra.json').read_text(encoding='utf-8'))
assert {m['texto'] for m in mediciones} == {'2 movimientos', '4 movimientos'}
for medida in mediciones:
    assert medida['viewport'] == 375 and medida['ancho'] == medida['contenido']
    assert len(medida['botones']) == 2
    for boton in medida['botones']:
        assert boton['top'] - medida['contador']['bottom'] == 8
        assert boton['width'] == 96 and boton['height'] == 32
    assert medida['botones'][1]['left'] - medida['botones'][0]['right'] == 8
    assert medida['botones'][1]['right'] == medida['contador']['right']
resultado = {'fuentes_originales_intactas': list(origen['fuentes']),
             'enlaces_galeria_resueltos': len(enlaces.rutas), 'capturas': capturas,
             'medicion_navegador': {'escritorio_innerWidth': 1366, 'iframe_innerWidth': 375,
                                   'iframe_body_clientWidth': 360, 'iframe_body_scrollWidth': 360},
             'medicion_barra_movil': mediciones,
             'limite': 'Integridad de la propuesta; no implementacion ni prueba financiera.'}
(PROPUESTA / 'verificacion.json').write_text(json.dumps(resultado, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
print(f"OK: {len(capturas)} JPEG, {len(enlaces.rutas)} enlaces locales y {len(origen['fuentes'])} fuentes intactas.")
