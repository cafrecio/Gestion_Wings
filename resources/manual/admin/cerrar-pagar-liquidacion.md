---
titulo: Cerrar y pagar una liquidación
rol: admin
categoria: Liquidaciones
orden: 35
resumen: Cómo finalizar la auditoría, congelar los importes y registrar la salida de dinero en Cashflow.
pantalla: /liquidaciones/{id}
---

# Cerrar y pagar una liquidación

Esta tarea te explica cómo dar por concluida la revisión de una liquidación docente, congelar definitivamente sus valores y registrar el pago formal para asentar la salida de dinero en las finanzas del club.

> [!IMPORTANT]
> *Dos pasos con impacto contable:*
> 1. **Cerrar:** Congela las clases y el monto final. Las clases computadas ya no podrán editarse ni cancelarse.
> 2. **Pagar:** Asienta el egreso de fondos en **Cashflow** en la cuenta seleccionada (Efectivo o la cuenta bancaria), salda la liquidación y habilita la descarga del recibo oficial.

---

## Pasos para cerrar y registrar el pago

### Paso 1. Cerrar la liquidación auditada
1. En **Liquidaciones**, abrí la liquidación del profesor que se encuentra en estado **Abierta**.
2. Verificá que no queden cuotas atrasadas pendientes de decisión. Si las hay, Wings muestra el aviso «Revisá las cuotas atrasadas para cerrar.» y no habilita el cierre.
3. Comprobá que haya concluido el mes liquidado. Antes de fin de mes, el botón **Cerrar** no aparece y Wings muestra «Se puede cerrar desde el » indicando la fecha de habilitación.
4. Una vez cumplida la fecha y resueltas las cuotas, en la barra de acciones superior tocá el botón **Cerrar**. Confirmá el aviso de confirmación.
5. Wings cambiará el estado a **Cerrada**. A partir de este momento los cálculos quedan sellados.

![Botón Cerrar en la cabecera](/ayuda/img/admin/cerrar-pagar-liquidacion-paso1.png)

---

### Paso 2. Registrar el pago en Cashflow
1. Con la liquidación cerrada, en la parte superior se desplegará el panel de pago.
2. Completá los datos del egreso:
   - **Tipo de caja:** Seleccioná la cuenta desde la que sale el dinero (**Efectivo**, la cuenta bancaria, etc.).
   - **Fecha de pago:** Indicá la fecha del egreso. Si elegís una fecha de un mes anterior, Wings muestra el aviso «Esta fecha es de un mes anterior. El pago va a quedar registrado con esa fecha y modificará el reporte de ese mes.» y el botón cambia a **Confirmar**.
   - **Observaciones:** Podés agregar una nota de control opcional.
3. Tocá el botón **Pagar** (o **Confirmar** si la fecha era anterior).
4. Wings registrará el movimiento de egreso en el libro **Cashflow**, pasará el estado a **Pagada** y habilitará el botón para descargar el recibo.

![Formulario de imputación y botón Pagar](/ayuda/img/admin/cerrar-pagar-liquidacion-paso2.png)

---

## Qué hago si…

### ¿Qué hago si no veo el botón Cerrar?
Wings no permite cerrar una liquidación antes de que concluya el período mensual liquidado. En la barra superior verás el aviso «Se puede cerrar desde el » con la fecha en que finaliza el mes. El botón **Cerrar** aparecerá a partir del primer día del mes siguiente. Si se intentara forzar el cierre antes de esa fecha, el sistema rechaza la acción con el mensaje: «No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el » indicando el día correspondiente.
Asimismo, si hay cuotas atrasadas pendientes de resolución, Wings muestra «Revisá las cuotas atrasadas para cerrar.» y bloquea el cierre con el error «Primero validá o rechazá cada cuota atrasada.».

### ¿Qué hago si al querer cerrar o pagar me dice que hay cuotas pendientes?
Si en pantalla ves el aviso «Validá o rechazá cada una antes de cerrar. Solo las validadas se incluyen en el recibo.», debés tomar una decisión (**Validar** o **Rechazar**) sobre cada cuota atrasada en la tabla de revisión. Si intentás cerrar o pagar con cuotas sin resolver, Wings rechaza la operación con el mensaje: «Primero validá o rechazá cada cuota atrasada.».

### ¿Puedo cancelar una liquidación si me equivoqué al cerrarla?
Mientras la liquidación esté *Cerrada* pero todavía no esté pagada, podés cancelarla indicando el motivo y tocando **Cancelar** en la sección Cancelar liquidación. Si ya fue pagada, el dinero ya impactó en Cashflow y Wings no permite cancelarla con el aviso: «No se puede cancelar una liquidación pagada.».
