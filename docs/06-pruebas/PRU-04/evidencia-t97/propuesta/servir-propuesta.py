"""Vista local de solo lectura: propuesta T97 y recursos públicos necesarios.

No inicia Laravel ni conecta una base. Solo escucha en 127.0.0.1.
"""
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path
from urllib.parse import unquote, urlsplit
import mimetypes

PROPUESTA = Path(__file__).resolve().parent
REPO = PROPUESTA.parents[4]
PUBLIC = REPO / 'public'
EVIDENCIA = PROPUESTA.parent


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        ruta = unquote(urlsplit(self.path).path)
        if ruta.startswith('/propuesta/'):
            base, relativa = PROPUESTA / 'html', ruta.removeprefix('/propuesta/')
        elif ruta.startswith('/assets/'):
            base, relativa = PUBLIC, ruta.removeprefix('/assets/')
            if relativa.split('/')[0] not in {'build', 'images', 'img', 'fonts'}:
                self.send_error(404)
                return
        elif ruta.startswith('/capturas/'):
            base, relativa = EVIDENCIA / 'capturas', ruta.removeprefix('/capturas/')
        elif ruta in {'/APROBAR-T97.html', '/IMPLEMENTACION-T97.md'}:
            base, relativa = EVIDENCIA.parent, ruta.removeprefix('/')
        else:
            self.send_error(404)
            return
        archivo = (base / relativa).resolve()
        if not archivo.is_relative_to(base.resolve()) or not archivo.is_file():
            self.send_error(404)
            return
        datos = archivo.read_bytes()
        if archivo.suffix == '.html':
            contenido = datos.decode('utf-8')
            contenido = contenido.replace(PUBLIC.as_uri() + '/', '/assets/')
            contenido = contenido.replace('evidencia-t97/capturas/', '/capturas/')
            datos = contenido.encode('utf-8')
        self.send_response(200)
        tipo = 'text/plain; charset=utf-8' if archivo.suffix == '.md' else mimetypes.guess_type(archivo.name)[0]
        self.send_header('Content-Type', tipo or 'application/octet-stream')
        self.send_header('Content-Length', str(len(datos)))
        self.send_header('Cache-Control', 'no-store')
        self.end_headers()
        self.wfile.write(datos)


if __name__ == '__main__':
    HTTPServer(('127.0.0.1', 8797), Handler).serve_forever()
