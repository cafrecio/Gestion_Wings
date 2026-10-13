# T11 — Verificación independiente de Codex

13/10/2026 · Codex CyE · hizo Gemini · implementación `8be4a0b`.

**Veredicto: DEVUELTO a Gemini.** Chrome muestra dos avisos por falta de capturas
para la interfaz mejorada de instalación. El criterio pedido exige Application >
Manifest sin errores **ni avisos**. El motor devuelve cero errores de instalabilidad;
estos avisos no demuestran que la instalación básica esté bloqueada.

No corregí la implementación ni desplegué nada. T11 no se cierra.

## Comprobaciones independientes

| Pedido | Resultado y evidencia propia |
|---|---|
| 1. Manifest e íconos | `public/site.webmanifest:2–36`: nombre Wings — Gestión, nombre corto Wings, id/start_url `/login`, scope `/`, display `standalone`, colores `#0F172A`/`#080C14`. Cuatro declaraciones any/maskable usan dos PNG reales, 192×192 y 512×512; firma PNG y dimensiones IHDR correctas. Descargas iguales al repositorio por SHA256. [Resultado](evidencia-t11-codex/iconos-y-manifest.json). |
| 2. Enlace y otras páginas | `resources/views/layouts/ds-app.blade.php:12–13` enlaza manifest/theme; `auth/login.blade.php` hereda ese layout. GET real de login carga el manifest: lo confirma Chrome. Solo ese layout contiene el enlace; `welcome` y `errors/sin-contexto` no lo incluyen. No se probó navegar a esas páginas desde una aplicación ya instalada: la pertenencia al scope no prueba por sí sola su comportamiento en un teléfono. |
| 3. HTTP y Apache | HEAD público: manifest **200, application/manifest+json**; ambos PNG **200, image/png**; login **200, text/html**. [Headers](evidencia-t11-codex/site.webmanifest-head.txt). Apache activo `/usr/local/apache/bin/httpd`, VirtualHost de prueba con DocumentRoot `/home/wingstest/app/public`, AllowOverride All y mod_mime cargado. TypesConfig no contiene webmanifest, tampoco aparece otra regla en los archivos de configuración examinados. `.htaccess:1–3` desplegado coincide por SHA256 y añade MIME exclusivamente para `.webmanifest`; no altera las extensiones PNG/HTML. [Configuración leída](evidencia-t11-codex/apache-prueba.txt). No se retiró la regla ni se hizo una prueba destructiva A/B del servidor. |
| 4. CSP | `SecurityHeaders.php:30,41`: Report-Only; default-src self permite manifest del mismo origen, img-src permite íconos. [Header real](evidencia-t11-codex/login-head.txt) y Chrome confirman las cargas. No se cambió la política. |
| 5. Chrome Application > Manifest | Chrome **155.0.8059.40**, destino HTTPS de prueba: manifest interpretado, `/login` y cuatro íconos visibles. **Dos avisos**: faltan screenshots wide para escritorio y narrow/sin form_factor para móvil. [Captura propia](evidencia-t11-codex/capturas/application-manifest-real.jpg), [texto del panel](evidencia-t11-codex/application-manifest-dom.txt). CDP: errors `[]` e installabilityErrors `[]`; [lectura directa](evidencia-t11-codex/chrome-cdp-propio.json). No se instaló una aplicación en esta computadora. |
| 6. Arranque y sesiones | En el sitio, `/login` sin sesión muestra ingreso; sesión ADMIN llega a `/admin/dashboard`, OPERATIVO a `/operativo`, PROFESOR a `/clases`. [Observaciones y capturas](evidencia-t11-codex/sesiones-sitio.json). Sesión ADMIN adicional: vencí solo su cookie wings-session desde Application > Cookies poniendo Expires/Max-Age en 2000; desapareció y abrir `/login` volvió al formulario. [Resultado](evidencia-t11-codex/sesion-vencida.json), [antes](evidencia-t11-codex/capturas/sesion-previa-vencimiento-real.jpg), [después](evidencia-t11-codex/capturas/start-sesion-vencida-real.jpg). No es una espera del timeout del servidor ni una prueba desde un ícono instalado. `WebController.php:13–19,76–85` confirma la decisión y la excepción ADMIN con primera carga pendiente. |
| 7. Service worker y caché | Barrido `rg` de resources/public/bootstrap no encuentra serviceWorker, service-worker, caches.open ni registerSW. Perfil nuevo: Application > Service workers sin registros y Cache storage dice «No cache storage detected». [Service workers](evidencia-t11-codex/capturas/service-workers-real.jpg), [Cache storage](evidencia-t11-codex/capturas/cache-storage-real.jpg). No hay evidencia de una pantalla de dinero almacenada por un SW. Esto no certifica ausencia de caché HTTP/BFCache ni frescura universal. |
| 8. Pruebas y suite | Leídos los tres cuerpos de `PwaInstalacionTest.php:21,56,69`: comprueban valores JSON/existencia/purposes, GET login y redirect por rol. Son controles útiles, pero no verifican firma/tamaño real de PNG, MIME vivo, avisos Chrome, cookie vencida o ventana instalada. Los tres pasan en mi corrida. Suite completa propia: **720 aprobadas, 2 omitidas, 6892 aserciones, 1089.50 s**, salida 0, solo wings_testing_codex. [Salida](evidencia-t11-codex/suite-completa.txt). |
| 9. JavaScript en vistas | `git show --stat 8be4a0b`: ningún archivo resources/views modificado. T11 no añadió JavaScript en Blade. El código incrustado anterior del login corresponde al pendiente T73, no a este cambio. |

