---
titulo: Configurar el efectivo del mostrador
rol: admin
categoria: Cajas y turnos
orden: 51
resumen: Cómo elegir en qué cuenta se registra el efectivo del cajón de la operativa, y cuándo se puede cambiar.
pantalla: /caja/configuracion
---

# Configurar el efectivo del mostrador

El cajón del mostrador es el efectivo que maneja la operativa en su turno. Wings necesita saber a qué cuenta pertenece ese efectivo (por ejemplo, la cuenta *Efectivo*). Se configura antes de abrir la primera caja, y se puede cambiar después siempre que no haya ninguna caja abierta.

Las transferencias y los cobros que hace el dueño directamente no pasan por este cajón.

---

## Pasos

1. En el menú lateral entrá a **Caja**. En la fila **Cajón del mostrador** tocá **Configurar**.
2. Se abre **Efectivo del mostrador**. En **Medio de efectivo** elegí la cuenta. Solo aparecen cuentas activas.
3. Tocá **Guardar**. Wings muestra «Medio de efectivo configurado.».

Si ya estaba configurado, la pantalla lo dice: «Configurado actualmente. Podés cambiarlo seleccionando otro medio activo.».

![Pantalla Efectivo del mostrador, ya configurada](/ayuda/img/admin/configurar-mostrador-paso1.png)

---

## Qué hago si…

### ¿Qué hago si dice «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.»?
El cambio no se puede hacer con un turno en curso, porque ese efectivo ya está contado en esa cuenta. Mientras haya una caja abierta, el botón **Configurar** queda inhabilitado en **Caja**. Cerrá la caja y volvé a intentarlo.

### ¿Qué hago si la cuenta que quiero no aparece?
Solo se ofrecen cuentas activas. Activala o creala en **Tipos de Caja**.
