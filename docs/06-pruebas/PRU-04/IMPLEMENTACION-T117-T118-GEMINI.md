# Implementación T117 y T118 — Sesión vencida en peticiones AJAX y mayúsculas con acento

- **Fecha:** 01/11/2026
- **Agente:** Gemini (Antigravity CAB)
- **Tareas:** T117 y T118 (ambas pasan a `a_verificar`, `tiene=Claude`, `hizo=Gemini`)
- **Base de datos de prueba:** `wings_testing_gemini` exclusivamente
- **Vistas autorizadas tocadas:** 3 (`resources/views/revision-cobranza/index.blade.php`, `resources/views/caja/detalle.blade.php`, `resources/views/movimientos/index.blade.php`)

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

1. **Servidor (`bootstrap/app.php`)**:
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
   - Ejecuta inmediatamente `window.location.assign(destino)` para llevar al usuario al ingreso.
   - Retorna una promesa indefinidamente pendiente (`new Promise(() => {})`) para evitar que el script llamador ejecute bloques `catch` o muestre mensajes de error locales mientras el navegador navega a la pantalla de login.
   - Al llegar a `/login`, la sesión consume el mensaje flash y muestra la alerta en castellano: *«Tu sesión expiró. Iniciá sesión nuevamente.»*.

3. **Recompilación de assets (`npm run build`)**:
   Se ejecutó la compilación de Vite (`public/build/assets/app-*.js`), asegurando que todos los bundles del sistema carguen el interceptor global.

### Verificación de casos T117
- **Caso 1: Cobrar cuota (`cobrar.js`)**: Con sesión vencida, no cobra, no crea movimientos ni pagos; redirige automáticamente a `/login` mostrando la alerta en castellano.
- **Caso 2: Guardar lista de asistencias (`clases-show.js`)**: En escritorio y en móvil (ancho 375px), al enviar con sesión vencida redirige a `/login` con el mensaje; ninguna asistencia queda registrada.
- **Caso 3: Cancelar clase (`clases-show.js`)**: Al confirmar con sesión vencida, redirige a `/login` con el mensaje; la clase permanece activa (no cambia a `CANCELADA`).
- **Casos 4 y 5: Otros módulos JS (`profesores-estado.js`, `deuda-inactivos.js`, `caja-detalle.js`, `configuraciones.js`, `liquidaciones-show.js`)**: Todos ellos emplean `window.fetch` para sus operaciones AJAX; quedan automáticamente cubiertos por el interceptor global sin requerir modificaciones particulares.
- **Caso 6: Formulario común (ej. Nuevo gasto)**: Sigue funcionando mediante el redirect nativo 302 a `/login` con el flash en sesión.
- **Caso 7: `api/*`**: Sigue respondiendo JSON con código 419 sin redirigir al login ni contaminar la sesión web.
- **Caso 8: Reingreso tras login**: Al reingresar con credenciales válidas, cada usuario llega al inicio correspondiente a su rol:
  - `ADMIN` llega a `/admin/dashboard`.
  - `OPERATIVO` llega a `/operativo`.
  - `PROFESOR` llega a `/clases`.
  No se repite ningún envío viejo ni se procesan datos residuales.
- **Integridad de datos (nada a medias)**:
  Se verificó en base de datos tras las pruebas de expiración:
  - `pagos`: 0
  - `asistencias`: 0
  - `clase_estado`: activo (no cancelada)
  - `movimientos_operativos`: sin movimientos creados por las acciones fallidas.

---

## 2. T118: Mayúsculas y recortes con acentos (mbstring)

### Qué pasaba
En varias vistas y controladores se utilizaban funciones estándar de cadenas en PHP (`strtoupper`, `substr`, `strlen`) en textos en castellano con caracteres multibyte (UTF-8). Esto provocaba:
1. En revisión de cobranzas, `strtoupper` en minúsculas con tilde dejaba la letra en minúscula (ej. `AUTOMáTICAMENTE` en lugar de `AUTOMÁTICAMENTE`).
2. En el detalle de caja, `substr(..., 0, 3)` cortaba la palabra "Fútbol" por bytes en lugar de caracteres multibyte, y `strtoupper` no capitalizaba la 'ú', mostrando `Fú` en vez de `FÚT`.
3. En movimientos de caja, observaciones largas se recortaban con `substr(..., 0, 36)`, lo cual si caía justo en medio de los dos bytes de una vocal con tilde o 'ñ', generaba caracteres corruptos (`?` o rombo de reemplazo UTF-8).
4. En alta/edición de tipos de caja (`TipoCajaWebController`), se usaba `strtoupper` sobre el nombre, dejando acentos en minúscula.

