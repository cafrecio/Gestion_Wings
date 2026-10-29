# Ficha de fuentes: Modificar datos de un alumno (Operativo)

- **Página del manual:** `resources/manual/operativo/modificar-alumno.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/{alumnoId}/edit`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Paso 1: Abrir edición** | Desde **Alumnos**, tocar botón **Editar** en tarjeta o ficha del alumno. Abre pantalla de edición. | `resources/views/alumnos/index.blade.php:224`<br>`resources/views/alumnos/show.blade.php:69`<br>`resources/views/alumnos/edit.blade.php:4` | Comprobado: acceso a `/alumnos/{id}/edit` muestra datos actuales del alumno. |
| **Paso 2: Actualizar campos** | Actualizar **Celular**, **Email**, **Nombre del tutor**, **Teléfono del tutor** o **Motivo si corrige la fecha de ingreso**. | `resources/views/alumnos/_form.blade.php:80, 95, 143, 179, 188` | Comprobado: campos editables; si se cambia fecha de alta el motivo es obligatorio. |
| **Paso 3: Guardar** | Tocar botón **Guardar**. Redirige a la ficha con aviso de confirmación. | `resources/views/alumnos/edit.blade.php:30`<br>`app/Http/Controllers/AlumnoWebController.php:320-335` | Comprobado: guardado exitoso actualiza el modelo y muestra mensaje de éxito. |
| **Qué hago si: Deporte fijo** | El campo Deporte aparece bloqueado porque DNI:Deporte es 1:1; para nuevo deporte se crea alta. | `resources/views/alumnos/_form.blade.php:107-110`<br>`app/Models/Alumno.php` | Comprobado: `deporte_id` no editable en edición; solo se reasigna grupo. |
| **Qué hago si: cancelar** | Botón **Cancelar** descarta cambios y regresa a la vista previa. | `resources/views/alumnos/edit.blade.php:29` | Comprobado: botón **Cancelar** retorna sin persistir modificaciones. |
