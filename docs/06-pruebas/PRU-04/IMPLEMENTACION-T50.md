# T50 — Fecha de ingreso futura, solo hasta el mes siguiente

Gemini CAB, 20/10/2026. Implementación local.
Decisión de Carlos: **DU-06 («Aceptar solo hasta el mes siguiente»)**. Caso: **ALU-007**.
Revisión independiente: Claude / Codex.

---

## 1. Regla de negocio

La fecha de ingreso (`fecha_alta`) puede ser pasada, de hoy o futura **solo hasta el último día del mes siguiente a hoy**.
- Ejemplo: Si el reloj de Wings (`Carbon::now()`, respetando el reloj simulado) está al 20/10/2026, el límite máximo permitido es el 30/11/2026.
- En diciembre de cualquier año (ej. 15/12/2026), el límite se extiende al 31/01/2027.
- Si se ingresa una fecha posterior al límite, la operación se rechaza con un mensaje claro en castellano:
  *«La fecha de ingreso no puede ser posterior al dd/mm/aaaa.»*

---

## 2. Cambios reales

1. **Regla central (`app/Support/FechaIngresoRegla.php`):**
   - Clase de soporte con métodos estáticos:
     - `limiteMaximo(?Carbon $referencia = null): Carbon`: calcula el último día del mes siguiente respetando reloj simulado y fin de año.
     - `esValida($fecha, ?Carbon $referencia = null): bool`: determina si la fecha es menor o igual al límite máximo.
     - `mensajeError(?Carbon $referencia = null): string`: genera el mensaje estándar en castellano con formato `d/m/Y`.

2. **Controlador de Alumnos (`app/Http/Controllers/AlumnoWebController.php`):**
   - `store`: valida `fecha_alta` contra el límite máximo antes de cualquier persistencia (alumno o deudas asociadas).
   - `update`: aplica la validación del límite máximo únicamente si `fecha_alta` fue modificada por el usuario (lo cual requiere motivo obligatorio). Si se modifican otros campos de un alumno que ya poseía una fecha histórica guardada, la edición no se bloquea.
   - `validationRules`: firma actualizada para recibir opcionalmente `?Alumno $alumno` y condicionar la regla de límite al cambio efectivo de la fecha.
   - `cuotaAltaPreview` e `inscripcionPreview`: validan la fecha recibida y retornan HTTP 422 con el mensaje explicativo si supera el límite.

3. **Servicio de Cuotas (`app/Services/PagoCuotaService.php`):**
   - `previsualizarCuotaAlta`: valida la fecha con `FechaIngresoRegla::esValida` y lanza `ValidationException` si excede el límite.

4. **Primera Carga por Excel (`app/Services/PrimeraCargaExcelService.php`):**
   - `revisar`: en la columna 5 (columna E, «Fecha de ingreso»), si la fecha leída supera el límite máximo permitido, la celda se marca con error, indicando el mensaje estándar y el límite esperado. La importación no avanza al paso 4 mientras existan errores.

5. **Interacción en Cliente (`resources/js/alumnos-form.js`):**
   - Calcula dinámicamente el valor máximo admisible en base a la fecha actual y lo asigna al atributo `max` del input `fecha_alta`.
   - Vincula validación nativa (`setCustomValidity`) para mostrar el mensaje en castellano en tiempo real si el usuario tipea o selecciona una fecha fuera de rango.
   - Cumple estrictamente con **AGENTS.md §5-bis**: sin bloques `<script>` ni manejadores inline en Blade.
   - Compilado con `npm run build` sin errores.

6. **Integridad del Diseño Visual (AGENTS.md §1):**
   - **No se modificó ningún archivo en `resources/views/**` ni en `resources/css/**`.**
   - El mensaje de error del servidor se renderiza automáticamente mediante las estructuras y componentes ya existentes (`@error('fecha_alta')`).

---

## 3. Pruebas automatizadas

