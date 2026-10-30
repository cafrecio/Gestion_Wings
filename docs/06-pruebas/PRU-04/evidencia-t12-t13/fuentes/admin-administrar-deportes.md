# Ficha de fuentes: administrar-deportes

- **Página:** `resources/manual/admin/administrar-deportes.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Crear: «Deporte creado correctamente.» | `app/Http/Controllers/DeporteWebController.php:40` | `return redirect()->route('web.deportes.index')->with('success', 'Deporte creado correctamente.');` | Captura de la pantalla real (CapturasEtapaCTest). |
| Cambiar la forma de liquidar rige desde el mes siguiente | `app/Http/Controllers/DeporteWebController.php:67` | `->with('success', $cambio ? 'Cambio programado desde el '.today()->startOfMonth()->addMonth()->format('d/m/Y').'.' : 'Deporte actualizado correctamente.');` | Captura de la pantalla real (CapturasEtapaCTest). Muestra «Programado: Por comisión desde el 01/11/2026». |
| El cambio requiere no tener liquidaciones abiertas del deporte | `app/Services/DeporteLiquidacionService.php:24` | `if ($abiertas->isNotEmpty()) {` | Leído en el código; no ejecutado en pantalla. |
| No desactiva con alumnos activos o clases futuras | `app/Http/Controllers/DeporteWebController.php:87` | `if ($alumnosActivos > 0 \|\| $clasesFuturas > 0) {` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede desactivar el deporte porque tiene 5 alumnos activos y 9 clases futuras.» |
| Un deporte inactivo no se ofrece al crear grupos, alumnos y profesores | `app/Http/Controllers/GrupoWebController.php:41` | `$deportes = Deporte::where('activo', true)->orderBy('nombre')->get();` | Leído en el código; no ejecutado en pantalla. Igual en AlumnoWebController y ProfesorWebController. |
| Aviso de profesores sin porcentaje al pasar a comisión | `resources/views/deportes/edit.blade.php:24` | `@if(old('tipo_liquidacion', $tipoSiguiente) === 'COMISION' && $sinPorcentaje->isNotEmpty())` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Deporte creado correctamente.» | `app/Http/Controllers/DeporteWebController.php:40` | Sí — Crear un deporte |
| «Deporte actualizado correctamente.» | `app/Http/Controllers/DeporteWebController.php:67` | Sí — Cambiar el nombre o la forma de liquidar |
| «Para liquidar por comisión falta cargar un porcentaje válido a:» | `resources/views/deportes/edit.blade.php:26` | Sí — ¿Qué hago si al pasar a comisión aparece «Para liquidar por comisión falta cargar un porcentaje válido a:»? |
| «Ya existe un deporte con ese nombre.» | `app/Http/Controllers/DeporteWebController.php:28` | Sí — ¿Qué hago si dice «Ya existe un deporte con ese nombre.»? |
