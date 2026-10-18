# T37 — Verificación de Claude · 17/10/2026 · APROBADA Y CERRADA

Verifico las reglas del rechazo de `f0e2509` (Codex). Carlos ya aprobó el aspecto de Detalle en
celular (`575d99f`, `4b050ac`, `48382d0`); eso no se vuelve a revisar acá.

## Qué leí

- `CajaWebController::rechazar`: motivo `required|string|max:500` con mensajes en castellano. Los
  espacios solos llegan vacíos (Laravel recorta y convierte vacío en nulo) y caen en «obligatorio».
- `CajaService::rechazarCaja`: recorta y valida lo mismo antes de abrir la transacción, así que
  ninguna otra puerta guarda un rechazo sin motivo. La API que también llama a este servicio
  está apagada (`bootstrap/app.php:17`).
- `caja/detalle` y `caja/resumen`: el campo dice `required`, se sacó «(opcional)», conserva lo
  escrito si vuelve con error y el error se ve arriba de la pantalla (`@error('motivo')`), no
  dentro del modal cerrado. Sin JavaScript nuevo en las vistas.
- `motivo_rechazo` es `TEXT`: 500 caracteres entran sin cortarse.

## Pruebas

Las 8 de Codex (`CajaRechazoMotivoT37Test`) y las 22 de `CajaCambioInicialA25Test` pasan en
`wings_testing_claude`. Mis controles,
[`evidencia-t37-claude/VerificacionT37ClaudeTest.php`](evidencia-t37-claude/VerificacionT37ClaudeTest.php),
3 pruebas en verde:

| Control | Resultado |
|---|---|
| La operativa intenta rechazar con motivo | 403; la caja sigue Cerrada y sin motivo |
| 500 eñes (caracteres de dos bytes) | Se acepta y se guarda completo |
| Motivo con espacios en los bordes | Se guarda recortado |

No corrí la suite completa: no cambié código. Codex informa la suya en `IMPLEMENTACION-T37.md`.

## Pendiente fuera de T37

Que la operativa vea la caja rechazada y su motivo en su **Inicio** (caso CAJ-029, línea c).
Carlos eligió entregar T37 sin eso; hoy lo ve en el Resumen de su caja. Si se quiere, va en una
tarea nueva.