Se creó la suite de pruebas `tests/Feature/FechaIngresoFuturaT50Test.php` con 10 pruebas exhaustivas:
1. `test_fecha_ingreso_limite_exacto_ultimo_dia_mes_siguiente_acepta`: verifica que la fecha límite exacta (30/11/2026) sea aceptada y persista el alumno.
2. `test_fecha_ingreso_dia_posterior_al_limite_rechaza_y_no_guarda_nada`: verifica que 01/12/2026 sea rechazado con mensaje exacto y no se persista nada en base de datos.
3. `test_fecha_ingreso_pasada_y_de_hoy_aceptan`: comprueba que fechas pasadas y del día de hoy sean admitidas sin restricciones.
4. `test_cambio_de_anio_en_diciembre_limite_es_31_de_enero`: verifica la transición de fin de año (15/12/2026 → límite 31/01/2027).
5. `test_edicion_permite_cambiar_fecha_hasta_el_limite_y_rechaza_posterior`: comprueba edición con cambio de fecha válido y rechazo cuando supera el límite.
6. `test_edicion_alumno_antiguo_con_fecha_fuera_de_limite_no_se_bloquea_si_no_se_cambia_la_fecha`: confirma que un alumno preexistente no quede atrapado ni bloqueado al actualizar teléfono u otros campos.
7. `test_previsualizacion_cuota_alta_rechaza_fecha_posterior_al_limite`: comprueba validación en `PagoCuotaService::previsualizarCuotaAlta`.
8. `test_previsualizacion_cuota_endpoint_retorna_422_en_fecha_invalida`: comprueba el endpoint HTTP `web.alumnos.cuota-alta-preview`.
9. `test_previsualizacion_inscripcion_rechaza_fecha_posterior_al_limite`: comprueba el endpoint HTTP `web.alumnos.inscripcion-preview`.
10. `test_primera_carga_excel_revisar_marca_error_en_fecha_posterior_al_limite`: comprueba que `PrimeraCargaExcelService::revisar` marque la fila en columna E con error impidiendo la carga.

**Resultado de la suite de T50:**
`10 passed (44 assertions) en wings_testing_gemini`.

---

## 4. Evidencia visual generada

Las capturas de pantalla reales del sistema funcionando se encuentran en `docs/06-pruebas/PRU-04/evidencia-t50/`:
- `01-alumno-alta-error-1366.png`: Formulario de alta con error en fecha de ingreso (1366px escritorio).
- `02-alumno-alta-error-marco-375.png`: Formulario de alta con error en fecha de ingreso (375px teléfono en marco).
- `03-primera-carga-error-1366.png`: Paso 3 de Primera Carga Excel con fila rechazada por fecha fuera de límite (1366px escritorio).
- `04-primera-carga-error-marco-375.png`: Paso 3 de Primera Carga Excel con fila rechazada por fecha fuera de límite (375px teléfono en marco).

Documento unificado para revisión:
`docs/06-pruebas/PRU-04/APROBAR-T50.html`.

---

## 5. Contratos y catálogo actualizados

- `docs/02-contratos/Wings-contrato-estadosAlum-cobranza-asistencia-V1.md`: agregada la *Enmienda del 20/10/2026 (T50)* y actualizada la tabla de estados.
- `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/ALU.json`: caso `ALU-007` actualizado a `"estado": "vigente"`.
- `docs/00-estado/ESTADO-ACTUAL.md`: actualizado el estado de T50 a `a_verificar`.
- `docs/00-estado/LOG-GEMINI.md`: registrada la sesión completa bajo la firma `LOG GEM CAB`.

---

## 6. Segunda vuelta — H2 y H3 (21/10/2026)

Revisión tras el informe de verificación independiente de Codex CAB (`docs/06-pruebas/PRU-04/VERIFICACION-T50-CODEX.md`).

### 6.1. Decisión de Carlos sobre H1 (Inscripción en alta futura)
Codex reportó que dar de alta a un alumno con fecha de ingreso en el mes siguiente genera un cargo de inscripción exigible hoy ($5.000), dejándolo en estado `EN_PLAZO` y sumándolo en Cobrar e Inicio.
- **Decisión del dueño (Carlos, 21/10/2026):** *«Inscripción a partir de hoy»*. La inscripción se debe desde el momento del alta (regla vigente del club); la cuota del mes que aún no empezó no cuenta como deuda previa al inicio del período. Por lo tanto, no se modifica la lógica de inscripción. Claude actualizó el caso `ALU-007` en la tabla de pruebas.

