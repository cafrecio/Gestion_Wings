# Implementación T117 y T118 — Sesión vencida en peticiones AJAX y mayúsculas con acento

- **Fecha:** 01/11/2026 (Segunda vuelta: 01/11/2026)
- **Agente:** Gemini (Antigravity CAB)
- **Tareas:** T117 y T118 (ambas pasan a `a_verificar`, `tiene=Claude`, `hizo=Gemini`)
- **Base de datos de prueba:** `wings_testing_gemini` exclusivamente
- **Vistas autorizadas tocadas:** 3 (`resources/views/revision-cobranza/index.blade.php`, `resources/views/caja/detalle.blade.php`, `resources/views/movimientos/index.blade.php`) — *Sin cambios visuales adicionales en segunda vuelta*.

---

## 1. T117: Sesión vencida en peticiones AJAX / fetch (ACC-006)

### Qué pasaba
Cuando una sesión expiraba (token CSRF vencido, error HTTP 419):
- Un formulario clásico con envío estándar (POST completo con recarga de página) era capturado por `bootstrap/app.php:98`, que redirigía a `/login` con el mensaje flash: *«Tu sesión expiró. Iniciá sesión nuevamente.»*.
- En cambio, las acciones realizadas mediante JavaScript asíncrono (`window.fetch`), tales como **Cobrar cuota** (`cobrar.js`), **Guardar lista de asistencias** (`clases-show.js`), **Cancelar clase**, **Cambiar profesoras**, alternar estado de profesor (`profesores-estado.js`), o acciones de morosos (`deuda-inactivos.js`), enviaban `Accept: application/json` o `X-Requested-With: XMLHttpRequest`.
- `bootstrap/app.php` excluía las peticiones que esperaban JSON (`if (!$request->expectsJson())`), por lo que Laravel devolvía un JSON nativo en inglés con `{ "message": "CSRF token mismatch." }` (HTTP 419).
- En el cliente, `cobrar.js` o `clases-show.js` mostraban el mensaje crudo en inglés: `✗ CSRF token mismatch.` en un toast o texto en pantalla, y la página quedaba estancada sin redirigir al login ni permitir recuperarse limpiamente.

### Solución en un solo lugar
Se resolvió la raíz del problema en la arquitectura compartida cliente/servidor, sin parches individuales por pantalla:

1. **Servidor (`bootstrap/app.php:82-93`)**:
   En el manejador central de excepciones HTTP 419 (`TokenMismatchException` / `HttpException` con código 419), se detecta si la petición no pertenece a la API externa (`!$request->is('api/*')`):
   - Si espera JSON (`$request->expectsJson()`), almacena en la sesión el flash `'error' => 'Tu sesión expiró. Iniciá sesión nuevamente.'` y devuelve JSON con status 419:
     ```json
     {
       "message": "Tu sesión expiró. Iniciá sesión nuevamente.",
       "redirect": "http://.../login"
     }
     ```
   - Si no espera JSON (formulario común), mantiene la redirección tradicional:
     ```php
     return redirect()->route('login')->with('error', 'Tu sesión expiró. Iniciá sesión nuevamente.');
     ```
   - Las rutas `api/*` se mantienen intactas devolviendo su error JSON estándar sin redirección ni flashes en sesión web.

2. **Cliente (`resources/js/bootstrap.js`)**:
   Se instaló un interceptor global sobre `window.fetch`. Cuando cualquier llamada `fetch` del sistema recibe un código 419:
   - Extrae el destino de redirección (`data.redirect` o `/login`).
   - Activa `window.__ignorarAvisoSalida = true` y `window.onbeforeunload = null` para que advertencias de formulario sucio no frenen la salida.
   - Registra además un listener en fase de captura para `beforeunload` con `event.stopImmediatePropagation()`, cancelando avisos de cualquier script.
   - Ejecuta inmediatamente `window.location.assign(destino)` para llevar al usuario al ingreso.
   - Retorna una promesa indefinidamente pendiente (`new Promise(() => {})`) para evitar que el script llamador ejecute bloques `catch` o muestre mensajes de error locales mientras el navegador navega a la pantalla de login.
   - Al llegar a `/login`, la sesión consume el mensaje flash y muestra la alerta en castellano: *«Tu sesión expiró. Iniciá sesión nuevamente.»*.

