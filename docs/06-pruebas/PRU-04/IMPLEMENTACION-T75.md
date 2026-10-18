# T75 — Botones a la derecha en celular

Entrega local de Codex CAB, 18/10/2026. Inicio del tablero: `4a23634`.
Estado de entrega: **a verificar por Carlos**, sin autocierre ni despliegue.

[Galería antes/después](evidencia-t75/GALERIA.html) ·
[Inventario completo](evidencia-t75/INVENTARIO.md) ·
[Casos para Carlos](evidencia-t75/GALERIA.html#consultas) ·
[Reproducir](evidencia-t75/REPRODUCIR.md).

## Seguimiento de Carlos — 18/10/2026

Carlos observa botones de tamaños distintos y bordes desalineados entre cabecera y deuda
de la ficha. Pide registrar en el Design System: tamaño Cobrar como referencia, padding
común, mismo borde derecho y ubicación estable de cada acción aunque falten otros botones.
Reglas y checklist asentados en [DESIGN-RULES.md §7](../../03-diseno-ui/design-system/DESIGN-RULES.md)
y en wings-design. Carlos pidió después aplicar las reglas a las pantallas de celular:
la [segunda entrega de consistencia](IMPLEMENTACION-T75-CONSISTENCIA.md) contiene esa aplicación,
sus mediciones y nuevas capturas. T75 sigue esperando revisión visual de Carlos;
las mediciones siguientes conservan el alcance histórico de la primera entrega.

## Qué cambió

La regla general de `.alumno-actions` alinea a la derecha hasta 640 px, incluidas
las filas partidas. Se conserva el comportamiento de escritorio. Las barras propias
usan la clase móvil existente; dos ayudantes móviles dan ancho completo a una fila
anidada y llevan una acción individual al extremo derecho.

| Fuente de producto | Cambio limitado a celular |
|---|---|
| resources/css/app.css | Regla general y ayudantes dentro de media query |
| resources/views/alumnos/show.blade.php | Acciones superiores e inferiores |
| resources/views/caja/resumen.blade.php | Grupo de acciones |
| resources/views/liquidaciones/show.blade.php | Grupo de acciones de la ficha |
| resources/views/clases/show.blade.php | Editar profesores y filas de los paneles existentes |
| resources/views/revision-cobranza/index.blade.php | Limpiar y ambas resoluciones |
| resources/views/operativo/dashboard.blade.php | Barra superior y acciones según estado del cajón |

El Inicio operativo desbordaba a 438 px en tres estados a 360/375. El resumen de
recaudación de tres columnas pasa a una columna solo en celular; importes, datos,
tokens y distribución de escritorio se conservan. Las seis mediciones con desborde
previo quedan en cero después. Este ajuste cumple el criterio expreso de no desbordar.

El verificador de límites confirma que las seis vistas difieren del inicio únicamente
en sus clases; el CSS difiere únicamente en el bloque móvil. No cambian controladores,
reglas, permisos, pruebas de negocio ni JavaScript (incluido el incrustado de T73).
[Comprobación y huellas](evidencia-t75/limites-cambio.json).

## Cobertura y capturas reales

Se leyeron las **101 plantillas Blade**, con huellas e inventario: 77 contienen marcas
interactivas. Se renderizaron **94 plantillas del repositorio**, incluyendo layouts y componentes,
y la paginación de Laravel (95 en total). Las otras siete: cinco de correo/PDF sin
botones, `components/ds/card.blade.php` y `layouts/panel.blade.php`. Estas dos piezas
no tienen usos en vistas/app/rutas según la búsqueda actual; se revisaron en fuente,
sin inventarles una pantalla. Ninguna plantilla con marcas de botón quedó sin cobertura.
El inventario detalla por archivo dónde se ejecutó y, por pantalla, si sus grupos estaban
a izquierda, centrados, distribuidos o ya a derecha. Incluye enlaces y controles de campos;
sus categorías no convierten un campo o un enlace en una barra de acciones.

**99 casos de pantalla**, ADMIN/OPERATIVO/PROFESOR, estados de caja/liquidación, errores
y primera carga. Cada fase produjo 99 respuestas reales de Laravel, todas 200,
y **297 mediciones a 360/375/1366**. No son maquetas: Chrome carga HTML literal devuelto
por la aplicación con sus assets compilados, dentro de un iframe al ancho real.
Los datos son sintéticos en `wings_testing_codex`; HTML con tokens queda ignorado.
La preparación y los archivos de evidencia viven fuera de `tests/`.

**37 casos cambiaron en celular** (un caso distingue rol/estado, no equivale a una vista
única). Se publican **428 PNG** con hash, pares antes/después a los tres anchos para
cada caso cambiado y los seis controles de regresión, más paneles y casos para Carlos.
Se inspeccionaron visualmente los pares móviles y los paneles principales.
Incluye 12 capturas finales adicionales: recaudación completa a 360/375 y formulario
de resolución de Revisión para ambos roles a 360/375/1366; se desplegó su DOM
existente sin enviar operaciones. Las capturas originales antes/después de estas
pantallas permanecen en la galería.
La captura posterior se reanudó tras un error de escritura de Windows; se comparó
únicamente el conjunto completo de 297 mediciones, sin mezclar versiones de producto.

## Resultado visual medido

- **Cero desbordes de documento a 360/375**, también en los paneles capturados.
- **99 casos de escritorio sin diferencias** en posición/tamaño de controles ni alto
  del documento. Comparación con tolerancia de redondeo de 0,51 px en controles.
- **18 controles de regresión sin cambios**: listados de Alumnos/Grupos/Profesores/Niveles,
  Detalle ADMIN cerrado y Detalle OPERATIVO abierto, a los tres anchos.
- Detalle de caja conserva explícitamente **2 × 2 a la derecha** a 360/375.
- Hijo con `margin-left:auto`: comprobado sobre Subrubro en el DOM real a los tres
  anchos; conserva posición y visibilidad. No se agrega una tarjeta ficticia.
- Los paneles ocultos existentes se mostraron para medir y capturar; esta comprobación
  visual no afirma que se enviaran sus formularios en navegador.

[Comparación completa](evidencia-t75/comparacion.json) ·
[Control de hijo](evidencia-t75/control-hijo.json) ·
[Manifiesto PNG](evidencia-t75/capturas.json).

## Controles cuya disposición decide Carlos

Se conservan y fotografían **19 casos**: botones de filas de Caja/Cashflow/Historial;
ingreso; Agregar período junto al importe; interruptores Afecta caja/Descubierto;
ojo de contraseña; pasos de primera carga; enlaces de Reportes/Ver todos; modales
existentes de alumno y caja. Ver [tabla completa con capturas](evidencia-t75/INVENTARIO.md#casos-para-carlos).
No se elige una nueva disposición para estos controles. La navegación del encabezado
conserva su comportamiento. Los modales de caja ya aprobados no se reabren como defecto.

## Aprobaciones anteriores conservadas

Carlos aprobó las dos imágenes de Detalle: «Bien ambas imagenes!!»; quedan aceptados
los botones 2 × 2 y la pareja Resumen/Volver a derecha, scroll y modal, según
[registro visual T37](DETALLE-CAJA-MOBILE.md). T37 está cerrada por Claude.
T36 está cerrada por Claude tras verificar reglas y con aprobación visual de Carlos
(«visualmente están bien»). Su decisión sigue siendo **«Destildar solo los movimientos
que cambien»**. No queda pendiente volver a aprobar esas imágenes en T75.

## Verificación y coordinación

`npm run build` correcto (último: 5,46 s); sintaxis de las seis vistas correcta;
`view:cache` y `view:clear` correctos, con almacenamiento propio. Render de evidencia:
1 prueba/6 aserciones correcta en la preparación final (5 sin rehacer el fixture);
no añade métodos a la suite de negocio.

La primera suite: 819 aprobadas, 2 omitidas, 2 fallidas, 7578 aserciones,
910,73 s. Fallos: una expectativa estructural de A37 sobre la clase base y un
`ProcessTimedOutException` de 20 s en concurrencia de inscripción T33. Se conservó
la clase base `justify-start sm:justify-end` y se superpuso la clase móvil a derecha,
sin cambiar la prueba. Se renovaron las capturas de Alumnos para ambos roles y los
tres anchos: geometría y textos idénticos a la versión anterior del cambio T75.
Repetición aislada: **2 aprobadas/20 aserciones, 50,00 s**,
incluido el caso de concurrencia sin modificar su código ni su tiempo límite.

**Suite completa final: 821 aprobadas, 2 omitidas, 0 fallidas, 7593 aserciones, 908.56 s; salida 0.**
Base `wings_testing_codex` y almacenamiento exclusivo `storage/app/t75-runtime`.
No se modificó la prueba de concurrencia; la causa del primer timeout no quedó
establecida. El fallo y la repetición se conservan, sin presentar la primera
corrida como verde. [Síntesis verificable](evidencia-t75/suite-final-sintesis.json).

Carlos indicó que Gemini no implemente lo mismo. Quedó escrito al tomar T75 en el
tablero; no hay un chat de Gemini disponible para enviarle directamente el mensaje.
El próximo paso es la revisión visual de Carlos en la galería y su decisión sobre
los casos fuera de tarjetas/barras. No se despliega ni se declara cerrada T75.
