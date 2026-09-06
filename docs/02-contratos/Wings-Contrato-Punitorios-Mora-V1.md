# Wings — Contrato de Punitorios por Mora V1

> Definido por Carlos entre el 05 y el 06/09/2026. **Especificación, sin implementar.**
> Hasta hoy estas decisiones existían únicamente en el chat: no había una sola línea
> escrita en contratos ni en estado.
>
> Lo que dice este documento sobre el código está **verificado contra el código**, no
> heredado de otro documento. Donde algo es una propuesta mía y no una decisión de
> Carlos, está dicho.

## 1. Para qué existe

El club cobra la cuota por mes. Quien paga tarde hoy paga exactamente lo mismo que
quien paga a tiempo, así que **pagar tarde no cuesta nada**. El recargo por mora es
lo que hace que cueste.

Tiene que ser **configurable**, no un número escrito en el código: Wings también se
va a usar en Gestión Club, y cada club cobra distinto — o no cobra nada.

## 2. La configuración

Dos valores nuevos en la tabla `configuraciones`, que es donde ya viven
`dias_gracia_cobranza` y `dia_generacion_deuda`:

| Clave | Valor inicial | Tipo | Qué significa |
|---|---:|---|---|
| `mora_dia_desde` | 10 | integer | Día del mes a partir del cual empieza a correr el recargo |
| `mora_porcentaje` | 0 | string | Porcentaje que se suma a la cuota impaga |

**El porcentaje arranca en 0 a propósito.** Con 0, el sistema se comporta
exactamente como hoy: no se aplica ningún recargo, no aparece ningún renglón nuevo,
no cambia ningún importe. El club que quiera cobrar mora lo prende poniendo un
número; el que no, no se entera de que existe.

**El día arranca en 10 para coincidir con `dias_gracia_cobranza`**, que hoy vale 10.
Son dos cosas distintas —una decide cuándo el alumno se muestra como MOROSO, la otra
cuándo empieza a costarle plata— y por eso son dos claves separadas. Un club puede
querer avisar el día 10 y cobrar recién el 15.

### Dos trampas verificadas que hay que esquivar

**Primera: estas claves no se pueden crear desde la aplicación.** La pantalla de
configuración solo tiene `PATCH configuraciones/{clave}`, que edita claves
existentes; no hay ninguna ruta que dé de alta una. Y `Configuracion::set()` usa
`update()`: sobre una fila que no existe **no hace nada y no avisa**. Por eso las dos
claves nuevas **tienen que entrar por una migración**, como las dos que ya hay.

**Segunda, y peor: ya existe en el sistema una configuración que nadie lee.**
`dia_generacion_deuda` aparece únicamente en su migración. La generación mensual está
programada en `routes/console.php:12` con `->monthlyOn(1, '06:00')`: el día está
escrito en el código. El administrador puede cambiar ese valor, guardarlo, verlo
guardado — y no pasa nada.

**Este contrato no se da por cumplido si las dos claves nuevas terminan igual.** El
criterio de aceptación de §11 es explícito sobre eso.

### El tipo del porcentaje

`Configuracion::get()` convierte según `tipo`: `integer` a entero, `boolean` a
booleano, **cualquier otra cosa queda como texto**. Un porcentaje con decimales
(12,5%) no entra en `integer`, así que va como texto y **quien lo lea tiene que
convertirlo**. Guardarlo como `integer` sería aceptar en silencio que 12,5 se
convierta en 12.

## 3. Cuándo se aplica

**Pasado el día configurado, sobre la cuota del mes en curso que siga impaga.**
Decidido por Carlos el 06/09.

Con el día en 10: el 11 de marzo, la cuota de marzo impaga ya tiene recargo. Es el
mismo momento en que hoy el alumno pasa a MOROSO.