3. **Recompilación de assets (`npm run build`)**:
   Se ejecutó la compilación de Vite (`public/build/assets/app-*.js`), asegurando que todos los bundles del sistema carguen el interceptor global.

### Verificación de casos T117 (separación entre lo ejecutado y lo leído)
- **Ejecutado en navegador real (Chrome CDP):**
  - **Cobrar cuota (`cobrar.js`)**: Con sesión vencida (cookie de sesión eliminada), redirige automáticamente a `/login` mostrando la alerta: *«Tu sesión expiró. Iniciá sesión nuevamente.»*.
  - **Guardar lista de asistencias a 375px (`clases-show.js`)**: Al presionar Guardar lista con sesión vencida a 375px de ancho, redirige a `/login` con el mensaje flash.
  - **Cancelar clase (`clases-show.js`)**: Al confirmar la cancelación con sesión vencida, redirige a `/login` con el mensaje flash.
  - **Reingreso de operativa**: Tras iniciar sesión nuevamente, llega a su inicio `/operativo`.
  - **Reingreso de admin**: Tras iniciar sesión nuevamente, llega a su inicio `/admin/dashboard`.
  - **Guardar configuración (`configuraciones.js`)**: (Segunda vuelta) Con campo modificado y sesión vencida, clic de mouse real en Guardar navega directo a `/login` con mensaje flash, sin diálogo `beforeunload`.
  - **Guardar regla (`configuraciones.js`)**: (Segunda vuelta) Con regla nueva y sesión vencida, navega directo a `/login` con mensaje flash, sin diálogo `beforeunload`.
  - **Sesión viva con cambios en Configuración**: (Segunda vuelta) Intentar salir con cambios sin guardar sí abre el diálogo `beforeunload`.
- **Ejecutado por Claude en su control cruzado:**
  - Guardar lista en escritorio -> `/login` con aviso flash.
  - Cambiar profesoras -> `/login` con aviso flash.
  - Estado de profesora -> `/login` con aviso flash.
  - Reingreso de profesora -> `/clases`.
- **Leído (no ejecutado por navegador):**
  - `deuda-inactivos.js`, `liquidaciones-show.js`, `caja-detalle.js`: usan el mismo `fetch` global; cubiertos por arquitectura.
- **Formularios estándar y API:**
  - Formulario común web: probado en `test_peticion_web_formulario_con_sesion_vencida_redirige_a_login_con_flash` (redirect 302 con flash).
  - Petición `api/*`: probado en `test_peticion_api_con_sesion_vencida_no_redirige_a_login` (JSON 419 sin redirección ni flash web).
- **Integridad de datos (nada a medias):**
  - Verificado mediante aserciones de suite y comprobación en base descartable: las peticiones rechazadas con 419 no crearon pagos, asistencias, movimientos operativos ni cancelaron clases.

---

## 2. T118: Mayúsculas y recortes con acentos (mbstring)

### Qué pasaba
En varias vistas y controladores se utilizaban funciones estándar de cadenas en PHP (`strtoupper`, `substr`, `strlen`) en textos en castellano con caracteres multibyte (UTF-8). Esto provocaba:
1. En revisión de cobranzas, `strtoupper` en minúsculas con tilde dejaba la letra en minúscula (ej. `AUTOMáTICAMENTE` en lugar de `AUTOMÁTICAMENTE`).
2. En el detalle de caja, `substr(..., 0, 3)` cortaba la palabra "Fútbol" por bytes en lugar de caracteres multibyte, y `strtoupper` no capitalizaba la 'ú', mostrando `Fú` en vez de `FÚT`.
3. En movimientos de caja, observaciones largas se recortaban con `substr(..., 0, 36)`, lo cual si caía justo en medio de los dos bytes de una vocal con tilde o 'ñ', generaba caracteres corruptos (`?` o rombo de reemplazo UTF-8). Y con `strlen > 36`, observaciones con exactamente 36 caracteres y acentos se truncaban erróneamente agregando `…` porque su conteo de bytes era > 36.
4. En alta/edición de tipos de caja (`TipoCajaWebController`), se usaba `strtoupper` sobre la **abreviatura**, dejando acentos en minúscula (ej. `FúT` en vez de `FÚT`, `PáD` en vez de `PÁD`).

