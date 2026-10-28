# Verificación de T89 — Claude, 28/10/2026

Entrega de Codex en `6da3f3a5` (opciones de Carlos: Movimientos A, Cashflow A, Inicio B, Detalle B).
Verificado contra el diff, `PERMISOS-ROLES.md` y las rutas. Base descartable `wings_testing_claude`.

## Resultado: aprobada en lógica. Falta el OK visual de Carlos.

| Punto | Cómo | Estado |
|---|---|---|
| Movimientos: «Registró» es el usuario del movimiento, no el titular de la caja; Recibo abre el pago de la fila | Diff de controlador (`usuario` cargado) y vista; prueba 1 y 7 | Verificado |
| Caja de otra operativa: la operativa la consulta con los movimientos de subrubros OPERATIVO; los totales se calculan sobre esa lista | `CajaWebController::detalle` y `caja/detalle.blade.php` líneas 36-40 | Verificado por lectura |
| La consulta ajena no habilita escribir | Editar solo con `$esProp`; verificar, validar y rechazar en el grupo `ensure.admin.web`; Resumen sigue con `abort(403)` para otra caja; la prueba fuerza cerrar, editar, actualizar, borrar y cancelar (403) | Verificado |
| Profesor sin acceso al detalle | Prueba | Verificado |
| Cashflow: Historial junto a Editar, solo dueño | Diff y prueba (operativa 403) | Verificado |
| Inicio: Sin lista (sin filas), Todos ausentes (filas sin presentes), N presentes | Diff de `withCount` y vista; prueba 6 | Verificado |
| Detalle: hora de registro en hora argentina, separada de la fecha contable | Diff de vista; prueba 4 | Verificado |
| Pruebas de Codex | `PantallasPendientesT89Test`: 7/7, 47 aserciones, corridas acá | Verificado |

**La titular también ve su caja filtrada** (antes la veía completa). Si una caja de operativa
tuviera movimientos de un subrubro del dueño, su detalle no coincidiría con el arqueo. En el sitio
de prueba, el 28/10, los 128 movimientos de caja son de subrubros OPERATIVO. La operativa no puede
cargar subrubros del dueño. *Sin riesgo hoy; inferido para el futuro.*

No verifiqué el aspecto: lo aprueba Carlos con [APROBAR-T89.html](APROBAR-T89.html).
