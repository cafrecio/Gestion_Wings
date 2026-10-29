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

Esta tarea te explica cómo registrar el cobro de cuotas, matrícula de inscripción o recargos por mora de un alumno en el mostrador.

> [!NOTE]
> *Antes de empezar:* Para poder cobrar necesitás tener tu turno de caja abierto del día. Si todavía no abriste la caja, Wings no te va a dejar operar y te va a mostrar «Debés abrir la caja y confirmar el efectivo antes de operar.». En ese caso, entrá a **Caja**, ingresá el importe en **Efectivo recibido**, marcá la casilla de confirmación y tocá **Abrir**.

---

## Pasos para cobrar

### Paso 1. Buscar al alumno
1. Desde el menú lateral, entrá a **Caja** y tocá el botón **Cobrar**. Se abre la pantalla **Cobrar cuota** (si estás en **Cobranza**, podés tocar directamente **Cobrar** en la tarjeta del alumno).
2. Escribí el nombre, apellido o DNI en el buscador y tocá **Buscar** (o elegí al alumno en el listado).
3. En la tarjeta del alumno, tocá el botón **Cobrar**.

![Buscar alumno y tocar Cobrar](/ayuda/img/operativo/cobrar-cuota-paso1.png)

---

### Paso 2. Elegir qué vas a cobrar
1. En la parte superior de la pantalla vas a ver los datos del alumno y el **Total pendiente**.
2. Marcá la casilla del período o concepto que el alumno va a pagar (por ejemplo, cuotas del mes, meses anteriores, matrícula de inscripción o recargos por mora).
3. Si el alumno entrega un importe parcial, podés modificar el monto directamente en el campo de la cuota; o si adeuda matrícula de inscripción o recargos, ingresar lo que entrega en el campo **Importe que entrega (dejar vacío para cobrar el total)** para que Wings lo distribuya automáticamente.

![Seleccionar las cuotas a cobrar](/ayuda/img/operativo/cobrar-cuota-paso2.png)

---

### Paso 3. Indicar cómo paga
1. En el campo **Medio de pago**, seleccioná el medio con el que abona entre *los medios habilitados* (por ejemplo, **Efectivo**).
2. La **Fecha del pago** viene por defecto con el día de hoy.
3. En el campo **Observaciones**, podés escribir una aclaración opcional sobre el cobro (por ejemplo: "Pago cuota octubre en mostrador").

![Elegir medio de pago](/ayuda/img/operativo/cobrar-cuota-paso3.png)

---

### Paso 4. Confirmar el cobro
1. Revisá el importe en **Total a cobrar:**.
2. Tocá el botón **Cobrar** al final de la pantalla.
3. Wings guarda el cobro, actualiza la cuenta del alumno y muestra el aviso de confirmación con el botón **Recibo** para abrir el comprobante oficial.

![Confirmar cobro y ver recibo](/ayuda/img/operativo/cobrar-cuota-paso4.png)

---

## Qué hago si…

### ¿Qué hago si me equivoqué al registrar un cobro?
Si tu caja sigue abierta (o fue rechazada), entrá a **Caja**, tocá **Detalle** de tu caja y en la fila del cobro tocá el botón **Cancelar**. Wings te va a pedir un motivo obligatorio para anularlo. Al confirmar, Wings cancela el cobro y revierte la deuda del alumno.

Si la caja ya fue cerrada pero el dueño todavía no la validó (está en estado *Cerrada*), la operativa no puede cancelar cobros: tenés que pedirle al dueño que rechace la caja para que vuelva a estar en estado *Rechazada* y puedas cancelarlo.

Si la caja ya fue validada por el dueño, la caja validada no se puede modificar ni cancelar. Avisale al dueño para que registre un *ajuste directo en Cashflow* con el motivo correspondiente.

### ¿Qué hago si el cobro deja meses anteriores impagos?
Si cobrás cuotas salteadas dejando deuda más antigua sin cancelar, Wings abre el panel de confirmación con el detalle de los meses adeudados y pregunta: «¿Cobrás igual solo los meses elegidos?», solicitando «Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.». Para continuar, completá el campo obligatorio **Motivo** indicando la justificación de la excepción y tocá **Cobrar** dentro del panel. Sin motivo no cobra.

### ¿Qué hago si la pantalla advierte que estoy cobrando por adelantado?
Si seleccionás un mes futuro que todavía no empezó, Wings abre el panel de confirmación advirtiendo: «Estás cobrando por adelantado» ese período e indicando: «El importe queda fijo aunque después cambie la cuota.». Verificá el aviso y confirmá tocando **Cobrar**. El cobro se registra con el valor de la cuota actual, pero tené en cuenta que cobrar un mes por adelantado no habilita al alumno a tomar clases antes del primer día de ese mes.

### ¿Qué hago si la pantalla advierte que la fecha corresponde a un mes cerrado?
Si modificás el campo **Fecha del pago** seleccionando un día de un mes anterior cerrado, Wings muestra el aviso: «Esta fecha de pago corresponde a un mes anterior ya cerrado. El cobro quedará registrado con esa fecha real, pero entrará en la caja de hoy y modificará el reporte de ese mes.». Tocá **Cobrar** en el panel para confirmar la operación con fecha histórica en los reportes pero asentando el dinero en tu caja abierta actual.

### ¿Qué hago si el alumno al que le cobro está inactivo?
Wings no permite cobrar directamente a un inactivo sin definir su situación: abre el panel **Alumno inactivo** preguntando: «¿Pasa a activo?». Consultá la guía detallada [Pasar a un alumno a inactivo](pasar-alumno-inactivo.md) para ver las opciones disponibles: **Sí** para reactivarlo con cuota de reingreso, **No** para cobrar solo deuda vieja manteniéndolo inactivo, o **Cancelar** para volver (si el cobro incluye el mes en curso o uno futuro, pagar el mes es volver y no se ofrece la opción «No»).

### ¿Qué hago si la pantalla dice «Debés abrir la caja y confirmar el efectivo antes de operar.»?
No podés cobrar sin caja abierta porque cada peso que ingresa tiene que quedar asignado a un responsable y a un turno de mostrador. Andá a **Caja** en el menú, ingresá el importe con el que arrancás en **Efectivo recibido**, marcá la casilla de confirmación y tocá **Abrir**.

### ¿Qué hago si la pantalla dice «Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.»?
Wings no permite operar en un nuevo día si quedó un turno pendiente de cierre. Andá a **Caja**, seleccioná el turno abierto de la fecha anterior, ingresá el monto en **Efectivo contado** y tocá **Cerrar** para dejar la rendición lista antes de abrir el turno de hoy.

### ¿Qué hago si el alumno paga una parte de la cuota?
Modificá el importe en la casilla de la cuota (o ingresá lo entregado en **Importe que entrega (dejar vacío para cobrar el total)** si adeuda matrícula o recargos). En Wings no existe el estado parcial: la cuota continúa con estado *Pendiente* y muestra el saldo restante que queda por pagar.

### ¿Qué hago si el alumno dice que ya pagó por transferencia pero no figura?
No le vuelvas a cobrar una cuota que ya pagó. Pedile el comprobante de la transferencia bancaria y consultá con el dueño para verificar si el dinero ingresó a la cuenta del club. Una vez confirmado el ingreso, se registra el cobro indicando la fecha real de la transferencia.
