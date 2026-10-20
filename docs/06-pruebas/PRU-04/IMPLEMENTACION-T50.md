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
