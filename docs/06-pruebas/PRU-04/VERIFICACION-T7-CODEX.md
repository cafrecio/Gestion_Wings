# T7 — Verificación independiente de Codex

13/10/2026 · Codex CyE · hizo Claude · `d4f02d4`.

**Veredicto: APROBADO en el código y en Chrome, con el OK visual de Carlos pendiente.**
No se cierra T7. No se corrigió código ni se desplegó nada.

## Qué comprobé

| Pedido | Evidencia propia y resultado |
|---|---|
| 1. Copia literal de las reglas | `git show d4f02d4^:resources/css/sidebar.css`, más el `app.css` anterior, contra el archivo real actual. Árbol CSS completo igual al quitar comentarios; bloque final literal igual. [Comparación](evidencia-t7-codex/resultado-css.json) y [verificador](evidencia-t7-codex/verificar-css.mjs). |
| 2. Cascada | `resources/css/app.css:1350–1515`: el bloque queda al final y conserva el orden de reglas/declaraciones de `app.css` + `sidebar.css` anteriores. No hay otra regla de menú posterior. Igualdad confirmada por el árbol CSS completo y por estilos calculados. |
| 3. Cargas y compilación | Eliminado el archivo original y su entrada de Vite; las cargas de `layouts/ds-app.blade.php:14` y `errors/sin-contexto.blade.php:8` solo incluyen `app.css` y JavaScript. `npm run build`: salida 0, 30.16 s; `public/build/assets/sidebar-*.css`: ninguno. [Build](evidencia-t7-codex/build.txt). |
| 4. Otras vistas | Barrido de `resources/views`: solo `ds-app` y `sin-contexto` contienen clases `.ds-sidebar`, `.ds-nav-*` o `.ds-brand*`. `welcome.blade.php:15` carga `app.css`, pero no usa esas clases. El barrido y su ausencia se comprueban también en el verificador. |
| 5. Sitio y pantallas | Ingresos reales con ADMIN, OPERATIVO y PROFESOR; menús en 1366 y 360, desplegados en celular, renglón activo «Sin asistencia» y contador 33. HTML real preservado; después se comparó ese mismo documento con CSS histórico y actual en marcos de ancho real. [Observaciones](evidencia-t7-codex/observaciones-sitio.json), [estilos](evidencia-t7-codex/comparaciones-estilos.json), [capturas](evidencia-t7-codex/visor.html). |
| 6. Suite propia | Solo `DB_DATABASE=wings_testing_codex`: **722 aprobadas, 2 omitidas, 6910 aserciones, 1106.41 s**. [Salida completa](evidencia-t7-codex/suite-completa.txt). No se heredó la corrida de Claude. |

`git pull --ff-only` al comenzar: ya actualizado. El índice `codebase-memory` no
conectó (Transport closed); se verificó leyendo fuentes, con búsqueda de texto y Git.
`git diff --stat -- resources/views resources/css`: vacío; ninguna vista o CSS de producto modificados.

La carpeta siguió compartida: durante la revisión hubo cambios ajenos de T2,
incluidas eliminaciones preparadas del exportador y sus pruebas. La cifra de arriba
es la corrida registrada, no un conteo heredado ni una afirmación sobre una carpeta
inmutable. Esos cambios no entran en la publicación de esta verificación.

## Comprobación del sitio y del error

El servidor de prueba declara `d4f02d4` y no tiene cambios de trabajo, comprobado por SSH.
[Estado leído](evidencia-t7-codex/estado-servidor.json). El CSS descargado desde el enlace
observado en Chrome tiene las mismas 37 reglas de menú que el compilado actual verificado.
[Comprobación](evidencia-t7-codex/css-sitio-confirmado.json).

La pantalla 500 «sin contexto» se obtuvo del **renderer real de `/home/wingstest/app`**
con PHP 8.2, sin inducir un fallo HTTP ni poner el sitio en mantenimiento. Se bloqueó
cualquier conexión a base antes de registrar proveedores: devolvió 500 y cero consultas.
[Diagnóstico](evidencia-t7-codex/render-error-servidor.php), [resultado](evidencia-t7-codex/error-renderer.json)
y [HTML emitido](evidencia-t7-codex/error-sin-contexto-respuesta.html).
`AppServiceProvider.php:46–50` fuerza el contexto sin sesión y contador cero para esa
pantalla; el layout no usa el rol. La suite también recorre los errores con los cuatro perfiles.
Se renderizó su HTML en Chrome a 1366 y en un iframe real de 360.
Los HTML preservados tienen los tokens CSRF enmascarados y los espacios al final de
línea normalizados; no se reconstruyó contenido ni se escribió una maqueta del sistema.

## Las declaraciones de Claude

- **Fuente y cascada conservadas:** confirmado, regla por regla, incluyendo el prefijo antiguo de `app.css`.
- **Compilado literalmente igual:** no. Hay 36 reglas de menú antes y 37 después: se agrega el respaldo de gradiente y su condición `@supports`. Esto coincide con la diferencia que Claude declaró; no se escondió contando solo la fuente.
- **Respaldo sin efecto en el Chrome probado:** confirmado. Chrome admite `color-mix` en `srgb` y en `lab`; la rama con mezcla mantiene el mismo fondo calculado. [Soporte leído](evidencia-t7-codex/soporte-chrome.json). No se extrapola a un navegador sin ese soporte.
- **Logo `0% 0%` → `0px 0px`:** confirmado. Es la única diferencia de estilos calculados, sobre el logo; antes y después su `background-image` es `none`.
- **67 elementos del menú ADMIN:** comprobados independientemente, con todos sus estilos y pseudo-elementos. OPERATIVO: 35; PROFESOR: 11. En ambos anchos, la única diferencia es la posición de un fondo inexistente.

Las **ocho parejas de capturas de menú/error son idénticas byte por byte**:
tres roles × dos anchos y error × dos anchos. No son las capturas de Claude.
[Hashes y controles](evidencia-t7-codex/resumen-final.json) ·
[Control reproducible de la evidencia](evidencia-t7-codex/cerrar-evidencia.mjs).
El login en marco 360 se usó como control del método; no se exige igualdad de su cursor parpadeante.

Queda una mención **comentada** a `sidebar.css` en `app.css:1353`, documentando de dónde
vino el bloque. También quedan archivos anteriores dentro de evidencias históricas.
No hay importación ni carga activa de `sidebar.css` en el producto. Las compilaciones
históricas de esta evidencia conservan el archivo separado a propósito.

## Límites y siguiente paso

Sin verificar: navegador sin `color-mix`, teléfono físico y un error 500 provocado por
HTTP con sesión en cada rol. Para el último se comprobó el renderer desplegado sin
base ni sesión, más las pruebas HTTP locales; no se hace pasar eso por un incidente real del sitio.
Producción, datos del sitio de prueba y reloj no se tocaron.

Tablero: `a_verificar`, hizo Claude, verifica Codex, tiene Carlos.
**Carlos: mirar las capturas y dar el OK visual.** Esta verificación no cierra T7.
