"""Integridad de capturas reales, fuentes, galería y copia del manual T97."""
from hashlib import sha256
from html.parser import HTMLParser
from pathlib import Path
import argparse
import json
import re
import subprocess
from urllib.parse import unquote
from PIL import Image

FINAL = Path(__file__).resolve().parent
REPO = FINAL.parents[4]
PRU = FINAL.parents[1]
parser = argparse.ArgumentParser()
modo = parser.add_mutually_exclusive_group()
modo.add_argument('--revision', help='Comprueba fuentes del commit indicado en lugar de la carpeta compartida')
modo.add_argument('--solo-artefactos', action='store_true', help='Comprueba imágenes y enlaces, sin certificar fuentes actuales')
opciones = parser.parse_args()


class Enlaces(HTMLParser):
    def __init__(self):
        super().__init__()
        self.rutas = []

    def handle_starttag(self, tag, attrs):
        for clave, valor in attrs:
            if clave in {'href', 'src'} and valor and not valor.startswith(('#', 'http:', 'https:')):
                self.rutas.append(valor)


origen = json.loads((FINAL / 'origen.json').read_text(encoding='utf-8'))
fuentes_lf = json.loads((FINAL / 'fuentes-lf.json').read_text(encoding='utf-8'))
assert origen['base'] == 'wings_testing_codex'
identicas = 0
for nombre, esperado in ([] if opciones.solo_artefactos else origen['fuentes'].items()):
    contenido = (subprocess.check_output(['git', 'show', opciones.revision + ':' + nombre], cwd=REPO)
                 if opciones.revision else (REPO / nombre).read_bytes())
    identicas += sha256(contenido).hexdigest() == esperado
    # El checkout usa eol=lf; el CSS capturado tenía CRLF en la copia Windows.
    # La huella normalizada se derivó solo tras verificar los ocho originales.
    assert sha256(contenido.replace(b'\r\n', b'\n')).hexdigest() == fuentes_lf[nombre], nombre
enlaces = Enlaces()
enlaces.feed((PRU / 'VER-T97.html').read_text(encoding='utf-8'))
for ruta in enlaces.rutas:
    assert (PRU / ruta).is_file(), ruta
capturas = []
for archivo in sorted((FINAL / 'capturas').glob('*.jpg')):
    with Image.open(archivo) as imagen:
        ancho, alto = imagen.size
        assert imagen.format == 'JPEG'
        imagen.verify()
    assert ancho == (1366 if '-1366.' in archivo.name else 375), archivo.name
    capturas.append({'archivo': archivo.relative_to(PRU).as_posix(), 'ancho': ancho, 'alto': alto,
                     'sha256': sha256(archivo.read_bytes()).hexdigest()})
assert len(capturas) == 7
mediciones = json.loads((FINAL / 'mediciones.json').read_text(encoding='utf-8'))
for estado in ('antes', 'despues'):
    medida = mediciones[estado]
    assert medida['width'] == medida['scrollWidth'] == 360
    primero, segundo = [b['rect'] for b in medida['buttons']]
    assert primero['top'] - medida['stats']['bottom'] == 8
    assert segundo['left'] - primero['right'] == 8
    assert segundo['right'] == medida['stats']['right']
    for boton in (primero, segundo):
        assert (boton['width'], boton['height']) == (96, 32)
assert (REPO / 'resources/manual/img/admin/transferir-entre-cuentas-anular.jpg').read_bytes() == (
    FINAL / 'capturas/02-confirmacion-1366.jpg').read_bytes()
enlaces_informe = 0
for documento in (PRU / 'IMPLEMENTACION-T97.md', FINAL.parent / 'LEEME.md', FINAL.parent / 'REVISION-MANUAL.md'):
    for ruta in re.findall(r'!?\[[^\]]*\]\(([^)\n]+)\)', documento.read_text(encoding='utf-8')):
        if ruta.startswith(('http:', 'https:', '#')):
            continue
        assert (documento.parent / unquote(ruta.split('#')[0])).is_file(), (documento.name, ruta)
        enlaces_informe += 1
resultado = {'capturas': capturas, 'enlaces_galeria': len(enlaces.rutas),
             'enlaces_informe_verificados': enlaces_informe,
             'revision': opciones.revision, 'solo_artefactos': opciones.solo_artefactos,
             'fuentes_verificadas': 0 if opciones.solo_artefactos else len(origen['fuentes']),
             'fuentes_identicas_en_bytes': identicas,
             'normalizacion_permitida': 'solo CRLF a LF', 'sin_desborde_horizontal': True,
             'barra_sin_superposicion': True, 'copia_manual_identica': True}
salida = ('verificacion-artefactos.json' if opciones.solo_artefactos else
          'verificacion-commit.json' if opciones.revision else 'verificacion.json')
(FINAL / salida).write_text(json.dumps(resultado, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
print(f"OK: {len(capturas)} JPEG, {len(enlaces.rutas)} enlaces, {resultado['fuentes_verificadas']} fuentes, barra y manual")
