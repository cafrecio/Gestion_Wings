---
titulo: Cobrar un pago parcial de cuota
rol: operativo
categoria: Caja y cobranza
orden: 20
resumen: Cómo registrar un pago por un importe menor al total de la cuota y cómo se computa el saldo pendiente en Wings.
pantalla: /caja/cobrar/{alumnoId}
du: COB-002
---

# Cobrar un pago parcial de cuota

Esta tarea te explica cómo registrar un pago cuando un alumno entrega un importe menor al total de su cuota mensual en el mostrador.

> [!NOTE]
> *Antes de empezar:* Necesitás tener tu turno de caja abierto del día. Si no tenés caja abierta, Wings muestra «Debés abrir la caja y confirmar el efectivo antes de operar.». En ese caso, entrá a **Caja**, colocá el importe en **Efectivo recibido**, marcá la confirmación y tocá **Abrir**.

---

## Pasos para cobrar un pago parcial

### Paso 1. Buscar al alumno y abrir el cobro
1. Desde el menú lateral, entrá a **Caja** y tocá el botón **Cobrar** (o entrá por **Cobranza** y tocá **Cobrar** en la tarjeta del alumno).
2. Escribí el nombre, apellido o DNI del alumno en el buscador y tocá **Buscar**.
3. En la tarjeta del alumno seleccionado, tocá el botón **Cobrar**.

![Buscar alumno para cobro parcial](/ayuda/img/operativo/cobrar-pago-parcial-paso1.png)

---

### Paso 2. Modificar el importe a cobrar
1. En la lista de conceptos adeudados, marcá la casilla de la cuota que el alumno va a abonar parcialmente.
2. Hacé clic directamente sobre la casilla de importe de esa cuota e ingresá el valor exacto que entrega hoy (por ejemplo, $10.000 de una cuota de $25.000).
3. Si el alumno adeuda además matrícula de inscripción o recargos, podés ingresar lo que entrega en el campo **Importe que entrega (dejar vacío para cobrar el total)** para que Wings distribuya el pago automáticamente entre los conceptos adeudados.

![Modificar importe de cuota](/ayuda/img/operativo/cobrar-pago-parcial-paso2.png)

---

### Paso 3. Seleccionar medio de pago y confirmar
1. En el selector **Medio de pago**, elegí la opción correspondiente entre los medios habilitados (por ejemplo, **Efectivo**).
2. La **Fecha del pago** viene fijada con la fecha de hoy.
3. Si necesitás, escribí una aclaración en **Observaciones** (por ejemplo: "Entrega a cuenta cuota octubre").
4. Verificá que en **Total a cobrar:** figure el importe parcial ingresado y tocá el botón **Cobrar**.
5. Wings guarda el cobro, actualiza la cuenta corriente y muestra el botón **Recibo** para emitir el comprobante oficial por la entrega realizada.

![Confirmar pago parcial y ver recibo](/ayuda/img/operativo/cobrar-pago-parcial-paso3.png)

---

## Qué hago si…

### ¿Qué estado le queda a la cuota en el sistema después de un pago parcial?
En Wings no existe el estado "parcial". La cuota continúa en estado *Pendiente* en el modelo contable, pero su saldo pendiente se reduce: la ficha muestra el importe total, cuánto se cobró a cuenta y el saldo exacto que resta por pagar.

### ¿Cómo cobro el resto de la cuota cuando el alumno viene a completar el pago?
Cuando el alumno regrese, volvés a entrar a **Cobrar**: la cuota va a figurar con el saldo pendiente restante como importe sugerido. Marcás la cuota, elegís el medio de pago y tocás **Cobrar**. Al cancelarse el saldo total, la cuota pasa automáticamente al estado *Pagada*.

### ¿Qué hago si me equivoqué de importe al ingresar el pago parcial?
Si tu caja sigue abierta (o está rechazada), entrá a **Caja**, tocá **Detalle** de tu turno y en la fila del cobro tocá el botón **Cancelar**, ingresando el motivo obligatorio. Wings revierte la entrega y restaura el saldo pendiente previo. Si la caja ya fue validada por el dueño, avisale para asentar un ajuste en Cashflow (DU-27).
