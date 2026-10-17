# Implementación T48 y T49 — Operativa renombrada y operativa con la caja abierta

- **Fecha:** 17/10/2026
- **Autor:** Gemini (**LOG GEM CAB**)
- **Tareas:**
  - **T48:** Al renombrar a una operativa, su rubro de sueldo sigue con el nombre viejo (`Op-<nombre viejo>`). Decisión de Carlos: *«Renombrarlo solo al guardar»*.
  - **T49:** Se puede desactivar o cambiar de rol a una operativa con la caja abierta. Decisión de Carlos: *«Avisar y pedir que primero se cierre la caja»*.
- **Estado:** Implementado localmente (`a_verificar` por Claude). Sin despliegue.

---

## 1. Cambios implementados

### 1.1. T48 — Renombrado del subrubro de sueldo operativo al guardar

1. **[`SubrubroSueldoService`](../../../app/Services/SubrubroSueldoService.php):**
   - `paraUsuarioOperativo(User $usuario)`: cuando el usuario tiene rol `OPERATIVO` y ya posee `subrubro_id`, verifica si el atributo `name` cambió en el guardado (`$usuario->wasChanged('name')`). Si cambió, delega en `renombrarParaUsuarioOperativo($usuario)`; si no cambió, no hace nada y devuelve `null`.
   - `renombrarParaUsuarioOperativo(User $usuario)`: busca el `Subrubro` vinculado por `$usuario->subrubro_id` y actualiza su campo `nombre` a `'Op-' . $usuario->name` manteniendo el mismo `id`. No crea un subrubro nuevo ni toca los movimientos históricos de `cashflow_movimientos` imputados a ese `subrubro_id`.
   - `existeChoqueParaOperativo(User $usuario, string $nombreNuevo)`: consulta si ya existe otro registro en `subrubros` (`id != $usuario->subrubro_id`) con `nombre = 'Op-' . $nombreNuevo`.
   - No se modificó `paraProfesor(Profesor $profesor)`.

2. **[`UsuarioWebController::update`](../../../app/Http/Controllers/UsuarioWebController.php):**
   - Antes de persistir cambios ni revocar sesiones, si el usuario queda con rol `OPERATIVO`, ya tiene `subrubro_id` y su nombre cambió (`$usuario->name !== $request->name`), comprueba `$subrubroService->existeChoqueParaOperativo($usuario, $request->name)`. Si existe choque con otro subrubro, rechaza la operación con `back()->withInput()->with('error', 'Ya existe un subrubro de sueldo con ese nombre.')` sin guardar nada a medias.
   - Envuelve el guardado del usuario, la revocación de accesos (si cambió la contraseña) y la llamada a `$subrubroService->paraUsuarioOperativo($usuario)` dentro de `DB::transaction(...)` para garantizar atomicidad total («todo o nada»).
   - Conserva intacto el comentario que documenta que el subrubro nunca se le quita a quien deja de ser operativa.

### 1.2. T49 — Bloqueo de desactivación y de cambio de rol con caja abierta

1. **[`UsuarioWebController`](../../../app/Http/Controllers/UsuarioWebController.php):**
   - Define el aviso único `AVISO_CAJA_ABIERTA = 'Este usuario tiene una caja abierta. Primero hay que cerrar la caja.'`, redactado con el mismo tono de los demás avisos del controlador.
   - Usa `CajaService::obtenerCajaAbierta($usuario->id)` sin modificar [`CajaService.php`](../../../app/Services/CajaService.php).
   - En `toggleActivo`: si `$usuario->activo` es `true` y `obtenerCajaAbierta($usuario->id)` devuelve una caja abierta, no cambia `activo`. Si la petición espera JSON (`$request->expectsJson()`), responde HTTP `422` con `['error' => self::AVISO_CAJA_ABIERTA]`; por redirección responde `back()->with('error', self::AVISO_CAJA_ABIERTA)`.
   - En `update`: si el usuario actual es `OPERATIVO` (`$usuario->isOperativo()`), se está enviando un rol distinto al actual (`$request->input('rol') !== $usuario->rol`) y tiene caja abierta (`obtenerCajaAbierta($usuario->id)`), no guarda ningún cambio y devuelve `back()->withInput()->with('error', self::AVISO_CAJA_ABIERTA)`.
   - Si el rol se mantiene en `OPERATIVO`, la edición de nombre, email, contraseña y CBU/alias con la caja abierta sigue permitida.

