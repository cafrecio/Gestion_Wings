---
titulo: Administrar las cuentas (tipos de caja)
rol: admin
categoria: El club
orden: 45
resumen: Cómo dar de alta el efectivo, las cuentas bancarias y las billeteras del club, con su saldo inicial y si pueden quedar en negativo.
pantalla: /tipos-caja
---

# Administrar las cuentas (tipos de caja)

Un tipo de caja es cada lugar donde el club tiene plata: el efectivo, una cuenta bancaria, una billetera virtual. Son los medios de pago que aparecen al cobrar, al pagar y al transferir, y cada uno tiene su saldo en **Cashflow**.

---

## Crear una cuenta

1. En el menú lateral, sección *El club*, entrá a **Tipos de Caja** y tocá **Nuevo**.
2. Completá **Nombre**, **Abreviatura** (hasta 5 letras; Wings la pasa a mayúsculas) y, si querés, **Descripción**.
3. Cargá el **Saldo inicial**: la plata que había en esa cuenta cuando empezaste a usar Wings.
4. El interruptor **Descubierto permitido** / **Sin descubierto** define si Wings deja pagar una liquidación de profesor que dejaría la cuenta en negativo. Hoy solo se controla en ese pago: un gasto, un retiro o una transferencia pueden dejarla en negativo igual.
5. Tocá **Guardar**. Wings muestra «Tipo de caja creado correctamente.».

![Formulario de nueva cuenta](/ayuda/img/admin/administrar-tipos-caja-paso1.png)

---

## Editar o desactivar una cuenta

- **Editar** en la tarjeta cambia nombre, abreviatura, descripción y descubierto. El **Saldo inicial** solo se puede cambiar mientras la cuenta no tenga ningún movimiento, ni siquiera uno cancelado.
- **Desactivar** en la tarjeta la saca de uso; **Activar** la vuelve a habilitar.

---

## Qué hago si…

### ¿Qué hago si no puedo cambiar el saldo inicial?
La cuenta ya tiene movimientos, y cambiar el saldo inicial cambiaría todos los saldos hacia atrás. Si el saldo no coincide con la realidad, registrá un movimiento de ajuste en **Cashflow** con el motivo.

### ¿Qué hago si dice «No se puede desactivar porque es el medio de pago del cajón de mostrador.»?
Esa cuenta es el efectivo del mostrador y no se puede desactivar mientras lo sea. Ver *Configurar el efectivo del mostrador*.
