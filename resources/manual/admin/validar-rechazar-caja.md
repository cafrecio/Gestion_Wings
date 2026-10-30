---
titulo: Validar o rechazar el cierre de caja de un turno
rol: admin
categoria: Cajas y turnos
orden: 25
resumen: Cómo auditar el arqueo final rendido por la operativa, aprobar el pase a Cashflow o rechazar con motivo.
pantalla: /caja/{id}/resumen
---

# Validar o rechazar el cierre de caja de un turno

Esta tarea te explica cómo controlar el dinero rendido al final de un turno operativo, validar la caja para ingresar los fondos a las finanzas generales del club o rechazarla con una observación para que la operativa corrija una diferencia.

> [!IMPORTANT]
> *El pase a Cashflow:* Mientras una caja esté en estado *Cerrada*, el dinero pertenece al turno de la operativa y todavía no impacta en los saldos generales del club. Al tocar **Validar**, la caja pasa a estado *Validada*, los fondos ingresan a **Cashflow** y los movimientos quedan sellados para siempre.

---

## Pasos para validar o rechazar el turno

### Paso 1. Abrir el resumen de la caja cerrada
1. En el menú, ingresá a **Caja** y seleccioná la caja del turno que figure en estado **Cerrada**.
2. Revisá el cuadro **Efectivo del mostrador**:
   - **Inicial:** efectivo recibido al abrir la caja.
   - **Esperado:** efectivo que debería haber en el cajón según los movimientos del turno.
   - **Contado:** efectivo real declarado por la operativa al cerrar.
   - **Diferencia:** desvío entre lo esperado y lo contado (en verde si coincide o sobró, en rojo si faltó).
   - **Cambio que queda:** fondo que permanece en el cajón para el turno siguiente.
   - **Retiro / entrega:** dinero físico que se retira del mostrador.
3. Más abajo, revisá la tabla de totales por medio de pago con los ingresos y egresos registrados durante el turno.

![Resumen de caja cerrada y arqueo final](/ayuda/img/admin/validar-rechazar-caja-paso1.png)

---

### Paso 2. Opción A: Rechazar con motivo si hay diferencias
1. Si el dinero contado no coincide o hay cobros mal imputados, tocá el botón **Rechazar**.
2. Se abrirá el panel **Motivo del rechazo**.
3. En el campo **Describí el motivo...**, escribí con claridad qué debe revisar la operativa (por ejemplo: "Falta registrar el cobro de cuota de Alumno Gómez en efectivo").
4. Tocá **Rechazar**. La caja volverá al estado *Rechazada* para que la operativa entre a su turno, corrija o cancele los movimientos observados y vuelva a cerrarla.

![Panel para indicar el motivo del rechazo](/ayuda/img/admin/validar-rechazar-caja-paso2.png)

---

### Paso 3. Opción B: Validar el turno si todo coincide
1. Si el arqueo es correcto y el dinero físico coincide con lo declarado, tocá el botón **Validar**.
2. Wings cambia el estado a **Validada**.
3. El dinero en efectivo pasa automáticamente al libro **Cashflow** y el turno queda definitivamente cerrado y archivado.

![Caja en estado Validada](/ayuda/img/admin/validar-rechazar-caja-paso3.png)

---

## Qué hago si…

### ¿Qué hago si rechazo la caja y la operativa no sabe cómo corregirla?
Cuando rechazás la caja, la operativa puede ingresar al detalle de su turno y tocar **Cancelar** sobre los cobros o gastos equivocados (indicando el motivo). Una vez subsanado el error, la operativa vuelve a contar el efectivo y cierra la caja para que la valides nuevamente.

### ¿Se puede rechazar una caja que ya fue validada?
No. Una vez validada, la caja no se puede rechazar ni reabrir. Si posteriormente se detecta un error, la corrección se hace mediante un ajuste compensatorio directo en **Cashflow**.

### ¿Qué hago si la caja dice Cerrada pero no veo Validar ni Rechazar?
A esa caja le falta el arqueo: se cerró sin declarar el efectivo contado y el cambio retenido. Hasta que se declare, Wings no muestra **Validar** ni **Rechazar**, y si se intenta validar responde «Falta declarar el efectivo contado y el cambio retenido antes de validar.». En su lugar aparece **Cerrar**: tocalo (o pedíselo a la operativa), cargá **Efectivo contado** y **Cambio que queda**, y tocá **Cerrar**. Después la caja muestra **Validar** y **Rechazar**.
