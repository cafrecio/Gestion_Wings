# Ficha de fuentes: Consultar clases del día y asistencia (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-clases-operativo.md`
- **Rol:** Operativo
- **Pantalla:** `/clases`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso** | Accesible en mostrador desde el menú lateral en Clases. | `routes/web.php:60`<br>`app/Http/Controllers/ClaseWebController.php:30` | `Route::get('/clases', [ClaseWebController::class, 'index'])->name('web.clases.index');` | Accesible para Operativo, Profesor y Admin. Usuarios anónimos son redirigidos a `/login`. |
| **Paso 1: Sección Clases de hoy** | Encabezado **Clases de hoy** con fecha actual y actividades programadas para la jornada. | `resources/views/clases/index.blade.php:80-95` | `@if($misClasesHoy->count() > 0)` | Si no hay actividades en el día, muestra: `No hay clases programadas para hoy.`. |
| **Paso 2: Estados de las clases** | Estados calculados: **Programada**, **Por comenzar**, **En curso**, **Cerrada**, **Finalizada** y **Cancelada**. | `resources/views/clases/index.blade.php:12-30` | `function claseEstado($clase, $ahora): string {`<br>`if ($clase->cancelada) return 'cancelada';`<br>`$resuelta = $clase->validada_para_liquidacion \|\| $clase->asistencias->where('presente', true)->count() > 0;`<br>`if ($clase->fecha->isToday()) {`<br>`if ($ahora->lt($inicio->copy()->subHour())) return 'programada';`<br>`if ($ahora->lt($inicio)) return 'por_comenzar';`<br>`if ($ahora->lte($fin)) return 'en_curso';`<br>`return $resuelta ? 'cerrada' : 'finalizada'; }`<br>`if ($clase->fecha->isFuture()) return 'programada';`<br>`return $resuelta ? 'cerrada' : 'finalizada'; }` | Verificado en pantalla: cada clase muestra su badge de estado correspondiente según la hora y si la lista de asistencia fue completada o resuelta. |
| **Paso 3: Filtros de clases** | Filtros por **Deporte**, **Grupo** o docente. Botón **Ver** abre el detalle de la clase. | `resources/views/clases/index.blade.php:140-170` | `if ($request->filled('deporte_id')) { ... }` | Permite navegar y consultar el cronograma de actividades pasadas y futuras. |
| **Qué hago si: tomar lista en mostrador** | Tocar **Ver** permite revisar la nómina y tomar asistencia si el docente no tiene dispositivo. | `resources/views/clases/show.blade.php:23`<br>`resources/manual/operativo/tomar-lista-mostrador.md` | `@section('ayuda-slug', Auth::user()?->isProfesor() ? 'tomar-asistencia' : 'tomar-lista-mostrador')` | Abre la ficha de la clase con la lista de alumnos inscriptos y casillas de asistencia operativas. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Comentario / Motivo |
|---|---|---|---|
| `No hay clases programadas para hoy.` | `resources/views/clases/index.blade.php:85` | Sí | Mensaje cuando no hay clases agendadas en la fecha actual. |
| `0 clases encontradas` | `resources/views/clases/index.blade.php:190` | Sí | Mensaje de estado vacío cuando los filtros no devuelven actividades. |
