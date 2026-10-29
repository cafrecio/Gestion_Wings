# Control de la etapa A corregida de T12/T13 (Gemini, `d3973e2a`) — Claude CAB, 29/10/2026

Revisado contra mi devolución (`PROMPT-GEMINI-T12-T13-ETAPA-A-DEVOLUCION.md`), las páginas de
`resources/manual/operativo/`, sus fichas y el código. Corrí yo `verificar-manual.php` (22 páginas, 0
problemas) y `VerificarCaminosProhibidosManualTest` (18/18).

## Lo pedido, punto por punto

| Punto | Resultado |
|---|---|
| 1. `rectificar-caja.md` | **Bien.** Solo con la caja Abierta; sin «alerta de observación»; el «Qué hago si» de la caja rechazada coincide con Wings: el resumen ofrece Cobrar, Nuevo y Cerrar a su dueña (`caja/resumen.blade.php:59-66`) y lo contado no cambia. |
| 2. `tomar-lista-mostrador.md` | **Los dos mensajes de B3 están tal cual** (`ExcepcionCobranzaService.php:44-46`). **Pero mi devolución estaba equivocada en una parte:** dije que «excede los días de su plan» no existía, y existe. Cuando un presente supera las veces por semana de su plan, la lista muestra «Excede el plan — indicar motivo» con dos opciones, «Clase adicional (EXTRA)» y «Recupera clase perdida (RECUPERA)»; si no se elige, queda EXTRA (`clases/show.blade.php:497-505`, `clases-show.js:27-49`, `ClaseWebController.php:471-473`). No traba el guardado. Gemini lo sacó porque se lo pedí: la página quedó con un hueco que es mío. |
| 3. `pasar-alumno-inactivo.md` | **Bien.** Sí/No/Cancelar, sin No cuando incluye el mes en curso, con los textos de Wings; sin «baja temporal o definitiva» ni «T65». La cuota de reingreso con la regla del día es correcta (`PagoCuotaService::reglaDelDia`, y no duplica la del mes). |
| 4. Columna «Condición en el código» | Está en las 20 fichas, pero **no siempre es un `if`**. En `operativo-registrar-gasto-caja.md` la «regla del dueño» cita como fuente la propia página del manual y su condición es un `create(...)`; el paso 3 copia la firma de una función. |
| 5. Revisor independiente | Cerró en 0 graves y 0 medias. **No vio los tres huecos de abajo.** |

## Hallazgos nuevos

| # | Gravedad | Página | Qué falta | Qué hace Wings |
|---|---|---|---|---|
| 1 | Media | `registrar-gasto-caja.md` | No dice que un gasto en efectivo mayor que lo que hay en el cajón se rechaza, ni que pasa lo mismo al editarlo. | «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.» (`CajaService.php:614-615`, T88). Codex lo encontró en el día 25 de la prueba. |
| 2 | Media | `cobrar-pago-parcial.md` | No dice que un parcial pide motivo. El paso 3 lleva a **Cobrar** y nombra Observaciones, que no es lo mismo. | Panel: «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.» Sin motivo no cobra (`ExcepcionCobranzaService.php:93`, T88/B3). |
| 3 | Media | `cobrar-cuota.md` | No cuenta los avisos que aparecen dentro del cobro: meses anteriores impagos (pide motivo), cobro por adelantado de un mes que no empezó, alumno inactivo (Sí/No/Cancelar, ya explicado en `pasar-alumno-inactivo.md`: alcanza con remitir) y fecha de un mes ya cerrado. | «Quedan N meses anteriores impagos por $X (…). ¿Cobrás igual solo los meses elegidos? Indicá el motivo…»; «Estás cobrando por adelantado …» y el de la fecha de un mes cerrado (`ExcepcionCobranzaService.php:96`, `CajaWebController.php:900` y `:928`). |
| 4 | Menor | `registrar-gasto-caja.md` (título y nota) y otras cinco páginas | «[DU-20]» en el **título** que ve la operativa; «DU-27» en el texto de cinco páginas. | Son códigos internos, como «T65». En el título del menú y de la página no van; en el texto, decir la regla en palabras. |

## Por qué vuelve

Los tres huecos son reglas que la operativa encuentra a diario y que cambiaron con lo subido el 28/10
(T88, B3, T25). El control nuevo miró las condiciones **que cada página dice**, no las que **la acción tiene**:
por eso no aparecieron. No vuelve la etapa entera; vuelven estas páginas y un control más, por página.

Un barrido grueso de los mensajes de rechazo de caja, cobro, alumnos y clases contra el manual operativo dio
48 de 50 sin su texto literal. Muchos son del dueño o internos, y otros el manual los cuenta con otras
palabras, así que **el número no es un hallazgo**: es la lista por donde empezar el control nuevo.
