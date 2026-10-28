from pathlib import Path
import html
import json
import hashlib
from PIL import Image

base = Path(__file__).resolve().parent
pantallas = [
    ('movimientos', 'Movimientos A', 'Registró identifica a la persona que cargó cada movimiento. Caja permite consultar el turno y Recibo abre el comprobante de esa fila. En celular, ambas acciones quedan al pie de cada tarjeta.'),
    ('consulta-ajena', 'Consulta del otro turno', 'Ana consulta la caja de Rocío. Puede leer los movimientos permitidos y abrir recibos; no aparecen acciones para modificar o cerrar esa caja.'),
    ('cashflow', 'Cashflow A', 'El reloj junto al lápiz abre Historial. Conserva el acceso del dueño y el diseño de los controles de ícono existentes.'),
    ('inicio', 'Inicio B', 'Todos ausentes aparece en gris cuando la lista ya fue cargada y nadie estuvo presente. Sin lista queda reservado a clases sin registros de asistencia.'),
    ('detalle', 'Detalle B', 'Cada movimiento muestra su hora real de registro. Escritorio tiene la columna Hora; celular la reúne con la fecha en la misma celda.'),
]
secciones = []
metadatos = []
for slug, titulo, texto in pantallas:
    figuras = []
    for ancho in [1366, 375]:
        nombre = f't89-{slug}-{ancho}.jpg'
        archivo = base / nombre
        with Image.open(archivo) as im:
            im.verify()
        with Image.open(archivo) as im:
            medidas = list(im.size)
        metadatos.append(dict(archivo=nombre, viewport=ancho, pixeles=medidas,
                              sha256=hashlib.sha256(archivo.read_bytes()).hexdigest()))
        figuras.append(f'<figure><figcaption>{ancho} px · {html.escape(texto)}</figcaption><a href="evidencia-t89/{nombre}"><img src="evidencia-t89/{nombre}" alt="{html.escape(titulo)} a {ancho} px" loading="eager"></a></figure>')
    secciones.append(f'<section><h2>{html.escape(titulo)}</h2><p>{html.escape(texto)}</p>{"".join(figuras)}</section>')
pagina = '''<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>T89 · Así quedó Wings</title><style>body{font:16px Arial,sans-serif;background:#f4f6f8;color:#111827;max-width:1400px;margin:24px auto;padding:0 20px}h1{font-size:26px}h2{font-size:21px}p{line-height:1.5}section{background:white;border:1px solid #d8e0ea;border-radius:12px;padding:20px;margin:24px 0}figure{margin:22px 0}figcaption{line-height:1.5;margin-bottom:10px}img{max-width:100%;height:auto;border:1px solid #d8e0ea}a{color:#374151}</style><h1>T89 · Así quedó Wings</h1><p>Carlos eligió Movimientos A, Cashflow A, Inicio B y Detalle B. Estas son las pantallas reales después del cambio, con datos sintéticos locales. Revisar especialmente cómo se ve Movimientos; la elección anterior autorizó implementarlo, no acredita aprobación de estas capturas finales.</p>'''
pagina += ''.join(secciones)
pagina += '<p>Capturas de Chrome: escritorio a 1366 y contenido real a 375 dentro de un marco. Para respetar los encabezados de seguridad de Wings, el marco usa el DOM real exportado de la aplicación, sin tokens ni contraseñas. No es una maqueta. La fecha de prueba local es 02/11/2026. Sin despliegue.</p></html>'
(base.parent / 'APROBAR-T89.html').write_text(pagina, encoding='utf-8')
(base / 'capturas.json').write_text(json.dumps(dict(fecha='2026-10-28', capturas=metadatos), ensure_ascii=False, indent=2), encoding='utf-8')
print(f'{len(metadatos)} capturas verificadas; APROBAR-T89.html generado')
