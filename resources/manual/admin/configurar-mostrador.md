---
titulo: Cargar el saldo inicial de Efectivo
rol: admin
categoria: Cajas y turnos
orden: 51
resumen: Cómo cargar, una sola vez, cuánto efectivo tiene el club antes de abrir la primera caja.
pantalla: /caja/configuracion
---

# Cargar el saldo inicial de Efectivo

Antes de abrir la primera caja, Wings necesita saber cuánto efectivo tiene el club. Lo carga el dueño, una sola vez. La cuenta es siempre **Efectivo**: no se elige otra ni se le cambia el nombre.

Mientras no se cargue no se puede abrir ninguna caja. La operativa ve el aviso «El dueño tiene que configurar Efectivo antes de abrir la caja.».

---

## Pasos

1. En el menú lateral entrá a **Caja**. En la fila **Falta cargar el saldo de Efectivo** tocá **Configurar**.
2. Se abre **Saldo inicial de Efectivo**. En **Saldo inicial** escribí cuánto efectivo tiene el club hoy. Es obligatorio: si no hay nada, escribí 0.
3. Tocá **Guardar**. Wings muestra «Efectivo configurado.» y en **Caja** aparece **Abrir**.

![Pantalla Saldo inicial de Efectivo](/ayuda/img/admin/configurar-mostrador-paso1.png)

---

## Qué hago si…

### ¿Qué hago si cargué mal el saldo?
Una vez guardado no se puede cambiar: cualquier ajuste posterior se hace con un movimiento en **Cashflow**. Ver *Ajustar el saldo de una cuenta* en *Administrar las cuentas (tipos de caja)*.

### ¿Qué hago si dice «Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar.»?
Ya hay algún movimiento en esa cuenta, por ejemplo un cobro que hiciste antes de configurar. El saldo queda como está y **Guardar** solo deja la cuenta lista para abrir cajas. Si el saldo no coincide con la realidad, registrá un movimiento de ajuste.

### ¿Se puede usar otra cuenta para el cajón?
No. Las transferencias y los cobros que hace el dueño directamente no pasan por el cajón, pero el efectivo del cajón siempre es de la cuenta **Efectivo**.
