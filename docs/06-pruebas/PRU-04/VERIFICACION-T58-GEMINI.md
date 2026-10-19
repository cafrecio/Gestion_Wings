# Verificación de T58 — Creación de cuota de mes pasado desde la ficha por el dueño

**Fecha de verificación:** 18/10/2026  
**Verificador:** Gemini (independiente)  
**Implementó:** Claude (commits `687262f` y `c154ac4`)  
**Base de pruebas:** `wings_testing_gemini`  
**Decisión de Carlos (DU-23):** «Que el dueño pueda crear la cuota de un mes pasado desde la ficha».

---

## 1. Veredicto

**APROBADA con recomendación menor.** La implementación cumple fielmente el requerimiento y la regla de negocio de Carlos (DU-23), implementa los controles de seguridad necesarios en ruta, controlador y servicio, respeta estrictamente las directivas de diseño del Design System y la directiva de cero JavaScript inline (utilizando `data-confirmar` con `ds-app.js`).

---

## 2. Inspección de Código

### A. Servicio: `PagoCuotaService::mesesPasadosSinCuota` y `crearCuotaMesPasado`
- `mesesPasadosSinCuota($alumno)`:
  - Establece la cota inferior en el primer día del mes de `fecha_alta` del alumno (`startOfMonth()`).
  - Establece la cota superior en el mes anterior al actual (`startOfMonth()->subMonth()`).
  - Si el alumno fue dado de alta en el mes actual o posterior, devuelve arreglo vacío de inmediato.
  - Itera mes por mes. Verifica si ya existe registro en `pago_cuotas` para ese `periodo_mes` y `periodo_anio`. Si existe, lo omite.
  - Verifica si para ese mes el alumno tenía plan asignado llamando a `obtenerPlanParaPeriodo($alumno, $cursor)`. Si no tenía plan activo en dicho mes, lo omite.
  - Retorna un arreglo asociativo con `periodo` (`Y-m`), `label` (mes y año en castellano), `plan_id`, `plan_nombre` y `monto`.
- `obtenerPlanParaPeriodo($alumno, Carbon $fecha)`:
  - Inspecciona `planesHistorial()`. Busca solapamiento temporal: `fecha_desde <= finDeMes` y (`fecha_hasta IS NULL` o `fecha_hasta >= inicioDeMes`).
  - Si no encuentra en historial, recurre a `$alumno->plan` como fallback si `fecha_alta <= finDeMes`.
- `crearCuotaMesPasado($alumno, string $periodo)`:
  - Valida formato de `$periodo` (`YYYY-MM`). Si no coincide con `^\d{4}-(0[1-9]|1[0-2])$`, lanza `InvalidArgumentException`.
  - Comprueba que `$periodo` esté dentro de los candidatos retornados por `mesesPasadosSinCuota($alumno)`. Esto garantiza automáticamente que:
    1. No sea el mes actual ni futuro.
    2. No sea anterior al alta.
    3. No tenga ya cuota creada.
    4. Tenga un plan vigente para dicho mes.
  - Crea el registro en `PagoCuota`:
    - `estado = 'pendiente'`.
    - `monto_esperado` = valor del plan correspondiente a ese mes.
    - `monto_pagado = 0`.
    - `dias_vencimiento` = límite estándar de la cuota según `CobranzaEstadoService`.
  - Retorna la instancia de `PagoCuota`.

### B. Controlador: `AlumnoWebController::crearCuotaPasada`
- Autorización por rol: verifica `$request->user()->isAdmin()`. Si no es admin, lanza `abort(403)`.
- Valida `$request->validate(['periodo' => 'required|string'])`.
- Ejecuta dentro de bloque `try / catch` invocando `PagoCuotaService::crearCuotaMesPasado`.
- Redirige de vuelta a la ficha del alumno con mensaje flash de éxito (`'exito' => 'Cuota creada correctamente.'`) o de error si captura `InvalidArgumentException`.

### C. Rutas y Middleware
- Ruta declarada en `routes/web.php`:
  `POST /alumnos/{alumno}/cuotas-pasadas` -> `AlumnoWebController@crearCuotaPasada` con nombre `web.alumnos.cuotas-pasadas`.
- Protegida por los middlewares de grupo web: `['auth', 'activo']`.
  - Si un usuario está desactivado (`activo = false`), el middleware `UsuarioActivo` aborta con 403 antes de alcanzar el controlador.
  - Si el usuario es de rol `OPERATIVO`, el controlador aborta con 403 (`$request->user()->isAdmin()`).
  - Si el usuario es `PROFESOR`, no tiene acceso a la ficha ni a las rutas de alumnos (`abort(403)` en middleware de ruta).

### D. Vista: `resources/views/alumnos/show.blade.php`
- Bloque condicional: `@if(auth()->user()->isAdmin() && count($mesesPasadosSinCuota) > 0)`.
  - Solo se renderiza si el usuario es administrador y si efectivamente hay meses pendientes pasados.
- UI: Componente estilizado con tokens del sistema (`border: 1px solid var(--color-border); background: var(--color-card); border-radius: var(--radius-md); padding: 1.25rem;`).
- Selector `<select name="periodo">` con los meses disponibles, indicando el nombre del plan y el importe correspondiente.
- Botón: `<button type="submit" class="ds-btn ds-btn-primary" data-confirmar="¿Crear la cuota seleccionada para este alumno?">Crear</button>`.
- Alineación: Botón ubicado a la derecha (`flex justify-end`), cumpliendo la directiva de diseño para móviles y escritorio.
- Sin JavaScript incrustado: utiliza el atributo declarativo `data-confirmar` gestionado por `resources/js/ds-app.js`.

