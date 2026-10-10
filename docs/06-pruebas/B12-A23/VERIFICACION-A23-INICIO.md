# A23 — Verificación de la lógica del inicio del admin

10/10/2026 · Claude CyE, que no lo implementó. Hizo: Codex. Sobre main `5870414`, base
`wings_testing_claude`. El aspecto lo aprobó Carlos el 09/10 y no se juzga acá.

**Dictamen: aprobado. A23 cerrado.**

## Cómo se verificó

No con datos armados para el reporte. Un recorrido que opera el sistema como el club, por
pedidos reales: alta de tres alumnos, apertura de caja, dos cobros de la operativa (efectivo
y transferencia), un gasto, un cobro directo del admin, cierre y validación. En cuatro
momentos se abrió el inicio y se comparó cada número contra la cuenta hecha a mano, sacada
de las tablas de pagos y de deudas.

[Recorrido](verificacion-claude/VerificacionInicioAdminTest.php) ·
[Resultado completo](verificacion-claude/resultado.json)

## Resultado

| Momento | Ingresos | Egresos | Resultado | Disponible | Efectivo confirmado / pendiente | Transferencia confirmado / pendiente | Deuda de cuotas | Coinciden |
|---|---|---|---|---|---|---|---|---|
| Antes de operar | 0 | 0 | 0 | 0 | 0 / 0 | 0 / 0 | 144.000 | 10 de 10 |
| Caja abierta, con cobros y un gasto | 93.000 | 6.000 | 87.000 | 87.000 | 15.000 / 47.000 | 0 / 25.000 | 66.000 | 14 de 14 |
| Caja cerrada, sin validar | 93.000 | 6.000 | 87.000 | 87.000 | 15.000 / 47.000 | 0 / 25.000 | 66.000 | 14 de 14 |
| Caja validada | 93.000 | 6.000 | 87.000 | 87.000 | 62.000 / 0 | 25.000 / 0 | 66.000 | 14 de 14 |

Lo que eso prueba:

- **Los cobros no se cuentan dos veces al validar la caja.** Lo pendiente pasa a confirmado y
  los totales no cambian.
- **El cambio con que se abre la caja ($10.000) no figura como ingreso ni como disponible.**
- **El cobro directo del admin entra confirmado desde el primer momento**; el de la operativa
  queda pendiente hasta que se valida su caja.
- **La deuda baja exactamente lo imputado a cuotas** (144.000 − 78.000 = 66.000).
- **Los cuatro avisos cuentan bien**: cajas por validar pasó de 0 a 1 al cerrar y volvió a 0 al
  validar; una clase de ayer sin lista y una revisión pendiente dieron 1 y 1.
- **Reportes, para el mismo mes, da los mismos cuatro números que el inicio.**
- Ningún movimiento quedó sin clasificar: los que genera la operación normal ya nacen clasificados.

## Observación, fuera de A23

Al cobrar a un alumno que debe la inscripción, el total cobrado incluye la inscripción además
de lo que se indicó para la cuota: se pidió $20.000 y se cobraron $25.000; se pidió $10.000 y
se cobraron $15.000. Coincide con la regla «inscripción primero» (ENT-01), y los $5.000 no
bajan la deuda de cuotas. Conviene mirarlo en la prueba manual, para confirmar que la pantalla
de cobro le muestra ese total a quien cobra antes de aceptar.

## No verificado

- El aspecto, que es de Carlos.
- El aviso de liquidaciones pendientes con datos (dio 0, no había liquidaciones).
- Un mes con movimientos sin clasificar, y los meses anteriores a la activación del historial.
- El efecto de anular un cobro.
- Los reportes de Alumnos y de Sueldos, que son de B12 y siguen abiertos.
- Una sesión de navegador a mano.
