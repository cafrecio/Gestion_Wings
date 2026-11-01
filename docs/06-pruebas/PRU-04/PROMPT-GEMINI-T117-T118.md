# PROMPT — T117 y T118: sesión vencida y mayúsculas con acento — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T117 y T118 `en_curso, tiene=Gemini`. Las dos van antes de producción, por decisión de Carlos del 01/11. **Acá implementás vos;** después controla Claude. Es aparte de la segunda vuelta de T12 y T13: si la tenés abierta, terminala antes o después, pero no mezcles commits.

## T117: con la sesión vencida, lo que se envía sin recargar la página avisa en inglés

**Qué pasa hoy.** Comprobado en un Chrome real y en el sitio de prueba:

- Con la sesión vencida, **Cobrar** muestra «CSRF token mismatch.» y **Guardar lista** muestra «✗ CSRF token mismatch.». Las dos se quedan en la pantalla. No se guarda nada.
- `bootstrap/app.php:98` lleva al ingreso con «Tu sesión expiró. Iniciá sesión nuevamente.» solo si el pedido no espera JSON. Un formulario común, como Nuevo gasto, ya funciona bien.
- Quien espera JSON recibe el mensaje en inglés del framework, y `clases-show.js:136` y `cobrar.js:183` lo muestran tal cual.

**Qué tiene que pasar:** lo que dice `ACC-006` en `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/ACC.json`. Va al ingreso con «Tu sesión expiró. Iniciá sesión nuevamente.», no se guarda nada a medias, y después de ingresar llega al inicio de su rol. Lo que estaba cargando se pierde: el caso lo admite.

Arreglalo en un solo lugar si se puede, antes que archivo por archivo, y explicá cómo quedó.

| # | Dónde | Comprobá |
|---|---|---|
| 1 | Cobrar, `cobrar.js:164` | Va al ingreso con el aviso; sin cobro ni movimiento |
| 2 | Guardar lista, `clases-show.js:104` | Va al ingreso con el aviso; sin marcas. Con teléfono a 375 también |
| 3 | Cancelar clase y cambiar profesoras, `clases-show.js:213` y `:318` | Ídem |
| 4 | `ds-app.js:84`, `profesores-estado.js:14`, `deuda-inactivos.js:17` | Ídem. Hacé la búsqueda de todos los `fetch` de `resources/js/` que escriben: esta lista puede no estar completa |
| 5 | `caja-detalle.js:37`, `configuraciones.js:31`, `liquidaciones-show.js:24` | Ya tienen un aviso propio en castellano. Decí si cambian o quedan; que no se rompan |
| 6 | Nuevo gasto, un formulario común | Sigue igual |
| 7 | `api/*` | Sigue respondiendo JSON, sin ir al ingreso |
| 8 | Después de ingresar | Llega al inicio de su rol, sin volver a un pedido viejo y sin repetir nada |

**Cómo probarlo.** En PHPUnit el control de CSRF no corre: una prueba común no reproduce el caso. Hacen falta dos cosas:

- **Navegador real:** `docs/06-pruebas/PRU-04/prueba-tecnica/navegador-dias-48-57.mjs` ya borra la cookie y lee lo que aparece. Adaptá una copia en tu carpeta de evidencia, contra una copia local descartable. Corré `npm run build` para ver el JS nuevo. Claves temporales solo en la copia, nunca en el repositorio. No escribas en el sitio de prueba.
- **Prueba en `tests/`:** que falle sin tu arreglo, por ejemplo forzando la excepción del token. Actualizá el número en `ESTADO-ACTUAL.md`: lo controla `DocumentacionNoMienteTest`.

**Fuera de esta tarea:** guardar con la **cuenta desactivada** hoy dice «✗ Error de conexión.» (`clases-show.js:138`). No lo arregles a propósito; si tu cambio lo modifica, informá cómo queda.

## T118: mayúsculas y recortes que rompen los acentos

**Qué pasa hoy.** `resources/views/revision-cobranza/index.blade.php:116` usa `strtoupper` y se ve «REACTIVADO AUTOMáTICAMENTE». `strtoupper` y `substr` trabajan por bytes:

- `resources/views/caja/detalle.blade.php:136`: `strtoupper(substr('Fútbol', 0, 3))` da `Fú`, comprobado con PHP. Patín da `PAT`.
- `resources/views/movimientos/index.blade.php:120`: `substr` de las observaciones puede cortar una letra con acento por la mitad. Esto es leído, no ejecutado.

**Qué hacer:** las versiones `mb_` en esos tres lugares y en cualquier otro igual de `resources/views/` y `app/`, sobre texto que pueda llevar acentos. No toques `substr` sobre fechas, períodos o DNI. Carlos autorizó este cambio de vistas: es solo esto, sin tocar diseño.

**Comprobá:** en pantalla, «REACTIVADO AUTOMÁTICAMENTE» y «FÚT». Una observación larga con acentos en el corte, sin caracteres rotos. Prueba en `tests/` que falle sin el arreglo.

## Reglas

- **No toques `ClaseWebController.php`, `CajaWebController.php` ni `AlumnoWebController.php`:** ahí Claude está reparando T119 al mismo tiempo. Si los necesitás, frená y avisá a Carlos.
- Prohibido JavaScript dentro de una vista (`AGENTS.md` §5-bis).
- Pruebas solo en `wings_testing_gemini`. Corré la suite rápida; la completa la corre Claude al controlar. Sin desplegar.
- Separá lo comprobado de lo leído. Citá `archivo:línea` reales.
- El catálogo y el manual no se tocan: si algo queda desactualizado, proponelo en tu informe. Buscá si alguna página de `resources/manual/` habla de la sesión vencida.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T117-T118-GEMINI.md`, con evidencia en una carpeta propia.
- T117 y T118 `a_verificar, tiene=Claude, hizo=Gemini`, con `php scripts/tablero/tablero.php cambiar`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