---

## 3. Pruebas Independientes Realizadas

En una base aislada (`wings_testing_gemini`) se ejecutó un script de verificación que probó:

1. **Alumno con cambio de plan a mitad de camino:**
   - Alta en junio 2026.
   - Plan Inicial ($10.000) asignado en junio y cerrado el 31 de julio.
   - Plan Nuevo ($16.000) asignado el 1 de agosto.
   - Mes actual de prueba: Octubre 2026.
   - Meses ofrecidos: Julio, Agosto, Septiembre.
   - **Resultado:** La cuota de Julio se creó exactamente con $10.000 (Plan Inicial), y la cuota de Agosto con $16.000 (Plan Nuevo).

2. **Casos de rechazo:**
   - Mes actual (2026-10): Rechazado.
   - Mes futuro (2026-11): Rechazado.
   - Mes anterior al alta (2026-05): Rechazado.
   - Mes que ya tiene cuota (se creó julio y se intentó crear de nuevo): Rechazado.
   - Mes sin plan asignado: Rechazado.
   - Texto inválido ("mes-invalido", "2026-13", ""): Rechazado con excepción.

3. **Permisos y Seguridad:**
   - Operativo enviando POST a la ruta: Recibe HTTP 403 Forbidden.
   - Operativo viendo la ficha del alumno: El bloque de meses pasados no se muestra en el HTML.
   - Dueño desactivado (`activo = false`): Middleware aborta con HTTP 403 Forbidden.
   - Profesor intentando acceder a la ficha: Aborta con HTTP 403 Forbidden.

4. **Flujo de Pantalla y Cobro Posterior:**
   - Se simuló el envío de creación de la cuota de Septiembre 2026.
   - La respuesta redirige a la ficha con el flash de éxito.
   - En la ficha actualizada, Septiembre desaparece del selector de cuotas pasadas pendientes.
   - La cuota creada en estado `pendiente` fue cobrada satisfactoriamente mediante `PagoCuotaService::cobrarCuotas` registrando el pago y emitiendo el comprobante correspondiente.

---

## 4. Inspección Visual y Capturas

Se generaron las capturas reales utilizando Chrome headless con marco de 375px/360px para móvil y 1366px para escritorio, renderizando el HTML producido directamente por Wings:

- **Escritorio (1366px):**
  - Vista Dueño con mes faltante: `docs/06-pruebas/PRU-04/evidencia-t58/01-ficha-duenio-con-mes-faltante-escritorio-1366.png`
  - Vista Dueño tras crear la cuota (bloque desaparece): `docs/06-pruebas/PRU-04/evidencia-t58/02-ficha-duenio-sin-meses-escritorio-1366.png`
  - Vista Operativo (sin bloque): `docs/06-pruebas/PRU-04/evidencia-t58/03-ficha-operativo-escritorio-1366.png`

- **Móvil (360px en marco):**
  - Vista Dueño con mes faltante: `docs/06-pruebas/PRU-04/evidencia-t58/01-ficha-duenio-con-mes-faltante-marco-360.png`
  - Vista Dueño tras crear la cuota: `docs/06-pruebas/PRU-04/evidencia-t58/02-ficha-duenio-sin-meses-marco-360.png`
  - Vista Operativo: `docs/06-pruebas/PRU-04/evidencia-t58/03-ficha-operativo-marco-360.png`

**Apreciación visual:**
El bloque se integra de forma limpia y sobria en la ficha del alumno, bajo la sección de cuotas. El botón "Crear" se ubica a la derecha según las reglas de estilo y los textos no se desbordan en 360px.

---

## 5. Revisión de Pruebas Automatizadas de Claude (`CuotaMesPasadoT58Test.php`)

Se analizaron las 8 pruebas existentes en `tests/Feature/CuotaMesPasadoT58Test.php`:
1. `duenio_ve_selector_de_meses_pasados_sin_cuota`: Verifica que el dueño logueado vea el formulario, la ruta de acción y los meses formateados en el `<select>`.
2. `operativo_no_ve_selector_de_meses_pasados`: Verifica que el operativo no vea el formulario ni el texto indicativo.
3. `duenio_puede_crear_cuota_de_mes_pasado`: Comprueba la persistencia en base de datos con el monto exacto del plan, estado 'pendiente' y redirección exitosa.
4. `operativo_no_puede_crear_cuota_de_mes_pasado`: Comprueba el `assertForbidden()` ante el POST de un operativo.
5. `no_se_puede_crear_cuota_de_mes_futuro_o_actual`: Verifica rechazo ante periodos futuros o el mes en curso.
6. `no_se_puede_crear_cuota_de_mes_anterior_al_alta`: Verifica rechazo de meses previos al alta del alumno.
7. `no_se_puede_duplicar_cuota_de_un_mes`: Verifica rechazo cuando el alumno ya posee una cuota en ese mes.
8. `crear_cuota_pasada_toma_el_plan_vigente_en_ese_mes`: Verifica la asignación del precio histórico si hubo cambios en los planes del alumno.

Las 8 pruebas pasan limpiamente (29 aserciones).

---

## 6. Resultado de la Suite Completa

Corrida sobre base dedicada `wings_testing_gemini`:
- **Total:** 862 tests ejecutados.
- **Resultado:** 859 pasaron, 3 omitidos (skipped por configuración de entorno), 0 fallas.
