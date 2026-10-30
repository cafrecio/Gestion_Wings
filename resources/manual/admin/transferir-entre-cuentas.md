---
titulo: Transferir entre medios propios
rol: admin
categoria: Cashflow y finanzas
orden: 30
resumen: Cómo mover fondos entre cuentas del club (ej. pasar efectivo a banco) sin alterar el resultado contable.
pantalla: /cashflow/transferencia
---

# Transferir entre medios propios

Esta tarea te explica cómo registrar movimientos internos de dinero entre las cuentas del club, por ejemplo cuando llevás la recaudación en efectivo a depositar al banco o transferís fondos desde Mercado Pago a la cuenta corriente del club.

> [!IMPORTANT]
> *No altera el resultado del club:* Una transferencia interna no es ni un ingreso ni un gasto. Wings genera dos patas automáticas vinculadas (un egreso en la cuenta de origen y un ingreso en la cuenta de destino por el mismo importe) bajo la clasificación **Traspaso entre medios**, manteniendo idéntico el resultado financiero global.

---

## Pasos para transferir entre cuentas

### Paso 1. Abrir la pantalla de transferencia
1. En el menú, ingresá a **Cashflow**.
2. En la barra de acciones superior, tocá el botón **Transferir**.
3. Se abrirá la pantalla de transferencia entre cuentas.

![Pantalla de transferencia interna entre cuentas](/ayuda/img/admin/transferir-entre-cuentas-paso1.png)

---

### Paso 2. Indicar salida, destino e importe
1. **Fecha:** Por defecto figura la fecha de hoy. (Si elegís una fecha de un mes anterior, Wings te pedirá confirmar porque se recalcularán los saldos históricos de ambas cuentas desde ese período).
2. **Cuenta de salida:** Seleccioná la cuenta desde donde se retira el dinero (por ejemplo, **Efectivo**).
3. **Cuenta de destino:** Seleccioná la cuenta que recibe los fondos (por ejemplo, **Banco Galicia**). Las dos cuentas deben ser distintas.
4. **Importe:** Escribí el monto exacto a transferir.
5. **Observaciones:** Podés agregar una nota de control (por ejemplo: `Depósito de recaudación semanal en sucursal bancaria`).
6. Tocá el botón **Transferir** (o **Confirmar** si la fecha era de un mes anterior).
7. Wings registrará ambas operaciones de inmediato y mostrará: `Transferencia #... registrada: salida y entrada guardadas.`

![Completar origen, destino y monto a transferir](/ayuda/img/admin/transferir-entre-cuentas-paso2.png)

---

## Qué hago si…

### ¿Qué hago si elijo la misma cuenta en salida y destino?
Si Wings rechaza la operación y muestra: `Elegí una cuenta de destino distinta de la salida.`, recordá que una transferencia requiere dos cuentas diferentes. Si necesitás corregir un saldo dentro de la misma cuenta, hacé un ajuste directo en Cashflow.

### ¿Qué hago si la fecha es de un mes anterior?
Si aparece el mensaje: `La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.`, Wings te advierte que ambas cuentas ajustarán sus saldos históricos hacia atrás. Marcá la confirmación y guardá.
