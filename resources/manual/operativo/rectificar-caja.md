---
titulo: Rectificar una apertura o cierre observado
rol: operativo
categoria: Caja y cobranza
orden: 150
resumen: Cómo corregir el efectivo declarado cuando el dueño rechaza la caja indicando una observación.
pantalla: /caja/{cajaId}/rectificar
du: CAJ-005
---

# Rectificar una apertura o cierre observado

Esta tarea te explica cómo rectificar el saldo inicial declarado en una caja cuando se detecta un error de tipeo en la apertura, o cuando el dueño observa el turno al revisarlo.

> [!NOTE]
> *Requisito de rectificación:* Podés rectificar el efectivo declarado en la apertura si tu caja continúa en estado **Abierta** o si fue pasada a estado **Rechazada** por el dueño. Siempre se exige indicar el motivo del cambio para que quede registrado en la auditoría.

---

## Pasos para rectificar la apertura

### Paso 1. Ingresar a la pantalla de rectificación
1. En el menú lateral, hacé clic en **Caja**.
2. En la vista de resumen o detalle de tu caja, tocá el botón **Rectificar** (o accedé desde la alerta de observación del turno).
3. Se abre la pantalla **Rectificar apertura**, mostrando el **Responsable**, la fecha de **Apertura** y el **Efectivo declarado actual**.

![Pantalla de Rectificar apertura](/ayuda/img/operativo/rectificar-caja-paso1.png)

---

### Paso 2. Ingresar el valor corregido y el motivo
1. En el campo **Nuevo efectivo recibido**, escribí el importe correcto que realmente contaste y recibiste al abrir el cajón.
2. En **Motivo de la rectificación**, escribí de manera clara la justificación del cambio (por ejemplo: "Se tipeó un cero de más en la apertura" o "Error al contar billetes iniciales").
3. Hacé clic en el botón **Guardar**.
4. Wings actualiza el saldo inicial de la caja, recalcula el efectivo esperado de todo el turno y te devuelve al panel de operaciones con el aviso de confirmación.

![Formulario de rectificación](/ayuda/img/operativo/rectificar-caja-paso2.png)

---

## Qué hago si…

### ¿Qué pasa si el dueño ya validó la caja?
Si la caja ya tiene estado *Validada*, no se puede rectificar: los arqueos y cierres aprobados son definitivos e inalterables. En caso de requerir una corrección monetaria posterior, el dueño debe ingresar un ajuste directo en Cashflow (DU-27).

### ¿Puedo volver atrás si no quiero rectificar?
Sí. Antes de guardar, hacé clic en el botón **Volver** al pie del formulario para regresar al resumen de la caja sin modificar el efectivo inicial declarado.
