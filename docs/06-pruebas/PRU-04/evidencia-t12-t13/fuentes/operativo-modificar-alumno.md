# Ficha de fuentes: Modificar datos de un alumno (Operativo)

- **Página del manual:** `resources/manual/operativo/modificar-alumno.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/{alumnoId}/edit`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso** | Accesible tocando el botón **Editar** en el listado o en la ficha. | `resources/views/alumnos/index.blade.php:148`<br>`routes/web.php:168` | `Route::get('/alumnos/{id}/edit', ...)` | Accesible para Operativo y Admin. Un usuario con rol Profesor intentando acceder a `/alumnos/{id}/edit` recibe `HTTP 403 Forbidden`. |
| **Paso 1: Pantalla de edición** | Muestra datos actuales del alumno, teléfonos, email, grupo y fecha de alta. | `resources/views/alumnos/edit.blade.php:23`<br>`resources/views/alumnos/_form.blade.php:67` | `@isset($alumno)` | Carga el formulario con los datos preexistentes del alumno vinculados a su legajo. |
| **Paso 2: Corregir fecha de alta** | Si se modifica la **Fecha de alta**, es obligatorio completar **Motivo si corrige la fecha de ingreso**. | `app/Http/Controllers/AlumnoWebController.php:525-531`<br>`resources/views/alumnos/_form.blade.php:143` | `if ($fechaAnterior !== Carbon::parse($validated['fecha_alta'])->format('Y-m-d')) { ... $request->validate(['motivo_fecha_alta' => 'required\|string\|min:5\|max:500']); }` | Al alterar la fecha de alta dejando el motivo vacío o con menos de 5 caracteres, Wings rechaza con error: `El campo motivo fecha alta es obligatorio.` o de longitud mínima. |
| **Paso 2: Límite de fecha de alta** | La fecha de alta corregida no puede ser posterior al fin del mes siguiente. | `app/Support/FechaIngresoRegla.php:23-42`<br>`app/Http/Controllers/AlumnoWebController.php:526` | `if (!FechaIngresoRegla::esValida($validated['fecha_alta'])) { throw ValidationException::withMessages(['fecha_alta' => FechaIngresoRegla::mensajeError()]); }` | Al ingresar una fecha posterior al último día del mes siguiente, Wings rechaza con el mensaje exacto: `La fecha de ingreso no puede ser posterior al <fecha_limite>.`. |
| **Paso 3: Guardar cambios** | Botón **Guardar** actualiza el legajo y regresa a la ficha personal. | `resources/views/alumnos/edit.blade.php:31`<br>`app/Http/Controllers/AlumnoWebController.php:547` | `return redirect()->route('web.alumnos.show', $alumno->id)->with('success', 'Alumno actualizado.');` | Guarda modificaciones válidas y devuelve al detalle del alumno con el cartel de éxito. |
| **Qué hago si: botón Cancelar** | Botón **Cancelar** descarta cambios y regresa al listado. | `resources/views/alumnos/edit.blade.php:30` | `<x-ds.button variant="secondary" href="{{ route('web.alumnos.index') }}">Cancelar</x-ds.button>` | Al presionar **Cancelar**, vuelve a `/alumnos` sin persistir ninguna modificación. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `El campo motivo fecha alta es obligatorio.` | `app/Http/Controllers/AlumnoWebController.php:530` | Sí | Explica que si se altera la fecha de alta original se exige justificar el motivo. |
| `La fecha de ingreso no puede ser posterior al :fecha_limite.` | `app/Support/FechaIngresoRegla.php:42` | Sí | Explica que la fecha de ingreso no puede exceder el fin del mes siguiente. |
| `El nombre del tutor es obligatorio para menores de edad.` | `app/Http/Controllers/AlumnoWebController.php:611` | Sí | Explica que para menores de edad se debe conservar el tutor responsable. |
| `El teléfono del tutor es obligatorio para menores de edad.` | `app/Http/Controllers/AlumnoWebController.php:612` | Sí | Explica que para menores de edad se debe conservar el contacto del tutor. |
| `Alumno actualizado.` | `app/Http/Controllers/AlumnoWebController.php:547` | Sí | Mensaje de éxito al guardar los cambios del legajo. |