### 6.2. Corrección H2 — Fecha mal formada al editar (422 en castellano sin 500)
- **Problema detectado:** En `AlumnoWebController::validationRules`, se ejecutaba `Carbon::parse($fechaNueva)` para comparar si la fecha cambiaba respecto a `$alumno->fecha_alta` antes de que actuara la regla `date`. Si se enviaba `fecha_alta=no-es-fecha` en la edición, se producía un error 500 (`InvalidFormatException`).
- **Solución implementada:**
  1. En `validationRules`, se envolvió el análisis de `$fechaNueva` en un bloque `try/catch (\Throwable)`: si el valor no es parseable, `$cambiaFechaAlta = true`.
  2. Se antepuso la regla `'bail'` a las reglas de `fecha_alta`: `['bail', 'required', 'date']`. De esta forma, si el valor no es una fecha válida, Laravel detiene la evaluación inmediatamente y devuelve el error descriptivo en castellano: *«La fecha de alta no es una fecha válida.»* (422), sin llegar a invocar el closure de límite ni intentar formateos con Carbon.
  3. Mismo blindaje aplicado en `store`, `cuotaAltaPreview` e `inscripcionPreview`.
- **Relevamiento de puertas:** Se auditó todo el sistema para verificar si existía el mismo patrón en otras áreas:
  - `store`: `$request->validate(...)` valida antes de `Carbon::parse`. Se añadió `'bail'`.
  - `cuotaAltaPreview` e `inscripcionPreview`: se añadió `'bail'` y `try/catch` para `Carbon::parse($value)`.
  - `PrimeraCargaExcelService`: ya utilizaba `$this->fecha(...)`, que retorna `null` ante cadenas no válidas antes de evaluar `FechaIngresoRegla::esValida`. No presentaba este defecto.
- **Resultado en pruebas:**
  - Nueva regresión permanente: `test_h2_edicion_con_fecha_mal_formada_devuelve_422_sin_escrituras` en `FechaIngresoFuturaT50Test` (**VERDE**).
  - Control 06 de Codex (`test_06_fecha_mal_formada_al_editar_devuelve_validacion_y_no_500` en `ControlT50Test.php`): **PASÓ**.

### 6.3. Corrección H3 — La pantalla usa el reloj de Wings (data-max y fecha por defecto)
- **Problema detectado:**
  1. `resources/js/alumnos-form.js` recurría a `new Date()` del navegador si el campo no tenía `data-max`. Cuando el reloj de la computadora no coincidía con el reloj simulado de Wings, la pantalla calculaba un límite desfasado.
  2. En el alta, la fecha por defecto usaba `date('Y-m-d')` de PHP en vez del reloj simulado de Wings.
  3. Tras el aviso específico de fecha fuera de límite, la previsualización de cuota recibía 422 y lo reemplazaba en pantalla por el aviso genérico *«No se pudo verificar la cuota. Revisá la conexión y la fecha antes de guardar.»*.
- **Solución implementada:**
  1. `resources/views/alumnos/_form.blade.php`:
     - Se agregó el atributo `data-max="{{ \App\Support\FechaIngresoRegla::limiteMaximo()->format('Y-m-d') }}"` al input `#fecha_alta`.
     - La fecha por defecto ahora usa `now()->format('Y-m-d')` (reloj de Wings / `RelojSimulado`).
     - Cambio autorizado en commit con línea `Diseno-autorizado:` (solo atributos de datos, sin modificación visual, sin JS inline en Blade, AGENTS.md §1 y §5-bis).
  2. `resources/js/alumnos-inscripcion.js`:
     - Al tipear una fecha fuera de límite, no se sobreescribe la validez previa con el texto provisional *«Esperá a verificar...»*.
     - Al recibir respuesta 422, se extrae el mensaje de error específico devuelto por el servidor (`datos?.errors?.fecha_alta?.[0]`) y se asigna tanto al contenedor de mensaje como a `setCustomValidity`. Si la fecha supera `dataset.max`, se garantiza la permanencia del aviso específico en castellano.
  3. Compilado con `npm run build` sin errores.
