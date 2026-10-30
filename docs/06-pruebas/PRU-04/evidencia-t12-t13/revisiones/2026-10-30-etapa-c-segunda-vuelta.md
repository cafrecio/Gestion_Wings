# Revisión independiente del manual: etapa C, segunda vuelta

> **Fecha:** 30/10/2026
> **Revisor:** Claude, subagente revisor independiente. No escribió estas páginas ni la primera revisión.
> **Protocolo:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`, puntos 1 a 8, con el punto 6 aplicado a cada condición: el `if` se buscó en el código sin partir de la ficha y se comparó en los dos sentidos.
> **Base:** los 27 hallazgos de `revisiones/2026-10-30-etapa-c.md`.
> **Páginas releídas completas (24):** admin/administrar-deportes, administrar-niveles, administrar-grupos, administrar-profesores, administrar-rubros, administrar-tipos-caja, condonar-deuda-alumno, eliminar-alumno, configurar-parametros, administrar-usuarios, reportes-ingresos-egresos, reportes-alumnos, reportes-sueldos, programar-clases, configurar-mostrador, inicio-dueno, corregir-movimiento-cashflow y validar-rechazar-caja (la sección de Qué hago si). También operativo/inicio-operativa y profesor/consultar-mis-clases, corregir-asistencia-anterior, clases-sin-asistencia, alumnos-de-mi-clase y tomar-asistencia (la sección de Qué hago si).
>
> **Herramientas:** el código y las vistas se leyeron directamente con Read, Grep y sed. Para estos archivos el índice de codebase-memory no tenía cobertura confirmada, así que todo lo que se afirma sale de leer el archivo. No se corrió la suite ni se tocó ninguna base. Lo único que se modificó es este informe.

---

## 1. Control automático

```
php scripts/manual/verificar-manual.php
59 páginas revisadas, 0 problemas.
```

---

## 2. Estado de los 27 hallazgos de la primera vuelta

| # | Grav. original | Estado | Página:línea actual | Comprobación (archivo:línea) |
|---|---|---|---|---|
| 1 | Grave | **Resuelto** | admin/administrar-deportes.md:48 | Ahora la página dice «Pasá esos alumnos a **Inactivo**: un alumno no cambia de deporte, y moverlo a otro grupo del mismo deporte no destraba». Coincide con el conteo `where('deporte_id')->orWhereIn('grupo_id', grupos del deporte)` de `DeporteWebController.php:75-80`. Las clases futuras se cuentan con `cancelada=false` y `fecha >= today` (`:82-85`), y la página las nombra. |
| 2 | Grave | **Resuelto** | admin/administrar-grupos.md:51 | La página describe bien los tres desenlaces. (a) Si la frecuencia tiene una asignación con `activo=true`, se omite en silencio por el `whereNotExists` y el guardado sigue con «Grupo actualizado correctamente.» (`GrupoWebController.php:180-188`, `:215-216`). (b) Si tuvo asignaciones que ya no están activas, el borrado choca con la FK `restrict` (`create_alumno_planes_table.php:17`, `create_pagos_table.php:17`) y aparece la página de error (`bootstrap/app.php:130-146`). (c) Si se borran todas las filas, sale el mensaje `planes.min` (`:150-151`). Ver el hallazgo nuevo N6: hay otro camino desde la ficha del grupo. |
| 3 | Grave | **Resuelto** | admin/programar-clases.md:61 | La página ahora dice que una clase en una liquidación cerrada o pagada tampoco se cancela, con el texto literal (`ClaseWebController.php:614-615`). La salida que propone, «si está cerrada y sin pagar, primero cancelá la liquidación», coincide con `LiquidacionService::cancelar`, que rechaza una liquidación pagada y acepta una cerrada (`LiquidacionService.php:497-502`). |
| 4 | Media | **Resuelto** | admin/administrar-grupos.md:20 | La página dice «de 1 a 5», igual que `grupos/_form.blade.php:96` (`$f <= 5`) y `:126-130`. |
| 5 | Media | **Resuelto** | admin/administrar-usuarios.md:24 | La página dice «Tocá **Crear**», igual que `usuarios/create.blade.php:17`. |
| 6 | Media | **Resuelto** | admin/eliminar-alumno.md:33-39 | La página lista siete motivos, que coinciden uno a uno con los siete `return` de `Alumno::motivoNoEliminable()` (`Alumno.php:239, 242, 246, 250, 254, 264, 278`). |
| 7 | Media | **Resuelto** | admin/reportes-ingresos-egresos.md:12 y :37-39 | La página nombra la sección y el botón **Perder**, que solo ve el dueño (`cobranza/_deuda-inactivos.blade.php:19-24`), y el texto del aviso «No vuelve a cobrarse al reactivar.» (`:20`). La sección se incluye al final del reporte (`reportes/index.blade.php:110`) y en Cobranza (`cobranza/index.blade.php:239`). |
| 8 | Media | **Resuelto** | admin/corregir-movimiento-cashflow.md:14 | La página dice que una transferencia «hoy no se puede corregir ni anular» y propone hacer otra en sentido contrario. Las rutas de transferencia son solo `create` y `store` (`routes/web.php:138-139`). Una transferencia lleva `referencia_tipo` (`TransferenciaCashflowService.php:82`), así que no es directa (`CashflowMovimiento.php:80-88`). |
| 9 | Media | **Resuelto** | admin/configurar-mostrador.md:12 y :31 | La página ya no indica «después cambialo». Ahora dice que, una vez configurado, no hay botón para volver y que hay que consultar a soporte. Eso coincide con el código: `caja/index.blade.php:48` muestra **Abrir** si está configurado, y `caja/apertura.blade.php:22-27` ofrece **Configurar** solo si no hay medio o si está inactivo. Tampoco se puede desactivar el medio del mostrador (`TipoCajaWebController.php:98`, `:121`). La línea 31 coincide con `caja/configuracion.blade.php:23-32`: aparece el aviso y **Guardar** se oculta con `@unless($hayCajaAbierta)`. |
| 10 | Media | **Resuelto** (queda un detalle, ver N1) | admin/configurar-mostrador.md:24 | La captura regenerada muestra el formulario con **Guardar** y sin el aviso naranja. Es la pantalla del paso, aunque en el estado «ya configurado» (N1). |
| 11 | Media | **Resuelto** | admin/programar-clases.md:38 | La página ahora manda a la tarjeta del listado (`clases/_card.blade.php:99-104`, solo `$esAdmin`) y aclara que en la ficha **Editar** aparece solo sin presentes y sin validar, igual que el `@if` de `show.blade.php:133`. |
| 12 | Media | **Resuelto** (queda un matiz, ver N2) | admin/programar-clases.md:44 | La página dice que las profesoras de una clase pasada se cambian en la ficha, con el **Editar** junto a sus nombres (`show.blade.php:18`, `:67-74`), y que se pide motivo (`ClaseWebController.php:741-748`). |
| 13 | Media | **Resuelto** | admin/programar-clases.md:25 | Agrega la superposición («el aviso nombra la clase», `ClaseService.php:161-165`, que se usa en `ClaseWebController.php:240-245`) y el texto literal «Solo se pueden asignar profesores activos del deporte de la clase.» (`:268-274`). |
| 14 | Media | **Resuelto** | admin/administrar-tipos-caja.md:21 | La página limita el control al pago de liquidaciones y advierte que un gasto, un retiro o una transferencia pueden dejar la cuenta en negativo. La única lectura de `permite_descubierto` en `app/` es `LiquidacionWebController.php:439`. Las otras apariciones son de guardado o de muestra. |
| 15 | Media | **Resuelto** | profesor/corregir-asistencia-anterior.md:18 | La página dice que la búsqueda llega hasta 35 días atrás y que, para una clase más vieja con la lista tomada, hay que avisar al dueño. Coincide con `ClaseWebController.php:96-100`: `!$esAdmin && !$atajoSinAsistencia` y `subDays(35)`. |
| 16 | Media | **Resuelto** | profesor/consultar-mis-clases.md:30-32 | La página dice que sin filtros salen las próximas clases (`:102-105`, `cancelada=false` y `fecha > today`), que el límite es de 35 días y que la excepción es el atajo «sin lista» (`estado=finalizada`, `:96-97`). |
| 17 | Media | **Resuelto** (queda un matiz, ver N5) | profesor/tomar-asistencia.md:62 | La regla ahora es la del código: un alumno nuevo pide motivo con 2 o más presentes previos, y uno que no es nuevo, si está en Deudor (`ExcepcionCobranzaService.php:41-42`). La página aclara que no lo pide un alumno nuevo en Deudor con menos de 2 presentes. Los textos «Tiene deuda vencida…» y «Todavía no registró un pago de cuota…» están en `:47-48`, y «Indicá el motivo para admitir al alumno.» en `resources/js/clases-show.js:97`. |
| 18 | Media | **Resuelto a medias** | Fichas de las 22 páginas | Ya las 22 tienen la tabla «Rechazos y avisos de la acción», y cada «Sí» nombra una sección que existe en la página. Siguen sin fila de fuente muchos pasos numerados. `admin-administrar-usuarios.md` no cubre los pasos 1 a 5: no hay fila para el botón **Crear** (`create.blade.php:17`) ni para los campos. `admin-reportes-sueldos.md` y `admin-reportes-alumnos.md` siguen con una sola fila para toda la página. `admin-corregir-movimiento-cashflow.md` no tiene fila para «se abre el mismo formulario» ni para «Motivo (opcional)». Además, `admin-programar-clases.md:31` cita una fuente equivocada (N3). |
| 19 | Menor | **Resuelto** | admin/administrar-niveles.md:43 | La página dice «sin importar mayúsculas, acentos ni espacios de más», igual que `NombreUnico::normalizar` (`app/Rules/NombreUnico.php:29-36`). |
| 20 | Menor | **Resuelto** | admin/administrar-rubros.md:34 y :56 | La página explica que la columna *Permitido para* muestra OPERATIVO y ADMIN (`rubros/index.blade.php:77`) y que un subrubro pausado figura como *(inactivo)* (`:74`). |
| 21 | Menor | **Resuelto** | admin/administrar-deportes.md:23 | La captura regenerada ya no tiene el aviso verde ajeno. Muestra el formulario limpio con el menú de admin («Laura Gómez»). |
| 22 | Menor | **Resuelto** | admin/administrar-usuarios.md:55-56 | La pregunta se reescribió: «Tu propia tarjeta no tiene interruptor» (`usuarios/index.blade.php:90-99`). El texto «No podés cambiar tu propio rol.» aparece en el formulario propio (`usuarios/_form.blade.php:139-142`, con los demás roles deshabilitados en `:112`) y en el controlador (`UsuarioWebController.php:155`). |
| 23 | Menor | **Resuelto** | admin/validar-rechazar-caja.md:64 | La página nombra **Efectivo contado** y **Cambio que queda** (`caja/cierre.blade.php:26`) y el botón **Cerrar** (`:50`). El `@if` de `cierre()` (`CajaWebController.php:86-93`) y el de `cerrarCajaOperativa` (`CajaService.php:364-367`) coinciden con «Cerrada sin arqueo». |
| 24 | Menor | **Resuelto** | operativo/inicio-operativa.md:52 | La página pide **Efectivo contado** y **Cambio que queda** (`caja/cierre.blade.php:26`, ambos `required` en `CajaService.php:357-360`). |
| 25 | Menor | **Resuelto** | profesor/consultar-mis-clases.md:20 | La página dice «Tocá **Ver**». Las clases de hoy se dibujan con `_card` (`clases/index.blade.php:87-88`), que tiene **Ver** (`_card.blade.php:95-99`). |
| 26 | Menor | **Resuelto** | profesor/alumnos-de-mi-clase.md:24 | El punto de color se explica igual que en `show.blade.php:424-427`: neutral sin plan, rojo si excede, amarillo si completó y verde si le quedan clases. |
| 27 | Menor | **Resuelto** | admin/programar-clases.md:39 | Dice «las profesoras (en **Profesores**)». |

**Resumen:** 26 resueltos y 1 resuelto a medias (el 18). No quedó ningún hallazgo sin resolver.

---

## 3. Hallazgos nuevos

| # | Gravedad | Página:línea | Qué dice la página | Qué hace Wings (archivo:línea) |
|---|---|---|---|---|
| N1 | Menor | admin/configurar-mostrador.md:20-24 (captura `configurar-mostrador-paso1.png`) | Los pasos describen la primera configuración: «Mientras el mostrador no está configurado, el botón dice **Configurar**». | La captura muestra el mostrador **ya configurado**: «Efectivo» preseleccionado y la nota «Configurado actualmente. Podés cambiarlo seleccionando otro medio activo.» (`caja/configuracion.blade.php:25-26`, que sale solo si `tipo_caja_id !== null`). Es la pantalla correcta pero en otro momento. Además, la nota de pantalla invita a cambiar algo que la página (línea 12) dice que ya no tiene camino. |
| N2 | Menor | admin/programar-clases.md:39 y :44 | Cambiar profesoras desde **Editar** (clase futura) o desde la ficha (clase pasada); «Wings pide el motivo del cambio». No se nombra ningún rechazo. | Toda reasignación pasa por `validarCambioProfesores` (`ClaseWebController.php:279-300`), que rechaza con «La clase integra una liquidación pagada y no se puede modificar.» y «Primero cancelá la liquidación cerrada antes de cambiar las profesoras de esta clase.». Estos rechazos son frecuentes justamente en las clases pasadas. Además, el motivo se pide solo si la clase ya tenía profesora: `$esCorreccion = $esPasada && !empty($actualesIds)` (`:739-748`). Los textos se explican solos, por eso la gravedad es Menor. |
| N3 | Menor | Ficha `fuentes/admin-programar-clases.md:31` | La fila «No se puede eliminar:» tiene como fuente `app/Http/Controllers/AlumnoWebController.php:648`. | Esa línea es el rechazo de **eliminar alumno** («No se puede eliminar el alumno: …»). El texto que ve el dueño en la clase está en `resources/views/clases/show.blade.php:177-181` y el motivo en `app/Models/Clase.php:187-205`. La fuente no respalda la fila. |
| N4 | Menor | admin/programar-clases.md:50 y :61 | La página pide confirmar solo para **Cancelar serie** («si alguna ya tiene lista, Wings pide confirmarlo»), y el Qué hago si de la línea 61 manda a **Cancelar** una clase que tiene asistencias. | Cancelar **una sola** clase con lista también pide confirmar: «Esta clase ya tiene la lista tomada. Las marcas quedarán guardadas pero la clase dejará de contar para liquidaciones y comisiones. ¿Confirmás la cancelación?» (`ClaseWebController.php:656-668`). Es justo el caso al que manda la línea 61, y el lector va a encontrar ese aviso sin que la página lo anticipe. |
| N5 | Menor | profesor/tomar-asistencia.md:62 | «Un alumno nuevo en Deudor con menos de 2 presentes, **o uno Moroso**, no lo pide.» «Si ya pagó alguna vez y ahora está Deudor…». | Un alumno **nuevo** en Moroso con 2 o más presentes previos **sí** pide motivo, porque para el nuevo la regla no mira el estado: `$nuevo ? (previas >= 2 ? …)` (`ExcepcionCobranzaService.php:41`). Además, los alumnos de la primera carga no cuentan como nuevos aunque nunca hayan pagado en Wings (`:25-29`, `:40`). «Ya pagó alguna vez» no los cubre. |
| N6 | Menor | admin/administrar-grupos.md:51 | El único camino descrito para sacar una frecuencia es borrar la fila en **Editar**. Ahí, si tiene alumnos activos, «Wings no la elimina y no muestra ningún aviso». | Hay un segundo camino: la ficha del grupo (**Ver**) tiene **Eliminar** en cada frecuencia (`grupos/show.blade.php:129-142`). Ese camino sí avisa «No se puede eliminar: hay alumnos con este plan activo.» y «No se puede eliminar: el grupo quedaría sin frecuencias…» (`GrupoWebController.php:322-330`). Con una frecuencia que tuvo alumnos, ese camino también termina en la página de error, por la misma FK `restrict` (`:333`). La página no lo nombra. No es un camino equivocado, pero falta. |

Revisé y encontré sin hallazgos nuevos: administrar-deportes, administrar-niveles, administrar-profesores (con `ProfesorEstadoService.php:19-31`: los tres rechazos coinciden en los dos sentidos), administrar-rubros, administrar-tipos-caja, condonar-deuda-alumno (con los `@if` de `alumnos/show.blade.php:35-44`, `:214`, `:246-254`), eliminar-alumno, configurar-parametros, administrar-usuarios (la frase «la cerrás vos desde Caja» coincide con `CajaWebController.php:86` y `CajaService.php:368-370`), reportes-ingresos-egresos, reportes-alumnos, reportes-sueldos, inicio-dueno, corregir-movimiento-cashflow, validar-rechazar-caja (sección), inicio-operativa, consultar-mis-clases, corregir-asistencia-anterior, clases-sin-asistencia y alumnos-de-mi-clase.

---

## 4. Conteo final de lo abierto

- **Graves abiertos:** 0
- **Medios abiertos:** 1 (el 18, resuelto a medias: faltan filas de fuente por paso en algunas fichas)
- **Menores abiertos:** 6 (N1 a N6, todos nuevos)

---

## 5. Comprobado e inferido

**Comprobado leyendo el código o la vista, sin ambigüedad:** del 1 al 27 (el estado de cada uno contra la línea actual de la página y la condición del código) y N2 a N6. N1 y los hallazgos 10 y 21 se comprobaron mirando la imagen regenerada.

**Inferido del código, sin ejecutar:**
- 2 y N6: que borrar una frecuencia con historia termina en la página de error sale de la FK `restrict` y del manejador de `bootstrap/app.php`. No se ejecutó.
- 1: que pasar al alumno a otro grupo del mismo deporte no destraba la desactivación sale de la consulta. No se ejecutó.
- 3: que cancelar la liquidación cerrada destraba la cancelación de la clase sale de `integraLiquidacionCerrada()`, que solo mira `estado = CERRADA` (`Clase.php:151-176`). No se ejecutó.
- 15 y 16: el límite de 35 días sale de la consulta. No se probó con datos.
- N5: el caso «nuevo, Moroso, 2 o más presentes» sale de leer la expresión. No se ejecutó.
