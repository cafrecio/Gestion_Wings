# Ficha de fuentes: consultar-mis-clases

- **Página:** `resources/manual/profesor/consultar-mis-clases.md` · **Rol:** profesor · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Fuera del dueño, hasta 35 días atrás salvo Sin asistencia | `app/Http/Controllers/ClaseWebController.php:98` | `$limiteAtras = Carbon::now()->subDays(35)->format('Y-m-d');` | Leído en el código; no ejecutado en pantalla. |
| Sin filtros: próximas clases | `app/Http/Controllers/ClaseWebController.php:104` | `->whereDate('fecha', '>', today());` | Captura de la pantalla real (CapturasEtapaCTest). |
| La profesora entra directo a Clases | `app/Http/Controllers/WebController.php:85` | `User::ROL_PROFESOR => redirect()->route('web.clases.index'),` | Leído en el código; no ejecutado en pantalla. |
| Tus clases de hoy y Otras clases de hoy | `app/Http/Controllers/ClaseWebController.php:50` | `$misClasesHoy   = $clasesHoy->filter(fn($c) => $c->profesores->contains('id', $miProfesorId))->values();` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «No tenés clases programadas para hoy.» | `resources/views/clases/index.blade.php:93` | Sí — Tus clases de hoy |
