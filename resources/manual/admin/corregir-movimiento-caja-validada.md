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
> *Por qué la caja validada no se toca:* Una caja validada representa el dinero físico real que la operativa contó y entregó al cerrar su turno. Si editáramos o borráramos movimientos de una caja ya validada, romperíamos la trazabilidad histórica de lo que efectivamente rindió ese turno y no habría forma de auditar qué pasó. En Wings, lo validado queda cerrado para siempre (DU-27) y cualquier corrección posterior se asienta como un *ajuste directo en Cashflow*, con motivo claro.

---

## Pasos para hacer el ajuste en Cashflow

### Paso 1. Entrar a Cashflow
1. Desde el menú lateral del administrador, entrá a **Cashflow**.
2. Revisá el saldo del medio de pago afectado (por ejemplo, **Efectivo** o **Banco**) y el período en el que ocurrió el error.

![Entrar a Cashflow](/ayuda/img/admin/corregir-movimiento-paso1.png)

---

### Paso 2. Abrir el formulario de nuevo movimiento
1. En la barra superior de acciones de Cashflow, tocá el botón **Nuevo**.
2. Se va a abrir la pantalla **Nuevo movimiento** para registrar una operación directa de administración.

![Tocar el botón Nuevo en Cashflow](/ayuda/img/admin/corregir-movimiento-paso2.png)

---

### Paso 3. Cargar el ajuste compensatorio
Completá los campos según el sentido del error:
1. **Tipo de movimiento:**
   - Si en la caja se cargó dinero de más (un cobro duplicado o mayor al real), tocá el botón **EGRESO** compensatorio.
   - Si se cobró de menos o se anotó un gasto de más, tocá el botón **INGRESO** compensatorio.
2. **Fecha:** Elegí la fecha en la que realizás el ajuste (o la fecha del turno si estás cerrando el período; si elegís una fecha de un mes anterior, Wings te va a mostrar la advertencia `Atención: fecha de un mes anterior` y el botón cambiará a **Confirmar** para que apruebes el registro).
3. **Medio de pago:** Seleccioná el medio de pago que tuvo la diferencia (**Efectivo**, **Banco**, etc.).
4. **Rubro:** Seleccioná el rubro de ajuste correspondiente.
5. **Subrubro:** Seleccioná el subrubro correspondiente.
6. **Monto:** Escribí la diferencia exacta que necesitás corregir.
7. **Observaciones:** Escribí con claridad la razón del ajuste (por ejemplo: "Ajuste compensatorio por cobro duplicado a Alumno Pérez en turno mañana del 15/10/2026, caja ya validada").

![Completar formulario de ajuste directo](/ayuda/img/admin/corregir-movimiento-paso3.png)

---

### Paso 4. Guardar y verificar
1. Tocá el botón **Registrar** (o **Confirmar** si la fecha correspondía a un mes anterior).
2. Wings registra el movimiento en el libro **Cashflow** y actualiza los saldos generales del club.
3. El historial conserva tanto la operación original de la caja como el ajuste que hiciste vos, con su explicación auditada.

![Guardar y ver el movimiento reflejado en Cashflow](/ayuda/img/admin/corregir-movimiento-paso4.png)

---

## Qué hago si…

### ¿Qué hago si la caja todavía no está validada?
Si la caja del turno fue cerrada por la operativa pero vos todavía no la validaste (está en estado *Cerrada*), no hace falta hacer un ajuste en Cashflow:
1. Andá a **Caja**, abrí el detalle de esa caja y tocá **Rechazar**.
2. Escribí la observación para que la operativa sepa qué corregir.
3. La operativa va a poder entrar a su caja rechazada, tocar **Cancelar** sobre el cobro erróneo y volver a cerrarla para que la valides limpia.

### ¿Qué hago si el error afectó la deuda de un alumno?
*Importante:* Hoy Wings no tiene forma de devolverle la deuda a ese alumno. Una cuota cobrada por caja no se anula desde la ficha del alumno y una caja ya validada no permite cancelar movimientos. El botón para reactivar cuotas solo acepta cuotas condonadas, nunca cuotas pagadas.
Por lo tanto, el ajuste en **Cashflow** compensa el saldo financiero del club, pero la ficha del alumno conservará la cuota como saldada. La deuda no se puede reabrir en el sistema.

### ¿Qué hago si lo que tengo que hacer es pasar efectivo al banco?
Eso no es un error de caja, es una transferencia entre cuentas del club (DU-29). En **Cashflow**, tocá el botón **Transferir** y elegí la **Cuenta de salida** y la **Cuenta de destino**. Wings genera las dos patas vinculadas sin alterar el resultado del club.
