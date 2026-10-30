---
titulo: Auditar el detalle y arqueo de una caja
rol: admin
categoria: Cajas y turnos
orden: 26
resumen: Cómo inspeccionar cada cobro y gasto de un turno operativo y verificar los comprobantes emitidos.
pantalla: /caja/{id}/detalle
---

# Auditar el detalle y arqueo de una caja

Esta tarea te explica cómo revisar en profundidad cada una de las operaciones registradas durante el turno de una operativa, controlar los comprobantes emitidos y verificar los importes rendidos.

> [!IMPORTANT]
> *Auditoría individual:* A diferencia del resumen global, el **Detalle de caja** te permite ver renglón por renglón quién pagó, a qué hora, qué medio de pago utilizó y abrir el recibo oficial de cada operación.

---

## Pasos para auditar las operaciones del turno

### Paso 1. Inspeccionar el estado y totales del turno
1. En el menú, ingresá a **Caja** y tocá sobre la caja que deseás auditar para abrir su **Detalle**.
2. En la parte superior, revisá la tarjeta de datos del turno:
   - **Operativo:** usuario responsable del turno.
   - **Apertura:** fecha y hora de inicio de la caja.
   - **Cierre:** fecha y hora de cierre (o guion si todavía está abierta).
   - **Estado:** indica si la caja se encuentra *Abierta*, *Cerrada*, *Validada* o *Rechazada*.
3. En la barra de acciones superior podés tocar **Validar** o **Rechazar** (si la caja está cerrada), o tocar **Resumen** para consultar el cuadro consolidado de arqueo de efectivo del mostrador.

![Revisar panel de datos y estado del turno](/ayuda/img/admin/auditar-detalle-caja-paso1.png)

---

### Paso 2. Auditar los movimientos individuales
1. Desplazate a la tabla inferior de operaciones. Cada fila muestra:
   - **Fecha** y **Hora** exacta del movimiento.
   - **I/E:** indica si es ingreso o egreso.
   - **Medio:** medio de pago utilizado (**Efectivo**, transferencia u otro canal).
   - **Rubro — Subrubro:** imputación contable de la operación.
   - **Alumno / Obs.:** nombre del alumno o concepto anotado.
   - **Monto:** importe registrado.
2. Al pie de la tabla, revisá el consolidado con el total de **Ingresos**, **Egresos** y el saldo neto del turno.
3. Si la caja contiene cobros a alumnos, tocá el enlace **Recibo** de la fila para abrir en una pestaña nueva el comprobante PDF oficial emitido.
4. Si encontrás un cobro mal anotado y la caja todavía no fue validada, podés rechazar el turno tocando **Rechazar** para que la operativa lo cancele con motivo.

![Tabla de movimientos detallados y enlace al recibo](/ayuda/img/admin/auditar-detalle-caja-paso2.png)

---

## Qué hago si…

### ¿Puedo anular un cobro desde esta pantalla siendo administrador?
No. Si la caja está abierta o rechazada, la cancelación la realiza la operativa desde su sesión indicando el motivo. Si la caja ya fue validada, ningún usuario puede borrar el cobro: el ajuste se realiza en **Cashflow**.

### ¿Cómo sé si un movimiento fue cancelado?
Los movimientos cancelados aparecen tachados o con la indicación de anulación y su motivo correspondiente, conservando la trazabilidad completa del turno.
