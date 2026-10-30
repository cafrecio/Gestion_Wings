# Ficha de fuentes: corregir-asistencia-anterior

- **Página:** `resources/manual/profesor/corregir-asistencia-anterior.md` · **Rol:** profesor · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Límite de 35 días | `app/Http/Controllers/ClaseWebController.php:97` | `if (!$esAdmin && !$atajoSinAsistencia) {` | Leído en el código; no ejecutado en pantalla. |
| Pide motivo solo si la clase pasó y tenía lista | `app/Http/Controllers/ClaseWebController.php:392` | `$esCorreccion       = $esPasada && $yaTieneAsistencias;` | Ejecutado (CapturasEtapaCTest): HTTP 422 · «Esta clase ya tenía lista tomada: indicá el motivo de la corrección.» |
| No se modifica con liquidación cerrada | `app/Http/Controllers/ClaseWebController.php:376` | `$mensaje = 'No se pueden modificar asistencias: la clase integra una liquidación cerrada.';` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Estás corrigiendo una lista ya tomada — indicá el motivo» | `resources/views/clases/show.blade.php:541` | Sí — Pasos |
| «Asistencias guardadas.» | `app/Http/Controllers/ClaseWebController.php:512` | Sí — Pasos |
| «No se pueden modificar asistencias: la clase integra una liquidación cerrada.» | `app/Http/Controllers/ClaseWebController.php:376` | Sí — ¿Qué hago si dice «No se pueden modificar asistencias: la clase integra una liquidación cerrada.»? |
| «Indicá el motivo de la corrección.» | `resources/js/clases-show.js:80` | Sí — ¿Qué hago si dice «Indicá el motivo de la corrección.»? |
