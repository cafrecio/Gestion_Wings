# Wings — Contrato de Punitorios por Mora V1

> Definido por Carlos entre el 05 y el 06/09/2026; enmendado el 22/09 y 16/10.
> **Implementado en B11 (Codex) y verificado por Claude el 16/10 ([verificación](../06-pruebas/PRU-04/VERIFICACION-B11.md)).**
> [Implementación y evidencia](../06-pruebas/PRU-04/IMPLEMENTACION-B11.md).
> Las advertencias de septiembre se conservan con su contexto histórico; las reglas
> y referencias técnicas actualizadas al 16/10 se indican debajo.

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

ADMIN edita ambas claves desde Configuración: día entero del 1 al 28 y porcentaje
de 0 a 100 con coma o punto y hasta dos decimales. `12,5` se guarda como texto
`12.50`, nunca como entero 12. Errores en castellano. Las claves y el catálogo se
crean por migración y por `CatalogosSeeder`; repetir el seeder conserva los valores.

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

**Advertencia histórica de septiembre: una configuración que nadie leía.**
`dia_generacion_deuda` aparece únicamente en su migración. La generación mensual está
programada en `routes/console.php:12` con `->monthlyOn(1, '06:00')`: el día está
escrito en el código. Actualmente ese día se muestra como fijo en Configuración
y el controlador rechaza editarlo; el problema descrito motivó exigir que las dos
claves de mora sí cambien el comportamiento del motor.

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

**D1–D3, decisiones de Carlos del 16/10/2026:**

- Solo se procesa el mes vigente de Wings. Activar el porcentaje no agrega recargos
  a meses cerrados; una deuda antigua solo conserva el que se aplicó en su propio mes.
- La cuota del mes de alta conserva el plazo más largo entre `dias_plazo_alta` y
  la gracia general de ese mes, como T31. También debe haber pasado el día de mora.
  Desde el 17/10/2026 (T38) vale lo mismo para la cuota que se genera al reactivar a un
  alumno, con el plazo contado desde el día del reingreso (`deuda_cuotas.fecha_reingreso`).
- No se aplican recargos nuevos a alumnos inactivos, cuotas pagadas o condonadas.
  Una cuota parcialmente pagada conserva la base original mientras tenga saldo.

**Sobre cada mes impago por separado.** Decidido por Carlos el 06/09. Si alguien debe
marzo, abril y mayo, cada cuota conserva el recargo aplicado durante su propio mes.
No se generan los tres al activar la configuración. No es un
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

**Enmienda aprobada por Carlos el 22/09/2026 (ENT-01).** El recargo se guarda en
`cargos_alumno`, tipo `PUNITORIO`, con `deuda_cuota_id` y clave de origen única
`punitorio:deuda:{id}`. Sustituye las columnas `recargo_*` propuestas anteriormente
para `deuda_cuotas`. No hay dos mecanismos ni se suma el recargo al capital mensual.

- `monto_original`: importe del recargo, congelado.
- `calculo`: porcentaje aplicado, base original y fecha de aplicación, congelados.
- `monto_condonado`: importe perdonado, con usuario, fecha y motivo en el historial
  `cargo_alumno_eventos`. Solo ADMIN; se conserva la regla de §7.
- `pago_cargo_alumno`: cada imputación del pago al cargo. Lo pagado se obtiene de
  esas imputaciones, sin otro acumulador que pueda contradecirlas.
- La unicidad de `clave_origen` impide aplicar dos veces el punitorio a la misma
  cuota, incluso con procesos concurrentes. El cargo no se borra al cancelarlo.

`pago_deuda_cuota` registra únicamente capital de cuota. Se conserva la invariante
`deuda.monto_pagado = suma de imputaciones a esa cuota`. El total de un pago incluye
sus imputaciones a cuotas y cargos; el recibo las muestra separadas. Ni inscripción
ni punitorios integran comisiones docentes. Los pagos anteriores conservan su significado.

ENT-01 preparó la estructura compartida. **B11 incorpora el motor y la configuración
de FIN-14**, conservando porcentaje/importe congelados, una vez por cuota,
condonación ADMIN con motivo, mes en recibo y el rubro Punitorios → Punitorio Cuota.
El motor toma alumno y cuota con candado antes de crear el cargo y su evento CREAR.

## 6. Cómo se cobra

**Primero la cuota, después el recargo.** Decidido por Carlos.

El orden completo, cuando alguien paga y debe varios meses:

1. Los meses se cubren **del más viejo al más nuevo** — es el FIFO que ya rige hoy y
   no cambia.
2. Dentro de cada mes, **la cuota antes que el recargo**.

Así, quien paga apenas una parte está pagando cuota, no punitorios. El recargo es lo
último que se cobra, no lo primero.