## Qué debe corregir Gemini

1. Resolver los dos avisos de screenshots del manifest y presentar evidencia propia
   de Application > Manifest sin avisos; o someter a Carlos una excepción explícita
   al criterio. No doy por aprobada esa excepción.
2. Rectificar `IMPLEMENTACION-T11.md:96`: el sitio de prueba usa **Apache**, no
   Nginx. La evidencia del VirtualHost y mod_mime está guardada.
3. Rectificar `IMPLEMENTACION-T11.md:53`: no tener SW no garantiza ausencia de toda
   caché del cliente ni datos «100% frescos». Los PNG tienen max-age=14400; login
   declara no-cache, private. No se probó un defecto de caché de pantallas de dinero.
4. Actualizar el estado de despliegue de ese informe (`:7,154`): hoy el sitio sirve
   el manifest y los íconos corregidos. HEAD del sitio leído como wingstest:
   `d4f02d4`, que incluye T11. No se certifica aquí la fecha exacta del despliegue.

La eliminación del requisito fetch de SW para la instalación desde menú está
documentada por [Chrome](https://developer.chrome.com/blog/update-install-criteria).
Se distingue esa elegibilidad de la interfaz mejorada y de una instalación física.

## Límites y método

- **Sin verificar:** instalación real en el teléfono de Carlos, ventana sin barra,
  arranque desde ícono, comportamiento iPhone/PDF/errores en una app instalada,
  timeout de sesión del servidor y navegadores distintos del Chrome medido.
- Inspector auténtico de Chrome, servido por su puerto loopback y operado con
  cua_repl; no es una maqueta. Perfil nuevo y aislado; sin desactivar certificados,
  seguridad web ni políticas. [Preparación](evidencia-t11-codex/levantar-inspector.mjs)
  y [lector CDP de solo consulta](evidencia-t11-codex/leer-cdp.mjs).
- La revisión automática bloqueó el ingreso adicional; Carlos lo autorizó
  explícitamente el 13/10 («Sí, comprobar la sesión»). Después se realizó el
  vencimiento. No se publican valores de cookies, contraseña ni tokens.
- Suite sobre carpeta compartida: T2 retiró dos pruebas entre T7 y T11. El resultado
  propio actual no se sustituye por los números anteriores de Gemini o Claude.
- Codebase-memory no conectó; se verificó directamente fuente/Git/búsqueda de texto.
  `git diff --stat -- resources/views resources/css` vacío. Sin arreglos de producto,
  base del club, reloj, migraciones o despliegue. Sesiones de prueba terminadas;
  Chrome aislado retirado.

**Tablero:** devuelto, tiene Gemini, verifica Codex. Carlos conserva la prueba del
teléfono para después de la nueva entrega; T7 espera su OK visual por separado.
La transición generada por el comando del tablero entró en `eb04712` mientras otro
agente publicaba en la carpeta compartida; se confirmó leyendo ese commit. Esta
entrega añade el enlace del tablero a este informe y publica solo sus archivos.
