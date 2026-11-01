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
3. **Cuenta de destino:** Seleccioná la cuenta que recibe los fondos (por ejemplo, la cuenta bancaria). Las dos cuentas deben ser distintas.
4. **Importe:** Escribí el monto exacto a transferir.
5. **Observaciones:** Podés agregar una nota de control (por ejemplo: "Depósito de recaudación semanal en sucursal bancaria").
6. Tocá el botón **Transferir** (o **Confirmar** si la fecha era de un mes anterior).
7. Wings registrará ambas operaciones de inmediato y mostrará: «salida y entrada guardadas.»

![Completar origen, destino y monto a transferir](/ayuda/img/admin/transferir-entre-cuentas-paso2.png)

---

## Qué hago si…

### ¿Qué hago si elijo la misma cuenta en salida y destino?
Si Wings rechaza la operación y muestra: «Elegí una cuenta de destino distinta de la salida.», recordá que una transferencia requiere dos cuentas diferentes. Si necesitás corregir un saldo dentro de la misma cuenta, hacé un ajuste directo en Cashflow.

### ¿Qué hago si la fecha es de un mes anterior?
Si aparece el mensaje: «La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.», Wings te advierte que ambas cuentas ajustarán sus saldos históricos hacia atrás. Marcá la confirmación y guardá.

---

## Anular una transferencia

Solo el dueño con cuenta activa puede anularla. Puede ser una transferencia registrada por otro dueño. No se edita una transferencia ni se corrige una sola de sus filas: si te equivocaste, anulá la operación completa y cargá otra.

1. En **Cashflow**, buscá la transferencia por su fecha o cuenta. En cualquiera de sus dos filas tocá **Anular**.
2. Revisá la cuenta de salida, la de destino, el importe y la fecha que muestra la confirmación.
3. Escribí el **Motivo**, obligatorio y de hasta 500 caracteres. No puede quedar vacío ni contener solo espacios.
4. Tocá **Anular** para confirmar, o **Volver** para regresar sin cambiar nada.

![Confirmación de anulación de una transferencia](/ayuda/img/admin/transferir-entre-cuentas-anular.jpg)

Se conservan los dos movimientos originales y se agregan dos asientos en contra: el importe vuelve a la cuenta de salida y se descuenta de la cuenta de destino. Cada inverso conserva la fecha de su original, aunque sea de un mes anterior; se actualizan los saldos y el reporte de ese mes. No hace falta reactivar una cuenta para deshacer su transferencia.

Por ejemplo, si Efectivo tenía $600,000.00 y Banco $100,000.00, transferir $300,000.00 los deja en $300,000.00 y $400,000.00. Anular devuelve los saldos a $600,000.00 y $100,000.00. El total del club se conserva.

En Cashflow quedan la transferencia marcada como anulada, sus dos asientos en contra y el motivo. Las cuatro filas quedan solo con **Ver**: no se editan ni se anula un contraasiento. Volver a confirmar no duplica la anulación.

### ¿Qué hago si no deja anular?

- «Indicá el motivo de la anulación.»: completá el motivo. Si excede 500 caracteres, acortalo.
- «La transferencia ya está anulada.»: otra persona o pestaña ya confirmó la operación. Revisá las cuatro filas; no hace falta volver a cargar nada.
- «Los movimientos de la transferencia necesitan revisión. No se anuló la operación.»: Wings no encontró un par íntegro con las mismas cuentas, importe y fecha que su transferencia, o encontró marcas previas de anulación. No cambia ni repara esos datos; pedí que revisen esa operación antes de continuar.

Las antiguas cargas independientes de traspasos, sin una transferencia que las una, no muestran **Anular** como transferencia. No se enlazan ni se reparan automáticamente.