2. **[`resources/js/ds-app.js`](../../../resources/js/ds-app.js):**
   - En el manejador compartido de `.ds-toggle[data-url]` (usado por `/usuarios`), cuando la respuesta JSON contiene `data.error`, revierte el estado visual del interruptor (`input.checked = !input.checked` y `aria-checked`) y muestra el mensaje en el contenedor `.ds-content` mediante el cartel estándar `<div class="ds-flash ds-flash--error">` ya existente en el sistema. No se tocó ninguna vista Blade ni `resources/css/app.css`.

---

## 2. Caso de choque de nombres en `subrubros.nombre` (consulta para Carlos)

La tabla `subrubros` tiene índice único sobre `nombre` (`2026_02_11_000002_add_unique_nombre_to_subrubros_table.php`).

- **Escenario:** existen dos operativas (o una operativa actual y el subrubro histórico de otra que dejó de serlo) y el dueño intenta renombrar a la segunda con exactamente el mismo nombre que la primera (por ejemplo, ya existe `Op-Sandra Vidal` en otro `subrubro_id` y se intenta guardar a otro usuario operativo como `Sandra Vidal`).
- **Comportamiento actual seguro implementado:** el sistema detecta el choque antes de tocar la base, no inventa ninguna regla de fusión ni sufijos, no guarda cambios parciales en `users` ni en `subrubros` («o se guarda todo o nada») y devuelve el aviso: *«Ya existe un subrubro de sueldo con ese nombre.»*.
- **Pregunta para Carlos:** si alguna vez hubiera dos operativas con el mismo nombre y apellido (o se renombrara a una con el nombre de un subrubro `Op-...` ya existente), ¿confirmás que el sistema rechace el guardado pidiendo diferenciar el nombre (por ejemplo agregando segundo nombre o inicial), como quedó protegido hoy?

---

## 3. Lo verificado vs. lo inferido

### 3.1. Verificado

1. **Pruebas automatizadas nuevas (7 pruebas nuevas, todas en verde sobre `wings_testing_gemini`):**
   - En [`tests/Feature/RubroReservadoTest.php`](../../../tests/Feature/RubroReservadoTest.php):
     - `test_editar_un_operativo_no_le_duplica_el_subrubro`: comprueba además que el nombre del subrubro pasa a `Op-Nombre Cambiado`.
     - `test_renombrar_operativo_renombra_su_subrubro_y_preserva_movimientos_imputados`: verifica que al renombrar a `Sandra Vidal` a `Sandra Gomez`, su `subrubro_id` se mantiene idéntico, el subrubro pasa a `Op-Sandra Gomez`, no se crea un segundo subrubro `Op-%` y el movimiento de `cashflow_movimientos` imputado conserva intactos su `subrubro_id`, monto y observación.
     - `test_editar_operativo_sin_cambiar_el_nombre_no_toca_el_subrubro`: verifica que al cambiar solo el email de la operativa sin cambiar `name`, el subrubro conserva su nombre y su `updated_at` original sin ser modificado.
     - `test_renombrar_operativo_con_choque_de_subrubro_no_guarda_nada`: verifica que si `Op-Sandra Vidal` ya pertenece a otro subrubro y se intenta renombrar a `Pablo Ledesma` como `Sandra Vidal` (cambiando también su email), se rechaza con error en sesión y no se modifica ni el nombre ni el email de Pablo ni ninguno de los dos subrubros.
   - En [`tests/Feature/UsuarioActivoWebTest.php`](../../../tests/Feature/UsuarioActivoWebTest.php):
     - `test_no_se_puede_desactivar_operativa_con_caja_abierta`: verifica el rechazo y la permanencia de `activo = true` tanto por redirección (`PATCH /usuarios/{id}/toggle-activo`) como por JSON (`patchJson`, HTTP `422` con `error`).
     - `test_se_puede_desactivar_operativa_sin_caja_abierta`: verifica que con caja en estado `CERRADA` (sin caja abierta) se desactiva normalmente por redirección y por JSON.
     - `test_no_se_puede_cambiar_el_rol_de_operativa_con_caja_abierta`: verifica que `PUT /usuarios/{id}` cambiando `rol` a `ADMIN` con caja `ABIERTA` devuelve el aviso en `session('error')` y no guarda ni el cambio de rol ni el de nombre o email.
     - `test_se_puede_editar_el_nombre_con_caja_abierta`: verifica que con caja `ABIERTA` y manteniendo `rol = OPERATIVO`, se guardan el nuevo nombre, email y contraseña, y se renombra su subrubro a `Op-Sandra Vidal Renombrada`.