**Confirmado por la orden B11 del 16/10:** capital y después recargo dentro de cada
mes. La inscripción pendiente conserva su prioridad anterior. El servidor controla
el reparto aunque se manipulen los importes enviados. Pagar o condonar capital no
elimina un recargo ya aplicado: se puede cobrar solo ese cargo. Los recargos viejos
participan del aviso y confirmación con motivo existentes al saltear deuda.
ADMIN cobra directo a cashflow; OPERATIVO por su caja. Anular el pago revierte sus
imputaciones a capital y cargos y todos sus movimientos, conservando conceptos y
meses en el detalle documental del recibo anulado.

## 7. Condonación

**Solo ADMIN**, por el mismo camino que la condonación de deuda que ya existe: con
motivo obligatorio de 10 a 500 caracteres, validado dentro del servicio.

**Se puede condonar el recargo sin condonar la cuota.** Es el caso normal: el alumno
tuvo un motivo atendible para pagar tarde, se le perdona el punitorio y paga la cuota
completa. Al revés también vale.

Condonar el recargo **no** limpia capital impago ni vuelve por sí solo al alumno
Al día. Rigen sus deudas y plazos: MOROSO para mes corriente vencido y DEUDOR para
mes cerrado, con la excepción del plazo de alta. Perdonar no equivale a pagar.
ADMIN activo también puede reactivar con motivo de 10 a 500 caracteres. Se perdona
solo el saldo y se conservan pagos, importe/cálculo congelados y auditoría; reactivar
recupera lo perdonado. Rutas y servicio controlan el permiso.

## 8. En el recibo

**El recargo va en su propio renglón, con el mes al que corresponde.** No se suma
adentro del importe de la cuota.

Un recibo de alguien que paga marzo tarde muestra dos líneas: la cuota de marzo y el
recargo por mora de marzo. Si el recargo fue condonado, no aparece.

Etiqueta actual: **«Recargo por mora — <mes>»**. La inscripción conserva
«Inscripción al club — por única vez». Si se perdona solo el saldo después de un
pago parcial, no se borra del recibo la parte efectivamente cobrada.

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
| `clasificacion_resultado` | `NEGOCIO`: ingreso del club |

Es exactamente la configuración de `Cuota Mensual`, que ya funciona así.

### "Rubro reservado" ya existe, y no hace falta ninguna columna nueva

**Actualizado al 16/10.** Tanto rubro como subrubro tienen
`es_reservado_sistema`. `Punitorios` nace reservado; la protección existente impide
agregarle otro subrubro y editarlo o borrarlo. También protege su subrubro:

| Qué queda bloqueado | Dónde está |
|---|---|
| Agregar otro subrubro | `SubrubroWebController::guardarNuevo` rechaza el rubro reservado con "administrado por el sistema" |
| Editar o borrar el rubro | `RubroWebController` rechaza la reserva |
| Editar, desactivar o borrar el subrubro | `SubrubroWebController` rechaza la reserva |
| Elegirlo manualmente en caja/cashflow | Los selectores excluyen `es_reservado_sistema = true`; el flujo de cobro sí lo registra |

Ese último punto es el que importa entender: **reservado no quiere decir que el
operativo no pueda cobrarlo.** Quiere decir que **nadie lo elige a mano**. La plata
entra por el flujo de cobro de cuota, que lo escribe solo, igual que hoy hace con
`Cuota Mensual`.

El rubro y subrubro se crean por migración y seeder. No se modifican los
controladores de catálogos: se usa su protección existente.

### Cómo lo tiene que buscar el código

Por el **nombre exacto del subrubro**, como ya hace `PagoCuotaService.php:505` con
`Cuota Mensual`, y fallando ruidosamente si no está:

```php
$subrubro = Subrubro::where('nombre', 'Punitorio Cuota')->first();
// si no existe: excepción, no seguir de largo
```

**Nunca buscarlo por el nombre del rubro.** El motivo original era que
`RubroWebController::update()` no comprobaba nada y cualquiera podía renombrar un
rubro o cambiarle el tipo.

> **Actualizado el 06/09 (commit `b2868ea`).** Ese agujero está cerrado: los rubros
> tienen `es_reservado_sistema` y uno reservado ya no se renombra, no cambia de tipo
> y no se borra. **La regla de este contrato no cambia igual**: se busca por el
> nombre del **subrubro**, que es el nivel donde vive la plata, y el rubro
> `Punitorios` tiene que nacer del seeder marcado como reservado — igual que
> `Sueldos` y `Cuotas`, marcados en esa misma migración.

> La advertencia original sobre renombrar `Sueldos` precede a esa protección.
> No describe un permiso actual de edición de rubros reservados.

## 10. Con el porcentaje en 0, no existe

Es un requisito, no una consecuencia. Con `mora_porcentaje = 0` y sin recargos previos:

- No se escribe ningún recargo, ni en 0.
- No aparece ningún renglón en ningún recibo.
- No cambia ningún importe ni ningún estado de cobranza.
- Cobrar, ficha y recibo conservan su aspecto anterior. Configuración incorpora
  los dos controles necesarios para activar la regla.

