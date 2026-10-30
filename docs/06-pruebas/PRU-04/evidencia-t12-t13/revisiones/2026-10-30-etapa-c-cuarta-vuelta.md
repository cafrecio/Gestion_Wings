# Revisión independiente del manual: etapa C, cuarta vuelta

> **Fecha:** 30/10/2026
> **Revisor:** Claude, subagente revisor independiente. No escribió estas páginas, sus fichas ni las revisiones anteriores.
> **Protocolo:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`. Puntos 2 y 6 aplicados a lo abierto: abrí cada línea que cita la ficha y la comparé con lo que afirma la fila. Busqué cada `if` sin partir de la ficha y lo comparé con la página en los dos sentidos.
> **Base:** lo que quedó abierto en `revisiones/2026-10-30-etapa-c-tercera-vuelta.md`: el medio #18 y el medio N7, más los menores N8 y N9.
> **Páginas releídas completas:** profesor/tomar-asistencia y admin/administrar-grupos. Para el #18 leí las seis fichas y sus páginas: administrar-usuarios, reportes-sueldos, reportes-alumnos, corregir-movimiento-cashflow, configurar-mostrador y programar-clases.
> **Herramientas:** leí el código con Read, Grep y sed. Según el aviso del índice, codebase-memory no tenía cobertura confirmada para estos archivos, así que no saqué ninguna conclusión de él. No corrí la suite ni toqué ninguna base. El único archivo que escribí es este informe.

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
| 18 | Media | **A medias** | Fichas de usuarios, reportes-sueldos, reportes-alumnos, corregir-movimiento-cashflow, configurar-mostrador y programar-clases | **Se resolvió:** la fila «No se desactiva a uno mismo» (`fuentes/admin-administrar-usuarios.md:17`) cita ahora `UsuarioWebController.php:261`, que es `return back()->with('error', 'No podés inactivarte a vos mismo.')`, la rama 302. Ahora coincide con «HTTP 302». Todos los pasos que la tercera vuelta listó tienen fila: las fichas ganaron filas para Editar/Guardar, (vos), los gráficos, la exportación, Pases a Inactivo, Sin asistencia, la pantalla de Eliminar del cashflow, los pasos 2 y 3 del mostrador y los pasos 1 a 4 de Crear clase. **Sigue abierto:** varias filas nuevas juntan varias afirmaciones y citan **una sola** línea, que respalda solo la primera. Una fila de programar-clases cita una línea de otro método. Ver el detalle abajo. En todos los casos la **página** dice lo que Wings hace (lo comprobé): el defecto está en la ficha. |
| N7 | Media | **Resuelto** | `fuentes/profesor-tomar-asistencia.md` | La ficha se regeneró. Desapareció la fila falsa («moroso o con exceso… abre campo de motivo», «ejecutado para alumnos con cuotas impagas vencidas»). Hay filas nuevas que coinciden con el código: Deudor con historia pide motivo (`ExcepcionCobranzaService.php:42`); nuevo con menos de 2 presentes no lo pide (`:41`); los de la primera carga no son nuevos (`:39`); el exceso de plan no obliga (`show.blade.php:505`); sin motivo la pantalla no guarda (`clases-show.js:97`). Las dos filas «Ejecutado» corresponden a casos reales de `etapa-c/caminos-prohibidos.json:143-162`: 422 con «Tiene deuda vencida…» y 200 con «Asistencias guardadas.». Ahora la ficha tiene la tabla «Rechazos y avisos». Sus 10 filas apuntan a líneas que contienen el texto literal (`ClaseWebController.php:376, :512`; `ExcepcionCobranzaService.php:47, :48`; `clases-show.js:80, :97`; `show.blade.php:390, :394, :505, :541`). Imprimí y comparé las 17 líneas que cita. |
| N8 | Menor | **Resuelto** | profesor/tomar-asistencia.md:49 y :58 | La página cita ahora los carteles que ve la profesora: «Esta clase está cancelada. No se pueden registrar asistencias.» (`show.blade.php:388-391`, `@if($clase->cancelada)`) y «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.» (`:392-395`, `@elseif($todaviaNoOcurrio)`). «Guardar deshabilitado» coincide con `:553`. «Presente deshabilitada» coincide con `:484`. La ficha cita `:390` y `:484`. |
| N9 | Menor | **Resuelto** | admin/administrar-grupos.md:53 | La página dice: «pide confirmar «¿Eliminar este plan?» y, si se puede, muestra «Plan eliminado.»». Coincide con `grupos/show.blade.php:132` (`data-confirmar-envio`) y `GrupoWebController.php:335`. La ficha los lista en `fuentes/admin-administrar-grupos.md:26-27`. |

### Detalle de lo que sigue abierto en el #18

| Ficha:línea | Qué afirma la fila | Qué dice la línea citada | Lo que falta citar (comprobado) |
|---|---|---|---|
| `admin-administrar-usuarios.md:8` | «Editar y Guardar» | `edit.blade.php:18`: solo **Guardar** | **Editar** de la tarjeta: `usuarios/index.blade.php:89` |
| `admin-administrar-usuarios.md:9` | «(vos) y sin interruptor» | `index.blade.php:58`: solo «(vos)» | Sin interruptor: `index.blade.php:90-99` (`@if($esSelf)` pone el candado «Tu cuenta») |
| `admin-administrar-usuarios.md:11` | Siete campos de los pasos 2 a 4 | `_form.blade.php:163`: solo «Profesor vinculado» | `_form.blade.php:11, :25, :43, :72, :104, :149` (siguen sin citar, igual que en la tercera vuelta) |
| `admin-reportes-sueldos.md:8` | Evolución, Por deporte, Por profesor | `sueldos.blade.php:50`: solo «Evolución: cuotas y sueldos» | `:51` «Por deporte», `:53` «Por profesor» |
| `admin-reportes-sueldos.md:10` | «pestaña Sueldos, mes y deporte, Filtrar» | `ReporteWebController.php:13`: firma del método `sueldos(...)` | `sueldos.blade.php:19` (pestañas), `:22-23` (mes, deporte), `:24` (**Filtrar**) |
| `admin-reportes-sueldos.md:11` | Cuatro indicadores | `sueldos.blade.php:31`: solo «Costo por asistencia» | `:28`, `:29`, `:30` |
| `admin-reportes-sueldos.md:12` | «Pagos de operativas y Total pagado» | `sueldos.blade.php:33`: solo «Pagos de operativas» | `:35` «Total pagado» |
| `admin-reportes-alumnos.md:9` | Gráficos de evolución, deporte y nivel | `alumnos.blade.php:38`: solo «Evolución de asistencia» | `:45`, `:56`, `:57` |
| `admin-reportes-alumnos.md:11` | «pestaña Alumnos, mes y deporte, Filtrar» | `ReporteWebController.php:29`: firma del método | `alumnos.blade.php:19`, `:22-24` |
| `admin-reportes-alumnos.md:12` | Cuatro indicadores | `alumnos.blade.php:31`: solo «Clases sin registros» | `:28`, `:29`, `:30` |
| `admin-reportes-alumnos` (sin fila) | Qué hago si: «traban la liquidación de los profesores» | — | `LiquidacionService.php:158-161`. Ver el menor N11. |
| `admin-corregir-movimiento-cashflow.md:8` | «Motivo (opcional), Eliminar y Cancelar» | `eliminar.blade.php:17`: solo la etiqueta Motivo | `eliminar.blade.php:24` (**Cancelar**), `:25` (**Eliminar**) |
| `admin-corregir-movimiento-cashflow.md:10` | «Motivo (opcional)» del paso 3 de Editar | `CashflowCorreccionService.php:35`: la regla `max:500` | La etiqueta: `cashflow/movimiento.blade.php:175` |
| `admin-configurar-mostrador.md:8` | «Paso 2: Medio de efectivo, solo cuentas activas» | `CajaWebController.php:44`: el filtro `activo` | Título «Efectivo del mostrador» `caja/configuracion.blade.php:3` y etiqueta «Medio de efectivo» `:14` |
| `admin-programar-clases.md:9` | «Clase única o Clases recurrentes con horario por día» | `create.blade.php:26`: solo «Clases recurrentes» | `:23` «Clase única», `:38` Fecha, `:55` Fecha desde, `:66` Fecha hasta, `:78` Días de la semana, `:103/:108` y `:152/:165` Hora inicio/fin |
| `admin-programar-clases.md:10` | «Paso 3: solo profesoras activas del deporte» | `ClaseWebController.php:163`: está en `index()` (`:28-170`), **no** en `create()`. Además, filtra solo `activo`, no el deporte. | Lo que ve el lector: `create()` `:181` (activas), el filtro por deporte en la pantalla `resources/js/clases-form.js:72-75` y el rechazo del servidor en `:269-273`. Tampoco tienen fila **Grupo** `create.blade.php:128` ni **Profesores** `:180`. |
| `admin-administrar-grupos.md` (sin fila) | Fuera de la lista del #18, pero lo vi al releer: los pasos de «Crear un grupo» (Nuevo, Deporte, Nivel, Precios por frecuencia, Guardar) y de «Cambiar un precio» (Editar, Nuevo, Guardar) solo tienen fila para los mensajes de éxito. La fila «Eliminar en la ficha del grupo» cita un mensaje de rechazo (`GrupoWebController.php:329`), no el botón. | — | `grupos/index.blade.php:46` (Nuevo), `:116` (Editar), `:121` (Ver); `_form.blade.php:17`, `:40`, `:75`, `:86`; `create.blade.php:15` y `edit.blade.php:16` (Guardar); `show.blade.php:128-142` (Eliminar) |
| `profesor-tomar-asistencia` (sin fila) | Paso 1.2 «el listado de tus clases programadas para hoy» y paso 2 casilla **Presente** | — | Presente: `show.blade.php:478` |

Las filas de la exportación de sueldos (`routes/web.php:136`, el bloque de `:133-135` solo tiene `GET`), de Pases a Inactivo (`alumnos.blade.php:35`) y de Sin asistencia (`ds-app.blade.php:50`) dicen lo que afirman. También las de cashflow «Editar abre el mismo formulario» (`CashflowCorreccionWebController.php:28`) y «Editar / Historial / Eliminar» (`CashflowWebController.php:211`, que es la condición; los nombres están en `:213-215`), la de mostrador «Configurar» (`caja/index.blade.php:48`) y las 15 filas restantes de programar-clases.

---

## 3. Hallazgos nuevos

| # | Gravedad | Página o ficha:línea | Qué dice | Qué hace Wings (archivo:línea) |
|---|---|---|---|---|
| N10 | Menor | Ficha `fuentes/admin-programar-clases.md:41` | La fila «No se puede eliminar:» cita `ClaseWebController.php:817`. | Esa línea es «No se puede eliminar **la serie**: la clase del {fecha} no es eliminable…». El texto que cita la página (programar-clases.md:60) está en `resources/views/clases/show.blade.php:180`. **Es una regresión del N3**, que la tercera vuelta dio por resuelto con `show.blade.php:180`. Las fichas se regeneraron con `etapa-c/generar-fichas.py` (fecha de archivo 03:57, posterior a la tercera vuelta, 03:56). La función `donde()` (`generar-fichas.py:28-43`) le quita los dos puntos finales al texto y toma la primera aparición en los archivos preferidos. Así «No se puede eliminar» cae en `:817`. Lo mismo puede pasar con cualquier mensaje corto que termine en «:». |
| N11 | Menor | admin/reportes-alumnos.md:84 | «Son clases que ya pasaron sin lista. Además de dejar el reporte incompleto, **traban la liquidación** de los profesores.» | Una clase sin lista no traba la liquidación: queda afuera. `LiquidacionService.php:158-161` solo liquida clases `validada_para_liquidacion` o con algún presente. La liquidación se calcula igual, sin pagar esa clase. El menú lo dice bien: «Clases que ya pasaron sin lista tomada: no se pueden liquidar» (`ds-app.blade.php:49`). Es un problema de redacción: el dueño puede creer que no va a poder liquidar. |

**Releídas sin otros hallazgos:**

- **profesor/tomar-asistencia (página completa).** Comprobé cada condición en los dos sentidos:
  - Clase cancelada: `show.blade.php:388`, `:439`, `:553`; la reactivación solo la hace el dueño (`ClaseWebController.php:677-682`).
  - Clase futura: `show.blade.php:392`, `:484`, `:536`.
  - Corrección con motivo «si ya pasó y ya tenía lista»: `ClaseWebController.php:390-392` y `:338`, con «Indicá el motivo de la corrección.» en `clases-show.js:78-80`.
  - Una clase de hoy no pide motivo: `fecha->lt(today())`.
  - Liquidación cerrada: `:374-383`. Se controla antes que la corrección.
  - Regla del motivo de admisión: `ExcepcionCobranzaService.php:39-48`. Nuevo con 2 presentes o más; si no es nuevo, solo Deudor (Moroso queda afuera); los de la primera carga no son nuevos; los dos textos dependen de `$nuevo`.
  - Exceso: aparece cuando el presente supera el plan de la semana (`clases-show.js:39-41`). Empieza en EXTRA (`show.blade.php:511`) y no frena el guardado.
  - «El dueño lo ve en Inicio y en el resumen diario»: es el texto de `show.blade.php:491`.
- **admin/administrar-grupos (página completa).**
  - Un grupo por deporte y nivel: `GrupoWebController.php:55-57` y `:70`. El mismo texto también aparece en vivo y deshabilita Guardar (`grupos.js:31-33`, `_form.blade.php:67`).
  - Frecuencias de 1 a 5: `_form.blade.php:96`. Se puede quitar una fila (`grupos.js:65-68`).
  - Aviso de cuándo rige el precio: `_form.blade.php:80-81`.
  - El deporte y el nivel no cambian al editar: `update` no los toca.
  - Una frecuencia con alumnos activos no se borra, sin aviso: `:182-188`.
  - Al menos una frecuencia: `:59`, `:63`.
  - Eliminar desde la ficha del grupo: `destroyPlan` `:314-337`, con los controles en el mismo orden que la página.
  - No se desactiva con alumnos activos o clases futuras: `:243-266`.
  - Un grupo inactivo no se ofrece al dar de alta alumnos (`AlumnoWebController.php:322`) ni al crear clases (`ClaseWebController.php:175`).
  - El listado muestra también los grupos inactivos (`GrupoWebController.php:22-33`, sin filtro `activo`), así que el lector puede «buscarlo y activarlo».

---

## 4. Conteo final de lo abierto

- **Graves abiertos:** 0
- **Medios abiertos:** 1
  - #18, a medias. Ya no hay pasos sin ninguna fila en las seis fichas pedidas, y se corrigió la cita de «No se desactiva a uno mismo». Pero 15 filas citan una sola línea que respalda solo parte de lo que afirman. La fila del paso 3 de programar-clases cita una línea de `index()` que no decide el filtro por deporte. La ficha de administrar-grupos tiene el mismo hueco en sus pasos.
- **Menores abiertos:** 2, los dos nuevos. N10 es una regresión del N3 que causó el generador de fichas. N11 es la redacción de «traban la liquidación».
- **Resueltos en esta vuelta:** N7 (medio), N8 y N9 (menores).

Ningún abierto deja al lector sin poder hacer lo que indica la página. El medio y el N10 están en las fichas de fuentes, no en lo que lee el usuario.

---

## 5. Comprobado e inferido

**Comprobado leyendo el archivo en la línea:**
- Cada línea que citan las fichas de usuarios, reportes-sueldos, reportes-alumnos, corregir-movimiento-cashflow, configurar-mostrador, programar-clases, tomar-asistencia y administrar-grupos, tanto en la tabla principal como en la de rechazos. Imprimí cada una y la comparé con su fila.
- Las líneas de la columna «Lo que falta citar»: el elemento existe ahí.
- Que `ClaseWebController.php:163` está dentro de `index()` (`:28`) y no de `create()` (`:172`).
- N10: el comportamiento de `donde()` en `generar-fichas.py:28-43` y la fecha de los archivos (`ls -la`).
- N11: la condición de `LiquidacionService.php:158-161` y el `title` del menú.
- Los casos ejecutados que cita la ficha de tomar-asistencia: están en `caminos-prohibidos.json` con el HTTP y el texto que dice la fila. **No** volví a correr `CapturasEtapaCTest`.
- El control automático: lo corrí y dio 0.

**Inferido, sin ejecutar:**
- administrar-grupos:51 y :53. Que borrar una frecuencia que tuvo alumnos termina en una página de error sale de la FK `restrict` (`create_alumno_planes_table.php:17`) y del `delete()`. No lo ejecuté. Lo mismo dijo la tercera vuelta.
- N10: que la causa es la regeneración de la ficha y no una edición a mano. Lo saco de la fecha del archivo y de la lógica del script, no de un historial de git, porque la ficha no está versionada.
- N11: que «traban» confunde al dueño es un juicio de redacción. Lo que hace el código, en cambio, está comprobado.
- No miré las capturas en esta vuelta: ninguno de los puntos abiertos las involucraba.
