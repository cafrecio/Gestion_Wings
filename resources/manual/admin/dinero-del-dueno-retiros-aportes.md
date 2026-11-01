---
titulo: Retiros y aportes personales del dueño
rol: admin
categoria: Cashflow y finanzas
orden: 31
resumen: Cómo asentar retiros de ganancias o aportes de capital del dueño, también cuando la plata sale o entra por el cajón de la operativa.
pantalla: /cashflow/movimiento
---

# Retiros y aportes personales del dueño

Esta tarea te explica cómo asentar el dinero que los dueños retiran del club o los aportes de capital personal que ingresan para cubrir gastos, asegurando que no se confundan con ingresos o pérdidas de la actividad deportiva.

> [!IMPORTANT]
> *Clasificación contable en Wings:* En Wings, los subrubros contables llevan una clasificación especial («Retiro de los dueños» y «Aporte de los dueños»). Esto permite que los retiros no se contabilicen como gastos operativos del club y que los aportes personales no figuren como ingresos por cuotas deportivas.

---

## Cómo asentar retiros y aportes según el canal de dinero

### Canal 1. Movimiento directo desde cuentas o administración (en Cashflow)
1. En el menú del administrador, ingresá a **Cashflow** y tocá el botón **Nuevo**.
2. Si retirás dinero:
   - Seleccioná la pestaña **EGRESO**.
   - Elegí la cuenta de donde sale el dinero (la cuenta bancaria, **Efectivo**, etc.).
   - En **Rubro** y **Subrubro**, seleccioná el concepto clasificado como «Retiro de los dueños» (por ejemplo, *Retiros* → *Retiro de dueños*).
   - Escribí el monto y guardá.
3. Si aportás capital personal:
   - Seleccioná la pestaña **INGRESO**.
   - Elegí la cuenta de destino (la cuenta bancaria, **Efectivo**, etc.).
   - Seleccioná el subrubro clasificado como «Aporte de los dueños». Wings no trae uno de fábrica: si no lo tenés, crealo en **Rubros**.
   - Escribí el monto y guardá.

![Registrar retiro o aporte directo en Cashflow](/ayuda/img/admin/dinero-del-dueno-retiros-aportes-paso1.png)

---

### Canal 2. Plata del cajón durante el turno de la operativa

Si sacás efectivo del cajón, un retiro no se puede cargar dentro del turno, ni vos ni la operativa. Va por Cashflow.
1. Cargalo como en el Canal 1, con la cuenta **Efectivo**.
2. Avisale a la operativa. El efectivo esperado de su turno no cambia, así que al contar le va a faltar ese importe y la caja cierra con faltante.
3. Cuando valides, vas a ver esa diferencia. La plata baja una sola vez, por tu retiro: el faltante no vuelve a descontarla de la cuenta.

Si podés esperar, sacala después del cierre, de lo que entrega la operativa. El retiro va igual por Cashflow, pero el turno no queda con faltante.

Si ponés efectivo en el cajón:
1. En **Caja**, en la tarjeta del turno, tocá **Agregar** y elegí tu subrubro de aporte. Tiene que estar en **Ambos** y en **Afecta caja**: así también lo ve y lo puede cargar la operativa.
2. El movimiento queda a tu nombre, en la columna **Registró**, y sube el efectivo esperado del turno.
3. Cuando valides la caja, pasa a Cashflow con su clasificación.

---

## Qué hago si…

### ¿Qué pasa si dice «Tus movimientos se cargan en Cashflow; la caja es de la operativa. No se registró nada.»?
Entraste por el formulario general de movimientos de caja, que es el de la operativa. Lo tuyo va por **Cashflow** (Canal 1).

### ¿Qué pasa si dice «El subrubro seleccionado no está permitido para usuarios operativos.»?
En **Agregar**, Wings te ofrece todos tus subrubros, pero solo acepta los que están en **Ambos**. No se registró nada: cargalo por **Cashflow**.

### ¿Por qué la cuenta Efectivo quedó en negativo después de mi retiro?
Los cobros en efectivo del turno entran a la cuenta recién cuando validás la caja. Wings te deja cargar el retiro igual, y el saldo se acomoda al validar.

### ¿Qué pasa si un gasto en el turno supera lo disponible en el cajón?
Wings muestra: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.» Cargá primero el aporte o corregí el importe.
