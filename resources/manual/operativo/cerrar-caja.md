---
titulo: Cerrar el turno de caja y rendir el arqueo
rol: operativo
categoria: Caja y cobranza
orden: 140
resumen: Cómo contar el efectivo del mostrador, declarar el arqueo y cerrar el turno para revisión del dueño.
pantalla: /caja/{cajaId}/cerrar
du: CAJ-004
---

# Cerrar el turno de caja y rendir el arqueo

Esta tarea te explica cómo realizar el cierre de tu turno de caja al finalizar la jornada laboral, declarar el dinero físico contado en el cajón y dejar la rendición lista para validación del dueño.

> [!NOTE]
> *Solo efectivo:* Al realizar el arqueo contá únicamente el efectivo físico presente en el cajón de mostrador. Las transferencias bancarias o cobros electrónicos no se suman al arqueo de billetes. Podés cerrar con diferencia (faltante o sobrante): el dueño revisará la discrepancia al auditar la caja.

---

## Pasos para cerrar la caja

### Paso 1. Ingresar al formulario de cierre
1. En el menú lateral, hacé clic en **Caja**.
2. En la barra de acciones de tu turno abierto, tocá el botón **Cerrar**.
3. Se abre la pantalla **Cierre de caja** mostrando tu **Efectivo inicial** y el **Efectivo esperado** que el sistema calculó según las operaciones realizadas.

![Pantalla de Cierre de caja](/ayuda/img/operativo/cerrar-caja-paso1.png)

---

### Paso 2. Ingresar el efectivo contado y el cambio retenido
1. En el campo **Efectivo contado**, ingresá la suma total de los billetes y monedas contados en el cajón.
2. En el campo **Cambio que queda**, escribí el importe en efectivo que vas a dejar guardado en el cajón como fondo de cambio para la apertura del turno siguiente.
3. Wings calcula en tiempo real dos valores:
   - **Diferencia:** Indica si el dinero coincide exactamente con lo esperado, o si existe sobrante o faltante.
   - **Retiro / entrega:** Muestra el dinero que se retira físicamente en el sobre de rendición (Efectivo contado menos Cambio que queda).

![Ingresar montos de arqueo](/ayuda/img/operativo/cerrar-caja-paso2.png)

---

### Paso 3. Confirmar el cierre
1. Revisá los importes calculados en pantalla.
2. Hacé clic en el botón **Cerrar**.
3. Wings pasa el turno al estado *Cerrada*, bloquea la carga de nuevas operaciones en ese turno y deja la rendición disponible para que el dueño la revise y valide.

![Confirmar con botón Cerrar](/ayuda/img/operativo/cerrar-caja-paso3.png)

---

## Qué hago si…

### ¿Qué hago si tengo un faltante o sobrante de dinero?
No alteres los números para forzar que den cero: ingresá el efectivo real que contaste físicamente. Wings permite cerrar el turno con diferencia. El dueño revisará los cobros y egresos al momento de la validación y decidirá cómo proceder.

### ¿Qué hago si cerré el turno y me acordé de un cobro que no cargué?
Una vez cerrada la caja, no podés registrar movimientos nuevos. Avisale al dueño para que revise la rendición: si aún no la validó, puede rechazarla para que vuelva a estar en estado *Rechazada*, permitiéndote agregar o corregir los movimientos pendientes antes del cierre definitivo.
