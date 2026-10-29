# Ficha de fuentes: Buscar alumnos en el listado (Operativo)

- **Página del manual:** `resources/manual/operativo/buscar-alumnos.md`
- **Rol:** Operativo
- **Pantalla:** `/alumnos`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso y rol** | La pantalla de alumnos está disponible para el personal operativo y admin. | `routes/web.php:161-163`<br>`docs/02-contratos/PERMISOS-ROLES.md` | `Route::middleware(['auth', 'role:ADMIN,OPERATIVO'])` | Si un usuario con rol `PROFESOR` intenta ingresar a `/alumnos`, Wings responde inmediatamente con error `HTTP 403 Forbidden`. Los anónimos son redirigidos a `/login`. |
| **Paso 1: Entrar a Alumnos** | En menú lateral hacer clic en **Alumnos**. Muestra filtros, barra de estadísticas y tarjetas individuales. | `resources/views/alumnos/index.blade.php:24, 75`<br>`app/Http/Controllers/AlumnoWebController.php:95` | `if ($request->filled('deporte_id'))`<br>`if ($request->filled('grupo_id'))` | Carga inicial lista la nómina de alumnos activos con paginación y tarjetas individuales con riel de color por deporte. |
| **Paso 2: Buscador predictivo** | Escribir nombre, apellido o DNI. A partir de 1 carácter despliega sugerencias automáticas. | `resources/js/alumnos-index.js:101`<br>`app/Http/Controllers/AlumnoWebController.php:115` | `if (q.length >= 1)`<br>`if ($request->filled('q')) { $query->buscar($request->input('q')); }` | Con 0 caracteres el menú desplegable no se abre. Al tipear 1 carácter, despliega la lista con las coincidencias encontradas. |
| **Paso 3: Filtros por deporte y grupo** | Seleccionar **Deporte**, **Grupo**. Botón **Limpiar** restablece los filtros. | `resources/views/alumnos/index.blade.php:27, 43`<br>`app/Http/Controllers/AlumnoWebController.php:100` | `if ($request->filled('deporte_id')) { $query->where('deporte_id', ...); }` | Al aplicar un deporte sin coincidencias, muestra el estado vacío: `0 alumnos encontrados`. Al tocar **Limpiar**, regresa al listado general completo. |
| **Qué hago si: acciones en tarjeta** | Tarjeta cuenta con botones **Ver** (ficha completa), **Editar** y botón directo **Cobrar**. | `resources/views/alumnos/index.blade.php:204-215` | `@if(Auth::user()->isOperativo() \|\| Auth::user()->isAdmin())` | Operativa tiene disponibles los botones **Ver**, **Editar** y **Cobrar** directamente en la tarjeta de Alumnos. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `HTTP 403 Forbidden` | `routes/web.php:161`<br>`app/Http/Middleware/CheckRole.php:25` | Sí | Explica que el rol Profesor no tiene acceso a Alumnos. |
| `0 alumnos encontrados` | `resources/views/alumnos/index.blade.php:80` | Sí | Mensaje de estado vacío cuando ningún alumno coincide con la búsqueda o filtros aplicados. |
| `El alumno no existe.` / `HTTP 404 Not Found` | `app/Http/Controllers/AlumnoWebController.php:150` | No | Excepción solo provocada por manipulación manual de URL con id inexistente. |
