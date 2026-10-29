# Ficha de fuentes: Pasar a un alumno a Inactivo (Operativo)

- **Página del manual:** `resources/manual/operativo/pasar-alumno-inactivo.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/{alumnoId}`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Inactivo vs Eliminación** | Pasar a Inactivo conserva historial de pagos y deudas; no confundir con eliminación de cargados por error (T65). | `app/Http/Controllers/AlumnoWebController.php:428-460, 465-515` | Comprobado: toggle conmuta booleano `activo`; eliminación T65 es destructiva y restringida. |
| **Paso 1: Ficha del alumno** | En **Alumnos**, tocar **Ver** o nombre. En barra superior derecha está interruptor **Activo** / **Inactivo**. | `resources/views/alumnos/show.blade.php:60-65`<br>`resources/views/components/ds/toggle.blade.php` | Comprobado: toggle visible en ficha con estados Activo e Inactivo. |
| **Paso 2: Cambiar a Inactivo** | Clic en interruptor pasa a **Inactivo**. Wings emite aviso de confirmación. Generador del día 1° ya no crea cuotas ni figura en listas. | `app/Http/Controllers/AlumnoWebController.php:435, 453`<br>`app/Services/PagoCuotaService.php:120` | Comprobado: llamada AJAX conmuta a `activo = 0`; generador excluye inactivos. |
| **Paso 3: Reactivar alumno** | Clic en interruptor pasa a **Activo** y genera automáticamente cuota de reingreso del mes en curso. | `app/Http/Controllers/AlumnoWebController.php:437-441`<br>`app/Services/PagoCuotaService.php:480` | Comprobado: conmutación a activo crea cuota con porcentaje de reingreso y cierra revisión. |
| **Qué hago si: deuda previa** | Deudas viejas no se borran; quedan en cuenta corriente y se pueden cobrar normalmente con botón **Cobrar**. | `resources/views/alumnos/show.blade.php:67`<br>`app/Models/DeudaCuota.php` | Comprobado: alumno inactivo mantiene saldos pendientes en ficha y listados. |
| **Qué hago si: cobro a inactivo** | Al cobrar a inactivo con deuda vieja, Wings pregunta «¿Pasa a activo?» con opciones **Sí**, **No** y **Cancelar**. | `resources/views/caja/cobrar.blade.php:325-335`<br>`resources/js/cobrar.js:115-142` | Comprobado (T25): respuesta **No** cobra sin reactivar ni crear cuota del mes. |
| **Qué hago si: borrar por error** | Alumnos cargados por error sin cobros se eliminan mediante la función T65 del dueño. | `app/Http/Controllers/AlumnoWebController.php:465-515`<br>`resources/views/alumnos/show.blade.php:470` | Comprobado: eliminación atómica permanente exige no tener pagos registrados. |
