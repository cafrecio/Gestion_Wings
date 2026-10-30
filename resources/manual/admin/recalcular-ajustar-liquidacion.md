---
titulo: Recalcular o ajustar una liquidación
rol: admin
categoria: Liquidaciones
orden: 34
resumen: Cómo actualizar los importes por asistencias tardías o acordar un monto final en liquidaciones a comisión.
pantalla: /liquidaciones/{id}
---

# Recalcular o ajustar una liquidación

Esta tarea te explica cómo actualizar una liquidación si se cargaron asistencias tardías o cómo ingresar un ajuste manual de importe para acordar el monto final a pagar al docente en liquidaciones a comisión.

> [!IMPORTANT]
> *Diferencia según el tipo:* El recálculo de clases y horas está disponible para cualquier liquidación en estado **Abierta**. En cambio, el ajuste manual del monto final aplica exclusivamente a liquidaciones a comisión, y puede realizarse tanto en estado **Abierta** como en estado **Cerrada**, siempre que permanezca pendiente de pago y no tenga un egreso registrado.

---

## Dos formas de actualizar el importe

### Opción 1. Recalcular por cambios en asistencias o clases (Abierta)
1. Si una profesora terminó de pasar la lista de una clase atrasada o se corrigió una inasistencia después de haber generado la liquidación, abrí la liquidación en **Liquidaciones**.
2. En la barra de acciones superior, tocá el botón **Recalcular**.
3. Wings volverá a consultar todas las clases dictadas y cuotas cobradas del período, conservando la tarifa congelada, y actualizará de inmediato la cantidad de horas o el total bruto a pagar.

![Botón Recalcular en la barra de acciones](/ayuda/img/admin/recalcular-ajustar-liquidacion-paso1.png)

---

### Opción 2. Ajustar el monto final a pagar (Solo liquidaciones a comisión)
1. Si la liquidación es a comisión y necesitás aplicar una bonificación, un descuento extraordinario o un redondeo acordado con el docente, desplazate hasta el panel de importes.
2. Tocá **Editar monto final** para abrir el formulario de ajuste.
3. En el campo **Monto ajustado**, escribí el importe final en pesos que efectivamente se le abonará al profesor.
4. En el campo **Motivo del ajuste**, indicá la justificación del cambio (entre 5 y 255 caracteres).
5. Tocá **Guardar**.
6. Wings registrará el ajuste en la auditoría interna sin alterar el desglose de clases ni el total calculado original, y actualizará el **Monto final** a pagar.

![Fijar el monto final ajustado en la liquidación](/ayuda/img/admin/recalcular-ajustar-liquidacion-paso2.png)

---

## Qué hago si…

### ¿Qué hago si la liquidación ya fue cerrada y necesito cambiar el monto?
- *Si es a comisión:* podés ajustar el monto final aun con la liquidación cerrada, siempre que no esté pagada. Desplegá **Editar monto final**, ingresá el importe junto con el **Motivo del ajuste** y tocá **Guardar**.
- *Si es por hora:* las liquidaciones por hora no admiten ajuste manual ni recálculo directo una vez cerradas. Si todavía no fue pagada, podés cancelarla desde la sección inferior **Cancelar liquidación** indicando el motivo, tocar **Cancelar**, corregir las asistencias de las clases correspondientes y volver a generar la liquidación.

### ¿Qué pasa con el ajuste si luego toco Recalcular?
Si la liquidación a comisión está abierta y tocás **Recalcular**, Wings refresca las clases y cuotas del sistema pero respeta el monto final que hayas fijado manualmente, recalculando la nueva diferencia de ajuste.
