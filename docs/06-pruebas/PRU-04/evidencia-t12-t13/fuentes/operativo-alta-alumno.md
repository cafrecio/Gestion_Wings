# Ficha de fuentes: Dar de alta a un alumno nuevo (Operativo)

- **Página del manual:** `resources/manual/operativo/alta-alumno.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos/create`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Paso 1: Botón Nuevo** | En **Alumnos**, tocar botón **Nuevo**. Abre formulario **Nuevo Alumno**. | `resources/views/alumnos/index.blade.php:82`<br>`resources/views/alumnos/create.blade.php:2` | `@if(Auth::user()->isOperativo() \|\| Auth::user()->isAdmin())` | El botón **Nuevo** está visible para el operativo. Si un rol no autorizado (ej. Profesor) intenta acceder a `/alumnos/create`, recibe `HTTP 403 Forbidden`. |
| **Paso 2: Datos de tutor para menores** | Para menores de 18 años es obligatorio ingresar nombre y teléfono del tutor. | `app/Http/Controllers/AlumnoWebController.php:570, 611-612, 723-724` | `$esMenor = $fechaNac ? Carbon::parse($fechaNac)->age < 18 : false;`<br>`'nombre_tutor' => $esMenor ? 'required\|string\|max:255' : 'nullable\|string\|max:255',`<br>`'telefono_tutor' => $esMenor ? 'required\|string\|max:255' : 'nullable\|string\|max:255'` | Al enviar un alumno menor de 18 años omitiendo tutor, Wings bloquea el alta y muestra los errores textuales: `El nombre del tutor es obligatorio para menores de edad.` y `El teléfono del tutor es obligatorio para menores de edad.`. |
| **Paso 2: Límite de fecha de alta** | La fecha de alta no puede ser posterior al fin del mes siguiente. | `app/Support/FechaIngresoRegla.php:23-42`<br>`app/Http/Controllers/AlumnoWebController.php:352` | `if ($value && !FechaIngresoRegla::esValida($value)) { $fail(FechaIngresoRegla::mensajeError()); }` | Al enviar una fecha posterior al último día del mes siguiente, Wings rechaza con el mensaje exacto: `La fecha de ingreso no puede ser posterior al <fecha_limite>.`. |
| **Paso 3: Frecuencia semanal y cuota** | Asignar deporte, grupo y frecuencia semanal. Indicar si genera cuota actual (**Sí.** o **No.**). | `resources/views/alumnos/_form.blade.php:153-180`<br>`app/Http/Controllers/AlumnoWebController.php:372` | `'plan_id' => ['required', Rule::exists('grupo_planes', 'id')->where('grupo_id', ...)]` | Si no se selecciona frecuencia semanal, Wings bloquea el envío con: `Debe seleccionar la frecuencia semanal.`. |
| **Qué hago si: DNI duplicado** | El DNI es único por persona y deporte en el club. | `app/Http/Controllers/AlumnoWebController.php:371` | `Rule::unique('alumnos', 'dni')->where('deporte_id', ...)` | Al intentar dar de alta un alumno con DNI ya existente en esa disciplina, Wings rechaza con: `El campo dni ya ha sido registrado.`. |
| **Qué hago si: botón Cancelar** | Botón **Cancelar** descarta los datos y vuelve al listado de alumnos. | `resources/views/alumnos/create.blade.php:15` | `<x-ds.button variant="secondary" href="{{ route('web.alumnos.index') }}">Cancelar</x-ds.button>` | Al presionar **Cancelar**, regresa a `/alumnos` sin crear ningún registro en la base de datos. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `El nombre del tutor es obligatorio para menores de edad.` | `app/Http/Controllers/AlumnoWebController.php:611` | No | En > [!NOTE] Requisito previo se indica que los datos del tutor son obligatorios para menores, sin citar el texto de validación exacto. |
| `El teléfono del tutor es obligatorio para menores de edad.` | `app/Http/Controllers/AlumnoWebController.php:612` | No | En > [!NOTE] Requisito previo se indica que los datos del tutor son obligatorios para menores, sin citar el texto de validación exacto. |
| `La fecha de ingreso no puede ser posterior al :fecha_limite.` | `app/Support/FechaIngresoRegla.php:42` | Sí | Qué hago si: ¿Qué hago si la fecha de ingreso es a futuro? |
| `Debe seleccionar la frecuencia semanal.` | `app/Http/Controllers/AlumnoWebController.php:372` | No | El Paso 3 explica que se debe elegir la frecuencia semanal para definir el plan, sin citar el mensaje de validación del formulario. |
| `El campo dni ya ha sido registrado.` | `app/Http/Controllers/AlumnoWebController.php:371` | No | En «Qué hago si: ¿Qué hago si el DNI ya está registrado en el sistema?» explica conceptualmente que el DNI es único sin citar la validación técnica. |
| `Alumno creado correctamente.` | `app/Http/Controllers/AlumnoWebController.php:420` | No | Mensaje flash del backend tras redirección; en Paso 3 se explica que Wings guarda y te lleva directo a su ficha. |