**Sobre cada mes impago por separado.** Decidido por Carlos el 06/09. Si alguien debe
marzo, abril y mayo, cada una de las tres cuotas suma su propio recargo. No es un
porcentaje único sobre el total: así se puede decir en el recibo qué recargo
corresponde a qué mes, y el alumno que paga solo marzo paga el recargo de marzo.

**Se aplica una sola vez por cuota.** No se acumula mes a mes. Una cuota de marzo
impaga en agosto tiene **un** recargo, el que se le puso el 11 de marzo, no cinco.

## 4. Cuánto es, y por qué queda congelado

El recargo es el porcentaje **vigente el día en que se aplica**, calculado sobre
`monto_original` de esa cuota. Una vez aplicado, **el importe queda escrito y no se
vuelve a calcular nunca.**

Decidido por Carlos el 06/09, y es el mismo criterio que él ya había fijado para la
deuda vieja que viene del Excel: **no recalcular montos pasados con el precio de
hoy.** Si el recargo se calculara al vuelo con el porcentaje vigente, subir el
porcentaje en agosto le encarecería a un alumno una deuda de marzo que ya le fue
comunicada y que quizá ya discutió.

Congelarlo también es lo que permite que `mora_porcentaje` vuelva a 0 sin borrar la
historia: los recargos ya aplicados siguen existiendo, y no se aplican nuevos.

## 5. Dónde se guarda

Sobre `deuda_cuotas`, que hoy tiene `id`, `alumno_id`, `periodo`, `monto_original`,
`monto_pagado`, `estado`, `observaciones` (verificado contra la base).

| Campo nuevo | Para qué |
|---|---|
| `recargo_porcentaje` | El porcentaje que se aplicó, congelado |
| `recargo_monto` | El importe que se aplicó, congelado |
| `recargo_condonado` | Cuánto de ese recargo se perdonó |
| `recargo_aplicado_el` | La fecha en que se aplicó. **Es lo que garantiza que se aplique una sola vez**: si tiene fecha, no se vuelve a tocar |

**Sobre el campo `pagado` — acá propongo algo distinto de lo que definiste, y lo
marco.** Carlos enumeró los campos como *porcentaje, condonado y pagado*. Propongo
**no** guardar `recargo_pagado`, y deducirlo.

El motivo es concreto: hoy rige la invariante **`deuda.monto_pagado` = suma de sus
imputaciones en `pago_deuda_cuota`**. Si además guardamos cuánto se pagó del recargo,
pasa a haber dos lugares que dicen cuánta plata entró, y tarde o temprano dicen cosas
distintas. Como la cuota se cubre antes que el recargo (§6), la cuenta sale sola:

- Lo pagado de la cuota es lo que haya entrado, hasta el tope de `monto_original`.
- Lo pagado del recargo es lo que sobre de eso.

Un solo lugar con la verdad, y el recibo puede mostrar los dos renglones igual.
**Si preferís que se guarde igual, se guarda: es tu decisión, no mía.**

## 6. Cómo se cobra

**Primero la cuota, después el recargo.** Decidido por Carlos.

El orden completo, cuando alguien paga y debe varios meses:

1. Los meses se cubren **del más viejo al más nuevo** — es el FIFO que ya rige hoy y
   no cambia.
2. Dentro de cada mes, **la cuota antes que el recargo**.

Así, quien paga apenas una parte está pagando cuota, no punitorios. El recargo es lo
último que se cobra, no lo primero.

> **Esto es lectura mía de la regla de Carlos, no una frase suya.** Él fijó que la
> cuota se cubre antes que el recargo; que eso sea *dentro de cada mes* y no *todas
> las cuotas de todos los meses antes que todos los recargos* lo deduzco de que el
> FIFO existente cierra cada período antes de pasar al siguiente. Si querés la otra
> lectura, cambia el orden de imputación.

## 7. Condonación

**Solo ADMIN**, por el mismo camino que la condonación de deuda que ya existe: con
motivo obligatorio de 10 a 500 caracteres, validado dentro del servicio.

