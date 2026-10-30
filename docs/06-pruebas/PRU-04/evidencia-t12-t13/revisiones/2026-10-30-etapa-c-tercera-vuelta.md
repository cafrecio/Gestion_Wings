# Revisión independiente del manual: etapa C, tercera vuelta

> **Fecha:** 30/10/2026
> **Revisor:** Claude, subagente revisor independiente. No escribió estas páginas ni las revisiones anteriores.
> **Protocolo:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`. Apliqué el punto 6 a cada condición de las páginas que cambiaron: busqué el `if` del controlador o del servicio y el `@if` de la vista sin partir de la ficha, y los comparé en los dos sentidos.
> **Base:** lo que quedó abierto en `revisiones/2026-10-30-etapa-c-segunda-vuelta.md`: el medio #18 y los menores N1 a N6.
> **Páginas releídas completas:** admin/configurar-mostrador, admin/programar-clases, admin/administrar-grupos y profesor/tomar-asistencia. Para comprobar el #18 también leí admin/administrar-usuarios, admin/reportes-sueldos, admin/reportes-alumnos y admin/corregir-movimiento-cashflow, junto con sus fichas.
>
> **Herramientas:** leí el código y las vistas directamente con Read, Grep y sed. El índice de codebase-memory no tenía cobertura confirmada para estos archivos, así que todo lo que afirmo sale de leer el archivo. No corrí la suite ni toqué ninguna base. El único archivo que modifiqué es este informe.

---

## 1. Control automático

```
php scripts/manual/verificar-manual.php
59 páginas revisadas, 0 problemas.   (código de salida 0)
```

---

## 2. Estado de lo que quedó abierto

| # | Grav. | Estado | Página o ficha:línea | Comprobación (archivo:línea) |
|---|---|---|---|---|
| 18 | Media | **A medias** | Fichas `admin-administrar-usuarios`, `admin-reportes-sueldos`, `admin-reportes-alumnos`, `admin-corregir-movimiento-cashflow` (y las de las páginas que cambiaron) | **Se cerraron los huecos que nombró la segunda vuelta.** La ficha de usuarios ahora tiene una fila para **Crear** (`usuarios/create.blade.php:17`, verificada), otra para **Nuevo** (`usuarios/index.blade.php:36`) y otra para los campos. Las fichas de los reportes pasaron de 1 fila a 4, y las 4 líneas citadas dicen lo que la fila afirma (`ReporteWebController.php:13`, `:29`; `reportes/sueldos.blade.php:31, 33, 63`; `reportes/alumnos.blade.php:31, 34, 62`). La ficha de cashflow tiene fila para «se abre el mismo formulario» (`CashflowCorreccionWebController.php:28`) y para «Motivo (opcional)» (`CashflowCorreccionService.php:35`). Todas las líneas citadas coinciden con el texto de la fila. **Siguen sin fila algunos pasos y afirmaciones, que el punto 2 del protocolo cuenta como hallazgo:** ver el detalle abajo. |
| N1 | Menor | **Resuelto** | admin/configurar-mostrador.md:24-26 | La captura (que miré) muestra la pantalla ya configurada. La página ahora lo dice dos veces: en la línea 24, que cita la nota literal «Configurado actualmente. Podés cambiarlo seleccionando otro medio activo.» (`caja/configuracion.blade.php:25-26`, `@elseif($propuesta['tipo_caja_id'] !== null)`), y en el texto alternativo de la imagen, «ya configurada». La línea 12 sigue siendo cierta: `caja/index.blade.php:48` muestra **Configurar** solo si no está configurado, y `caja/apertura.blade.php:23-27` ofrece **Configurar** solo con `!$tipoEfectivo \|\| !$tipoEfectivo->activo`. |
| N2 | Menor | **Resuelto** | admin/programar-clases.md:44 | La página dice ahora que el motivo se pide solo si la clase ya tenía profesora, con el texto literal «Esta clase ya tenía profesor asignado: indicá el motivo del cambio.». Coincide con `ClaseWebController.php:740` (`$esCorreccion = $esPasada && !empty($actualesIds)`) y `:742-746`. También nombra los dos rechazos por liquidación, pagada y cerrada, igual que `validarCambioProfesores` (`:296-300`). Ese control también se aplica desde **Editar** en una clase futura (`:565-568`). Wings tiene un tercer rechazo, «La clase está cancelada. No se pueden cambiar sus profesoras.» (`:281-283`), pero no se alcanza desde la ficha: el botón está bajo `@if(!$clase->cancelada && …)` (`show.blade.php:67`). |
| N3 | Menor | **Resuelto** | Ficha `admin-programar-clases.md:36` | La fila de «No se puede eliminar:» ahora cita `resources/views/clases/show.blade.php:180`. Esa línea es «No se puede eliminar: {{ $motivoNoEliminable }}», dentro del `@if($esAdmin && !$puedeEliminar && $motivoNoEliminable)` de `:178`. |
| N4 | Menor | **Resuelto** | admin/programar-clases.md:50 | La página dice ahora: «Si la clase ya tenía la lista tomada, Wings pide confirmar: las marcas se guardan, pero la clase deja de contar para liquidaciones y comisiones.». Coincide con `ClaseWebController.php:654-668` (`$tieneLista && !$confirmado`). Es una paráfrasis fiel, no el texto literal. La ficha cita el literal en `:658` (fila 11). |
| N5 | Menor | **Resuelto** | profesor/tomar-asistencia.md:62 | Comparé la regla en los dos sentidos con `ExcepcionCobranzaService.php:40-42`. (a) Alumno nuevo: pide motivo con `previas >= 2`, sin mirar el estado. La página dice «esté como esté su cuota». (b) Alumno que no es nuevo: pide motivo solo si está en `DEUDOR`, y un Moroso queda afuera, como dice la página. (c) Los alumnos de la primera carga no cuentan como nuevos (`:25-29`, `:40`), y la página ahora lo dice. Los dos textos dependen de `$nuevo` (`:47-48`) y la página los cita bien. «Indicá el motivo para admitir al alumno.» está en `resources/js/clases-show.js:97`. El segundo punto también está bien. «Excede el plan — indicar motivo» aparece en `show.blade.php:505`, y el selector empieza en EXTRA (`:511`); el servidor también usa EXTRA si no llega otro valor (`ClaseWebController.php:471-473`). Ver el hallazgo nuevo N7: la ficha no acompañó el cambio. |
| N6 | Menor | **Resuelto** | admin/administrar-grupos.md:53 | La página nombra el segundo camino, **Eliminar** en cada frecuencia de la ficha del grupo (`grupos/show.blade.php:128-142`, solo `$isAdmin`), con sus dos textos literales en el mismo orden que el código: primero el de «quedaría sin frecuencias» (`GrupoWebController.php:322-323`, `count() <= 1`) y después el de «alumnos con este plan activo» (`:326-329`). La frase «el resultado es el mismo error» coincide con el `$plan->delete()` de `:333` y la FK `restrict`, aunque lo infiero sin haberlo ejecutado. Ver el menor N9. |

### Detalle de lo que falta en el #18

| Ficha | Paso o afirmación de la página sin fila de fuente | Dónde está en Wings (lo comprobé) |
|---|---|---|
| `admin-administrar-usuarios` | «Cambiar la contraseña o el rol», paso 1: **Editar** en la tarjeta | `resources/views/usuarios/index.blade.php:89` |
| `admin-administrar-usuarios` | «Cambiar la contraseña o el rol», paso 3: **Guardar** | `resources/views/usuarios/edit.blade.php:18` |
| `admin-administrar-usuarios` | «Tu propia tarjeta dice *(vos)*» y «Tu propia tarjeta no tiene interruptor» | `usuarios/index.blade.php:57-58` y `:90-99` |
| `admin-administrar-usuarios` | La fila «Pasos 2 a 4» respalda siete campos con una sola línea, la etiqueta «Profesor vinculado» (`_form.blade.php:163`). Las demás etiquetas están en `:11`, `:25`, `:43`, `:72`, `:104` y `:149`. | ídem |
| `admin-administrar-usuarios` | La fila «No se desactiva a uno mismo» cita `:259`, que es la rama JSON (403), pero informa «HTTP 302». El 302 sale de `:261`. | `UsuarioWebController.php:257-262` |
| `admin-reportes-sueldos` | «**Evolución: cuotas y sueldos**, **Por deporte** y **Por profesor**» | `reportes/sueldos.blade.php:50`, `:51`, `:53` |
| `admin-reportes-sueldos` | «Wings no exporta este reporte a Excel ni a otro archivo» | Las únicas rutas de reportes son tres `GET` sin exportación (`routes/web.php:134-136`) |
| `admin-reportes-alumnos` | «**Pases a Inactivo del mes** (cada alumno cuenta una vez…)» | `reportes/alumnos.blade.php:35` |
| `admin-reportes-alumnos` | «**Evolución de asistencia**, **Activos por deporte**, **Activos por nivel** y **Asistencia por nivel**» | `reportes/alumnos.blade.php:38`, `:45`, `:56`, `:57` |
| `admin-reportes-alumnos` | Qué hago si: «el acceso **Sin asistencia** las lista» y «traban la liquidación» | `layouts/ds-app.blade.php:50`. La traba la cito de memoria de otras fichas; no la comprobé en esta vuelta. |
| `admin-corregir-movimiento-cashflow` | «Eliminar un movimiento», pasos 1 a 3: **Eliminar** en la fila, la pantalla de confirmación con **Motivo (opcional)**, **Eliminar** y **Cancelar** | `CashflowWebController.php:215`; `cashflow/eliminar.blade.php:17`, `:24-25` |
| `admin-corregir-movimiento-cashflow` | La fila de «Motivo (opcional)» cita la regla de validación y no la etiqueta. La etiqueta existe. | `cashflow/movimiento.blade.php:175` |
| `admin-configurar-mostrador` | Pasos 2 y 3: **Efectivo del mostrador**, **Medio de efectivo**, «Solo aparecen cuentas activas», **Guardar**. «Medio de efectivo configurado.» figura solo en la tabla de rechazos. | `caja/configuracion.blade.php:3`, `:14`, `:31`; `CajaWebController.php:44` (`where('activo', true)`) |
| `admin-programar-clases` | «Crear una clase o una serie», pasos 1 a 4: **Nuevo**, **Clase única** y **Clases recurrentes** con sus campos, **Grupo**, **Profesores**, **Guardar** y «12 clase(s) creada(s)». También el **Editar** de la tarjeta del listado (paso 1 de Editar). | `clases/create.blade.php:23, 26, 55, 66, 78, 103, 108, 128, 176, 216`; `ClaseWebController.php:230`; `clases/_card.blade.php:99-104` |

En todos estos casos **la página dice lo que Wings hace**: lo comprobé en las líneas de la tercera columna. Lo que falta es la fila de la ficha, no hay un error en la página. Por eso el #18 sigue como medio, igual que en las dos vueltas anteriores, y no sube.

---

## 3. Hallazgos nuevos

| # | Gravedad | Página:línea | Qué dice la página | Qué hace Wings (archivo:línea) |
|---|---|---|---|---|
| N7 | Media | Ficha `fuentes/profesor-tomar-asistencia.md:22` (página profesor/tomar-asistencia.md:60-63) | La página cambió en esta vuelta. Ahora dice que hay dos avisos distintos, que un Moroso que ya pagó **no** pide motivo, que el exceso de plan **no** frena el guardado, y cita «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.», «Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.» y «Excede el plan — indicar motivo». | La ficha no se actualizó (último commit `120ae713`; sin cambios en el árbol de trabajo). Su fila sigue diciendo «Qué hago si: alumno moroso o con exceso… abre campo de motivo de admisión» y que fue «Ejecutado: para alumnos con cuotas impagas vencidas, se expande el panel de excepción». Cita `ExcepcionCobranzaService.php` sin línea. Eso contradice la página y el código: el Moroso que no es nuevo no pide motivo (`ExcepcionCobranzaService.php:41-42`) y el exceso es otro panel que no exige nada (`show.blade.php:498-513`; `ClaseWebController.php:471-473`). Los tres textos nuevos no tienen fila de fuente, y la ficha no tiene la tabla «Rechazos y avisos de la acción». Es el mismo tipo de defecto que el #18, pero peor, porque la fila afirma algo que hoy es falso como «ejecutado». |
| N8 | Menor | profesor/tomar-asistencia.md:49 y :58 | «Si intentás registrar asistencia, Wings bloquea la acción informando «La clase está cancelada. No se pueden registrar asistencias.»». Y también: «la casilla **Presente** estará deshabilitada y Wings mostrará «La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.»». | Esos dos textos son las respuestas JSON del controlador (`ClaseWebController.php:360`, `:367`), y el profesor no llega a verlas desde la pantalla. Con la clase cancelada, **Guardar** está `disabled` (`show.blade.php:553`) y las tarjetas tienen `pointer-events:none` (`:439`). Con una clase futura, **Guardar** ni aparece (`:536`). Lo que el profesor ve es el cartel de arriba: «Esta clase está cancelada. No se pueden registrar asistencias.» y «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.» (`show.blade.php:390`, `:394`). Cambia una palabra, «La» por «Esta». El control automático no lo detecta porque el texto de la página existe en el controlador. |
| N9 | Menor | admin/administrar-grupos.md:53 | «También podés sacar una frecuencia desde la ficha del grupo (**Ver**), con **Eliminar** en su fila. Ahí Wings avisa si…». | Antes de enviar, Wings pide confirmar con «¿Eliminar este plan?» (`grupos/show.blade.php:132`). Si sale bien, muestra «Plan eliminado.» (`GrupoWebController.php:335`). La página no nombra ninguno de los dos. Además, en esa pantalla Wings llama «plan» a lo que la página llama «frecuencia». El lector sale solo, pero el punto 8 pide nombrar el panel de confirmación. |

**Releídas sin otros hallazgos:**
- **admin/configurar-mostrador:** las líneas 9-36 contra `caja/configuracion.blade.php:9-33`, `CajaWebController.php:41-57` y `CajaService.php:54`. El `@if($hayCajaAbierta)` / `@unless` coincide en los dos sentidos con la línea 33. «Tipos de Caja» es el nombre del menú (`ds-app.blade.php:247`).
- **admin/programar-clases:** creación, edición (`ClaseWebController.php:527-594`), cancelación (`:596-716`), eliminación y serie (`:796-850`), y el `@if` de **Editar** en la ficha (`show.blade.php:133`) y en la tarjeta (`_card.blade.php:99`). «Cancelar esta» y «Cancelar serie» solo para el dueño (`show.blade.php:274-285`). «Motivo del cambio de horario» (`clases/edit.blade.php:81`).
- **admin/administrar-grupos:** `store` y `update` (`GrupoWebController.php:47-218`), `toggleActivo` (`:239-275`) y `destroyPlan` (`:314-337`).
- **profesor/tomar-asistencia:** pasos 1-3 y los Qué hago si de las líneas 45-56, contra `ClaseWebController.php:304-420` y `clases-show.js:58-110`.

---

## 4. Conteo final de lo abierto

- **Graves abiertos:** 0
- **Medios abiertos:** 2
  - #18, a medias: faltan filas de fuente por paso en seis fichas (ver el detalle de la sección 2).
  - N7: la ficha de `profesor-tomar-asistencia` contradice la página corregida y no respalda sus tres textos nuevos.
- **Menores abiertos:** 2, N8 y N9, los dos nuevos. N1 a N6 están resueltos.

Ninguno de los cuatro abiertos hace que el lector no pueda hacer lo que la página indica. Los dos medios son de las fichas de fuentes, no de lo que lee el usuario.

---

## 5. Comprobado e inferido

**Lo comprobé leyendo el código o la vista, sin ambigüedad:**
- Todas las líneas que citan las fichas de las cuatro páginas del #18: imprimí cada una y la comparé con la fila.
- Los huecos que listo en la sección 2: el elemento existe en la línea indicada y la ficha no tiene la fila.
- N2 a N6: comparé la condición del código con la página en los dos sentidos.
- N7: el contenido de la ficha, su historial en git y la regla del servicio.
- N8: el `@if` del cartel, el `disabled` de **Guardar** y el `@if` que lo oculta.
- N9: el `data-confirmar-envio` y el mensaje de éxito.
- N1: miré la captura `configurar-mostrador-paso1.png`. Es la pantalla del admin, ya configurada, con **Guardar** y sin el aviso naranja.
- El control automático: lo corrí y dio 0.

**Inferido, sin ejecutar:**
- N6 y la línea 51 de administrar-grupos: que borrar una frecuencia que tuvo alumnos termina en la página de error sale de la FK `restrict` y de `$plan->delete()` / `->delete()`. No lo ejecuté.
- N8: que el profesor no puede disparar el POST con la clase cancelada sale del `disabled` y del `pointer-events:none`. No lo probé en un navegador. Forzándolo por fuera de la pantalla, sí recibiría el texto que cita la página.
- #18, la fila «traban la liquidación» de reportes-alumnos: no busqué en esta vuelta el `if` de la liquidación que lo decide.
- Que el dueño ve las excepciones «en Inicio y en el resumen diario» (tomar-asistencia:62): solo confirmé que `admin/dashboard.blade.php` trabaja con excepciones y que la frase es el texto literal de la vista (`show.blade.php:491`). No revisé el resumen diario.