- **Pruebas y verificación en navegador real (Chrome CDP):**
  - Nueva regresión permanente: `test_h3_formulario_alta_y_edicion_traen_data_max_y_fecha_defecto_wings` (**VERDE**).
  - Verificación en Chrome real con `scripts/pruebas/cdp-helper.mjs`:
    - **Con `FECHA_SIMULADA="2026-11-01 10:00:00"`:**
      - Atributo `max` y `data-max`: `2026-12-31`.
      - Fecha por defecto: `2026-11-01`.
      - Fecha `2026-12-01` aceptada sin avisos.
      - Fecha `2027-01-01` rechazada con aviso específico: *«La fecha de ingreso no puede ser posterior al 31/12/2026.»* (conservado en cuota y en validación del campo).
      - Capturas generadas: `01-aviso-limite-1366.png` y `02-aviso-limite-375.png`.
    - **Con reloj real (Octubre 2026):**
      - Atributo `max` y `data-max`: `2026-11-30`.
      - Fecha por defecto: `2026-10-21`.
      - Fecha `2026-11-15` aceptada sin avisos.
      - Fecha `2026-12-01` rechazada con aviso específico: *«La fecha de ingreso no puede ser posterior al 30/11/2026.»*.
    - Consola sin errores (`consola: []` en ambos JSONs de recorrido).
  - Evidencia completa en `docs/06-pruebas/PRU-04/evidencia-t50/segunda-vuelta/`.

---

## 7. Tercera pasada — Resolución de observaciones complementarias de Codex (21/10/2026)

> Verificación posterior de Codex CyE, 21/10, commit d274cfd4: idioma aprobado (13/71 permanentes y 17/214 controles propios verdes). La guardia descripta en §7.2 sigue incompleta ante DB_URL: valida el nombre sin resolver la conexión efectiva. T50 devuelta solo por ese punto; reproducción sin escrituras en el [informe de tercera verificación](VERIFICACION-T50-TERCERA-CODEX.md). El texto de implementación que sigue conserva lo entregado por Gemini.

Revisión en `docs/06-pruebas/PRU-04/VERIFICACION-T50-SEGUNDA-CODEX.md`. Codex confirmó H2 y H3 en su informe (15 de 16 controles pasando, suite completa en verde y recorrido Chrome real conforme), y Carlos confirmó que H1 no se modifica (la inscripción rige a partir de hoy). Se resolvieron las dos observaciones complementarias:

### 7.1. Idioma castellano en previsualizaciones malformadas (`cuotaAltaPreview` e `inscripcionPreview`)
- **Problema:** Cuando se enviaban datos malformados (`fecha_alta=no-es-fecha`) o vacíos a los endpoints AJAX de previsualización, la validación respondía con 422 pero utilizaba los mensajes en inglés por defecto de Laravel (`The fecha alta field must be a valid date.` / `The fecha alta field is required.`) al no pasar `$this->validationMessages()` a `$request->validate()`.
- **Solución:**
  - En `AlumnoWebController::cuotaAltaPreview` e `inscripcionPreview`, se pasó `$this->validationMessages()` como segundo parámetro a `$request->validate()`.
  - Ahora devuelven exactamente: *«La fecha de alta no es una fecha válida.»* ante malformaciones sintácticas y *«La fecha de alta es obligatoria.»* ante campos vacíos/nulos.
- **Prueba permanente:**
  - `test_previews_con_fecha_mal_formada_o_vacia_responden_en_castellano` en `tests/Feature/FechaIngresoFuturaT50Test.php` (**VERDE**).

### 7.2. Guardia de seguridad estricta en script auxiliar CLI
- **Problema:** `docs/06-pruebas/PRU-04/evidencia-t50/segunda-vuelta/preparar-base.php` no comprobaba entorno ni base de datos antes de invocar `migrate --force` y `db:seed`.
- **Solución:**
  - Se agregó una guardia al inicio del script que exige `app()->environment('testing', 'local')` y valida que `config('database.connections.mysql.database')` pertenezca a la lista autorizada de bases de prueba (`wings_testing`, `wings_testing_gemini`, `wings_testing_claude`, `wings_testing_codex`). Si no se cumple, aborta con código 1 y mensaje de error en stderr.
