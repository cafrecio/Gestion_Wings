"""Tres documentos de revisión; usa capturas reales, no genera vistas de Wings."""
from pathlib import Path
from html import escape
import json

EVIDENCIA = Path(__file__).resolve().parent
BASE = EVIDENCIA.parent
PREFIJO = EVIDENCIA.name + "/"
CSS = """body{font:16px Arial,sans-serif;background:#f4f6f8;color:#111827;margin:24px}main{max-width:1400px;margin:auto}section{background:white;border:1px solid #ddd;border-radius:12px;padding:20px;margin:20px 0}img{display:block;max-width:100%;height:auto}a{color:#075985}p,li{line-height:1.5}nav{display:flex;flex-wrap:wrap;gap:16px;margin:18px 0}h1{margin-bottom:10px}h2{margin-top:0}h3{margin-bottom:10px}h4{margin:0 0 8px}figure{margin:0;min-width:0}figcaption{font-size:14px;line-height:1.45;margin-bottom:10px;min-height:64px}.par{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:20px;align-items:start;margin:16px 0 26px}.foto{position:relative}.marca{position:absolute;border:3px solid #b91c1c;border-radius:5px;box-sizing:border-box;pointer-events:none}.senal{border-left:4px solid #b91c1c;background:#fff7f7;padding:10px 14px}.opciones{display:grid;grid-template-columns:1fr 1fr;gap:16px}.opcion{border:1px solid #ddd;border-radius:8px;padding:14px}.opcion p:last-child{margin-bottom:0}.respuesta{border:2px solid #64748b}small,.nota{color:#4b5563}pre{font:15px Arial,sans-serif;line-height:1.8;white-space:pre-wrap}summary{cursor:pointer;font-weight:600;margin:12px 0}footer{margin:24px 0;font-size:14px;line-height:1.5}@media(max-width:900px){body{margin:12px}.par,.opciones{grid-template-columns:1fr}figure.telefono{max-width:411px}figcaption{min-height:0}}"""


def foto(slug, ancho, descripcion, marca=None):
    archivo = f"{PREFIJO}{slug}-{ancho}.jpg"
    assert (BASE / archivo).is_file(), archivo
    etiqueta = "Escritorio · 1366 px" if ancho == 1366 else "Celular · marco de 375 px"
    clase = ' class="telefono"' if ancho == 375 else ""
    recuadro = ""
    if marca:
        x, y, w, h = marca
        aw, ah = (1366, 1000) if ancho == 1366 else (411, 1270)
        recuadro = f'<span class="marca" aria-hidden="true" style="left:{100*x/aw:.3f}%;top:{100*y/ah:.3f}%;width:{100*w/aw:.3f}%;height:{100*h/ah:.3f}%"></span>'
    return f'<figure{clase}><figcaption><strong>{etiqueta}</strong><br>{escape(descripcion)}</figcaption><div class="foto"><a href="{archivo}" title="Abrir la captura original"><img src="{archivo}" alt="{escape(descripcion)} · {etiqueta}" loading="eager"></a>{recuadro}</div></figure>'


def par(slug, descripcion, marcas=None):
    marcas = marcas or {}
    return '<div class="par">' + foto(slug, 1366, descripcion, marcas.get(1366)) + foto(slug, 375, descripcion, marcas.get(375)) + '</div>'


ELECCIONES = iter(['A', 'A', 'B', 'B'])

def opciones(a, b):
    elegida = next(ELECCIONES)
    principal, _, referencia = (a if elegida == 'A' else b).partition('</p>')
    principal += '</p>'
    detalle = f'<details><summary>Referencia del sistema visual existente</summary>{referencia}</details>' if referencia else ''
    return f'<div class="opcion"><h4>Opción elegida · {elegida}</h4>{principal}{detalle}</div><details><summary>Si preferís otra forma, esta es la alternativa</summary>{b if elegida == "A" else a}</details>'


