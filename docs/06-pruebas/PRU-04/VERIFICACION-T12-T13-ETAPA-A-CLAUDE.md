# Control de la etapa A de T12/T13 (Gemini, `61fb5272`) — Claude CAB, 29/10/2026

**Resultado: la etapa vuelve entera.** Encontré un error grave en una página. Según la regla del prompt de
etapas, un error grave hace volver la etapa entera. El código (buscador y **?**) está bien y no vuelve.

## Lo que está bien (comprobado)

- `php scripts/manual/verificar-manual.php`: 22 páginas, 0 problemas.
- `AyudaManualT12T13Test`: 19 pasadas, 93 aserciones (corrida por Claude en `wings_testing_claude`).
- El buscador filtra solo dentro de los roles del usuario (`ManualService::listarPorRol`). El **?** está en
  las 15 vistas de la etapa que revisé.
- Paso 0: los dos detalles del control anterior están corregidos y la lista quedó revisada contra el código.
- Páginas controladas contra el código: `pasar-alumno-inactivo`, `rectificar-caja`, `tomar-lista-mostrador`,
  `resolver-revision-cobranza`, `cobrar-pago-parcial` y `cancelar-movimiento-caja`. Las dos últimas y
  Revisión no tienen errores.

## Hallazgos

| # | Gravedad | Página | Qué dice | Qué hace Wings |
|---|---|---|---|---|
| 1 | **Grave** | `operativo/rectificar-caja.md` (nota, título, resumen y paso 1) | Se puede rectificar con la caja **Abierta o Rechazada**, «cuando el dueño rechaza la caja», «desde la alerta de observación». | Solo con la caja **Abierta**: `CajaWebController.php:102` y `:113`, `CajaService.php:161` («Solo se puede rectificar una caja en estado ABIERTA.»). El botón aparece solo si está Abierta (`caja/resumen.blade.php:68`). No hay «alerta de observación». La pantalla rectifica la apertura, no el cierre. Con la caja rechazada, la operativa tiene **Cerrar** (`resumen.blade.php:65`). |
| 2 | Media | `operativo/tomar-lista-mostrador.md:35` | Se pide motivo si el alumno «adeuda cuotas vencidas o excede los días de su plan». | No existe la regla del plan. Se pide motivo si está **Deudor** («Tiene deuda vencida…») o si es **alumno nuevo sin ningún pago de cuota y ya tiene 2 presentes** («Todavía no registró un pago de cuota…»): `ExcepcionCobranzaService.php:40-48`. |
| 3 | Menor | `operativo/pasar-alumno-inactivo.md` | No dice qué pasa si el cobro a un inactivo incluye el mes en curso. Arranca con «dar de baja temporal o definitiva» y nombra «T65». | Si el cobro incluye el mes en curso, no aparece **No**: para cobrarlo tiene que volver a activo (`CajaWebController.php:938-952`). Inactivo no es baja (Carlos, 28/10). «T65» es jerga interna. |
| 4 | Menor | Informe de entrega | «Motor de búsqueda … con ranking y snippet contextual». | El código solo filtra por palabras (`ManualService.php`): no ordena por relevancia ni muestra fragmentos. El informe afirma lo que no hay. |

## Por qué vuelve la etapa entera

El error 1 no es de redacción, es de **método**. La ficha `fuentes/operativo-rectificar-caja.md:15` cita
`CajaWebController.php:360`, que es la carga del detalle de la caja, no la regla, y anota «Comprobado».
Si se hubiera intentado rectificar una caja rechazada, Wings habría mostrado el error. El revisor
independiente tampoco lo vio: es la segunda vez que se le pasa algo (la primera fue en la etapa 1). Si
pasó en una de las seis páginas que miré, puede estar en las otras catorce.

Se agregó a `REVISOR-MANUAL.md` el punto 6: cada condición de estado, rol o fecha se compara contra el `if`
que la decide, buscado por el revisor, no contra la línea que cita la ficha.