**Se puede condonar el recargo sin condonar la cuota.** Es el caso normal: el alumno
tuvo un motivo atendible para pagar tarde, se le perdona el punitorio y paga la cuota
completa. Al revés también vale.

Condonar el recargo **no** cambia el estado de cobranza por sí solo: un alumno sin
ningún pago sigue siendo DEUDOR, como fija hoy `CobranzaEstadoService` y el contrato
de estados. Perdonar un recargo no es haber pagado.

## 8. En el recibo

**El recargo va en su propio renglón, con el mes al que corresponde.** No se suma
adentro del importe de la cuota.

Un recibo de alguien que paga marzo tarde muestra dos líneas: la cuota de marzo y el
recargo por mora de marzo. Si el recargo fue condonado, no aparece.

El motivo es que el alumno tiene que poder ver por qué paga de más, y el club tiene
que poder mostrárselo sin explicarlo de memoria.

## 9. Dónde entra la plata — DECISIÓN ABIERTA

Carlos definió: **un subrubro nuevo, "Recargo por mora", bajo el rubro Intereses.**

**Verificado en `database/seeders/CatalogosSeeder.php:54-61`, y hay un problema:**

| Subrubro | permitido_para | afecta_caja |
|---|---|---|
| Cuota Mensual (rubro Cuotas) | OPERATIVO | **true** |
| Intereses Mercado Pago | ADMIN | **false** |
| Intereses Banco | ADMIN | **false** |

El rubro Intereses está armado para plata que **nunca pasa por el mostrador**:
intereses que genera el banco o Mercado Pago solos. Sus dos subrubros son de ADMIN y
**no afectan la caja**.

El recargo por mora es lo contrario: **lo recibe el operativo, en mano, junto con la
cuota**. Si se carga bajo Intereses tal como está ese rubro hoy, pasan dos cosas:

1. **El operativo no puede cobrarlo**, porque el subrubro sería de ADMIN.
2. **La plata que recibió no entra a la caja**, porque el subrubro no la afecta. El
   arqueo del día le va a dar de más, todos los días, y nadie va a saber por qué.

Tres salidas posibles. **No elijo yo:**

| Opción | Qué implica |
|---|---|
| **A. Bajo Cuotas** | "Recargo por mora" como segundo subrubro de Cuotas, `OPERATIVO` y `afecta_caja = true`, igual que Cuota Mensual. Es lo que el dinero realmente es: cobro al alumno en el mostrador. En los reportes se separa igual, porque es un subrubro distinto |
| **B. Bajo Intereses, cambiándole las reglas** | Se crea ahí, pero como `OPERATIVO` y `afecta_caja = true`. Queda un rubro con subrubros que se comportan de dos maneras distintas, y el próximo que lo lea se va a confundir |
| **C. Rubro propio** | Un rubro nuevo "Punitorios". Lo más prolijo conceptualmente, pero suma un rubro para un solo subrubro |

**Mi recomendación es A**, porque el criterio del rubro debería ser de dónde viene la
plata, y esta viene del alumno, igual que la cuota.

Además, `Cuota Mensual` tiene `es_reservado_sistema = true` porque el código lo busca
por nombre exacto. El nuevo subrubro va a necesitar lo mismo, o alguien lo renombra y
el cobro deja de encontrarlo.

## 10. Con el porcentaje en 0, no existe

Es un requisito, no una consecuencia. Con `mora_porcentaje = 0`:

- No se escribe ningún recargo, ni en 0.
- No aparece ningún renglón en ningún recibo.
- No cambia ningún importe ni ningún estado de cobranza.
- Las pantallas se ven exactamente igual que hoy.

Es el estado en que se entrega el sistema.

## 11. Qué hay que probar antes de darlo por hecho

**Cada criterio dice cómo se comprueba.** Un criterio que no se puede comprobar con
un comando concreto no es un criterio.