def documento(nombre, titulo, intro, contenido, respuesta):
    aprobacion = ''
    if nombre == 'APROBAR-T54.html':
        aprobacion = '<p class="opcion"><strong>Aprobada por Carlos el 28/10:</strong> «T54 OK». La lógica había sido verificada por Claude.</p>'
    if nombre == 'APROBAR-T87.html':
        aprobacion = '<p class="opcion"><strong>Aprobación visual de Carlos el 28/10:</strong> «T87 Visualmente OK, asumo que el funcionamiento logico esta bien». La lógica ya estaba aprobada por Claude en su verificación del 27/10.</p>'
    metodo = '''<details><summary>Cómo se obtuvieron estas imágenes</summary><p>Chrome real, Wings local y datos sintéticos de <strong>wings_testing_codex</strong>. Escritorio a 1366 px. Para celular se exportó el DOM real de la misma pantalla, conservando los valores y las opciones visibles, y Chrome lo remaquetó dentro de un iframe de 375 px. El login se usó para comprobar que el método no recorta. Wings bloquea incrustar la aplicación viva por sus encabezados de seguridad; por eso el marco muestra la exportación de su HTML real, con tokens neutralizados. No se construyeron maquetas ni se cambiaron vistas o estilos del producto.</p><p>Las imágenes originales no se retocaron. En T89, los recuadros rojos pertenecen a esta galería y señalan la zona a revisar; al abrir la imagen se ve la captura sin marcas.</p><p><a href="''' + PREFIJO + '''login-calibracion-375.jpg">Ver login de calibración</a> · <a href="''' + PREFIJO + '''capturas.json">Registro de capturas</a></p></details>'''
    html = f'''<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{escape(titulo)}</title><style>{CSS}</style></head><body><main>
<h1>{escape(titulo)}</h1><p>{intro}</p>{aprobacion}
<nav aria-label="Otras revisiones"><a href="APROBAR-T54.html">T54 · Buscar alumnos</a><a href="APROBAR-T87.html">T87 · Nueva liquidación</a><a href="DECIDIR-T89.html">T89 · Elegir opciones</a></nav>
{contenido}
<section class="respuesta"><h2>Qué tiene que contestar Carlos</h2>{respuesta}</section>
<footer><p>Preparado el 28/10/2026. Los nombres, cobros y clases son datos de demostración. Mirá las capturas y respondé en el chat; no hay que operar en estas imágenes.</p>{metodo}</footer>
</main></body></html>'''
    (BASE / nombre).write_text(html + "\n", encoding="utf-8")


lugares = [
    ("listado", "Listado de Alumnos", [
        ("letra", "Una letra: g", "Con «g» quedan García y Gómez. Antes el listado también aceptaba una letra; se conserva ese acceso."),
        ("acento", "Sin acento: gomez", "«gomez» encuentra a Gómez. Antes el cotejo de la base ya ignoraba el acento; se mantiene al unificar el criterio."),
        ("orden", "Nombre y apellido en otro orden: perez juan", "«perez juan» deja solo a Pérez, Juan. Antes buscaba la frase entera dentro de un único campo y no lo encontraba."),
    ]),
    ("autocompletar", "Autocompletar", [
        ("letra", "Una letra: g", "Con «g» ya aparecen sugerencias. Antes el desplegable esperaba por lo menos dos caracteres."),
        ("acento", "Sin acento: gomez", "«gomez» ofrece a Gómez sin escribir la tilde. Antes la base ya resolvía este acento; el comportamiento se conserva."),
        ("orden", "Nombre y apellido en otro orden: perez juan", "«perez juan» ofrece a Pérez, Juan. Antes no encontraba la frase repartida entre nombre y apellido."),
    ]),
    ("cobrar", "Buscar en Cobrar", [
        ("letra", "Una letra: g", "Buscar «g» muestra a García y Gómez, aunque estén al día. Antes ya se podía buscar con una letra."),
        ("acento", "Sin acento: gomez", "«gomez» encuentra a Gómez para cobrarle. Antes la base ya ignoraba el acento; se conserva."),
        ("orden", "Nombre y apellido en otro orden: perez juan", "«perez juan» encuentra a Pérez, Juan. Antes pedía que la frase completa apareciera en un mismo campo."),
    ]),
    ("historial", "Historial de caja", [
        ("letra", "Una letra: g", "«g» encuentra esa letra en alumno, subrubro u observación. Antes también admitía un carácter; acá se conserva."),
        ("acento", "Sin acento: gomez", "«gomez» encuentra el cobro de Gómez. Antes no: el Historial comparaba el texto sin quitar las tildes."),
        ("orden", "Nombre y apellido en otro orden: perez juan", "«perez juan» encuentra el cobro de Pérez, Juan. Antes no encontraba esas palabras en otro orden."),
    ]),
]
contenido = '<nav aria-label="Lugares de búsqueda">' + ''.join(f'<a href="#{k}">{escape(t)}</a>' for k,t,_ in lugares) + '</nav>'
for lugar, titulo, casos in lugares:
    contenido += f'<section id="{lugar}"><h2>{escape(titulo)}</h2>'
    for slug, subtitulo, texto in casos:
        contenido += '<h3>' + escape(subtitulo) + '</h3>' + par('t54-' + lugar + '-' + slug, texto)
    contenido += '</section>'
documento('APROBAR-T54.html', 'T54 · Buscar alumnos',
    'Para aprobar mirando: las mismas tres búsquedas en los cuatro lugares donde se busca un alumno. El desplegable ahora ofrece opciones desde la primera letra; las palabras se pueden combinar en cualquier orden. Las capturas muestran el estado actual. Cada epígrafe aclara qué pasaba antes, también cuando algo ya funcionaba.',
    contenido,
    '<p>Respondé <strong>«T54 OK»</strong> o indicá qué está mal, nombrando el lugar y la búsqueda. Por ejemplo: «T54 · Historial · gomez: …».</p>')

