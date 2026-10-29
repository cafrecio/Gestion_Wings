# Ficha de fuentes: Consultar grupos y planes deportivos (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-grupos-planes.md`
- **Rol:** Operativo
- **Pantalla:** `/grupos`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso y rol** | La pantalla de consulta de grupos está disponible para el rol operativo. | `routes/web.php:66`<br>`docs/02-contratos/PERMISOS-ROLES.md` | `Route::middleware(['auth', 'role:ADMIN,OPERATIVO'])` | Accesible para Operativo y Admin. Usuarios anónimos son redirigidos a `/login`. |
| **Paso 1: Entrar a Grupos** | En menú lateral hacer clic en **Grupos**. Muestra filtro por deporte, total de grupos y tarjetas por nivel. | `resources/views/grupos/index.blade.php:15, 34`<br>`app/Http/Controllers/GrupoWebController.php:30` | `if ($request->filled('deporte_id')) { $query->where('deporte_id', ...); }` | Lista todas las categorías activas con sus planes arancelarios correspondientes. |
| **Paso 2: Filtrar por deporte** | Seleccionar **Deporte** en el desplegable. Botón **Limpiar** restablece la lista. | `resources/views/grupos/index.blade.php:17, 27` | `data-enviar-al-cambiar` | Filtra en tiempo real los grupos pertenecientes a la disciplina elegida. |
| **Paso 3: Aranceles y alumnos activos** | Muestra indicador de Activo, disciplina y nivel, cantidad de **Alumnos activos**, planes arancelarios y botón **Ver**. | `resources/views/grupos/index.blade.php:65-125` | `<x-ds.button variant="secondary" href="{{ route('web.grupos.show', $grupo->id) }}">Ver</x-ds.button>` | Al presionar **Ver**, abre el detalle del grupo con la nómina de deportistas inscriptos. |
| **Facultad exclusiva de ADMIN** | La creación de grupos y modificación de precios de cuotas es facultad exclusiva de ADMIN. El operativo no tiene botones de edición ni creación. | `resources/views/grupos/index.blade.php:44`<br>`routes/web.php:200-210` | `@if(Auth::user()->rol === 'ADMIN') <x-ds.button variant="primary" href="{{ route('web.grupos.create') }}">Nuevo</x-ds.button> @endif`<br>`Route::middleware(['auth', 'admin'])->group(...)` | Para el rol Operativo el botón **Nuevo** no se muestra en la vista. Si un usuario Operativo intenta acceder directamente por URL a `/grupos/create` o `/grupos/{id}/edit`, Wings responde inmediatamente con `HTTP 403 Forbidden`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `HTTP 403 Forbidden` | `routes/web.php:200`<br>`app/Http/Middleware/CheckRole.php:25` | Sí | Explica que crear o editar grupos es potestad exclusiva de Admin y rechaza a la operativa. |
| `0 grupos encontrados` | `resources/views/grupos/index.blade.php:80` | Sí | Mensaje de estado vacío cuando el filtro no devuelve registros. |
