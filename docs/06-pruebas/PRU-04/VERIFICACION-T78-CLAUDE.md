# Verificación de T78 (Gemini) — Claude

**25/10/2026 · Claude CAB · Commits `0e4a495c`, `3a7c2e83`, `7e491642` · Veredicto: APROBADA, con cuatro observaciones para Carlos.**

## Qué se verificó

### Motivo del cobro cancelado en el Detalle de caja

| Punto | Resultado | Cómo |
|---|---|---|
| Se ve «Cancelado: motivo» en caja abierta y cerrada, para operativa y dueño | Verificado | Prueba de Gemini, pasa en mi base |
| También en caja **validada**, para los dos roles | Verificado | Prueba propia |
| Un motivo con marcado se muestra como texto, no como HTML | Verificado | Prueba propia: `<b>` sale escapado |
| El motivo viene del único lugar que lo escribe, la anulación de un cobro | Verificado | `PagoCuotaService.php:1208`, leído |

### Vencimiento y total en la ficha del alumno

| Punto | Resultado | Cómo |
|---|---|---|
| El total sale del mismo cálculo que Cobranza (`saldoDeAlumnos`), no de uno propio | Verificado | Código leído |
| El vencimiento sale de `limiteDeLaCuota` y `limiteDeAlta` del servicio, sin fechas calculadas en la vista | Verificado | Código leído |
| Misma persona en dos deportes: la inscripción se cuenta una vez, y la ficha de cada uno dice lo mismo que su fila de Cobranza (45.000 y 40.000) | Verificado | Prueba propia |
| Moroso: muestra el total y no muestra fecha | Verificado | Prueba propia |
| Un solo Cobrar, el de la barra superior. Condonar y Reactivar siguen para el dueño | Verificado | Código y prueba de Gemini |
| El profesor no entra a la ficha | Verificado | Prueba propia |

### Pruebas y capturas

| Punto | Resultado | Cómo |
|---|---|---|
| Suite rápida con los tres commits, en `wings_testing_claude` | Verificado | **922 aprobadas, 6 omitidas, 0 fallidas**, 263 s |
| Capturas de Mora a 375 px y del detalle de caja cerrada del dueño | Mirado | Se leen bien |

Pruebas propias: `evidencia-verificacion-t78-claude/VerificacionT78ClaudeTest.php`, 5/5.

## Observaciones para Carlos (no son defectos de lo pedido)

1. **Alumno que no es nuevo, dentro de la gracia del mes.** Ve «N días de gracia», pero no la fecha en que vence. `limiteDeLaCuota` solo da fecha para la cuota del mes del alta o la de un reingreso; la cuota mensual común vence con la gracia general y no tiene fecha propia. El pedido era para el alumno nuevo. Si querés la fecha para todos, es un cambio chico aparte.
2. **Con dos fechas posibles, muestra la más lejana.** Es el mismo criterio que ya usaban los «días de gracia». Inferido, sin un caso real: hace falta una cuota de alta y otra de reingreso pendientes a la vez.
3. **Los registros de la misma persona se buscan por DNI exacto.** Cobranza lo compara normalizado. No cambia nada mientras el DNI se guarde normalizado, como hace el alta (`AlumnoWebController.php:352`). Inferido, sin verificar en datos viejos.
4. **El margen lateral nuevo es solo del recuadro de la cuota.** En el celular, los recuadros de punitorios, cuotas condonadas y pagos siguen sin margen lateral. Es una cuestión de aspecto: lo decidís vos.

## Otro dato

Los commits `3a7c2e83` y `7e491642` de Gemini no estaban en GitHub. Suben con el commit de esta verificación.
