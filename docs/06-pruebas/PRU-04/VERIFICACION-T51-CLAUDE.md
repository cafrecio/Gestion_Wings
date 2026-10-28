# Verificación de T51 (Codex, `983c6bab`) — Claude CyE, 28/10/2026

**Resultado: aprobada en lógica.** Con el OK visual de Carlos (28/10), T51 queda cerrada. **Pendiente de Carlos:** mirar la «Historia de correcciones» con movimientos reales cuando esté en el sitio de prueba. Los nombres de la base de prueba de Codex («Efectivo de prueba», «Gastos de prueba») no dejan juzgar los textos.

## Lo que se comprobó

- Código leído: `CajaCorreccionService` (bloquea caja y después movimiento, valida rol, usuario activo y estado de la caja, guarda la foto de antes y de después dentro de la misma transacción), `MovimientoOperativo::esManualEditable` (excluye cobros, pagos y reservados), `CajaWebController` (editar, eliminar y el rastro en el detalle; la operativa no ve correcciones de rubros solo del dueño) y la migración (el rastro no tiene FK al movimiento, así que sobrevive a la eliminación).
- Pruebas de Codex y del área de caja y movimientos: **106 aprobadas y 1 omitida** en `wings_testing_claude`.
- Ataques propios, **3/3** ([prueba](evidencia-verificacion-t51-claude/VerificacionT51ClaudeTest.php), fuera de la suite permanente):
  1. Una operativa desactivada con la sesión abierta no corrige nada.
  2. Un movimiento de otra caja, pedido cambiando la dirección: 404 o 403, y nada escrito.
  3. Eliminar dos veces deja un solo rastro; la segunda vez da 404.

## Observación (no frena)

Guardar una edición sin cambiar nada también deja un renglón «Editado», con antes y después iguales.
