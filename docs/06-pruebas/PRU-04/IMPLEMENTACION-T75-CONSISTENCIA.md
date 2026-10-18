# T75 — Tamaño, padding y ubicación de acciones en celular

Entrega local de Codex CAB, 18/10/2026. **Para revisión visual de Carlos; sin despliegue ni autocierre.**

[Galería antes/después de todas las pantallas](evidencia-t75-consistencia/GALERIA.html) ·
[Cobertura](evidencia-t75-consistencia/INVENTARIO.md) ·
[Mediciones verificadas](evidencia-t75-consistencia/verificacion.json) ·
[Reproducción](evidencia-t75-consistencia/REPRODUCIR.md).

## Pedido y resultado

Carlos señaló que Cobrar cambiaba de tamaño entre la cabecera y la deuda y que los grupos
terminaban en bordes diferentes. Pidió el tamaño del Cobrar superior como referencia,
padding común, acciones a la derecha y ubicación estable aunque falten otros botones:
«Que el usuario sepa donde esta el boton sin ver, por repeticion».
Después pidió aplicar esas reglas a las pantallas de celular.

Los botones de acción con texto usan **96 × 32 px**, padding **0**, fuente **0.82rem/600**,
interlínea **1** y separación **8 px** hasta 640 px. El contenido deja **16 px** laterales
y las acciones una referencia interior de **24 px**. Se descuentan bordes y rieles para
alinear tarjetas, barras, secciones internas, modales y pies fijos en el mismo borde.

Cobrar queda en la columna derecha tanto en la cabecera como en la deuda. Filtrar y las
confirmaciones quedan a la derecha de sus complementarias. En Liquidaciones, Volver queda
en la columna derecha con la ficha abierta, cerrada o pagada. El ingreso también usa el
tamaño y borde comunes. Las columnas de tablas reservan lugar para esos botones y siguen
admitiendo desplazamiento horizontal. Detalle conserva el 2 × 2 aprobado por Carlos.

Los íconos y los interruptores conservan sus tipos. En Inicio, la lista de clases usa el
desplazamiento de la página en celular para evitar un segundo margen por su barra interna;
en escritorio conserva su límite anterior de 165 px. No se modifican colores ni textos.

## Alcance de código

Un bloque nuevo de CSS dentro de `@media (max-width: 640px)` y clases añadidas en once
vistas: Alumno, Ingreso, Cobrar, Detalle y Resumen de caja, Clase, Cobranza, listado y ficha
de Liquidaciones, Inicio operativo y Rubros. La regla compartida alcanza todas las vistas
móviles que usan los controles del sistema, no solo esas once fuentes.

[Fuentes y huellas finales](evidencia-t75-consistencia/fuentes-verificadas.json) ·
[Inventario final completo](evidencia-t75-consistencia/inventario-fuente-despues.json).
El verificador compara los once Blade con `1d73322` quitando únicamente atributos `class`:
el resto es idéntico. Al retirar el bloque CSS nuevo, el archivo coincide con esa base.
No hay cambios propios en JavaScript, controladores, servicios, permisos ni reglas de negocio.
Los cambios concurrentes de negocio se mantienen fuera de esta entrega.

## Evidencia de presentación

- 101 fuentes Blade inventariadas; 94 del repositorio renderizadas más paginación Laravel.
  Las siete restantes son dos piezas sin uso en estos recorridos y cinco plantillas de PDF/mail.
- 99 casos antes/después a **360, 375 y 1366**: 297 mediciones por fase.
  Se incluyen ADMIN, OPERATIVO, PROFESOR, ingreso, errores, estados de caja y liquidación.
- Dos casos adicionales a los tres anchos: Liquidaciones con Limpiar y clase con serie.
  Otros 80 estados de paneles ocultos y tablas desplazadas se miden sobre el HTML real.
- Cero desbordes de documento en los recorridos medidos. Las tablas conservan su scroll.
- 1.476 mediciones de botones móviles con texto y 904 filas al mismo borde derecho,
  todas aprobadas; 1.053 PNG publicados y 1.454 enlaces de galería comprobados.
- 126 mediciones de íconos e interruptores conservan ancho y alto respecto del antes.
- Los 99 escritorios conservan alturas de documento, cantidad/etiquetas de controles y
  coordenadas/tamaños respecto del antes. La comparación no afirma identidad de píxeles:
  gráficos y datos de cambios concurrentes pueden variar.
- Cobrar conserva el mismo rectángulo con/sin Condonar (ADMIN frente a OPERATIVO).
  Filtrar conserva el mismo rectángulo con/sin Limpiar. Cerrar/Cancelar esta conservan
  sus rectángulos con/sin Cancelar serie, a ambos anchos móviles.
