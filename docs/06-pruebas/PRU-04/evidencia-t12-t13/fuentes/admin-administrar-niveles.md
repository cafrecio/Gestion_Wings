# Ficha de fuentes: administrar-niveles

- **Página:** `resources/manual/admin/administrar-niveles.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| No elimina un nivel que usa un grupo | `app/Http/Controllers/NivelWebController.php:79` | `if ($nivel->grupos_count > 0) {` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede eliminar: hay 1 grupo(s) que usan este nivel.» |
| Nombre repetido: compara en minúsculas y sin espacios en los extremos | `app/Rules/NombreUnico.php:29` | `$nombre = mb_strtolower(trim($nombre));` | Leído en el código; no ejecutado en pantalla. |
| Un grupo no cambia de nivel | `app/Http/Controllers/GrupoWebController.php:166` | `// nivel_id no cambia en edición` | Leído en el código; no ejecutado en pantalla. No hay ruta para borrar grupos. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Nivel creado correctamente.» | `app/Http/Controllers/NivelWebController.php:35` | Sí — Crear un nivel |
| «Nivel eliminado.» | `app/Http/Controllers/NivelWebController.php:85` | Sí — Editar o eliminar un nivel |
| «Ya existe un nivel con un nombre similar.» | `app/Http/Controllers/NivelWebController.php:26` | Sí — ¿Qué hago si dice «Ya existe un nivel con un nombre similar.»? |