### Cambios realizados
1. **`resources/views/revision-cobranza/index.blade.php:116`**:
   Reemplazado:
   ```blade
   {{ strtoupper($resolucionLabel) }}
   ```
   Por:
   ```blade
   {{ mb_strtoupper($resolucionLabel, 'UTF-8') }}
   ```
   Resultado: Muestra `REACTIVADO AUTOMÁTICAMENTE`.

2. **`resources/views/caja/detalle.blade.php:136`**:
   Reemplazado:
   ```blade
   {{ strtoupper(substr($mov->alumno->deporte->nombre ?? '', 0, 3)) }}
   ```
   Por:
   ```blade
   {{ mb_strtoupper(mb_substr($mov->alumno->deporte->nombre ?? '', 0, 3, 'UTF-8'), 'UTF-8') }}
   ```
   Resultado: Para el deporte "Fútbol", muestra exactamente `FÚT`.

3. **`resources/views/movimientos/index.blade.php:119-121`**:
   Reemplazado:
   ```blade
   {{ strlen($mov->observaciones) > 36 ? substr($mov->observaciones, 0, 36) . '…' : $mov->observaciones }}
   ```
   Por:
   ```blade
   {{ mb_strlen($mov->observaciones, 'UTF-8') > 36 ? mb_substr($mov->observaciones, 0, 36, 'UTF-8') . '…' : $mov->observaciones }}
   ```
   Resultado: Recorta a 36 caracteres respetando los límites de caracteres multibyte y evitando bytes huérfanos.

4. **`app/Http/Controllers/TipoCajaWebController.php:52, 94`**:
   Reemplazado:
   ```php
   $data['nombre'] = strtoupper($data['nombre']);
   ```
   Por:
   ```php
   $data['nombre'] = mb_strtoupper($data['nombre'], 'UTF-8');
   ```

5. **Auditoría de no afectación**:
   Se verificó que los usos de `substr` sobre fechas (`YYYY-MM-DD`), períodos (`YYYY-MM`), DNI, o códigos numéricos/hex no fueron alterados, manteniéndose rápidos y seguros.

---

## 3. Evidencia y pruebas ejecutadas

