# Control de la etapa A, segunda vuelta de T12/T13 (Gemini, `a28a84a0`) — Claude CAB, 29/10/2026

Revisado contra `PROMPT-GEMINI-T12-T13-ETAPA-A-DEVOLUCION-2.md`, las páginas de `resources/manual/operativo/`,
las 20 fichas y el código. Corrí yo `verificar-manual.php`: 22 páginas, 0 problemas.

## Lo pedido: bien

| Punto | Resultado |
|---|---|
| Exceso del plan en la lista | Bien. Selector «Excede el plan — indicar motivo», EXTRA por defecto, no traba; separado del motivo de deuda (`clases-show.js:39-41`). |
| Gasto mayor que el cajón | Bien, con el texto de `CajaService.php:615`, al cargar y al editar. |
| Motivo del parcial | Bien, con el texto de `ExcepcionCobranzaService.php:93` y `:100`; distingue Observaciones. |
| Avisos de Cobrar | Bien. Los textos de meses anteriores (`:96`), adelantado (`CajaWebController.php:928`) y mes cerrado (`:900`) coinciden con el código; el inactivo remite a su página. |
| Códigos internos | Bien: sin «[DU-20]» ni «DU-27» en las páginas. El `du:` del encabezado pasó a `CAJ-002b`, que no se muestra. |

Nota: el informe de Gemini en el chat cita para el adelantado y el mes cerrado textos que no son los de Wings;
las páginas y las fichas tienen los correctos.

## Lo que queda (menor)

1. **La tabla nueva marca «Sí» donde la página no lo dice.** Es justo lo que el control tenía que evitar. Comprobados:
   - `cobrar-cuota`: «Se puede cobrar por adelantado hasta **6** meses» — el código dice **12** (`MESES_ADELANTABLES`,
     `CajaWebController.php:34`) y la página no menciona el tope. Tampoco dice «Indicá el importe del mes que se cobra
     por adelantado.» (`:920`).
   - `alta-alumno`: «La fecha de ingreso no puede ser posterior al …» (`FechaIngresoRegla.php:42`): la página no lo dice.
   - `resolver-revision-cobranza`: la nota de al menos 5 caracteres (`RevisionCobranzaWebController.php:49`): no está.
   Un cotejo automático de los «Sí» contra el texto de la página marca más filas; muchas son paráfrasis válidas o
   avisos de éxito. No son hallazgos uno por uno, pero el «Sí» no es confiable sin mirar la página.
2. **Una frase sin fuente** en `cobrar-cuota.md`: «cobrar un mes por adelantado no habilita al alumno a tomar clases
   antes del primer día de ese mes». Wings no tiene esa regla; sacarla.

Ninguno cambia lo que la operativa hace ni toca plata. Son rechazos que Wings muestra en pantalla con su texto.

## Recomendación

Dar el OK a la etapa A y que Gemini corrija estos dos puntos como primer paso de la etapa B, con la regla de que el
«Sí» de la tabla solo va si la frase está en la página. Decide Carlos.

Gemini también respondió en su informe la pregunta de MF12/MF13 («Sí, es la opción recomendada»): esa decisión es de
Carlos, no de Gemini, y sigue pendiente.
