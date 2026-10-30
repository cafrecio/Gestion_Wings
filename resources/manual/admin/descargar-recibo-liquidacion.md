---
titulo: Descargar el recibo oficial de liquidación
rol: admin
categoria: Liquidaciones
orden: 36
resumen: Cómo obtener e imprimir el comprobante oficial en formato A5 con anexo de clases y comisiones.
pantalla: /recibos/liquidacion/{id}
---

# Descargar el recibo oficial de liquidación

Esta tarea te explica cómo generar, descargar e imprimir el comprobante oficial de pago de una liquidación docente para archivo del club y firma de conformidad del profesor.

> [!IMPORTANT]
> *Solo liquidaciones pagadas:* El recibo oficial con validez administrativa solo se emite una vez que la liquidación fue cerrada y su pago asentado en Cashflow. Wings no emite comprobantes de liquidaciones abiertas o pendientes de pago.

---

## Pasos para descargar e imprimir el recibo

### Paso 1. Abrir la liquidación pagada
1. En **Liquidaciones**, localizá la liquidación del profesor que figure con la etiqueta verde **Pagada**.
2. Hacé clic para abrir su detalle.
3. En la barra de acciones superior, observá el botón **Recibo**.

![Botón Recibo en la liquidación pagada](/ayuda/img/admin/descargar-recibo-liquidacion-paso1.png)

---

### Paso 2. Visualizar y descargar el comprobante en PDF
1. Tocá el botón **Recibo**.
2. Wings generará en una pestaña nueva el comprobante oficial en formato A5 vertical, estructurado en dos partes:
   - Página 1 (Recibo principal): Número de recibo (`LIQ-...`), datos del docente, período, imputación contable, cuenta de salida, monto final liquidado y espacios de firma de conformidad.
   - Página 2 (Anexo de detalle): Listado cronológico de todas las clases computadas con fecha, minutos y subtotal (en docentes por hora) o nómina de alumnos con cobro verificado (en docentes a comisión).
3. Podés guardarlo en tu computadora o imprimirlo directamente para que el docente lo firme al cobrar.

![Vista previa del recibo oficial de liquidación en formato A5](/ayuda/img/admin/descargar-recibo-liquidacion-paso2.png)

---

## Qué hago si…

### ¿Qué hago si el botón Recibo no aparece?
Si en la pantalla no figura el botón de descarga del comprobante, verificá el estado de la liquidación. Si figura como *Abierta*, debés cerrarla y asentar el pago. Wings bloquea la generación de recibos con el mensaje: `Solo se pueden generar recibos de liquidaciones que han sido pagadas` hasta que no se registre la salida de fondos.

### ¿Se puede volver a descargar el recibo más adelante?
Sí. El recibo queda almacenado de forma permanente en el historial del club. Podés volver a abrir la liquidación en cualquier momento y tocar **Recibo** para descargar una copia idéntica a la original.
