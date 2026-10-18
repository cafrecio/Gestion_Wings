# T36 — Verificación de Claude · 18/10/2026 · APROBADA Y CERRADA

Verificado sobre `8a7d102` (Codex CAB). Decisión de Carlos (DU-54): «Exigir todos tildados para
validar». Aspecto aprobado por Carlos el 18/10: «Las capturas de T36 visualmente están bien».

## Qué leí

- **Migración:** `verificado_at`, `verificado_por` y `verificacion_huella` por movimiento.
- **`MovimientoOperativo`:** la huella es un sha256 del contenido del movimiento (sus campos
  editables). El tilde vale solo si la huella guardada coincide con la actual. Un cambio hecho
  directo en la base, sin pasar por el modelo, también lo invalida; y al editar por el modelo
  se borra.
- **`CajaService::verificarMovimiento`:** solo un dueño activo, solo en caja cerrada y contada,
  y con la huella que vio la pantalla. Si el movimiento cambió desde que se abrió la pantalla,
  rechaza.
- **`CajaService::validarCaja`:** cuenta los movimientos sin tildar, también los cancelados,
  igual que muestra Detalle. Si falta alguno, no valida y dice cuántos. La regla está en el
  servicio, así que cubre Detalle, Resumen y cualquier otra puerta.
- **`CajaWebController`:** validar desde Resumen lleva a Detalle con el aviso. La ruta para
  tildar está dentro de `ensure.admin.web`.
- **JavaScript fuera de las vistas:** se sacó de Detalle y de Resumen (tres bloques y un
  `onsubmit`), a `caja-detalle.js` y `ds-app.js`.
- **Riesgo que descarté:** los campos `reporte_*` entran en la huella, pero solo se escriben al
  crear el movimiento o al cambiar su subrubro, que es un cambio real (`MovimientoReporteObserver`).
  El único cambio masivo es anular un cobro, que lo pasa a cancelado; ahí corresponde volver a
  revisarlo.

## Pruebas

Las 14 de Codex y las de caja, Cashflow, reportes, anulación de cobros y código en vistas: 162
aprobadas y 1 omitida en `wings_testing_claude`. Mis controles,
[`evidencia-t36-claude/VerificacionT36ClaudeTest.php`](evidencia-t36-claude/VerificacionT36ClaudeTest.php)
(se corren desde esa carpeta, sin tocar `tests/`), 3 en verde:

| Control | Resultado |
|---|---|
| Dueño desactivado intenta tildar | 403; el movimiento sigue sin tildar |
| Huella mal formada | 422; nada cambia |
| Tildar por la ruta real y validar | Valida y lleva al listado de cajas |

## Nota sobre la suite de Codex

Codex registró un fallo de T38 en su suite completa (esperaba 4000, obtuvo 7000). Fue por mí:
en ese momento estaba rompiendo el código a propósito en la carpeta compartida para probar mis
pruebas de T38, y su corrida tomó el código roto. Con el código restaurado pasa (su repetición
y mi suite final). No es un defecto de T36 ni de T38.
