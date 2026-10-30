# Revisión independiente del manual: etapa C, quinta vuelta

> **Fecha:** 30/10/2026
> **Revisor:** Claude, subagente revisor independiente. No escribí estas páginas, sus fichas, el generador ni las revisiones anteriores.
> **Protocolo:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`, puntos 2 y 6, aplicados a lo que quedó abierto. Abrí cada archivo en cada línea que cita cada fila mencionada en la cuarta vuelta. Después comprobé si, entre todas sus citas, la fila respalda **todo** lo que afirma.
> **Base:** lo abierto en `revisiones/2026-10-30-etapa-c-cuarta-vuelta.md`: el medio #18 (15 filas con una sola cita, el paso 3 de programar-clases y las filas sin respaldo de administrar-grupos, tomar-asistencia y reportes-alumnos) y los menores N10 y N11.
> **Herramientas:** leí con Read, sed y awk, y busqué con grep. El índice codebase-memory avisó que no tenía cobertura confirmada para estos archivos, así que no saqué conclusiones de él. Escribí un barrido en el scratchpad de la sesión, fuera del repo, que compara cada cita de las 23 fichas de la etapa C con la línea real del archivo. No corrí la suite ni toqué ninguna base. El único archivo que escribí en el repo es este informe.

---

## 1. Controles automáticos

```
php scripts/manual/verificar-manual.php
59 páginas revisadas, 0 problemas.   (código de salida 0)
```

**Barrido de citas.** Revisé las 23 fichas que genera `etapa-c/generar-fichas.py`: 241 citas en total, de las cuales 72 son mensajes de la tabla «Rechazos y avisos».
- En la tabla principal, la «Condición en el código» de cada fila es idéntica a la línea real de `archivo:línea`, una vez normalizados los espacios.
- En la tabla de rechazos, cada mensaje aparece en la línea citada.
- Resultado: **0 diferencias**.

Esto prueba que las fichas están al día con el código. **No** prueba que la línea elegida respalde lo que afirma la fila. Eso lo revisé a mano en la sección 2.

---

## 2. Estado de lo que quedó abierto

### 2.1 Las 15 filas del #18 (los números de fila son los de la ficha actual)

| Ficha:línea | Qué afirma | Citas actuales | Estado | Comprobación |
|---|---|---|---|---|
| `admin-administrar-usuarios.md:8` | Editar y Guardar | `usuarios/index.blade.php:88`, `edit.blade.php:18` | **Resuelto** | `:88` es el enlace **Editar** de la tarjeta y `edit.blade.php:18` es el botón **Guardar**. |
| `admin-administrar-usuarios.md:9` | Tarjeta propia: (vos) y sin interruptor | `index.blade.php:58`, `index.blade.php:57` | **A medias** | `:58` es «(vos)» y `:57` es el `@if($esSelf)` que lo envuelve (`:57-59`). Ese `@if` solo decide el «(vos)»: **no** decide el interruptor. Lo que reemplaza el interruptor por el candado «Tu cuenta» es el **otro** `@if($esSelf)`, en `:90-99`, con el `x-ds.toggle` en la rama `@else` (`:100-108`). El generador buscó el texto `@if($esSelf)` y tomó la primera aparición (ver N12). La página (`administrar-usuarios.md:56`) dice lo correcto. |
| `admin-administrar-usuarios.md:11` | Pasos 2 a 4: siete campos | `_form.blade.php:13, :27, :45, :74, :106, :151, :163` | **Resuelto** | Cada línea tiene el texto visible de su etiqueta: Nombre, Email, Contraseña, Confirmar contraseña, Rol, CBU o alias y Profesor vinculado. |
| `admin-reportes-sueldos.md:8` | Gráficos Evolución, Por deporte, Por profesor | `sueldos.blade.php:50, :51, :53` | **Resuelto** | Son los tres títulos. |
| `admin-reportes-sueldos.md:10` | Abrir: pestaña Sueldos, mes y deporte, Filtrar | `ReporteWebController.php:13` | **No resuelto** | Sigue citando solo la firma de `sueldos(...)` (generador `:153`, una sola cita). Faltan `sueldos.blade.php:19` (pestañas, `<strong aria-current="page">Sueldos</strong>`), `:22` (Mes), `:23` (Deporte) y `:24` (**Filtrar**). La página dice esto en `reportes-sueldos.md:18-19`. |
| `admin-reportes-sueldos.md:11` | Cuatro indicadores | `sueldos.blade.php:28, :29, :30, :31` | **Resuelto** | Son los cuatro rótulos. |
| `admin-reportes-sueldos.md:12` | Pagos de operativas y Total pagado | `sueldos.blade.php:33, :35` | **Resuelto** | — |
| `admin-reportes-alumnos.md:9` | Gráficos de evolución, deporte y nivel | `alumnos.blade.php:38, :45, :56, :57` | **Resuelto** | Son los títulos Evolución de asistencia, Activos por deporte, Activos por nivel y Asistencia por nivel. Coinciden con `reportes-alumnos.md:32`. |
| `admin-reportes-alumnos.md:11` | Abrir: pestaña Alumnos, mes y deporte, Filtrar | `ReporteWebController.php:29` | **No resuelto** | Sigue citando solo la firma de `alumnos(...)` (generador `:145`). Faltan `alumnos.blade.php:19` (pestañas), `:22`, `:23` y `:24` (**Filtrar**). |
| `admin-reportes-alumnos.md:12` | Cuatro indicadores | `alumnos.blade.php:28, :29, :30, :31` | **Resuelto** | — |
| `admin-corregir-movimiento-cashflow.md:8` | Eliminar: Motivo (opcional), Eliminar y Cancelar | `eliminar.blade.php:17, :24, :25` | **Resuelto** | En ese orden: la etiqueta «Motivo (opcional)», **Cancelar** y **Eliminar**. |
| `admin-corregir-movimiento-cashflow.md:10` | «Motivo (opcional), hasta 500 caracteres» | `CashflowCorreccionService.php:35` | **A medias** | La fila se reescribió para que diga lo que dice la regla (`nullable\|string\|max:500`), y en eso coincide. Pero queda sin cita lo que afirma la **página**, el campo **Motivo (opcional)** del paso 3 de Editar (`corregir-movimiento-cashflow.md:22`): la etiqueta está en `cashflow/movimiento.blade.php:175`. Además, la ficha dice «hasta 500 caracteres» y la página no lo dice en ningún lugar (`:22`, `:34`). La fila atribuye a la página algo que la página no afirma. |
| `admin-configurar-mostrador.md:8` | Paso 2: pantalla, Medio de efectivo, solo cuentas activas | `configuracion.blade.php:2, :14`, `CajaWebController.php:44` | **Resuelto**, con una observación | `:14` es la etiqueta «Medio de efectivo» y `:44` es el filtro `activo`. `:2` es `@section('title', ...)`, el título de la **pestaña del navegador** (`ds-app.blade.php:7`). El encabezado que ve el lector es `:3`, `@section('module-title', 'Efectivo del mostrador')`, que se dibuja en `ds-app.blade.php:355-356`. El texto es el mismo, así que la fila queda respaldada. Es otro caso de «primera aparición» (N12). |
| `admin-programar-clases.md:9` | Paso 2: Clase única o Clases recurrentes con horario por día | `create.blade.php:23, :26, :55, :66, :78, :103` | **A medias** | Citadas: los dos tipos, Fecha desde, Fecha hasta, Días de la semana y **Hora inicio** de cada día. Falta **Hora fin** de cada día (`:108`), así que «horario por día» queda cubierto a medias. También faltan los campos de **Clase única** que nombra la página (`programar-clases.md:20`): **Fecha** `:38`, **Hora inicio** `:152` y **Hora fin** `:165`. |
| `admin-programar-clases.md:10` | Paso 3: solo profesoras activas del deporte; la pantalla filtra por deporte y el servidor rechaza otras | `resources/js/clases-form.js:8`, `ClaseWebController.php:273` | **A medias** | Se fue la cita equivocada a `index():163`. `:273` es el mensaje del rechazo del servidor, dentro de `bloquearProfesoresActivos()` (`:267-275`); la condición está en `:269-270`. Esa parte queda respaldada. Pero `clases-form.js:8` es `const deporteInfo = document.getElementById('deporte-info');`, una declaración que **no** filtra nada. El filtro por deporte de la pantalla está en `clases-form.js:72-77`. El generador buscó la palabra `deporte` y tomó la primera aparición (generador `:161`; ver N12). Tampoco se cita que la pantalla solo ofrece profesoras activas (`create()`, `ClaseWebController.php:181`), ni los campos **Grupo** (`create.blade.php:128`) y **Profesores** (`:180`) que nombra el paso 3 (`programar-clases.md:22`). |

### 2.2 Filas sin respaldo que la cuarta vuelta listó aparte

| Ficha | Qué afirma la página | Estado | Comprobación |
|---|---|---|---|
| `admin-administrar-grupos.md:8` (Crear) | Nuevo, Deporte, Nivel, Precios por frecuencia, Guardar | **A medias** | `index.blade.php:46` (Nuevo), `_form.blade.php:17` (Deporte), `:40` (Nivel) y `create.blade.php:15` (Guardar) están bien. Pero «Precios por frecuencia» cita `_form.blade.php:70`, que es el comentario Blade `{{-- Precios por frecuencia --}}` y no se ve en pantalla. El rótulo visible está en `:75`. Es otra vez la primera aparición (N12). |
| `admin-administrar-grupos.md:9` (Cambiar un precio) | Editar, Nuevo, Guardar | **Resuelto** | `index.blade.php:116` (Editar), `_form.blade.php:85` (`id="btn-add-plan"`; el texto **Nuevo** está en `:86`) y `edit.blade.php:16` (Guardar). |
| `admin-administrar-grupos.md:11` | Eliminar en la ficha del grupo | **No resuelto** | Sigue citando el mensaje de rechazo `GrupoWebController.php:329`. No cita el botón **Eliminar** de la fila del plan (`grupos/show.blade.php:140`, en el formulario `:131-142`), ni **Ver**, que lleva a la ficha (`grupos/index.blade.php:121`). La página dice esto en `administrar-grupos.md:53`. |
| `profesor-tomar-asistencia.md` | Paso 1.2: «el listado de tus clases programadas para hoy». Paso 2: casilla **Presente**. | **No resuelto** | La fila `:9`, «Marcar Presente y Guardar», cita solo `show.blade.php:552` (`btn-guardar-asistencias`). La casilla **Presente** (`show.blade.php:478-485`) sigue sin cita. El paso 1.2 no tiene fila. En el código lo respalda `ClaseWebController.php:36-37` y `:49-51`: las clases de hoy (`whereDate('fecha', $hoy)`), separadas en `misClasesHoy` para la profesora. |
| `admin-reportes-alumnos.md` | «quedan afuera de la liquidación… hasta que se cargue la lista» (`reportes-alumnos.md:41`) | **No resuelto** en la ficha | La página se corrigió (ver N11), pero la ficha sigue sin una fila para esta afirmación. Lo mismo en `admin-inicio-dueno.md`: la fila «Para revisar» cita solo `admin/dashboard.blade.php:44`, que no dice nada de la liquidación, y la página afirma lo mismo en `inicio-dueno.md:35`. La fuente sería `LiquidacionService.php:158-161`. |

### 2.3 Menores

| # | Estado | Comprobación |
|---|---|---|
| N10 | **Resuelto** | `fuentes/admin-programar-clases.md:41` cita ahora `resources/views/clases/show.blade.php:180`, que dice literalmente `No se puede eliminar: {{ $motivoNoEliminable }}`, dentro del `@if` de `:178`. Es el texto que cita la página (`programar-clases.md:60`). La causa está corregida en el generador: `donde()` (`generar-fichas.py:28-33`, con `_donde()` en `:35-50`) ya no quita los dos puntos finales, solo prueba con y sin el punto final. Busqué `No se puede eliminar:` con los dos puntos en los archivos que el generador revisa antes: en `ClaseWebController.php` y en las vistas `index`, `create` y `_card` de clases no aparece. Por eso ahora cae en `show.blade.php:180`. |
| N11 | **Resuelto** en las páginas | En `resources/manual/` ya no aparece «traban la liquidación». La única «traba» que queda es `operativo/tomar-lista-mostrador.md:34` («no traba el guardado»), que habla de otra cosa. Ahora dice: `reportes-alumnos.md:41`, «quedan afuera de la liquidación de los profesores hasta que se cargue la lista», e `inicio-dueno.md:35`, «quedan afuera de la liquidación hasta que se cargue la lista». Coincide con `LiquidacionService.php:158-161`: entran las clases no canceladas que están `validada_para_liquidacion` o tienen algún presente. La clase sin lista no frena la liquidación: queda afuera. **Observación, no hallazgo:** «hasta que se cargue la lista» simplifica dos cosas. La clase también entra si el dueño la valida a mano (botón **Validar**, `clases/show.blade.php:133-145`, `ClaseWebController.php:788`), y una lista con todos ausentes no la hace entrar (`:161` pide al menos un presente). Además, si la liquidación ya existe, hay que **Recalcular**, cosa que el manual dice en `recalcular-ajustar-liquidacion.md:22`. Nada de esto deja al lector haciendo algo equivocado. |

---

## 3. Hallazgos nuevos

| # | Gravedad | Dónde | Qué pasa | Comprobación |
|---|---|---|---|---|
| N12 | Menor | `etapa-c/generar-fichas.py:21-26` (`linea()`) | Es la misma clase de defecto que el N10, pero en la tabla principal. `linea()` devuelve la **primera** línea del archivo que contiene el texto buscado. Cuando el texto aparece varias veces, la cita puede caer en una línea que no es la que respalda la fila. | Lo encontré en cuatro filas: usuarios `:9` (`@if($esSelf)` cae en `:57` y no en `:90`), programar-clases `:10` (`deporte` cae en `clases-form.js:8` y no en `:72-77`), grupos `:8` (`Precios por frecuencia` cae en el comentario `:70` y no en el rótulo `:75`) y mostrador `:8` (`Efectivo del mostrador` cae en el título de pestaña `:2` y no en el encabezado `:3`). El barrido automático no lo detecta: la línea citada sí contiene el texto. Lo que no hace es respaldar la afirmación. Para corregirlo hay que buscar un texto que sea único en el archivo (por ejemplo `@if($esSelf)` junto a `usuarios-cuenta-propia`, o `etiqueta.style.display = pertenece`), no solo regenerar. |

**Releído sin otros hallazgos:**
- Las 241 citas de las 23 fichas de la etapa C están al día con el código (barrido de la sección 1).
- `clases/show.blade.php:133`, la fila «Editar en la ficha solo sin presentes y no validada»: el mismo `@if` (`:133-154`) envuelve **Validar** y **Editar** (`:146-153`). La fila está respaldada.

---

## 4. Conteo final de lo abierto

- **Graves abiertos:** 0
- **Medios abiertos:** 1
  - **#18, a medias. Avanzó: de las 15 filas, 9 quedan resueltas.** Siguen abiertas:
    - **No resueltas:** las dos filas «Abrir», `reportes-sueldos:10` y `reportes-alumnos:11`, siguen con la firma del método como única cita. La fila «Eliminar en la ficha del grupo», `administrar-grupos:11`, sigue citando el rechazo. Tampoco tienen fila la casilla **Presente** ni el paso 1.2 de tomar-asistencia, ni la afirmación «quedan afuera de la liquidación» en reportes-alumnos e inicio-dueno.
    - **A medias:** `administrar-usuarios:9`, `corregir-movimiento-cashflow:10`, `programar-clases:9`, `programar-clases:10` y `administrar-grupos:8`.
    - En todos los casos la **página** dice lo que hace Wings: el defecto está solo en las fichas.
- **Menores abiertos:** 1, el **N12** (nuevo). El generador cita la primera aparición del texto y eso explica cuatro de las filas a medias.
- **Resueltos en esta vuelta:** N10 y N11 (menores), y 9 de las 15 filas del #18. También se resolvió la fila «Cambiar un precio» de grupos.

Ningún abierto lleva al lector a hacer algo equivocado. Todo lo que queda está en las fichas de fuentes y en el generador.

---

## 5. Comprobado e inferido

**Comprobado leyendo el archivo en la línea:**
- Cada línea citada por las filas de la sección 2 y cada línea que propongo como cita faltante. Las imprimí con su número.
- Que `@if($esSelf)` aparece en `usuarios/index.blade.php:57` y en `:90`, y que solo el segundo decide si se muestra el interruptor.
- Que `clases-form.js:8` es una declaración y que el filtro está en `:72-77`.
- Que `grupos/_form.blade.php:70` es un comentario Blade y que el rótulo visible está en `:75`.
- Que `caja/configuracion.blade.php:2` es el `title` de la pestaña y `:3` el `module-title`, y que este se dibuja en `ds-app.blade.php:355-356`.
- N10: la línea `show.blade.php:180` y el código actual de `donde()` y de `linea()` en `generar-fichas.py`.
- N11: el texto de las dos páginas, la condición de `LiquidacionService.php:158-161`, la validación manual (`ClaseWebController.php:778-791`, ruta en `routes/web.php:226`) y el conteo del menú (`AppServiceProvider.php:83-87`).
- El barrido de las 241 citas y el control automático (0).

**Inferido, sin ejecutar:**
- Que el N12 explica las cuatro filas. Lo deduzco del código de `linea()` y de los textos que busca el generador (`:124`, `:161`, la fila de grupos y la de mostrador). No regeneré las fichas.
- Que antes `donde()` quitaba los dos puntos: lo tomo de la cuarta vuelta. Yo solo vi el estado actual. La ficha y el generador no están versionados (`git status`: `etapa-c/` y la mayoría de las fichas aparecen como `??`).
- Las observaciones de redacción del N11 son un juicio sobre el texto. El comportamiento del código está comprobado.
- No ejecuté ningún camino en pantalla ni miré capturas: ninguno de los puntos abiertos las involucraba.
