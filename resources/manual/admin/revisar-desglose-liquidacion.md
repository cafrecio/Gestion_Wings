---
titulo: Revisar el desglose de una liquidación
rol: admin
categoria: Liquidaciones
orden: 33
resumen: Cómo auditar las clases computadas, tarifas congeladas y comisiones calculadas de un docente.
pantalla: /liquidaciones/{id}
---

# Revisar el desglose de una liquidación

Esta tarea te explica cómo auditar en detalle una liquidación generada en Wings, verificando qué clases fueron computadas, qué tarifas se aplicaron y cómo se determinó el importe final a pagar.

> [!IMPORTANT]
> *Auditoría transparente:* La pantalla de desglose te muestra exactamente cómo llegó Wings al total a pagar: la tarifa horaria congelada multiplicada por los minutos de cada clase dictada (en docentes por hora) o el porcentaje pactado sobre los alumnos con cobro verificado (en docentes a comisión).

---

## Pasos para auditar el desglose

### Paso 1. Revisar la cabecera e importes generales
1. En **Liquidaciones**, hacé clic sobre el profesor para abrir el desglose de su liquidación.
2. En la cabecera principal, revisá:
   - **Profesor** y deporte asignado.
   - **Período** liquidado (mes y año).
   - Tipo de liquidación: *Por hora* o *Por comisión*.
   - **Monto final** calculado hasta el momento.
   - **Estado:** Figura como *Abierta* mientras esté en revisión.

![Cabecera y resumen de la liquidación](/ayuda/img/admin/revisar-desglose-liquidacion-paso1.png)

---

### Paso 2. Auditar el detalle de clases o comisiones
1. Desplazate hacia abajo para revisar la tabla analítica:
   - Profesores por hora: Cada fila muestra la **Fecha** de la clase, el **Grupo**, la duración en minutos, el valor por hora congelado y el subtotal en pesos. Solo se computan clases dictadas con asistencia tomada y no canceladas.
   - Profesores por comisión: La tabla detalla cada alumno del grupo con pago verificado en el período, la base de cuota cobrada, el porcentaje aplicado y el subtotal resultante.
2. Si detectás alguna diferencia por una clase que se cargó tarde, podés mantener la liquidación abierta y usar las opciones de ajuste.

![Detalle de clases computadas y duración](/ayuda/img/admin/revisar-desglose-liquidacion-paso2.png)

---

## Qué hago si…

### ¿Qué hago si una clase dictada no aparece en el desglose?
Revisá que la clase tenga asistencia tomada (no figure en estado pendiente de lista) y que no haya sido marcada como cancelada. Si la asistencia se carga después de haber generado la liquidación, tocá el botón **Recalcular** para que Wings la incorpore.

### ¿Qué hago si hay cuotas atrasadas pendientes de revisión?
En profesores a comisión, si ingresaron pagos de meses anteriores, Wings mostrará en el bloque Cuotas atrasadas el aviso para validar o rechazar cada una antes de cerrar. Debés presionar **Validar** o **Rechazar** en cada fila para definir si esa comisión se abona en este período. Si intentás cerrar o pagar la liquidación sin resolverlas, Wings lo rechazará con el mensaje: «Primero validá o rechazá cada cuota atrasada.».
