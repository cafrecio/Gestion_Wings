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

**Sobre el campo `pagado`: no se guarda, se deduce.** Carlos lo había enumerado entre
los campos y el 06/09 delegó la decisión. Queda así.

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

## 9. Dónde entra la plata

**Decidido por Carlos el 06/09: un rubro reservado nuevo, `Punitorios`, con un solo
subrubro, `Punitorio Cuota`.**

Reemplaza la definición anterior —"Recargo por mora" bajo Intereses—, que no podía
funcionar: verificado en `CatalogosSeeder.php:54-61`, los dos subrubros de Intereses
son `permitido_para = ADMIN` y **`afecta_caja = false`**, porque ese rubro es para
intereses que generan el banco y Mercado Pago solos, plata que nunca pasa por el
mostrador. El recargo lo recibe el operativo en mano: ahí no habría podido cobrarlo,
y la plata no habría entrado a la caja.

### Cómo se define

| Qué | Valor |
|---|---|
| Rubro | `Punitorios`, tipo `INGRESO` |
| Subrubro | `Punitorio Cuota` |
| `permitido_para` | `OPERATIVO` — que en este sistema significa **admin y operativo**, no solo operativo |
| `afecta_caja` | `true` — la plata entra por el mostrador y tiene que estar en el arqueo |
| `es_reservado_sistema` | `true` |

Es exactamente la configuración de `Cuota Mensual`, que ya funciona así.

### "Rubro reservado" ya existe, y no hace falta ninguna columna nueva

**Verificado.** La tabla `rubros` tiene solo `id, nombre, tipo, observacion` y sus
fechas: **no hay** `es_reservado_sistema` a nivel de rubro. Pero el comportamiento
que pediste ya está, como propiedad emergente de tener un único subrubro reservado:

| Qué queda bloqueado | Dónde está |
|---|---|
| Agregarle otro subrubro al rubro | `SubrubroWebController.php:23` — si **todos** los subrubros de un rubro son reservados, rechaza el alta con "Este rubro es administrado por el sistema" |
| Borrar el rubro | `RubroWebController.php:70` — no se puede eliminar un rubro que tenga subrubros |
| Editar, desactivar o borrar el subrubro | `SubrubroWebController.php:49, 62, 86` — los tres rechazan si es reservado |
| Elegirlo a mano en un movimiento de caja o cashflow | `CajaWebController.php:848` y `CashflowWebController.php:68` filtran `es_reservado_sistema = false`, así que ni aparece en la lista |

Ese último punto es el que importa entender: **reservado no quiere decir que el
operativo no pueda cobrarlo.** Quiere decir que **nadie lo elige a mano**. La plata
entra por el flujo de cobro de cuota, que lo escribe solo, igual que hoy hace con
`Cuota Mensual`.

**Conclusión: el rubro y el subrubro se crean en el seeder de catálogos y no hace
falta tocar ningún controlador.**

### Cómo lo tiene que buscar el código

Por el **nombre exacto del subrubro**, como ya hace `PagoCuotaService.php:505` con
`Cuota Mensual`, y fallando ruidosamente si no está:

```php
$subrubro = Subrubro::where('nombre', 'Punitorio Cuota')->first();
// si no existe: excepción, no seguir de largo
```

**Nunca buscarlo por el nombre del rubro.** Motivo verificado: `RubroWebController::
update()` **no tiene ninguna comprobación de reservado**, así que un admin puede
renombrar cualquier rubro y también cambiarle el `tipo` de INGRESO a EGRESO. Si la
búsqueda dependiera del nombre del rubro, renombrarlo rompería el cobro en silencio.

> **Agujero preexistente que este contrato no arregla, pero deja anotado:** eso ya
> pasa hoy con `Sueldos`. `ProfesorWebController.php:116` hace
> `Rubro::where('nombre', 'Sueldos')->first()`, y si alguien renombra ese rubro, el
> alta de profesores deja de encontrarlo. Y cambiarle el `tipo` a un rubro de INGRESO
> lo daría vuelta contablemente. Es una tarea aparte: proteger los rubros que el
> sistema busca por nombre.

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
| 13 | El rubro queda cerrado | Intentar agregarle un segundo subrubro a `Punitorios` por pantalla: rechazado con "administrado por el sistema". Intentar eliminar el rubro: rechazado. Intentar editar o desactivar `Punitorio Cuota`: rechazado. **La base sin cambios en los tres intentos** |
| 14 | No se puede elegir a mano | `Punitorio Cuota` **no aparece** en el selector de subrubros de un movimiento de caja ni de cashflow, y sí queda escrito por el flujo de cobro |

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
| Rubro reservado `Punitorios` con subrubro `Punitorio Cuota` | Carlos, 06/09 — reemplaza "Recargo por mora bajo Intereses", que no podía funcionar |
| Sobre cada mes impago por separado | Carlos, 06/09 |
| También sobre el mes en curso, pasado el día | Carlos, 06/09 |
| Los recargos aplicados no se recalculan | Carlos, 06/09 |
| No guardar `recargo_pagado`, deducirlo | Carlos delega la decisión el 06/09; queda deducido (§5) |
| Dentro de cada mes, cuota antes que recargo | **Lectura mía de la regla, §6** |
