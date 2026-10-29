---
titulo: Registrar un gasto menor o salida de dinero del cajón
rol: operativo
categoria: Caja y cobranza
orden: 120
resumen: Cómo asentar una salida de efectivo del cajón para compras menores o cuando el dueño retira dinero del mostrador.
pantalla: /caja/movimiento
du: CAJ-002b
---

# Registrar un gasto menor o salida de dinero del cajón

Esta tarea te explica cómo asentar cualquier salida física de billetes del cajón de mostrador: compras menores de insumos, reparaciones urgentes o retiros de efectivo realizados por el dueño del club.

> [!NOTE]
> *Regla del dinero del dueño:* El dueño nunca retira dinero del mostrador de forma directa ni por fuera del sistema. Si el dueño necesita efectivo del cajón, la operativa asienta el **EGRESO** en su turno de caja abierto indicando el monto y el concepto. Esto garantiza que a la hora del arqueo el saldo físico coincida y no aparezca un faltante injustificado.

---

## Pasos para registrar un gasto o salida de dinero

### Paso 1. Ingresar a Nuevo movimiento
1. En el menú lateral, hacé clic en **Caja**.
2. En la botonera del turno abierto, tocá el botón **Nuevo**.
3. Se abre la pantalla **Nuevo movimiento**.

![Pantalla de Nuevo movimiento](/ayuda/img/operativo/registrar-gasto-caja-paso1.png)

---

### Paso 2. Seleccionar la opción EGRESO
1. En **Tipo de movimiento**, hacé clic en el botón rojo **EGRESO**.
2. En **Medio de pago**, seleccioná la caja de donde sale el dinero (por defecto, **Efectivo**).
3. En **Rubro**, elegí la clasificación correspondiente (por ejemplo: Gastos generales, Insumos o Retiros).
4. En **Subrubro**, seleccioná el concepto específico habilitado para mostrador.

![Selección de EGRESO](/ayuda/img/operativo/registrar-gasto-caja-paso2.png)

---

### Paso 3. Cargar el importe y registrar
1. En el campo **Monto**, escribí el importe exacto en pesos que sale del cajón.
2. En **Observaciones**, ingresá el detalle de la compra o destino del dinero (por ejemplo: "Compra artículos de limpieza para vestuarios" o "Retiro de efectivo por dueño").
3. Hacé clic en el botón **Registrar**.
4. Wings resta el importe del saldo de efectivo de tu turno de caja y lo asienta en el detalle de operaciones.

![Completar monto y Registrar](/ayuda/img/operativo/registrar-gasto-caja-paso3.png)

---

## Qué hago si…

### ¿Qué hago si el gasto supera el efectivo disponible en el cajón?
Wings no permite registrar ni editar un egreso si el monto supera los billetes físicos disponibles en el cajón y te mostrará el mensaje: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.». En ese caso, si ingresó dinero para cubrir la compra, registrá primero el aporte de efectivo como un **INGRESO** en tu caja y luego cargá el egreso.

### ¿Qué hago con los tickets y facturas de las compras menores?
Guardá todos los comprobantes físicos dentro del cajón de la caja. Cuando cierres tu turno al final del día, los comprobantes respaldarán los egresos cargados y permitirán al dueño auditar las compras al validar el turno.

### ¿Qué hago si me equivoqué y cargué un egreso de más?
Si tu turno de caja sigue abierto (o en estado rechazada), entrá a **Detalle** de la caja: en la fila del egreso tocá el botón **Eliminar** (o **Editar** si solo necesitás corregir el monto o concepto) y confirmá el aviso. El importe se restituye de inmediato al saldo de tu caja. Si la caja ya fue validada, avisale al dueño para realizar el ajuste en Cashflow.
