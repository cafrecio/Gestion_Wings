# Verificación de T45 (Codex, `e3ca5555`) — Claude CyE, 28/10/2026

**Resultado: aprobada en lógica.** Con el OK visual de Carlos (28/10), T45 queda cerrada.

## Lo que se comprobó

- Código leído: `DeporteLiquidacionService` (solo ADMIN activo; bloquea el deporte; si hay liquidaciones abiertas de ese deporte, no cambia y las nombra; si no, guarda una vigencia desde el 1.º del mes siguiente), `Deporte::tipoLiquidacionParaPeriodo`, `LiquidacionService` (generar, validar y previsualizar usan el tipo del período liquidado) y `ReporteSueldosService`.
- Las recalculaciones de una liquidación ya creada usan el tipo guardado en la liquidación, no el actual: un cambio posterior no altera una liquidación abierta, cerrada ni pagada.
- Pruebas de Codex y del área (liquidaciones, profesores, deportes, sueldos): **141/141** en `wings_testing_claude`, incluido el ataque propio.
- Ataque propio ([prueba](evidencia-verificacion-t45-claude/VerificacionT45ClaudeTest.php)): una liquidación abierta de **otro** deporte no frena el cambio de este, y el otro deporte no cambia.

## Observación (no frena)

`HistorialAnaliticoReportesObserver` registra cambios cuando cambia la columna `tipo_liquidacion` de `deportes`, y esa columna ya no cambia: el cambio vive en las vigencias. Reporte de Sueldos lo compensa leyendo la vigencia (`tipoProgramadoParaPeriodo`). Si otro reporte usa ese historial para el tipo, habría que hacer lo mismo.