### Cambios realizados
1. **`resources/views/revision-cobranza/index.blade.php:116`**:
   Reemplazado `strtoupper($resolucionLabel)` por:
   ```blade
   {{ mb_strtoupper($resolucionLabel, 'UTF-8') }}
   ```
   Resultado: Muestra `REACTIVADO AUTOMÁTICAMENTE`.

2. **`resources/views/caja/detalle.blade.php:136`**:
   Reemplazado `strtoupper(substr($mov->alumno->deporte->nombre ?? '', 0, 3))` por:
   ```blade
   {{ mb_strtoupper(mb_substr($mov->alumno->deporte->nombre ?? '', 0, 3, 'UTF-8'), 'UTF-8') }}
   ```
   Resultado: Para el deporte "Fútbol", muestra exactamente `FÚT`.

3. **`resources/views/movimientos/index.blade.php:119-121`**:
   Reemplazado el bloque por:
   ```blade
   $alumnoObs = $mov->alumno
       ? $mov->alumno->apellido . ', ' . $mov->alumno->nombre
       : (mb_strlen($mov->observaciones ?? '', 'UTF-8') > 36
           ? mb_substr($mov->observaciones, 0, 36, 'UTF-8') . '…'
           : ($mov->observaciones ?? '–'));
   ```
   Resultado: Recorta a 36 caracteres respetando caracteres multibyte, y una observación de exactamente 36 caracteres con acentos se muestra entera sin puntos suspensivos.

4. **`app/Http/Controllers/TipoCajaWebController.php:52, 94`**:
   El cambio aplica sobre el campo **`abreviatura`** (no sobre el nombre):
   - En `store` (línea 52):
     ```php
     'abreviatura' => mb_strtoupper($request->abreviatura, 'UTF-8'),
     ```
   - En `update` (línea 94):
     ```php
     $datos['abreviatura'] = mb_strtoupper($datos['abreviatura'], 'UTF-8');
     ```
   Resultado: Guarda la abreviatura con acento en mayúscula en base de datos (`FÚT`, `PÁD`).

5. **Auditoría de no afectación**:
   Se verificó que los usos de `substr` sobre fechas (`YYYY-MM-DD`), períodos (`YYYY-MM`), DNI, o códigos numéricos/hex no fueron alterados, manteniéndose rápidos y seguros.

---

## 3. Evidencia y pruebas ejecutadas

