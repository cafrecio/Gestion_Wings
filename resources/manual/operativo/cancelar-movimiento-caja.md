---
titulo: Cancelar un movimiento en una caja abierta o rechazada
rol: operativo
categoria: Caja y cobranza
orden: 130
resumen: Cómo anular un cobro o gasto mal registrado en la caja diaria con motivo obligatorio antes de que sea validada.
pantalla: /caja/{cajaId}/detalle
du: CAJ-003
---

# Cancelar un movimiento en una caja abierta o rechazada

Esta tarea te explica cómo cancelar o anular un cobro o gasto asentado por error en tu turno de caja diario antes de que el turno sea cerrado y validado definitivamente.

> [!NOTE]
> *Requisito de estado:* Solo se pueden cancelar movimientos mientras la caja está en estado **Abierta** o **Rechazada**. Si la caja está cerrada esperando validación o ya fue validada por el dueño, no se puede cancelar directamente desde este panel.

---

## Pasos para cancelar un movimiento

### Paso 1. Ingresar al detalle de tu caja
1. En el menú lateral, hacé clic en **Caja**.
2. Tocá el botón **Detalle** en la tarjeta de tu turno actual para abrir el listado completo de operaciones del día.

![Botón Detalle en Caja](/ayuda/img/operativo/cancelar-movimiento-caja-paso1.png)

---

### Paso 2. Localizar la operación y tocar Cancelar
1. En la tabla de movimientos, localizá la fila del cobro, ingreso o gasto que necesitás anular.
2. Al final de la fila, hacé clic en el botón rojo **Cancelar**.
3. Se despliega el panel **Cancelar cobro de cuota** con la indicación «La deuda del alumno volverá a estado pendiente.».

![Botón Cancelar en la fila](/ayuda/img/operativo/cancelar-movimiento-caja-paso2.png)

---

### Paso 3. Ingresar el motivo obligatorio y confirmar
1. En la casilla de texto, escribí el motivo claro y descriptivo de la anulación (por ejemplo: "Cobro duplicado por error" o "Monto ingresado incorrectamente").
2. Hacé clic en el botón **Confirmar** (o en **Cerrar** si no deseás cancelar).
3. Wings anula la operación, tacha el movimiento marcándolo en rojo como *Cancelado*, descuenta el dinero de la caja y restituye la deuda del alumno a su estado pendiente.

![Modal de confirmación con motivo](/ayuda/img/operativo/cancelar-movimiento-caja-paso3.png)

---

## Qué hago si…

### ¿Qué hago si la caja ya fue cerrada pero el dueño todavía no la validó?
Si la caja está en estado *Cerrada*, la botonera de cancelación se deshabilita para evitar adulteraciones posteriores al arqueo. Hablá con el dueño y pedile que rechace la caja: al pasar a estado *Rechazada*, el botón **Cancelar** vuelve a estar habilitado para que corrijas la operación.

### ¿Qué hago si la caja ya fue validada por el dueño?
Una caja validada representa un balance contable cerrado y auditado que nunca se reabre ni se altera. Avisale al dueño para que realice un ajuste compensatorio directo en Cashflow (DU-27).
