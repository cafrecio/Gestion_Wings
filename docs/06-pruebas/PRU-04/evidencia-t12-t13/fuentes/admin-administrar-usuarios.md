# Ficha de fuentes: administrar-usuarios

- **Página:** `resources/manual/admin/administrar-usuarios.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Editar y Guardar al cambiar contraseña o rol | `resources/views/usuarios/index.blade.php:88`<br>`resources/views/usuarios/edit.blade.php:18` | `<a href="{{ route('web.usuarios.edit', $usuario->id) }}" style="{{ $btnBSec }}">Editar</a>`<br>`<x-ds.button variant="primary" tabindex="9" type="submit">Guardar</x-ds.button>` | Captura de la pantalla real (CapturasEtapaCTest). |
| Tarjeta propia: (vos) y sin interruptor | `resources/views/usuarios/index.blade.php:58`<br>`resources/views/usuarios/index.blade.php:90` | `<span style="font-size:0.7rem; font-weight:500; color:var(--color-text-muted); margin-left:6px;">(vos)</span>`<br>`@if($esSelf)` | Captura de la pantalla real (CapturasEtapaCTest). |
| Paso 1: Nuevo en Usuarios | `resources/views/usuarios/index.blade.php:36` | `<x-ds.button variant="primary" href="{{ route('web.usuarios.create') }}">Nuevo</x-ds.button>` | Captura de la pantalla real (CapturasEtapaCTest). |
| Pasos 2 a 4: Nombre, Email, Contraseña, Confirmar contraseña, Rol, CBU o alias, Profesor vinculado | `resources/views/usuarios/_form.blade.php:13`<br>`resources/views/usuarios/_form.blade.php:27`<br>`resources/views/usuarios/_form.blade.php:45`<br>`resources/views/usuarios/_form.blade.php:74`<br>`resources/views/usuarios/_form.blade.php:106`<br>`resources/views/usuarios/_form.blade.php:151`<br>`resources/views/usuarios/_form.blade.php:163` | `Nombre <span class="form-required">*</span>`<br>`Email <span class="form-required">*</span>`<br>`Contraseña {{ isset($usuario) ? '' : '*' }}`<br>`Confirmar contraseña {{ isset($usuario) ? '' : '*' }}`<br>`Rol <span class="form-required">*</span>`<br>`CBU o alias`<br>`Profesor vinculado <span class="form-required">*</span>` | Captura de la pantalla real (CapturasEtapaCTest). |
| CBU o alias solo se guarda para la operativa | `app/Http/Controllers/UsuarioWebController.php:144` | `return $request->input('rol') === User::ROL_OPERATIVO` | Leído en el código; no ejecutado en pantalla. |
| Paso 5: botón Crear | `resources/views/usuarios/create.blade.php:17` | `<x-ds.button variant="primary" tabindex="9" type="submit">Crear</x-ds.button>` | Captura de la pantalla real (CapturasEtapaCTest). |
| Editar sin contraseña no la cambia | `app/Http/Controllers/UsuarioWebController.php:202` | `$cambioLaClave = $request->filled('password');` | Leído en el código; no ejecutado en pantalla. |
| Contraseña mínima de 12 | `app/Http/Controllers/UsuarioWebController.php:32` | `public const MINIMO_CONTRASENA = 12;` | Leído en el código; no ejecutado en pantalla. |
| Cambiar la contraseña cierra las sesiones | `app/Http/Controllers/UsuarioWebController.php:309` | `DB::table('sessions')->where('user_id', $usuario->id)->delete();` | Leído en el código; no ejecutado en pantalla. |
| No se desactiva a uno mismo | `app/Http/Controllers/UsuarioWebController.php:261` | `return back()->with('error', 'No podés inactivarte a vos mismo.');` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No podés inactivarte a vos mismo.» |
| No se desactiva a una operativa con caja abierta | `app/Http/Controllers/UsuarioWebController.php:264` | `if ($usuario->activo && app(CajaService::class)->obtenerCajaAbierta($usuario->id)) {` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.» |
| Profesor vinculado: solo profesores activos sin usuario | `app/Http/Controllers/UsuarioWebController.php:51` | `$profesoresSinUsuario = Profesor::where('activo', true)` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Usuario creado correctamente.» | `app/Http/Controllers/UsuarioWebController.php:119` | Sí — Crear un usuario |
| «Usuario actualizado correctamente.» | `app/Http/Controllers/UsuarioWebController.php:249` | Sí — Cambiar la contraseña o el rol |
| «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.» | `app/Http/Controllers/UsuarioWebController.php:19` | Sí — ¿Qué hago si dice «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.»? |
| «No podés cambiar tu propio rol.» | `resources/views/usuarios/_form.blade.php:141` | Sí — ¿Puedo desactivarme o cambiarme el rol a mí mismo? |
| «Las contraseñas no coinciden.» | `resources/views/usuarios/_form.blade.php:98` | Sí — ¿Qué hago si dice «Las contraseñas no coinciden.»? |
