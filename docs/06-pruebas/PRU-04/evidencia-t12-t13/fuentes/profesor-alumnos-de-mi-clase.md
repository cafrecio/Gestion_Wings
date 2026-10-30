# Ficha de fuentes: alumnos-de-mi-clase

- **Página:** `resources/manual/profesor/alumnos-de-mi-clase.md` · **Rol:** profesor · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Color del punto según la semana | `resources/views/clases/show.blade.php:425` | `elseif ($excede)                    $dotEstado = 'danger';` | Captura de la pantalla real (CapturasEtapaCTest). |
| Alumnos activos del grupo ese día | `app/Http/Controllers/ClaseWebController.php:312` | `$alumnos = Alumno::where(fn ($q) => $q->enGrupoElDia($clase->grupo_id, $clase->fecha)->where('activo', true))` | Captura de la pantalla real (CapturasEtapaCTest). |
| Motivo obligatorio: nueva con 2 presentes o con historia en Deudor | `app/Services/ExcepcionCobranzaService.php:41` | `$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null)` | Ejecutado (CapturasEtapaCTest): HTTP 422 · «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» |
| Una alumna nueva en Deudor con menos de 2 presentes no pide motivo | `app/Services/ExcepcionCobranzaService.php:42` | `: ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);` | Ejecutado (CapturasEtapaCTest): HTTP 200 · «Asistencias guardadas.» |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
