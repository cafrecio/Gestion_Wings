# T41 — Verificación de Claude · 18/10/2026 · APROBADA Y CERRADA

Verificado sobre `ee65b57` (Gemini CAB). Decisión de Carlos (DU-37): «Impedir cerrar antes de
que termine el mes», desde el día 1 del mes siguiente. Aspecto aprobado por Carlos el 18/10.

## Qué leí

- `Liquidacion::fechaHabilitacionCierre()` (día 1 del mes siguiente, 00:00, zona de Wings) y
  `puedeCerrarse()` (compara con `today()`, que sigue el reloj simulado).
- `LiquidacionService::cerrarLiquidacion`: la regla va en el servicio. Es el único lugar del
  código que pone una liquidación en CERRADA (búsqueda de `ESTADO_CERRADA` en `app/`), así que
  cubre la pantalla y la API apagada. Pagar ya exigía que esté cerrada.
- `liquidaciones/show`: si la liquidación está abierta y el mes no terminó, en vez de Cerrar
  muestra «Se puede cerrar desde el DD/MM/AAAA». Es la única vista que ofrece cerrar.
- El `onsubmit="return confirm(...)"` del formulario Cerrar ya estaba; Gemini lo conservó dentro
  del nuevo `@if`, no agregó código. Queda para T73.

## Pruebas

Las 5 de Gemini (`CerrarLiquidacionMesTerminadoTest`) y las demás de liquidaciones: 62
aprobadas en `wings_testing_claude`. Mis controles,
[`evidencia-t41-claude/VerificacionT41ClaudeTest.php`](evidencia-t41-claude/VerificacionT41ClaudeTest.php)
(se corren desde esa carpeta, sin tocar `tests/`), 3 en verde:

| Control | Resultado |
|---|---|
| Diciembre | Se cierra desde el 01/01/2027; el 31/12 a las 23:59 no |
| Hora del servidor en UTC | 02:30 UTC del 1/11 (23:30 del 31/10 en Buenos Aires) no cierra; 03:00 UTC sí |
| Liquidación ya cerrada | No muestra el aviso |

Captura de Gemini mirada: octubre en curso muestra el aviso en lugar de Cerrar; Recalcular,
Eliminar y Volver siguen.

## Aspecto

Aprobado por Carlos el 18/10 («Están OK») sobre capturas de celular tomadas con el código
actual, ya con T75: [evidencia-t41-claude/](evidencia-t41-claude/) (octubre en curso a 375 y 360, octubre
el 1/11 con fecha simulada y septiembre terminado).