| # | Qué tiene que pasar | Cómo se comprueba |
|---|---|---|
| 1 | Con porcentaje 0 no cambia nada | La suite completa en verde **sin tocar ninguna prueba existente**. Si hay que ajustar pruebas viejas, el requisito no se cumplió |
| 2 | Las dos claves existen en una base recién migrada | `migrate:fresh` sobre base descartable y consulta de las dos filas |
| 3 | **Las dos claves se leen de verdad** | Cambiar el valor por pantalla, y comprobar que el comportamiento cambia. Sin esta prueba repetimos `dia_generacion_deuda` |
| 4 | El recargo se aplica una sola vez | Correr el proceso dos veces sobre la misma cuota y comparar `recargo_monto` y `recargo_aplicado_el` antes y después |
| 5 | El recargo queda congelado | Aplicarlo, cambiar `mora_porcentaje`, y comprobar que la cuota vieja conserva su importe |
| 6 | Cada mes impago lleva el suyo | Alumno con tres meses impagos: tres recargos, calculados sobre el `monto_original` de cada mes |
| 7 | Se cobra la cuota antes que el recargo | Pago parcial menor a la cuota: la imputación no toca el recargo |
| 8 | La invariante sigue en pie | `deuda.monto_pagado` = suma de sus imputaciones, después de cobrar cuota y recargo |
| 9 | Solo ADMIN condona, con motivo | Intento como OPERATIVO: **la deuda queda sin cambios**. No se afirma el código de respuesta: `ensure.admin.web` redirige a `/caja` con 302, no devuelve 403 |
| 10 | Condonar el recargo no limpia al alumno | Alumno sin ningún pago con el recargo condonado: sigue DEUDOR |
| 11 | El recibo lo muestra aparte | Recibo generado con cuota y recargo: dos renglones, con el mes en el del recargo |
| 12 | La caja cuadra | Cobro con recargo y arqueo del día: el total incluye el recargo y no hay diferencia |

## 12. Lo que este contrato NO resuelve

- **Cuándo corre el proceso que aplica los recargos.** Hoy lo único programado es
  `cobranza:generar-deudas`, mensual el día 1. El recargo necesita mirar todos los
  días si ya se pasó `mora_dia_desde`. Si se hace una tarea diaria nueva, o se aplica
  al momento de consultar o cobrar, es decisión de implementación y hay que definirla
  antes de escribir código.
- **Notificar al alumno.** Este contrato no manda avisos.
- **Intereses sobre el recargo.** No los hay: el recargo no genera recargo.
- **Recargo sobre deuda importada del Excel.** La deuda vieja entra con su monto; si
  además le corresponde recargo se define en la etapa 2 de `PRIMERA-CARGA-V1.md`.

## 13. Decisiones y su fecha

| Decisión | Quién y cuándo |
|---|---|
| Debe existir el recargo, configurable por día y porcentaje | Carlos, 05/09 |
| Fijo, un porcentaje después del día X del mes | Carlos, 05/09 |
| El porcentaje arranca en 0 | Carlos, 05/09 |
| El día no va escrito en el código: es configuración | Carlos, 06/09 |
| Se aplica una sola vez, no se acumula | Carlos, 06/09 |
| La cuota se cubre antes que el recargo | Carlos, 06/09 |
| Va detallado en el recibo | Carlos, 06/09 |
| Subrubro nuevo "Recargo por mora" | Carlos, 06/09 — **con la objeción de §9 sin resolver** |
| Sobre cada mes impago por separado | Carlos, 06/09 |
| También sobre el mes en curso, pasado el día | Carlos, 06/09 |
| Los recargos aplicados no se recalculan | Carlos, 06/09 |
| No guardar `recargo_pagado`, deducirlo | **Propuesta mía, §5, pendiente de tu OK** |
| Dentro de cada mes, cuota antes que recargo | **Lectura mía de la regla, §6** |
