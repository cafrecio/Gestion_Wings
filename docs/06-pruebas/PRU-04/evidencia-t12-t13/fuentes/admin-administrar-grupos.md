# Ficha de fuentes: administrar-grupos

- **Página:** `resources/manual/admin/administrar-grupos.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Crear: Nuevo, Deporte, Nivel, Precios por frecuencia, Guardar | `resources/views/grupos/index.blade.php:46`<br>`resources/views/grupos/_form.blade.php:17`<br>`resources/views/grupos/_form.blade.php:40`<br>`resources/views/grupos/_form.blade.php:77`<br>`resources/views/grupos/create.blade.php:15` | `Nuevo`<br>`Deporte @unless(isset($grupo))<span class="form-required">*</span>@endunless`<br>`Nivel <span class="form-required">*</span>`<br>`Precios por frecuencia`<br>`<x-ds.button variant="primary" type="submit">Guardar</x-ds.button>` | Captura de la pantalla real (CapturasEtapaCTest). |
| Cambiar un precio: Editar, Nuevo, Guardar | `resources/views/grupos/index.blade.php:116`<br>`resources/views/grupos/_form.blade.php:88`<br>`resources/views/grupos/edit.blade.php:16` | `Editar`<br>`<button type="button" id="btn-add-plan" class="ds-btn ds-btn--secondary">`<br>`<x-ds.button variant="primary" type="submit">Guardar</x-ds.button>` | Captura de la pantalla real (CapturasEtapaCTest). |
| Deporte y nivel no cambian al editar | `resources/views/grupos/_form.blade.php:13` | `{{-- Deporte: fijo en edición, seleccionable en create --}}` | Leído en el código; no ejecutado en pantalla. |
| Eliminar en la ficha del grupo (Ver → Eliminar en la frecuencia) | `resources/views/grupos/index.blade.php:121`<br>`resources/views/grupos/show.blade.php:132`<br>`app/Http/Controllers/GrupoWebController.php:439` | `Ver`<br>`data-confirmar-envio="¿Eliminar este plan?">`<br>`return back()->with('error', "{$plan->clases_por_semana}x por semana tiene {$alumnosHoy} {$palabra}: cambiales el plan antes.");` | Leído en el código; no ejecutado en pantalla. |
| Una frecuencia que tuvo alumnos se desactiva (activo = false) sin borrarse físicamente (T94) | `app/Http/Controllers/GrupoWebController.php:460` | `$plan->update(['activo' => false]);` | Ejecutado (SacarFrecuenciaGrupoT94Test, T94): se desactiva y conserva historial. |
| Selector de frecuencia de 1 a 5 | `resources/views/grupos/_form.blade.php:100` | `@for($f = 1; $f <= 5; $f++)` | Captura de la pantalla real (CapturasEtapaCTest). |
| Un grupo por deporte y nivel | `app/Http/Controllers/GrupoWebController.php:72` | `'nivel_id.unique'                        => 'Ya existe un grupo con ese deporte y nivel.',` | Leído en el código; no ejecutado en pantalla. |
| Precio nuevo: altas desde hoy, existentes desde el mes siguiente | `resources/views/grupos/_form.blade.php:82` | `Al guardar un precio: altas nuevas desde {{ today()->format('d/m/Y') }};` | Captura de la pantalla real (CapturasEtapaCTest). |
| Una frecuencia con alumnos activos se rechaza con aviso (T94) | `app/Http/Controllers/GrupoWebController.php:195` | `return back()->withInput()->with('error', "{$removido->clases_por_semana}x por semana tiene {$alumnosHoy} {$palabra}: cambiales el plan antes.");` | Ejecutado (SacarFrecuenciaGrupoT94Test, T94): rechaza y avisa cuántos alumnos tiene. |
| Al menos una frecuencia | `app/Http/Controllers/GrupoWebController.php:60` | `'planes'                         => 'required\|array\|min:1',` | Leído en el código; no ejecutado en pantalla. |
| No desactiva con alumnos activos o clases futuras | `app/Http/Controllers/GrupoWebController.php:330` | `if ($alumnosActivos > 0 \|\| $clasesFuturas > 0) {` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede desactivar el grupo porque tiene 5 alumnos activos y 1 clase futura.» |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Grupo creado correctamente.» | `app/Http/Controllers/GrupoWebController.php:103` | Sí — Crear un grupo |
| «Grupo actualizado correctamente.» | `app/Http/Controllers/GrupoWebController.php:293` | Sí — Cambiar un precio o agregar una frecuencia |
| «Ya existe un grupo con ese deporte y nivel.» | `resources/views/grupos/_form.blade.php:67` | Sí — ¿Qué hago si dice «Ya existe un grupo con ese deporte y nivel.»? |
| «Hay dos filas con la misma frecuencia. Dejá una sola.» | `app/Http/Controllers/GrupoWebController.php:67` | Sí — ¿Qué hago si dice «Hay dos filas con la misma frecuencia. Dejá una sola.»? |
| «¿Eliminar este plan?» | `resources/views/grupos/show.blade.php:132` | Sí — ¿Qué hago si quiero sacar una frecuencia? |
| «Plan eliminado.» | `app/Http/Controllers/GrupoWebController.php:468` | Sí — ¿Qué hago si quiero sacar una frecuencia? |
| «Grupo actualizado correctamente.» | `app/Http/Controllers/GrupoWebController.php:293` | Sí — ¿Qué hago si quiero sacar una frecuencia? |
| «Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.» | `app/Http/Controllers/GrupoWebController.php:292` | Sí — ¿Qué hago si quiero sacar una frecuencia? |
| «No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.» | `app/Http/Controllers/GrupoWebController.php:433` | Sí — ¿Qué hago si quiero sacar una frecuencia? |
| «El grupo debe tener al menos una frecuencia para poder cargar alumnos.» | `app/Http/Controllers/GrupoWebController.php:64` | Sí — ¿Qué hago si quiero sacar una frecuencia? |