### A. Prueba automatizada de Feature
Archivo: [`tests/Feature/SesionVencidaYMayusculasT117T118Test.php`](file:///c:/xampp/htdocs/gestion-wings/tests/Feature/SesionVencidaYMayusculasT117T118Test.php)
Ejecutado sobre `wings_testing_gemini` (10 pruebas pasadas, 43 aserciones):
1. `test_peticion_web_formulario_con_sesion_vencida_redirige_a_login_con_flash`: PASS
2. `test_peticion_ajax_con_sesion_vencida_devuelve_419_json_con_aviso_y_redirect`: PASS
3. `test_peticion_api_con_sesion_vencida_no_redirige_a_login`: PASS
4. `test_despues_de_iniciar_sesion_cada_rol_llega_a_su_inicio`: PASS
5. `test_revision_muestra_reactivado_automaticamente_en_mayusculas_con_tilde`: PASS
6. `test_caja_detalle_abrevia_futbol_como_fut`: PASS
7. `test_movimientos_recorta_observaciones_con_acentos_sin_romper_caracteres`: PASS
8. `test_movimientos_observacion_de_36_caracteres_con_acentos_se_ve_entera_sin_puntos_suspensivos`: PASS *(agregada en segunda vuelta, defiende M8)*
9. `test_tipo_caja_alta_guarda_abreviatura_con_acento_en_mayuscula`: PASS *(agregada en segunda vuelta, defiende M9)*
10. `test_tipo_caja_edicion_guarda_abreviatura_con_acento_en_mayuscula`: PASS *(agregada en segunda vuelta, defiende M10)*

### B. Comprobación en navegador real (Chrome sin cabeza via CDP)
- **Primera vuelta:**
  - Script: `docs/06-pruebas/PRU-04/evidencia-t117-t118/comprobar-navegador-t117-t118.mjs`
  - Capturas:
    - `01-cobrar-sesion-vencida-login.png`
    - `02-guardar-lista-sesion-vencida-375.png`
    - `03-cancelar-clase-sesion-vencida.png`
    - `04-t118-revision-reactivado-automaticamente.png`
    - `05-t118-caja-detalle-fut.png`
    - `06-t118-movimientos-observaciones.png`
- **Segunda vuelta:**
  - Script: `docs/06-pruebas/PRU-04/evidencia-t117-t118/test-configuracion-segunda-vuelta.mjs`
  - Capturas:
    - `07-t117-configuracion-sesion-vencida-login.png`
    - `08-t117-reglas-sesion-vencida-login.png`

### C. Comportamiento comprobado ante cuenta desactivada a mitad de sesión (fuera de la tarea)
- **Comportamiento comprobado en navegador (H2):** Cuando un usuario con la cuenta desactivada ejecuta una acción AJAX como Guardar lista o Cobrar:
  - `fetch` sigue transparentemente la redirección 302 hacia `/login` emitida por `EnsureActiveUserWeb`.
  - Esa primera llamada interna a `/login` consume el flash `'error' => 'Tu cuenta fue desactivada.'`.
  - El interceptor global, al detectar que la respuesta redirigida conduce a `/login`, navega a `/login` por segunda vez.
  - Como el mensaje flash ya fue consumido en la primera petición, el usuario llega a `/login` **sin ningún mensaje de aviso visible**.
  - No se guardan datos a medias (0 asistencias, 0 pagos).
  - Tal como indicó Claude y el prompt, este caso queda fuera de la tarea y su comportamiento lo decidirá Carlos.

### D. Relevamiento en el manual (`resources/manual/`)
- Se verificó que ninguna página del manual describe hoy el comportamiento ante sesión expirada o tiempo de inactividad.

---

## 4. Estado de tareas

- **T117**: `a_verificar`, `hizo=Gemini`, `tiene=Claude`, paso: "Verificar segunda vuelta T117: aviso de salida neutralizado en 419 de configuraciones y reglas"
- **T118**: `a_verificar`, `hizo=Gemini`, `tiene=Claude`, paso: "Verificar segunda vuelta T118: 3 pruebas nuevas agregadas (M8, M9, M10) y suite rápida en verde"

---

## 5. Segunda vuelta (01/11/2026)

### A. T117: Configuración se trababa con sesión vencida (H1)
- **Causa:** En `/configuraciones`, al modificar un campo (`campo.value !== original`), `resources/js/configuraciones.js:89` mantenía activo el listener `beforeunload`. Al recibir 419, `bootstrap.js` ejecutaba `window.location.assign('/login')`, disparando el aviso del navegador. Si la persona cancelaba, la promesa pendiente de `bootstrap.js:18` nunca terminaba, dejando el botón y campo deshabilitados para siempre.
- **Arreglo implementado:**
  1. En `resources/js/bootstrap.js`:
     - Al detectar 419 o redirección a `/login`: define `window.__ignorarAvisoSalida = true` y `window.onbeforeunload = null`.
     - Registra un listener en fase de captura (`capture: true`) sobre `beforeunload`: si `window.__ignorarAvisoSalida` está activo, ejecuta `event.stopImmediatePropagation()`.
  2. En `resources/js/configuraciones.js:89`:
     - Se agrega salvaguarda `if (window.__ignorarAvisoSalida) return;` en el listener de `beforeunload`.
  3. En `resources/js/alumnos-form.js:247`:
     - Se agrega la misma comprobación preventiva `if (window.__ignorarAvisoSalida) return;`.
- **Comprobación en navegador real ([test-configuracion-segunda-vuelta.mjs](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t117-t118/test-configuracion-segunda-vuelta.mjs)):**
  - **Caso 1:** Campo de configuración modificado con teclado real, borrado de cookie de sesión, clic de mouse real en Guardar -> va directo a `/login` con flash `Tu sesión expiró. Iniciá sesión nuevamente.`, sin diálogo `beforeunload`. (Captura: `07-t117-configuracion-sesion-vencida-login.png`).
  - **Caso 2:** Regla de primer pago completada con teclado real, borrado de sesión, clic en Guardar regla -> va directo a `/login` con flash, sin diálogo `beforeunload`. (Captura: `08-t117-reglas-sesion-vencida-login.png`).
  - **Caso 3:** Sesión viva, campo modificado con teclado real, clic en enlace para salir -> sí abre el diálogo `beforeunload` impidiendo salida involuntaria.

### B. T118: Tres pruebas agregadas que defienden mutaciones (H4)
Se agregaron 3 métodos en [`tests/Feature/SesionVencidaYMayusculasT117T118Test.php`](file:///c:/xampp/htdocs/gestion-wings/tests/Feature/SesionVencidaYMayusculasT117T118Test.php):
1. `test_movimientos_observacion_de_36_caracteres_con_acentos_se_ve_entera_sin_puntos_suspensivos`:
   - Texto de 36 caracteres UTF-8 con acentos (`mb_strlen = 36`, `strlen = 38 bytes`).
   - Verifica que se vea completo y NO truncado con `…`.
   - **Prueba de mutación (M8):** Reemplazar `mb_strlen` por `strlen` en `movimientos/index.blade.php:119` hace fallar la prueba (`Failed asserting that ... does not contain ...…`).
2. `test_tipo_caja_alta_guarda_abreviatura_con_acento_en_mayuscula`:
   - Alta de tipo de caja con abreviatura `'fút'`. Verifica que en base de datos quede `'FÚT'`.
   - **Prueba de mutación (M9):** Reemplazar `mb_strtoupper` por `strtoupper` en `TipoCajaWebController.php:52` hace fallar la prueba (`Failed asserting that two strings are identical: -'FÚT' +'FúT'`).
3. `test_tipo_caja_edicion_guarda_abreviatura_con_acento_en_mayuscula`:
   - Edición de tipo de caja con abreviatura `'pád'`. Verifica que quede `'PÁD'`.
   - **Prueba de mutación (M10):** Reemplazar `mb_strtoupper` por `strtoupper` en `TipoCajaWebController.php:94` hace fallar la prueba (`Failed asserting that two strings are identical: -'PÁD' +'PáD'`).

### C. El sembrador seguro (H3)
En `docs/06-pruebas/PRU-04/evidencia-t117-t118/seed-test-gemini.php`:
- Se agregó verificación estricta de la base de datos configurada antes de invocar `migrate:fresh`.
- Solo admite las bases descartables declaradas en `AGENTS.md` §6-bis: `['wings_testing_gemini', 'wings_testing_claude', 'wings_testing_codex']`.
- Si se ejecuta sobre `wings_test` u otra base, emite error a STDERR y aborta con código 1 sin tocar la base. Comprobado en terminal.

### D. Conteo oficial y suite rápida
- Suite rápida en `wings_testing_gemini`:
  ```bash
  $env:DB_DATABASE='wings_testing_gemini'; php artisan test --exclude-group=lento
  ```
- **Conteo oficial:** Actualizado a 1517 pruebas (+3 pruebas en `SesionVencidaYMayusculasT117T118Test`) en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`.
- `DocumentacionNoMienteTest`: PASS (1517 pruebas coincidentes).
- `TableroAlDiaTest`: PASS (6 pruebas).
- `CspSinCodigoIncrustadoTest`: PASS (2 pruebas).
