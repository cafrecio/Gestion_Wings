# T59 y T47 — El dueño va a Cashflow; avisos en castellano cuando se usa mal una dirección

19/10/2026 · Claude CAB.

## T59 — El dueño que entra a Nuevo movimiento de caja va directo a Cashflow

Decisión de Carlos (DU-24, 13/10): «Llevarlo directo a Cashflow». Caso MOV-006.

**Qué pasaba:** el dueño que abría `/caja/movimiento` veía el formulario de la caja, y al
guardar recibía «Debés abrir la caja y confirmar el efectivo antes de operar», cosa que él
no hace.

| Dónde | Cambio |
|---|---|
| `CajaWebController::movimientoForm` | Si es el dueño, lo manda a `web.cashflow.movimiento` (Nuevo movimiento de Cashflow). |
| `CajaWebController::movimientoStore` | Si el dueño igual envía el formulario de caja (pestaña vieja, dirección escrita), vuelve a Cashflow con «Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.». El turno abierto no recibe nada. |

La operativa sigue igual. Ningún botón del dueño llevaba a esa pantalla; el único enlace está
en el Inicio de la operativa.

`tests/Feature/DuenoMovimientoCajaT59Test.php`, 3 pruebas: el dueño termina en el formulario
de Cashflow; si envía el de caja no se crea ningún movimiento (ni de caja ni de Cashflow) con
un turno de la operativa abierto; la operativa sigue cargando en su caja.

## T47 — Avisos en castellano: dirección de una acción y registro que no existe

Decisión de Carlos (DU-02, 13/10): página en castellano. Caso ACC-014.

**Lo que encontré al leer el código:** la premisa de DU-02 había cambiado. Desde T15 hay un
`Route::fallback` (solo GET) que atrapa toda dirección no encontrada, y abrir a mano una acción
—por ejemplo `/deudas/1/condonar`— ya no daba la página técnica en inglés sino «Página no
disponible» (404). Estaba en castellano, pero decía algo falso: la página existe, es de una
acción. La página en inglés seguía saliendo en el caso inverso, al enviar un formulario a una
dirección que solo se abre (405 sin pantalla propia).

| Dónde | Cambio |
|---|---|
| `resources/views/errors/405.blade.php` (nueva) | «Dirección de una acción» / «Esta dirección no se abre a mano» / «Es la dirección de una acción: se usa desde su botón en la pantalla. No se hizo ningún cambio.» con Volver al inicio del rol. Mismo formato que la 404. |
| `routes/web.php`, `Route::fallback` | Si la dirección existe con otro método, responde 405 (con la cabecera `Allow`) en lugar de 404. Si no existe con ningún método, sigue siendo 404. |
| `App\Support\AvisoError::texto` (nueva) | El texto que ve la persona cuando una acción falla: un registro inexistente dice «Ese registro no existe o ya no está. Volvé a la pantalla y probá de nuevo.»; un fallo de base o de PHP dice «No se pudo completar. Probá de nuevo y, si se repite, avisá al dueño.» y queda en el log; los avisos del negocio salen como estaban. |
| 29 `catch` de 6 controladores de pantalla | `AlumnoWebController` (6, incluidos los cuatro de condonar y reactivar que nombra la tarea), `CajaWebController` (13), `LiquidacionWebController` (7), `CashflowWebController`, `RevisionCobranzaWebController` y `ClaseWebController` (1 cada uno): mostraban `getMessage()` crudo; ahora pasan por `AvisoError`. |

**Lo que no se tocó:** los controladores de la API (`routes/api.php` está apagada desde S2);
`ReciboController` y `PrimeraCargaWebController`, cuyos `catch` registran en el log o
relanzan y no muestran el texto.

`tests/Feature/AvisosCastellanoT47Test.php`, 7 pruebas: seis direcciones de acción abiertas
a mano por el dueño y por la operativa dan la página en castellano con Volver a su inicio;
enviar a una dirección que solo se abre también; una dirección inventada sigue siendo «Página
no disponible»; la caja queda como estaba; un pedido JSON sigue recibiendo JSON; condonar y
reactivar cuota, inscripción y recargo inexistentes avisan en castellano, sin «No query
results» ni `App\Models`; un fallo de base no muestra la consulta y queda en el log; los
avisos del negocio no cambian. `PantallasErrorTest` (12) sigue pasando.

## Navegador

Chrome real sobre `wings_testing_claude` con dueño y operativa sintéticos y un turno abierto
(`evidencia-t59-t47-claude/preparar-local.php`, `recorrer-local.mjs`, `resultado-navegador.json`):

| Captura | Qué muestra |
|---|---|
| `01-t59-duenio-va-a-cashflow-1366.png` | El dueño abre `/caja/movimiento` y queda en Nuevo movimiento de Cashflow. |
| `02-t47-direccion-de-accion-1366.png` | El dueño abre `/deudas/1/condonar`: «Esta dirección no se abre a mano». |
| `03-t47-direccion-de-accion-375.png` | La misma en celular, Volver a la derecha, sin desborde. |
| `04-t47-operativa-validar-1366.png` | La operativa abre `/cajas/1/validar`; Volver la lleva a su Inicio. |

## Suite

Suite completa sola en `wings_testing_claude` (19/10): **901 aprobadas, 4 omitidas, 1 fallida,
8012 aserciones, 958 s**. La fallida es `DocumentacionNoMienteTest`: contó 906 y los documentos
decían 890. Las 6 de diferencia que no son mías son de Gemini (T56/T68), que las commiteó en
0414b68 sin actualizar el contador. Con T59 y T47 son **906**, y así queda declarado. La corrida
incluyó los cambios de Gemini, hoy ya commiteados.

**Lo que se cruzó:** el commit de Gemini (0414b68) se llevó, junto con lo suyo, mis 6 cambios en
`AlumnoWebController` (los `catch` que pasan por `AvisoError`), pero no la clase
`App\Support\AvisoError`. Entre ese commit y este, en HEAD esos `catch` daban error 500. Este
commit agrega la clase y lo resuelve.

## Qué no se verificó

- No se probó en el servidor ni con datos reales: base descartable `wings_testing_claude`.
- El aviso de registro inexistente no se recorrió en Chrome (no hay botón que lo produzca:
  hace falta un formulario viejo o una dirección escrita); lo cubren las pruebas.
