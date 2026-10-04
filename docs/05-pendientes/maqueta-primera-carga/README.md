# P1 — maqueta de primera carga · 04/10/2026

**Pendiente de aprobación de Carlos. No es un importador y no escribe en Wings.**
Abrir [index.html](index.html) en cualquier navegador. No requiere instalar nada ni
arrancar Laravel. En esta máquina también está disponible en http://127.0.0.1:8766/.

## Qué se propone acordar

Una pantalla con cuatro pasos: preparar catálogos, descargar plantilla, revisar y cargar.
El selector superior permite mirar un archivo con errores, uno corregido, catálogos
incompletos y una carga que ya tiene cobros. Las acciones de carga/deshacer son simuladas.
Los mensajes son los textos propuestos para esos casos; no provienen de un motor existente.

Un único Excel con tres hojas:

- **Alumnos:** DNI, Apellido, Nombre, Fecha nacimiento, Fecha ingreso, Celular, Email,
  Tutor, Teléfono tutor, Deporte, Grupo, Plan, Debe inscripción.
- **Deudas:** DNI, Deporte, Período, Importe. Una fila por cuota pendiente evita agregar
  columnas por cada mes y no impone un máximo de períodos. Se vincula por DNI y deporte.
- **Catálogos:** deportes, grupos y planes que alimentan los desplegables. Aquí son
  ficticios; en producción serán las listas reales del sistema, nunca creadas al importar.

Debe inscripción queda en No; Sí genera el cargo único por DNI al valor vigente de
Configuración. La carga inicial no usa el alta manual para generar cuotas nuevas:
declara solamente los saldos del archivo. Ausencia de filas de deuda significa sin deuda.
Las filas vacías de plantilla no son alumnos, aunque su celda de inscripción diga No.
Estos detalles del formato son parte de la propuesta para aprobar, no reglas implementadas.

## Excel de ejemplo descargables

Todos contienen únicamente datos ficticios, correos `example.invalid` y teléfonos de
ejemplo. No son un padrón para importar en el sitio de prueba.

- [Plantilla](ejemplos/plantilla-ejemplo.xlsx): tres hojas, encabezados y desplegables.
- [Archivo con errores](ejemplos/club-con-errores.xlsx): seis problemas en tres filas.
- [El mismo Excel revisado](ejemplos/club-con-errores-revisado.xlsx): originales preservados;
  columna Errores al final de Alumnos y Deudas, con todos los problemas de cada fila.
- [Archivo corregido](ejemplos/club-corregido.xlsx): cuatro alumnos, tres cuotas por
  $152.000 y una inscripción de $5.000. Total pendiente: $157.000; caja: sin movimiento.

Se conservaron como casos válidos DNI textual con puntos/espacios, período numérico
92026, período textual 082026, importe textual 52.000 y deporte con espacio sobrante.

## Comprobaciones realizadas, sin cerrar P1

- Navegador: errores visibles y Cargar bloqueado; revisión corregida, confirmación y
  resultado; Preparar habilita revisión; Deshacer bloqueado tras cobro simulado.
- Ancho de celular de 390 px: tarjetas apiladas y sin desborde de página. Solo la tabla
  del Excel tiene desplazamiento horizontal propio. Capturas en `capturas/`.
- Descarga de plantilla y Excel marcado desde el navegador verificada.
- Excel: previsualización de hojas y verificación del archivo exportado. Tipos/originales
  de DNI, fechas, períodos e importe textual preservados, cuatro desplegables de Alumnos
  enlazados a Catálogos. No se abrió en Excel de escritorio; no se afirma prueba nativa.
- Archivos HTML/CSS/JS estáticos fuera de `resources/`. Sin rutas ni cambios a Wings.

P0 se entregó por separado en `ad24769`, suite 338/1932 verde. Gemini debe verificarlo.
**Próximo paso:** Carlos mira y aprueba esta maqueta; recién después, pruebas en rojo e
implementación del importador P1. No se cierra la tarea ni se retiran los importadores viejos.
