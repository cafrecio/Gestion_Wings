# Ficha de fuentes: tomar-asistencia

- **Página:** `resources/manual/profesor/tomar-asistencia.md` · **Rol:** profesor · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Abrir la clase desde Clases con Ver | `resources/views/clases/_card.blade.php:97` | `Ver` | Captura de la pantalla real (CapturasEtapaCTest). |
| Marcar Presente y Guardar | `resources/views/clases/show.blade.php:478`<br>`resources/views/clases/show.blade.php:552` | `<span class="hidden sm:inline">Presente</span>`<br>`<button id="btn-guardar-asistencias"` | Captura de la pantalla real (CapturasEtapaCTest). |
| Guardar deshabilitado en una clase cancelada; cartel arriba | `resources/views/clases/show.blade.php:390` | `Esta clase está cancelada. No se pueden registrar asistencias.` | Leído en el código; no ejecutado en pantalla. |
| Clase futura: cartel y casillas deshabilitadas | `resources/views/clases/show.blade.php:484` | `{{ $todaviaNoOcurrio ? 'disabled' : '' }}` | Leído en el código; no ejecutado en pantalla. |
| Corrección: motivo si pasó y tenía lista | `app/Http/Controllers/ClaseWebController.php:392` | `$esCorreccion       = $esPasada && $yaTieneAsistencias;` | Ejecutado (CapturasEtapaCTest): HTTP 422 · «Esta clase ya tenía lista tomada: indicá el motivo de la corrección.» |
| No se modifica con liquidación cerrada | `app/Http/Controllers/ClaseWebController.php:376` | `$mensaje = 'No se pueden modificar asistencias: la clase integra una liquidación cerrada.';` | Leído en el código; no ejecutado en pantalla. |
| Motivo obligatorio: nueva con 2 presentes o con historia en Deudor; Moroso con historia no | `app/Services/ExcepcionCobranzaService.php:42` | `: ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);` | Ejecutado (CapturasEtapaCTest): HTTP 422 · «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» |
| Alumna nueva con menos de 2 presentes no pide motivo | `app/Services/ExcepcionCobranzaService.php:41` | `$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null)` | Ejecutado (CapturasEtapaCTest): HTTP 200 · «Asistencias guardadas.» |
| Los de la primera carga no son nuevos | `app/Services/ExcepcionCobranzaService.php:39` | `$nuevo = !$conCuota->has($alumno->id) && !$conHistoriaInicial->has($alumno->id);` | Leído en el código; no ejecutado en pantalla. |
| Aviso de exceso del plan, no obligatorio | `resources/views/clases/show.blade.php:505` | `Excede el plan — indicar motivo` | Leído en el código; no ejecutado en pantalla. |
| Sin motivo obligatorio, la pantalla no guarda | `resources/js/clases-show.js:97` | `mostrarToast('Indicá el motivo para admitir al alumno.', false);` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Asistencias guardadas.» | `app/Http/Controllers/ClaseWebController.php:512` | Sí — Paso 3. Guardar la lista |
| «Esta clase está cancelada. No se pueden registrar asistencias.» | `resources/views/clases/show.blade.php:390` | Sí — ¿Qué hago si la clase figura cancelada? |
| «Estás corrigiendo una lista ya tomada — indicá el motivo» | `resources/views/clases/show.blade.php:541` | Sí — ¿Qué hago si me equivoqué al marcar a un alumno? |
| «Indicá el motivo de la corrección.» | `resources/js/clases-show.js:80` | Sí — ¿Qué hago si me equivoqué al marcar a un alumno? |
| «No se pueden modificar asistencias: la clase integra una liquidación cerrada.» | `app/Http/Controllers/ClaseWebController.php:376` | Sí — ¿Qué hago si me equivoqué al marcar a un alumno? |
| «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.» | `resources/views/clases/show.blade.php:394` | Sí — ¿Qué hago si quiero tomar lista antes del día de la clase? |
| «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» | `app/Services/ExcepcionCobranzaService.php:48` | Sí — ¿Qué hago si un alumno tiene cuota vencida o ya vino todas las veces de su plan? |
| «Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.» | `app/Services/ExcepcionCobranzaService.php:47` | Sí — ¿Qué hago si un alumno tiene cuota vencida o ya vino todas las veces de su plan? |
| «Indicá el motivo para admitir al alumno.» | `resources/js/clases-show.js:97` | Sí — ¿Qué hago si un alumno tiene cuota vencida o ya vino todas las veces de su plan? |
| «Excede el plan — indicar motivo» | `resources/views/clases/show.blade.php:505` | Sí — ¿Qué hago si un alumno tiene cuota vencida o ya vino todas las veces de su plan? |
