# Ficha de fuentes: administrar-profesores

- **Página:** `resources/manual/admin/administrar-profesores.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Tarifa: el formulario la marca obligatoria pero Wings guarda sin ella | `app/Http/Controllers/ProfesorWebController.php:163` | `'valor_hora'          => ['nullable', 'numeric', 'min:0', new ImportePesos],` | Ejecutado (CapturasEtapaCTest): HTTP 302 · éxito: «Profesor creado correctamente.» · Se guardó: sí |
| Sin tarifa no se genera la liquidación | `app/Services/LiquidacionService.php:437` | `throw new \Exception('El profesor no tiene valor por hora configurado.');` | Leído en el código; no ejecutado en pantalla. |
| El concepto de sueldo se crea solo | `app/Http/Controllers/ProfesorWebController.php:84` | `$this->crearSubrubroProfesor($profesor);` | Leído en el código; no ejecutado en pantalla. |
| No pasa a inactiva con clases futuras | `app/Services/ProfesorEstadoService.php:23` | `throw ValidationException::withMessages(['profesor' => 'Tiene '.$futuras->count().' clases futuras o en curso. Reasignalas antes de desactivar a la profesora.']);` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «Tiene 9 clases futuras o en curso. Reasignalas antes de desactivar a la profesora.» |
| No pasa a inactiva con caja abierta del usuario vinculado | `app/Services/ProfesorEstadoService.php:31` | `throw ValidationException::withMessages(['profesor' => 'Primero cerrá la caja abierta del usuario vinculado.']);` | Leído en el código; no ejecutado en pantalla. |
| Inactivar desactiva el usuario y cierra sesiones | `app/Services/ProfesorEstadoService.php:33` | `$usuario->forceFill(['activo' => false, 'remember_token' => Str::random(60)])->save();` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «El profesor no tiene valor por hora configurado.» | `app/Services/LiquidacionService.php:437` | Sí — Dar de alta un profesor |
| «El profesor no tiene porcentaje de comisión configurado.» | `app/Services/LiquidacionService.php:443` | Sí — Dar de alta un profesor |
| «Profesor creado correctamente.» | `app/Http/Controllers/ProfesorWebController.php:86` | Sí — Dar de alta un profesor |
| «Profesor actualizado correctamente.» | `app/Http/Controllers/ProfesorWebController.php:113` | Sí — Ver y editar un profesor |
| «Primero cerrá la caja abierta del usuario vinculado.» | `app/Services/ProfesorEstadoService.php:31` | Sí — ¿Qué hago si no me deja pasarlo a inactivo? |
| «La cuenta vinculada está protegida. No se puede desactivar desde Profesores.» | `app/Services/ProfesorEstadoService.php:28` | Sí — ¿Qué hago si no me deja pasarlo a inactivo? |
| «Ya existe un profesor con ese DNI.» | `app/Http/Controllers/ProfesorWebController.php:176` | Sí — ¿Qué hago si dice «Ya existe un profesor con ese DNI.»? |