contenido = '<section><h2>La misma profesora, tres meses</h2><p>Méndez, Lucía tiene 17 clases liquidables en octubre y 3 en septiembre: 20 en total. En noviembre no tiene ninguna liquidable. Se cambió el selector de mes sin generar liquidaciones.</p>'
for slug, titulo, texto in [
    ('octubre','Octubre 2026','Antes mostraba 20 mezclando meses; ahora muestra 17 de octubre y avisa por las 2 clases sin asistencia de octubre.'),
    ('septiembre','Septiembre 2026','Antes mostraba las mismas 20; ahora muestra 3 de septiembre y avisa por 1 clase sin asistencia de septiembre.'),
    ('cero','Noviembre 2026 · cero clases','Antes seguía mostrando 20; ahora dice «0 clases liquidables» en gris. Lucía sigue seleccionada y se puede elegir.'),
]:
    contenido += '<h3>' + titulo + '</h3>' + par('t87-' + slug, texto)
contenido += '</section>'
documento('APROBAR-T87.html', 'T87 · Nueva liquidación',
    'Mirá el número al lado de la profesora y el mes elegido. El conteo y el aviso ahora corresponden a ese mes. El cálculo de la liquidación no cambió.',
    contenido,
    '<p>Respondé <strong>«T87 OK»</strong> o indicá qué está mal en octubre, septiembre o el caso de cero clases.</p>')

contenido = '<section class="respuesta"><h2>Elección de Carlos · 28/10</h2><p><strong>Movimientos A · Cashflow A · Inicio B · Detalle B.</strong> Carlos pidió ver cómo queda Movimientos antes de aprobar su aspecto. Las capturas de abajo muestran el estado previo a esos cambios.</p><h2>Qué se va a cambiar</h2><ol><li><strong>Movimientos:</strong> mostrar quién cobró, un botón Recibo y permitir consultar la caja del otro turno sin poder cerrarla.</li><li><strong>Cashflow:</strong> agregar un botón Historial al lado de Editar.</li><li><strong>Inicio:</strong> mostrar «Todos ausentes» cuando todos faltaron.</li><li><strong>Detalle de caja:</strong> agregar una columna Hora en escritorio y verla junto a la fecha en celular.</li></ol><p>La elección A/A/B/B ya quedó registrada. Las fotos de abajo muestran el estado anterior; el próximo paso es ver Movimientos con los cambios aplicados en Wings real.</p></section><nav><a href="#movimientos">1. Movimientos</a><a href="#cashflow">2. Cashflow</a><a href="#inicio">3. Inicio</a><a href="#hora">4. Hora</a></nav>'
contenido += '<section id="movimientos"><h2>1. Movimientos · MOV-011 / MOV-012</h2><p class="senal">Faltan quién registró cada cobro y el acceso al recibo. El enlace de Caja ofrece entrar, pero la caja de la otra operativa devuelve «Sin permiso».</p>'
contenido += par('t89-movimientos','Hoy: Ana ve los cobros de ambos turnos, pero no ve el autor ni un enlace al recibo en estas filas.',{1366:(255,365,1095,225),375:(33,724,328,256)})
contenido += '<details><summary>Ver qué pasa al abrir la caja de Rocío desde el cobro de Mario</summary>' + par('t89-caja-ajena-sin-permiso','Hoy: el enlace de la caja de otra operativa lleva a «Sin permiso», aun cuando su cobro figura en Movimientos.') + '</details>'
contenido += '<p><strong>Regla de permisos que aplica:</strong> <a href="../../02-contratos/PERMISOS-ROLES.md">PERMISOS-ROLES.md</a>, «Permisos por rubro», «Recibo de cuota» y «Caja»: el mostrador ve los movimientos de rubros OPERATIVO de todos los turnos y puede abrir sus recibos. La caja ajena no se oculta por privacidad; cerrar la caja abierta de otra persona sigue restringido por integridad del arqueo. Los rubros ADMIN conservan su restricción.</p>'
contenido += opciones(
    '<p><strong>Datos en la fila y consulta de la caja.</strong> Columna «Registró» y botón «Recibo» en cada cobro; Caja abre el detalle existente como consulta, mostrando solo lo permitido al operativo.</p><p>Usa la tabla actual y los botones secundarios de fila (<code>ds-btn-row</code>), como «Recibo» en Detalle de caja.</p>',
    '<p><strong>Datos bajo el alumno y panel en la misma página.</strong> «Registró: …» como segunda línea bajo el alumno, con «Recibo». Caja abre un panel de consulta con sus movimientos permitidos, sin salir de Movimientos.</p><p>Usa texto secundario, el botón de fila existente y el patrón de panel dentro de la página. El panel conserva las restricciones de operación y cierre.</p>')
