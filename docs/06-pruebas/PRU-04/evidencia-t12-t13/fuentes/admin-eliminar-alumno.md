# Ficha de fuentes: eliminar-alumno

- **Página:** `resources/manual/admin/eliminar-alumno.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Séptimo motivo | `app/Models/Alumno.php:278` | `return 'Tiene cargos con cobro o condonados.';` | Leído en el código; no ejecutado en pantalla. |
| Solo el dueño elimina | `app/Http/Controllers/AlumnoWebController.php:635` | `abort(403, 'Solo el dueño puede eliminar alumnos.');` | Leído en el código; no ejecutado en pantalla. |
| Motivos que impiden eliminar | `app/Models/Alumno.php:242` | `return 'Tiene pagos registrados.';` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede eliminar el alumno: Tiene asistencias registradas. Puede darlo de baja cambiando su estado a inactivo.» |
| La ficha muestra el motivo en lugar del botón | `resources/views/alumnos/show.blade.php:524` | `@if(Auth::user()->isAdmin() && !$puedeEliminar && $motivoNoEliminable)` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «¿Confirmás la eliminación permanente de este alumno?» | `resources/views/alumnos/show.blade.php:488` | Sí — Pasos para eliminar |
| «Esta acción eliminará la ficha, planes y deudas sin cobrar asociadas. El DNI quedará liberado.» | `resources/views/alumnos/show.blade.php:491` | Sí — Pasos para eliminar |
| «No se puede eliminar:» | `resources/views/alumnos/show.blade.php:526` | Sí — ¿Qué hago si en lugar del botón Eliminar aparece «No se puede eliminar:»? |
