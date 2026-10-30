---
titulo: Editar o eliminar un movimiento de Cashflow
rol: admin
categoria: Cashflow y finanzas
orden: 52
resumen: Cómo corregir un movimiento cargado directo en Cashflow, cómo eliminarlo sin perder el rastro y qué movimientos no se tocan desde acá.
pantalla: /cashflow
---

# Editar o eliminar un movimiento de Cashflow

Un movimiento que cargaste directo en **Cashflow** (un gasto, un ingreso, un aporte o un retiro) se puede corregir o eliminar desde ahí mismo. Wings guarda el historial: queda registrado qué decía antes, qué dice ahora, quién lo cambió y el motivo.

Los movimientos que vienen de otro lado (una caja validada, un cobro tuyo, el pago de una liquidación) no se editan desde Cashflow: cada uno se corrige en su propio recorrido. Una transferencia entre cuentas hoy no se puede corregir ni anular: si te equivocaste, hacé otra transferencia en sentido contrario.

---

## Editar un movimiento

1. Entrá a **Cashflow** y buscá el movimiento.
2. En su fila, tocá **Editar**. Se abre el mismo formulario con el que se cargó.
3. Corregí lo que haga falta. En **Motivo (opcional)** podés anotar por qué.
4. Tocá **Guardar**. Wings muestra «Movimiento actualizado. El historial se conserva.».

**Historial**, en la misma fila, muestra el movimiento con todos sus cambios.

![Movimiento con su historial](/ayuda/img/admin/corregir-movimiento-cashflow-paso1.png)

---

## Eliminar un movimiento

1. En la fila del movimiento, tocá **Eliminar**.
2. Wings muestra el movimiento que vas a eliminar. Si querés, anotá el **Motivo (opcional)**.
3. Tocá **Eliminar** para confirmar, o **Cancelar** para volver.

Eliminar no lo borra: Wings lo marca como anulado y carga un asiento en contra por el mismo importe, así el saldo vuelve a quedar como antes. Muestra «Movimiento anulado. El original y el asiento en contra se conservan.».

![Confirmar la eliminación](/ayuda/img/admin/corregir-movimiento-cashflow-paso2.png)

---

## Qué hago si…

### ¿Qué hago si en la fila no aparecen Editar ni Eliminar?
Ese movimiento no es directo, o ya fue anulado, o es de un concepto que administra Wings. En su lugar aparece **Ver** (o **Anular**, si es un cobro tuyo), que lleva al lugar donde se corrige. Para un error dentro de una caja ya validada, ver *Corregir un movimiento mal cargado en una caja ya validada*.

### ¿Qué hago si dice «El movimiento cambió mientras editabas. Recargá la pantalla antes de guardar.»?
Otra persona (u otra pestaña) lo modificó mientras lo tenías abierto. Recargá la pantalla para ver la versión actual y volvé a corregir.
