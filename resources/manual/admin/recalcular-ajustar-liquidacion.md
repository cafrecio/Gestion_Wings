---
titulo: Recalcular o ajustar una liquidación abierta
rol: admin
categoria: Liquidaciones
orden: 34
resumen: Cómo actualizar los totales ante cambios de asistencia o fijar un monto final acordado con el docente.
pantalla: /liquidaciones/{id}
---

# Recalcular o ajustar una liquidación abierta

Esta tarea te explica cómo actualizar una liquidación si se cargaron asistencias tardías o cómo ingresar un ajuste manual de importe para acordar el monto final a pagar al profesor.

> [!IMPORTANT]
> *Solo mientras esté abierta:* Las opciones de recálculo y ajuste solo están disponibles mientras la liquidación permanezca en estado **Abierta**. Una vez cerrada o pagada, las clases y montos quedan sellados para preservar la coherencia del recibo y del libro Cashflow.

---

## Dos formas de actualizar el importe

### Opción 1. Recalcular por cambios en asistencias o clases
1. Si una profesora terminó de pasar la lista de una clase atrasada o se corrigió una inasistencia después de haber generado la liquidación, abrí la liquidación en **Liquidaciones**.
2. En la barra de acciones superior, tocá el botón **Recalcular**.
3. Wings volverá a consultar todas las clases dictadas y cuotas cobradas del mes, conservando la tarifa congelada, y actualizará de inmediato la cantidad de horas y el total bruto a pagar.

![Botón Recalcular en la barra de acciones](/ayuda/img/admin/recalcular-ajustar-liquidacion-paso1.png)

---

### Opción 2. Ajustar el monto final a pagar
1. Si necesitás aplicar una bonificación, un descuento extraordinario, un redondeo o un anticipo pactado con el docente, desplazate hasta el panel **Monto ajustado**.
2. En el campo **Monto ajustado**, escribí el importe final neto en pesos que efectivamente se le abonará al profesor.
3. Wings te indica: `Total a pagar, desde $ 0. La diferencia se calcula automáticamente.`
4. Tocá **Guardar**.
5. El sistema recalculará la diferencia entre el total del sistema y el monto pactado, mostrando el ajuste en el resumen de la cabecera sin alterar el desglose de clases.

![Fijar el monto final ajustado en la liquidación](/ayuda/img/admin/recalcular-ajustar-liquidacion-paso2.png)

---

## Qué hago si…

### ¿Qué hago si la liquidación ya fue cerrada y necesito cambiar el monto?
Una liquidación en estado *Cerrada* no permite recalcular clases. Si todavía no fue pagada, podés cancelar la liquidación desde las acciones avanzadas y generar una nueva con los datos corregidos.

### ¿Qué pasa con el ajuste si luego toco Recalcular?
Si tocás **Recalcular**, Wings refresca las clases del sistema pero respeta el monto final que hayas fijado manualmente, recalculando la nueva diferencia de ajuste.