contenido += '<p><strong>Qué elegís acá:</strong> A o B para el recorrido de consulta y la ubicación de autor/recibo. La visibilidad por rubro ya está definida en el contrato; no se propone volver a «cada uno ve solo lo suyo».</p></section>'

contenido += '<section id="cashflow"><h2>2. Historial de corrección en Cashflow · CSH-007</h2><p class="senal">El listado ofrece Editar y Eliminar, pero no un acceso de consulta al historial. Editar ya muestra el historial al pie; además existe una pantalla de consulta separada.</p>'
contenido += par('t89-cashflow-sin-enlace','Hoy: en las acciones del movimiento corregido no aparece un enlace a su historial.',{1366:(1265,670,83,86),375:(272,1007,69,42)})
contenido += '<details><summary>Ver la pantalla de historial que ya existe</summary>' + par('t89-cashflow-historial-existente','Pantalla existente: muestra el cambio de $4.000 a $4.500, quién lo hizo y el motivo. No es una propuesta nueva.') + '</details>'
contenido += opciones(
    '<p><strong>Botón «Historial» junto a Editar.</strong> Abre la pantalla de consulta existente. Usa el botón secundario de fila del sistema (<code>ds-btn-row</code>).</p>',
    '<p><strong>Enlazar el detalle del movimiento.</strong> El texto del movimiento lleva a la misma pantalla existente; las acciones Editar y Eliminar quedan donde están. Usa el enlace de texto que ya se emplea para Caja.</p>')
contenido += '<p>Este acceso sigue en el dominio de ADMIN; no amplía los permisos del mostrador sobre Cashflow.</p></section>'

contenido += '<section id="inicio"><h2>3. Inicio de la operativa · todos ausentes</h2><p class="senal">Hay cinco ausencias guardadas y cero presentes. Inicio dice «Sin lista», confundiendo una lista completa con una lista pendiente.</p>'
contenido += par('t89-inicio-todos-ausentes','Hoy: la clase de las 17:00 figura «Sin lista», aunque sus cinco alumnos ya tienen ausencia registrada.',{1366:(280,516,350,44),375:(49,716,190,42)})
contenido += '<details><summary>Ver la lista de esa clase con cero presentes</summary>' + par('t89-lista-cargada','La clase del mismo día tiene cinco alumnos, cero presentes y sus cinco ausencias guardadas en la base sintética.') + '</details>'
contenido += opciones(
    '<p><strong>«Lista cargada · 0 presentes».</strong> Mantener la tarjeta y el botón Lista; cambiar solo el rótulo y usar el texto secundario gris existente. «Sin lista» queda reservado para cuando no hay ninguna marca.</p>',
    '<p><strong>«Todos ausentes».</strong> Misma tarjeta y botón Lista, con ese texto en gris. Si hay presentes, conservar el conteo actual; si no se tomó lista, «Sin lista».</p>')
contenido += '</section>'

contenido += '<section id="hora"><h2>4. Detalle de caja · CAJ-017</h2><p class="senal">La cabecera muestra la hora de apertura, pero cada movimiento solo muestra la fecha. Las horas de registro existen: en este ejemplo son 18:10, 18:20 y 18:30.</p>'
contenido += par('t89-detalle-sin-hora','Hoy: la columna Fecha repite 02/11/2026 sin la hora de cada movimiento; no confundirla con la apertura de la caja.',{1366:(264,368,85,162),375:(61,659,91,166)})
contenido += opciones(
    '<p><strong>Hora debajo de la fecha.</strong> Dejar la columna actual y agregar «18:10» en una segunda línea gris. Usa la tipografía secundaria que ya tiene la tabla; no agrega otra columna.</p>',
    '<p><strong>Columna Hora.</strong> Colocar «Hora» junto a Fecha en escritorio; en celular, mostrarla junto a la fecha de cada movimiento. Usa la tabla y los textos existentes.</p>')
contenido += '<p>En ambas opciones es la hora en que se registró el movimiento (<code>created_at</code>), en la zona horaria de Wings, no una hora inventada para su fecha contable.</p></section>'
documento('DECIDIR-T89.html','T89 · Propuesta concreta para estas pantallas',
    'T89 todavía está por hacer. Acá se propone cómo completar cuatro datos que hoy faltan, con capturas reales para ubicar cada problema.',
    contenido,
    '<p>Tu elección <strong>Movimientos A · Cashflow A · Inicio B · Detalle B</strong> ya quedó registrada. La siguiente revisión será mirar cómo queda Movimientos en escritorio y celular, como pediste.</p>')

print('Generadas APROBAR-T54.html, APROBAR-T87.html y DECIDIR-T89.html.')
