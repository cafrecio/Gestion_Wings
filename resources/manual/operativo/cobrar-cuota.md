---
titulo: Cobrar una cuota
rol: operativo
categoria: Caja y cobranza
orden: 10
resumen: Cómo buscar a un alumno, revisar lo que debe y cobrarle una o más cuotas en el mostrador.
pantalla: /caja/cobrar/{alumnoId}
du: COB-001
---

# Cobrar una cuota

Esta tarea te explica cómo registrar el pago de cuotas, matrícula o punitorios de un alumno que se acerca al mostrador.

> [!NOTE]
> **Antes de empezar:** Para poder cobrar necesitás tener tu turno de caja abierto del día. Si todavía no abriste la caja, Wings te va a pedir que ingreses el saldo inicial en **Caja**.

---

## Pasos para cobrar

### Paso 1. Buscar al alumno
1. Entrá a **Cobranza** o a **Alumnos** desde el menú lateral.
2. Escribí el nombre, apellido o DNI del alumno en el buscador.
3. En la fila del alumno, tocá el botón **Cobrar**.

![Buscar alumno y tocar Cobrar](/ayuda/img/operativo/cobrar-cuota-paso1.png)

---

### Paso 2. Elegir qué vas a cobrar
1. En la parte superior de la pantalla vas a ver los datos del alumno y el **Total pendiente**.
2. Más abajo tenés el detalle de los conceptos adeudados: cuotas del mes, meses anteriores, matrícula de inscripción (si debe) o punitorios.
3. Marcá la casilla de cada concepto que el alumno va a pagar ahora.
4. Si el alumno quiere pagar un importe distinto, ajustá el valor en el campo **Importe**.

![Seleccionar las cuotas a cobrar](/ayuda/img/operativo/cobrar-cuota-paso2.png)

---

### Paso 3. Indicar cómo paga
1. En la sección **Medio de pago**, elegí con qué te paga: **Efectivo**, **Transferencia**, **Tarjeta de débito**, **Tarjeta de crédito** u otro medio habilitado.
2. Si paga por transferencia o tarjeta, podés anotar el número de comprobante o referencia en el campo de observaciones para que quede registrado.

![Elegir medio de pago](/ayuda/img/operativo/cobrar-cuota-paso3.png)

---

### Paso 4. Confirmar el cobro
1. Revisá que el total coincida con el dinero recibido.
2. Tocá el botón **Cobrar** al final de la pantalla.
3. Wings guarda el cobro, actualiza la cuenta del alumno y te muestra el recibo oficial en pantalla para que lo puedas imprimir o compartir.

![Confirmar cobro y ver recibo](/ayuda/img/operativo/cobrar-cuota-paso4.png)

---

## Qué hago si…

### ¿Qué hago si me equivoqué de medio de pago o cobré de más?
Si tu turno de caja sigue abierto, andá a **Caja**, buscá el movimiento en la lista de cobros y tocalo para anularlo. Wings te va a pedir un motivo obligatorio para que quede registro del error. Una vez anulado, podés volver a cobrar correctamente.

> [!WARNING]
> Si la caja ya fue cerrada y el dueño ya la validó, la caja validada no se puede modificar ni reabrir. En ese caso, avisale al dueño: él va a registrar un ajuste compensatorio directo en Cashflow con el motivo correspondiente (DU-27).

### ¿Qué hago si el alumno dice que ya pagó por transferencia pero a mí no me figura?
No le vuelvas a cobrar una cuota que ya pagó. Pedile el comprobante de la transferencia bancaria y consultá con el dueño para verificar si el dinero ingresó a la cuenta del club. Una vez confirmado el ingreso, se registra el cobro indicando la fecha real de la transferencia.

### ¿Qué hago si la pantalla dice «No tenés una caja abierta»?
No podés cobrar sin caja abierta porque cada peso que ingresa tiene que quedar asignado a un responsable y a un turno de mostrador. Andá a **Caja** en el menú, poné el dinero con el que arrancás el turno en **Saldo inicial** y tocá **Abrir caja**.

### ¿Qué hago si el alumno quiere pagar una parte de la cuota?
Podés ingresar un pago parcial escribiendo el monto que entrega en el campo **Importe**. La cuota va a quedar con estado parcial y el saldo restante seguirá figurando como pendiente en **Cobranza**.