Es el estado en que se entrega el sistema.

Si hubo recargos anteriores, volver a cero no los borra ni condona.
**D4, 16/10:** el recargo impago es deuda de su mes incluso con capital pagado o
condonado. Suma al mismo saldo en Cobranza, buscador de Cobrar, Inicio y Reportes,
y el alumno no queda Al día mientras lo deba. Reportes lo distingue por concepto y
conserva el corte anterior a un perdón posterior. Una anulación, en cambio, cuenta desde
la fecha del cobro anulado (T39, Carlos 18/10): ese mes vuelve a deber lo que el cobro pagaba.

## 11. Qué hay que probar antes de darlo por hecho

**Cada criterio dice cómo se comprueba.** Un criterio que no se puede comprobar con
un comando concreto no es un criterio.

| # | Qué tiene que pasar | Cómo se comprueba |
|---|---|---|
| 1 | Con porcentaje 0 no cambia nada | Suite completa en verde. Excepción expresa de Carlos, 16/10: cambiar solo los conteos de `CatalogosSeederTest`, de 11/18 a 12/19 por Punitorios. Ninguna otra prueba vieja cambia |
| 2 | Las dos claves existen en una base recién migrada | `migrate:fresh` sobre base descartable y consulta de las dos filas |
| 3 | **Las dos claves se leen de verdad** | Cambiar el valor por pantalla, y comprobar que el comportamiento cambia. Sin esta prueba repetimos `dia_generacion_deuda` |
| 4 | El recargo se aplica una sola vez | Correr el proceso dos veces sobre la misma cuota y comprobar un solo cargo `punitorio:deuda:{id}`, su importe y fecha de aplicación congelados antes y después |
| 5 | El recargo queda congelado | Aplicarlo, cambiar `mora_porcentaje`, y comprobar que la cuota vieja conserva su importe |
| 6 | Cada mes impago lleva el suyo | Alumno con tres meses impagos: tres recargos, calculados sobre el `monto_original` de cada mes |
| 7 | Se cobra la cuota antes que el recargo | Pago parcial menor a la cuota: la imputación no toca el recargo |
| 8 | La invariante sigue en pie | `deuda.monto_pagado` = suma de sus imputaciones, después de cobrar cuota y recargo |
| 9 | Solo ADMIN condona, con motivo | Intentos como OPERATIVO/PROFESOR: 403 y cargo/eventos sin cambios. Anónimo debe ingresar. Motivo 10–500 validado también por servicio |
| 10 | Condonar el recargo no limpia al alumno | Alumno sin ningún pago con el recargo condonado: sigue DEUDOR |
| 11 | El recibo lo muestra aparte | Recibo generado con cuota y recargo: dos renglones, con el mes en el del recargo |
| 12 | La caja cuadra | Cobro con recargo y arqueo del día: el total incluye el recargo y no hay diferencia |
| 13 | El rubro queda cerrado | Intentar agregarle un segundo subrubro a `Punitorios` por pantalla: rechazado con "administrado por el sistema". Intentar eliminar el rubro: rechazado. Intentar editar o desactivar `Punitorio Cuota`: rechazado. **La base sin cambios en los tres intentos** |
| 14 | No se puede elegir a mano | `Punitorio Cuota` **no aparece** en el selector de subrubros de un movimiento de caja ni de cashflow, y sí queda escrito por el flujo de cobro |

## 12. Proceso y límites

Comando **`php artisan cobranza:aplicar-punitorios`**, programado diariamente a
las **06:15** en `routes/console.php`, sin solapamiento y en un solo servidor.
El servicio usa `Carbon::now()`, con la fecha de Wings que establece
`App\Support\RelojSimulado` al iniciar la aplicación; no cambia el reloj del servidor.
Si se omite un día recupera la aplicación el siguiente dentro del mismo mes,
nunca meses cerrados. No se aplica al abrir una pantalla.

Quedan fuera:
- **Notificar al alumno.** Este contrato no manda avisos.
- **Intereses sobre el recargo.** No los hay: el recargo no genera recargo.
- **Retroactividad sobre deuda importada.** D1 excluye meses cerrados; una cuota
  importada del mes vigente se rige por la misma regla de ese mes.

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
| Pagado del recargo por imputaciones al cargo | Enmienda Carlos 22/09; `pago_cargo_alumno` (§5) |
| Dentro de cada mes, cuota antes que recargo; inscripción primero | Confirmado por la orden B11 de Carlos, 16/10/2026 |
| D1 sin retroactividad; D2 plazo favorable de alta; D3 solo activos | Carlos, 16/10/2026 |
| D4 recargo como deuda de su mes y saldo igual en todas las pantallas | Carlos, 16/10/2026 |
| Comando diario idempotente y fecha de Wings | Decisión técnica fijada por la orden B11, 16/10/2026 |
| Actualizar solo conteos de catálogos de la prueba vieja | Carlos, 16/10/2026: «Actualizar los conteos y seguir» |