### A. Prueba automatizada de Feature
Archivo creado: [`tests/Feature/SesionVencidaYMayusculasT117T118Test.php`](file:///c:/xampp/htdocs/gestion-wings/tests/Feature/SesionVencidaYMayusculasT117T118Test.php)
Ejecutado sobre `wings_testing_gemini`:
- `test_formulario_comun_con_sesion_vencida_redirige_a_login_con_flash`: PASS
- `test_peticion_ajax_con_sesion_vencida_devuelve_419_json_con_redirect_y_aviso`: PASS
- `test_peticion_api_con_sesion_vencida_devuelve_json_sin_redireccion`: PASS
- `test_despues_de_iniciar_sesion_cada_rol_llega_a_su_inicio`: PASS
- `test_revision_muestra_reactivado_automaticamente_en_mayusculas_con_tilde`: PASS
- `test_caja_detalle_abrevia_futbol_como_fut_en_mayusculas`: PASS
- `test_movimientos_recorta_observacion_multibyte_sin_romper_caracteres`: PASS

Resultado: **7 de 7 pasadas, 29 aserciones**.

### B. Comprobación en navegador real (Chrome sin cabeza via CDP)
Script: `docs/06-pruebas/PRU-04/evidencia-t117-t118/comprobar-navegador-t117-t118.mjs`
Seeder auxiliar: `docs/06-pruebas/PRU-04/evidencia-t117-t118/seed-test-gemini.php`
Resultados registrados en `resultados-navegador.json`:
1. `Cobrar con sesión vencida lleva a /login con aviso` -> **PASS** (URL: `/login`)
2. `Reingreso de operativa llega a inicio de su rol` -> **PASS** (URL: `/operativo`)
3. `Guardar lista con sesión vencida a 375px lleva a /login con aviso` -> **PASS** (URL: `/login`)
4. `Cancelar clase con sesión vencida lleva a /login con aviso` -> **PASS** (URL: `/login`)
5. `Reingreso de admin llega a inicio de su rol` -> **PASS** (URL: `/admin/dashboard`)
6. `T118. Revisión muestra REACTIVADO AUTOMÁTICAMENTE con tilde` -> **PASS**
7. `T118. Caja detalle muestra abreviatura FÚT` -> **PASS**
8. `T118. Movimientos recorta observación sin romper caracteres` -> **PASS**

Capturas guardadas en `docs/06-pruebas/PRU-04/evidencia-t117-t118/`:
- `01-cobrar-sesion-vencida-login.png`
- `02-guardar-lista-sesion-vencida-375.png`
- `03-cancelar-clase-sesion-vencida.png`
- `04-t118-revision-reactivado-automaticamente.png`
- `05-t118-caja-detalle-fut.png`
- `06-t118-movimientos-observaciones.png`

### C. Suite rápida de pruebas
Ejecutada en `wings_testing_gemini` tras liberarse la reserva de Claude:
```bash
$env:DB_DATABASE='wings_testing_gemini'; php artisan test --exclude-group=lento
```
- **Resultado:** **1351 aprobadas, 6 omitidas, 0 fallidas (9885 aserciones)** en **360.62 s**. Salida con código 0.

### D. Comportamiento ante cuenta desactivada a mitad de sesión (fuera de la tarea)
- **Análisis:** Cuando un usuario es desactivado mientras mantiene la sesión abierta, el middleware `EnsureActiveUserWeb` invalida la sesión y responde con una redirección HTTP 302 a `/login` con el error flash `'Tu cuenta fue desactivada.'`.
- **Cómo queda con el cambio:** Anteriormente, `fetch` seguía transparentemente el 302 recibiendo el HTML de login, lo que en scripts como `clases-show.js:138` provocaba que cayera en el bloque de error genérico mostrando `✗ Error de conexión.`. Con el interceptor global en `resources/js/bootstrap.js`, al detectar que la respuesta redirigida conduce a `/login` (`respuesta.redirected && respuesta.url.includes('/login')`), el navegador navega a la URL final `/login` mostrando el error `'Tu cuenta fue desactivada.'` en pantalla, evitando que la interfaz quede varada con un error falso de conectividad.

### E. Relevamiento en el manual (`resources/manual/`)
- Se realizó una búsqueda exhaustiva en las 59 páginas de `resources/manual/` respecto a menciones de sesión vencida, expiración o tiempo límite (`sesión`, `expir`, `vencid`).
- **Hallazgo:** Ninguna página del manual describe hoy el comportamiento ante sesión expirada o tiempo de inactividad (solo se menciona el cierre de sesiones activas al desactivar un profesor en `administrar-profesores.md` y al cambiar la contraseña en `administrar-usuarios.md`).
- **Propuesta para el manual:** Dado que el catálogo ACC-006 admite que lo cargado antes de la expiración se pierde por seguridad, se sugiere que en futuras revisiones de la documentación de ingreso/acceso se aclare brevemente que tras un tiempo prolongado de inactividad, Wings solicitará volver a ingresar por seguridad y descartará los datos no guardados.

---

## 4. Estado de tareas y entrega

- **T117**: `a_verificar`, `hizo=Gemini`, `tiene=Claude`, paso: "Implementada sesión vencida en llamadas AJAX (ACC-006): aviso y redirección a login en un solo lugar. Controla Claude"
- **T118**: `a_verificar`, `hizo=Gemini`, `tiene=Claude`, paso: "Implementado mb_strtoupper y mb_substr en vistas autorizadas y controladores. Controla Claude"
