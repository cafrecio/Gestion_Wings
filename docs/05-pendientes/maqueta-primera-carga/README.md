# P1 — maqueta de primera carga · 04/10/2026

**Formato y maqueta completa aprobados por Carlos el 05/10/2026.** La implementación de P1
se hace sobre esta maqueta: cualquier desvío lo decide Carlos.
No es un importador y no escribe en Wings. Abrir [index.html](index.html) en un navegador.
No requiere Laravel. En esta máquina está disponible en http://127.0.0.1:8766/.

## Formato acordado e instructivo

Carlos reemplazó las hojas separadas de alumnos/deudas por **una fila por alumno y
deporte**, con datos, Debe inscripción, Tiene deuda y **12 pares Período / Monto**.
No se repite el DNI en otra hoja para declarar deuda. Hay una sola hoja para completar:

- **Alumnos:** 38 columnas, fila 1 con los títulos, datos desde fila 2. Los pares 1–12
  ocupan O–AL. La plantilla tiene 200 filas preparadas, sin alumnos de ejemplo.
- **Catálogos:** listas para los desplegables de deporte, grupo, plan y Sí/No.
  Tiene el aviso No completar. En esta maqueta son opciones ficticias; la plantilla
  del sistema llevará las reales. El importador no creará catálogos.
- **Guía:** pasos y ejemplos visuales para consultar. No se importa ni se completa.

Debe inscripción queda en No por defecto. Sí declara inscripción única por DNI al
valor vigente de Configuración. Tiene deuda se refiere a cuotas mensuales; las dos
respuestas son independientes. **Tiene deuda = No** exige todos los pares vacíos; Sí, al menos uno
completo. Cada par lleva mes/año y monto todavía pendiente de ese mes, sin duplicados
para el alumno/deporte. Los pares sin usar quedan vacíos; los meses pueden venir desordenados.

La pantalla muestra las indicaciones antes de descargar, sin esconderlas en un
desplegable: dónde escribir, cómo elegir, las dos respuestas, un par explicado y cuatro
casos completos. Incluye pagos parciales, fecha real de ingreso, menores/tutor,
guardado .xlsx, errores y nueva revisión. El Excel reproduce las indicaciones en Guía.
Todo esto sigue siendo preparación de P1, sin motor de importación implementado.

## Recorrido y colores

Cuatro pasos: preparar catálogos, descargar/completar, revisar y cargar. Solo el paso
actual muestra acciones. El selector permite mirar errores, archivo correcto, catálogos
incompletos y carga con cobros. Los mensajes, carga y Deshacer son simulaciones.

Carlos pidió ingreso automático obligatorio. El botón Alumnos permite mirar la propuesta
para impedir saltear por menú. Se propone guardar carga pendiente/terminada y aplicar
el control también en servidor, sin deducirlo del número de alumnos. Ese control no
está implementado y su detalle espera aprobación. Catálogos necesarios siguen accesibles.

Colores principales de Wings: marca #BE123C, botones #4A6880/#6888A0, encabezado
#4A4A4A y colores semánticos. No se tocaron vistas ni CSS de Wings.

## Archivos para mirar

Solo contienen datos ficticios, correos example.invalid y teléfonos de ejemplo.

- [Plantilla vacía](ejemplos/plantilla-ejemplo.xlsx): Alumnos con desplegables y 12 pares;
  Catálogos y Guía. No contiene alumnos para importar.
- [Ejemplo con errores](ejemplos/club-con-errores.xlsx): seis problemas en tres filas.
- [El mismo archivo marcado](ejemplos/club-con-errores-revisado.xlsx): datos originales
  conservados, Errores en AM (columna 39) al final de Alumnos. Problemas separados por columna.
- [Ejemplo correcto](ejemplos/club-corregido.xlsx): cuatro alumnos, seis cuotas por
  $296.000 y una inscripción de $5.000. Total: $301.000 de deuda, sin movimiento de caja.

Los ejemplos mantienen DNI textual con puntos/espacios, deporte con espacio sobrante,
período numérico 92026, período textual 082026 e importe textual 52.000. La guía aconseja
escribir DNI sin puntos y montos sin signo $, y explica los formatos que se aceptarán.

## Verificación de la entrega

Revisión en navegador y de los Excel realizada por Codex; Gemini debe verificarla
independientemente. No equivale a probar el futuro importador.

- Exportación y previsualización de Alumnos, Catálogos, Guía e informe de errores.
- Contenido/tipos del archivo original con errores comparados con el marcado: se
  conservan. Solo se agrega Errores. 12 pares, listas y encabezados comprobados en XLSX.
- La plantilla tiene los Sí/No en No y sus celdas de alumnos/deuda vacías; los ejemplos
  están exclusivamente en Guía. Datos del ejemplo correcto suman $301.000.
- Navegador: recorrido guiado, errores bloquean la carga; resumen antes de confirmar.
  Instructivo revisado en escritorio y celular de 390 px, sin desborde de página.
- No se abrió Excel de escritorio; no se afirma verificación nativa de sus controles.

Capturas iniciales escritorio-errores.png, celular-errores.png y revision.png corresponden
al formato anterior, reemplazado. entrada-guiada.png documenta la propuesta de acceso.
Las capturas instructivo-escritorio.png e instructivo-celular.png muestran la revisión actual.

P0 se entregó en ad24769, suite 338/1932 verde en ese corte; pendiente de Gemini.
**Siguiente:** Carlos mira y aprueba la maqueta completa; después, pruebas en rojo e
implementación de P1. No se cierra la tarea ni se retiran los importadores viejos.
