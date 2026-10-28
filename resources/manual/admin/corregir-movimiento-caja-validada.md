---
titulo: Corregir un movimiento mal cargado en una caja ya validada
rol: admin
categoria: Cashflow y finanzas
orden: 20
resumen: Cómo compensar un error de cobro o gasto de un turno ya cerrado y validado sin romper la trazabilidad histórica.
pantalla: /cashflow
du: DU-27
---

# Corregir un movimiento mal cargado en una caja ya validada

Esta tarea te explica cómo solucionar una diferencia cuando se descubre que en un turno anterior se cargó un cobro o gasto con un monto equivocado, o con el medio de pago incorrecto, y esa caja ya fue validada por el dueño.

> [!IMPORTANT]
> **Por qué la caja validada no se toca:** Una caja validada representa el dinero físico real que la operativa contó y entregó al cerrar su turno. Si editáramos o borráramos movimientos de una caja ya validada, romperíamos la trazabilidad histórica de lo que efectivamente rindió ese turno y no habría forma de auditar qué pasó. En Wings, lo validado queda cerrado para siempre (DU-27) y cualquier corrección posterior se asienta como un **ajuste directo en Cashflow**, con motivo claro.

---

## Pasos para hacer el ajuste en Cashflow

### Paso 1. Entrar a Cashflow
1. Desde el menú lateral del administrador, entrá a **Cashflow**.
2. Revisá el saldo del medio de pago afectado (por ejemplo, **Efectivo** o **Banco**) y el período en el que ocurrió el error.

![Entrar a Cashflow](/docs/06-pruebas/PRU-04/evidencia-t12-t13/corregir-movimiento-paso1.png)

---

### Paso 2. Abrir el formulario de nuevo movimiento
1. En la barra superior de acciones de Cashflow, tocá el botón **Nuevo**.
2. Se va a abrir la pantalla para registrar un movimiento directo de administración.

![Tocar el botón Nuevo en Cashflow](/docs/06-pruebas/PRU-04/evidencia-t12-t13/corregir-movimiento-paso2.png)

---

### Paso 3. Cargar el ajuste compensatorio
Completá los campos según el sentido del error:
1. **Tipo de movimiento:**
   - Si en la caja se cargó plata de más (un cobro duplicado o mayor al real), cargá un **Egreso** compensatorio.
   - Si se cobró de menos o se anotó un gasto de más, cargá un **Ingreso** compensatorio.
2. **Tipo de caja:** Seleccioná el medio de pago que tuvo la diferencia (**Efectivo**, **Banco**, etc.).
3. **Importe:** Escribí la diferencia exacta que necesitás corregir.
4. **Fecha:** Elegí la fecha en la que realizás el ajuste (o la fecha del turno si estás cerrando el período).
5. **Rubro y Subrubro:** Seleccioná el rubro y subrubro de ajuste correspondiente.
6. **Motivo:** Escribí con claridad la razón del ajuste (por ejemplo: *«Ajuste compensatorio por cobro duplicado a Alumno Pérez en turno mañana del 15/10/2026, caja ya validada»*).

![Completar formulario de ajuste directo](/docs/06-pruebas/PRU-04/evidencia-t12-t13/corregir-movimiento-paso3.png)

---

### Paso 4. Guardar y verificar
1. Tocá el botón **Guardar**.
2. Wings registra el movimiento en el libro Cashflow y actualiza los saldos generales del club.
3. El historial conserva tanto la operación original de la caja como el ajuste que hiciste vos, con su explicación auditada.

![Guardar y ver el movimiento reflejado en Cashflow](/docs/06-pruebas/PRU-04/evidencia-t12-t13/corregir-movimiento-paso4.png)

---

## Qué hago si…

### ¿Qué hago si la caja todavía NO está validada?
Si la caja del turno fue cerrada por la operativa pero vos todavía no la validaste (está en estado «Cerrada»), no hace falta hacer un ajuste en Cashflow:
1. Andá a **Caja**, abrí el detalle de esa caja y tocá **Rechazar**.
2. Escribí la observación para que la operativa sepa qué corregir.
3. La operativa va a poder rectificar o anular el movimiento erróneo y volver a cerrar la caja para que la valides limpia.

### ¿Qué hago si el error afectó la deuda de un alumno?
Si el cobro canceló indebidamente la cuota de un alumno y la caja ya está validada:
1. Hacé el ajuste en **Cashflow** para equilibrar el saldo de dinero.
2. Luego andá a la ficha del alumno en **Alumnos** o **Cobranza** y reactivá la cuota pendiente para que vuelva a figurar como adeudada.

### ¿Qué hago si lo que tengo que hacer es pasar efectivo al banco?
Eso no es un error de caja, es una transferencia entre cuentas del club (DU-29). En **Cashflow**, tocá el botón **Transferir** y elegí salida **Efectivo** y destino **Banco**. Wings genera las dos patas vinculadas sin alterar el resultado del club.
