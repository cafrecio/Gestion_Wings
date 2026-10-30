# Ficha de fuentes: clases-sin-asistencia

- **Página:** `resources/manual/profesor/clases-sin-asistencia.md` · **Rol:** profesor · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| El contador cuenta todas las clases pasadas sin lista | `app/Providers/AppServiceProvider.php:86` | `->doesntHave('asistencias')` | Captura de la pantalla real (CapturasEtapaCTest). La profesora ve «Sin asistencia 1». |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
