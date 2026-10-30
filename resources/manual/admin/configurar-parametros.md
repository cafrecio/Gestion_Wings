---
titulo: Configurar los parámetros del club
rol: admin
categoria: Sistema
orden: 48
resumen: Qué se configura en Configuración (inscripción, días de gracia, recargo por mora, primera cuota y avisos) y desde cuándo rige cada cambio.
pantalla: /configuraciones
---

# Configurar los parámetros del club

En **Configuración** están las reglas de plata que Wings aplica solo. Cada parámetro tiene su propio **Guardar**: se guarda de a uno y Wings muestra *Guardado* al lado del botón.

---

## La plata

- **Inscripción al club**: el **Importe de la inscripción ($)**. Se cobra una sola vez por persona. Cambiarlo afecta las inscripciones nuevas; las ya creadas conservan su precio.

## La cobranza

- **Días de gracia para pagar**: el **Último día de gracia del mes** (del 1 al 28). Con 10, una cuota del mes impaga pasa a morosa el día 11. Quien debe meses anteriores sigue siendo deudor.
- **Recargo por mora: día**: el **Último día sin recargo** (del 1 al 28). Con 10, el recargo puede aplicarse desde el día 11.
- **Recargo por mora: porcentaje**: el **Porcentaje de recargo (%)**, de 0 a 100. Se calcula una sola vez sobre el importe original de cada cuota; no cambia recargos anteriores ni se aplica a meses cerrados o a alumnos inactivos. Con 0 no se aplican recargos nuevos.
- **Generación de cuotas mensuales**: Wings genera las cuotas el día 1 de cada mes a las 06:00. No se cambia desde esta pantalla.

![Parámetros de la cobranza](/ayuda/img/admin/configurar-parametros-paso1.png)

---

## Importe de la primera cuota

Cuando un alumno se da de alta a mitad de mes, su fecha real de ingreso define qué porcentaje del plan paga ese primer mes. Las reglas dicen, para cada tramo de días, qué porcentaje se cobra (por ejemplo, del 1 al 15 el 100 %, del 16 al 31 el 50 %). No cambian cuotas ya creadas.

1. Tocá **Nuevo** para sumar una regla, o **Editar** en una existente.
2. Completá **Nombre**, **Porcentaje (%)** (de 1 a 100), **Día desde** y **Día hasta**.
3. Tocá **Guardar**.

Las reglas tienen que cubrir todos los días del 1 al 31, sin huecos y sin que un día quede en dos reglas. Si no, Wings no guarda y explica qué falta, por ejemplo *El día 1 queda sin cubrir* o *Los días 10 a 15 se pisan con la regla…*.

![Reglas de la primera cuota](/ayuda/img/admin/configurar-parametros-paso2.png)

---

## Los avisos

- **Correo para los avisos del club**: adónde llegan los pendientes y las situaciones que necesitan atención. Si queda vacío, se usan los correos de los administradores.
- **Telegram para los avisos del club**: el **Número del chat de Telegram**. Primero hay que escribirle al bot del club y usar el número de chat verificado, no un teléfono.

---

## Qué hago si…

### ¿Qué hago si al guardar aparece «No se guardó.»?
Arriba de la pantalla se lista qué parámetro falló y por qué. Tocá **Revisar** para ir al campo, corregilo y volvé a guardar ese parámetro.

### ¿Qué hago si quiero cambiar el día en que se generan las cuotas?
No se puede desde Wings: el día 1 a las 06:00 es fijo.

### ¿Qué hago si quiero eliminar una regla de la primera cuota?
Tocá **Eliminar** en la regla. Si al sacarla quedan días sin cubrir, Wings no la elimina: primero ampliá otra regla para que cubra esos días.