2. **Verificación en navegador Chrome local como dueño (`ADMIN` sintético sobre `wings_testing_gemini`):**
   - Evidencia guardada en [`docs/06-pruebas/PRU-04/evidencia-t48-t49/`](evidencia-t48-t49/):
     - [`01-rubros-antes-renombrar.png`](evidencia-t48-t49/01-rubros-antes-renombrar.png): pantalla `/rubros` mostrando `Op-Sandra Vidal` bajo el rubro reservado `Sueldos`.
     - [`02-usuarios-operativa-renombrada.png`](evidencia-t48-t49/02-usuarios-operativa-renombrada.png): pantalla `/usuarios` tras editar el nombre con caja abierta, mostrando `Usuario actualizado correctamente.` y `Sandra Vidal Renombrada`.
     - [`03-rubros-subrubro-renombrado.png`](evidencia-t48-t49/03-rubros-subrubro-renombrado.png): pantalla `/rubros` mostrando el mismo subrubro actualizado a `Op-Sandra Vidal Renombrada`.
     - [`04-usuarios-aviso-desactivar-caja-abierta.png`](evidencia-t48-t49/04-usuarios-aviso-desactivar-caja-abierta.png): pantalla `/usuarios` tras pulsar el interruptor de activación con caja abierta; muestra el cartel `.ds-flash--error` *«Este usuario tiene una caja abierta. Primero hay que cerrar la caja.»* y el interruptor revertido en posición `Activo`.
     - [`05-usuarios-aviso-cambio-rol-caja-abierta.png`](evidencia-t48-t49/05-usuarios-aviso-cambio-rol-caja-abierta.png): pantalla `/usuarios/{id}/edit` tras intentar cambiar el rol a `Administrador` con caja abierta; muestra el mismo aviso `.ds-flash--error` sin guardar cambios.
     - [`resultado-navegador.json`](evidencia-t48-t49/resultado-navegador.json): registro estructurado del recorrido.

3. **Controles de integridad y suite completa:**
   - Suite completa en `wings_testing_gemini`: **786 pruebas (784 aprobadas y 2 omitidas, 7395 aserciones, 1040,70 s)**, todas en verde (`DocumentacionNoMienteTest`, `TableroAlDiaTest`, `TablerosNoDivergenTest` y `CspSinCodigoIncrustadoTest` incluidos).
   - `php -l` sin errores de sintaxis en los cuatro archivos PHP tocados (`UsuarioWebController.php`, `SubrubroSueldoService.php`, `RubroReservadoTest.php`, `UsuarioActivoWebTest.php`).
   - `php artisan view:cache && php artisan view:clear` sin errores.
   - Ninguna vista ni hoja de estilos del sistema fue tocada por T48/T49.

### 3.2. Inferido / fuera de alcance

- En el alta de un usuario operativo nuevo (`UsuarioWebController::store`), `SubrubroSueldoService::paraUsuarioOperativo` mantiene su comportamiento previo (`firstOrCreate` sobre `Op-<name>`); T48 acota el cambio a la edición (`update`) de una operativa que ya tiene `subrubro_id`.
- No se desplegó al servidor de prueba ni a producción.
