# T15 — Pantallas de error en castellano

Entrega de Codex CyE, 10/10/2026. Implementación local; pendiente de control de Claude.
No se desplegó. Carlos autorizó: «No debería salir una 404 nunca. Debemos corregirlo.»

## Cambios

- 404: **Página no disponible**. Página inexistente o registro que ya no está.
- 500: **Algo falló**. Fallo interno registrado; volver a intentar.
- 503: **Estamos actualizando Wings**. Volver en unos minutos.
- 429: **Esperá un momento**. Demasiados intentos seguidos.
- Un botón **Volver**, sin números de error en el título. Se conserva el estado HTTP.
- ADMIN ve **Inicio**, también en la primera carga; se conserva la ruta del menú.

Se reutilizan los componentes, clases y colores de la pantalla 403. No se modificó
CSS ni se agregó JavaScript en HTML. La 403 y el ingreso ante sesión expirada se conservan.

404/429 conservan el menú y llevan al inicio del rol. 500/503 usan un layout que
no consulta autenticación, sesión ni base; el compositor global evita esas consultas.
Su botón va al ingreso existente: si el sistema se recuperó y la sesión sigue vigente,
redirige automáticamente al inicio del rol; si expiró, pide ingresar.

Los fallos internos normales y los HTTP500 explícitos se registran mediante el
manejador de Laravel. Los otros rechazos HTTP mantienen la exclusión del registro.
Los detalles internos no aparecen en las pantallas, incluso con debug habilitado.

## Permisos y respuestas

Carlos aclaró: **«Sí, conservar permisos y login»**.

| Caso | ADMIN | OPERATIVO | PROFESOR | Sin sesión |
|---|---|---|---|---|
| Dirección inventada | nueva 404 | nueva 404 | nueva 404 | nueva 404 |
| `/alumnos/999999` | nueva 404 | nueva 404 | Sin permiso (403) | ingreso (302) |
| Volver con sistema recuperado | Inicio | Inicio operativo | Clases | ingreso |

La ruta de respaldo conserva el contexto web sin cambiar los controles de las
rutas existentes. La API sigue deshabilitada. Solicitudes con `Accept: application/json`
y direcciones bajo `/api/` responden JSON, incluso si la API recibe `Accept: text/html`.
Se conservan validaciones422, ingreso401, permisos403, sesión419 y `Retry-After`.

## Evidencia

- [Visor de pantallas y menú compartido](evidencia/errores-t15/visor.html).
- [Resultados de navegador](evidencia/errores-t15/resultado.json).
- [Caída real de conexión y mantenimiento](evidencia/errores-t15/caida.json).
- [Suite completa](evidencia/errores-t15/suite.txt).
- Automatización permanente: `tests/Feature/PantallasErrorTest.php`.

Las capturas salen de Chrome sobre el Kernel real de Wings: escritorio1366 y un
iframe360 dentro de una ventana grande. Login se captura como control del método.
El router local retira `X-Frame-Options` solo de las respuestas de esta evidencia para
permitir el marco; el middleware y la política del sistema conservan su protección.
El navegador del capturador también omite enviar los avisos CSP causados por alojar
Wings en ese marco. Estos ajustes son exclusivos de la herramienta local de evidencia.
El cambio compartido del menú se captura en cada ruta ADMIN que devuelve dicho menú;
JSON, descargas y redirecciones quedan identificados en los resultados.

La prueba de caída usa otra instancia local, `127.0.0.1:8012`, con conexión al puerto1,
sesión en base y storage propio. `/login` falla durante la sesión y entrega nuestra500;
`artisan down --retry=120` entrega503 con la misma conexión inaccesible.
La conexión normal y la base del club no se detienen ni se modifican.
El mantenimiento se revierte en `finally`. Las claves ficticias y registros privados
quedan en `storage` ignorado, fuera del repositorio.

## Reproducción local

Solo después de terminar cualquier suite que use `wings_testing_codex`:

1. Variables: `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex`,
   `APP_DEBUG=false`, `CACHE_STORE=file`, `APP_MAINTENANCE_DRIVER=file`.
2. Crear las carpetas `logs`, `framework/views`, `framework/sessions`,
   `framework/cache/data` y `app/private` dentro de ambos storage propios.
3. Instancia8011: `DB_PORT=3306`, `SESSION_DRIVER=file`,
   `SESSION_COOKIE=t15-prueba`, `LARAVEL_STORAGE_PATH=storage/app/errores-t15-runtime`.
   Ejecutar `fixture.php` de esta evidencia. **Recrea únicamente la base descartable**
   y se niega a ejecutar fuera de ella.
4. Instancia8012: `DB_PORT=1`, `SESSION_DRIVER=database`,
   `SESSION_COOKIE=t15-caida`, `LARAVEL_STORAGE_PATH=storage/app/errores-t15-caida`.
5. Levantar cada instancia: `php -S 127.0.0.1:PUERTO -t public RUTA/router.php`.
   Usar rutas absolutas para `LARAVEL_STORAGE_PATH` y RUTA en Windows.
6. `node RUTA/capturar.cjs --caida`; conservar `resultado.json` como `caida.json`.
   Después `node RUTA/capturar.cjs` sobre la instancia normal.

El router de evidencia está fuera de `public`, limitado a entorno testing/base Codex.
Agrega rutas de fallo solo a la instancia local, no al archivo de rutas desplegado.

## Comprobaciones y próximo paso

13 pruebas nuevas y301 aserciones focalizadas aprobadas. Suite completa:
**594 aprobadas, 2 omitidas; 4966 aserciones, 695,25s**, en `wings_testing_codex`.
Las dos omitidas son los renderizadores auxiliares de capturas, que requieren
`WINGS_CAPTURAS=1`; las pruebas funcionales no tienen fallas.
Build correcto (46,84s); PHP sin errores; `view:cache` y `view:clear` correctos.

Navegador: **32 capturas** de los cuatro errores/perfiles, con clic real en Volver;
**134 capturas** en67 respuestas con menú ADMIN; cuatro de caída/mantenimiento,
dos de primera carga pendiente y un login360: **173 PNG** reales.
Inventario: 68 rutas GET revisadas; 66 devuelven200, una403 y una204 sin menú.
Cancelar el movimiento del fixture devuelve403: se capturó su menú, conservando
el comportamiento existente. La204 es el cookie CSRF de Sanctum, sin pantalla.
Sin recortes horizontales en las cuatro pantallas de error y control de login;
no se certifica ni se corrige el contenido de otros módulos por este cambio de etiqueta.
Las capturas de menú móvil abren el menú para mostrar Inicio; los marcos tienen
1000px de alto. Los resultados también registran el ancho y el contenido de cada ruta.

El entorno HTTP de caída quedó registrado por el propio servidor en `caida.json`:
puerto1, sesión en base, entorno testing y storage separado. La causa se comprobó
contra el registro privado; ese registro no se publica. Un control independiente
leyó fuente/resultados y cuatro PNG: confirmó el alcance de caída; no ejecutó pruebas.

Control acotado de otro agente: revisó código de manejo, permisos, JSON y registro,
sin correr pruebas ni cerrar la tarea. Claude debe verificar fuente y pantalla,
registrar su resultado y actualizar el sitio de prueba si aprueba. Codex no despliega.