- Revisión visual de las 99 parejas a 375 mediante láminas y capturas originales;
  inspección adicional de tablas desplazadas, paneles, modales, ingreso y pies de formulario.

Las capturas salen del HTML que devuelve Laravel con autenticación y datos sintéticos
en `wings_testing_codex`. Chrome dibuja ese HTML literal dentro de un iframe del ancho
real, en una ventana de 1366; el marco no sustituye la pantalla ni fabrica su contenido.
El ingreso se incluye como control del método. Los paneles ocultos se revelan en el DOM
real para comprobar presentación: eso no sustituye una prueba funcional de sus acciones.
Las imágenes se copian desde la captura original con verificación SHA-256 individual.

## Verificación de código y suite

Base aislada de código `c2db5d4`, más las doce fuentes propias congeladas en el manifiesto.
Base de datos `wings_testing_codex` y almacenamiento exclusivo de la copia de verificación.
Configuración y clave sintéticas; sin leer ni copiar credenciales del club.
Build Vite final correcto (4.39 s), once Blade e instrumental PHP sin errores de sintaxis,
compilación/limpieza de vistas correctas en la copia aislada. Scripts de captura sin
errores de sintaxis. [Integridad de imágenes y enlaces](evidencia-t75-consistencia/integridad.json).

Suite completa: **846 aprobadas, 3 omitidas, 0 fallidas, 7655 aserciones, 1091.37 s**, salida 0.
[Salida completa](evidencia-t75-consistencia/suite-final.txt) ·
[Resultado y fuentes de esa corrida](evidencia-t75-consistencia/suite-final.json).
Los registros conservan el contenido completo, normalizando solo finales de línea y espacios
terminales de consola ([huellas](evidencia-t75-consistencia/logs-huellas.json)).

Después de esa corrida se agregaron exclusivamente dos declaraciones CSS móviles:
dar todo el ancho del grupo al aviso de cierre de Liquidaciones y conservar Recalcular
en la columna derecha. Los once Blade y todo el PHP permanecen idénticos a los de la suite;
la [comparación exacta](evidencia-t75-consistencia/ajuste-css-final.json) identifica ese delta.
Con el CSS final se repitieron las respuestas reales, el recorrido completo de Chrome,
las capturas y los guardianes: **9 pruebas/36 aserciones**, salida 0
([salida](evidencia-t75-consistencia/guardianes-finales.txt)). El aviso se mide a 200 px,
sin quedar comprimido en el espacio de un botón; Recalcular conserva su columna con/sin Cerrar.

Tres intentos previos se interrumpieron expresamente y no se cuentan como suite aprobada:
el primero mostró avisos por faltar `.env` en los procesos hijos de la copia; se preparó
una configuración sintética. El segundo se detuvo al encontrar visualmente que Volver
cambiaba de columna en Liquidaciones; se corrigió y se reinició con las fuentes finales.
El tercero detectó que los documentos de la base aislada declaraban 853 pruebas, pero
esa base contiene 849: los cambios concurrentes posteriores quedan fuera del aislamiento.
Solo en la copia se ajustaron los tres contadores a sus 849 métodos reales; el guardián
documental pasó después (1 prueba, 7 aserciones) antes de la corrida definitiva.
Los documentos y pruebas ajenos del trabajo compartido no se modificaron para obtener
ese verde. [Alcance exacto y ajustes de la copia](evidencia-t75-consistencia/alcance-suite.json).
El resultado final de esa copia no certifica una suite conjunta de cambios concurrentes
que no incluye. El commit paralelo `687262f` llegó después de aislar esta corrida;
el guardián documental del árbol compartido volvió a pasar (1 prueba/7 aserciones).

Las capturas iniciales con CSS sin cargar se descartaron y se repitieron; el capturador
actual comprueba que el CSS real haya cargado antes de medir o fotografiar.

## Entrega

T75 quedó `a_verificar`, tiene/verifica Carlos; su referencia apunta a esta entrega.
Tablero y conteo documental del árbol compartido: **7 pruebas/34 aserciones** aprobadas
([salida](evidencia-t75-consistencia/tablero-final.txt)).

T75 se entrega a Carlos para revisar la nueva galería. Las aprobaciones anteriores de
Detalle/Resumen/modal siguen registradas en T36/T37; no se les atribuye aprobación de esta
uniformidad nueva. Se conserva «Destildar solo los movimientos que cambien».
Esta entrega sustituye el pendiente de aplicación del seguimiento documental anterior;
no cambia reglas de negocio, no se despliega y no se marca cerrada por el autor.
