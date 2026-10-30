---
titulo: Administrar deportes y su forma de liquidar
rol: admin
categoria: El club
orden: 40
resumen: Cómo crear un deporte, elegir si sus profesores cobran por hora o por comisión y cuándo rige un cambio.
pantalla: /deportes
---

# Administrar deportes y su forma de liquidar

Cada deporte del club define cómo se les paga a sus profesores: **Por hora** (según las clases dictadas) o **Por comisión** (un porcentaje de las cuotas cobradas). Los grupos, los alumnos y los profesores se cargan siempre dentro de un deporte.

---

## Crear un deporte

1. En el menú lateral, sección *El club*, entrá a **Deportes**.
2. Tocá **Nuevo**.
3. Completá **Nombre** (por ejemplo, "Patín") y elegí **Tipo de Liquidación**: **Por hora** o **Por comisión**.
4. Tocá **Guardar**. Wings muestra «Deporte creado correctamente.».

![Formulario de nuevo deporte](/ayuda/img/admin/administrar-deportes-paso1.png)

---

## Cambiar el nombre o la forma de liquidar

1. En **Deportes**, tocá **Editar** en la tarjeta del deporte.
2. Arriba ves cómo liquida hoy (*Actualmente: Por hora* o *Por comisión*).
3. Cambiá lo que necesites y tocá **Guardar**.
   - Si solo cambiaste el nombre, Wings muestra «Deporte actualizado correctamente.».
   - Si cambiaste la forma de liquidar, *el cambio no rige ya*: queda programado para el primer día del mes siguiente. Wings lo confirma con un aviso del tipo *Cambio programado desde el 01/12/2026* y la pantalla muestra la línea *Programado*. El mes en curso se sigue liquidando como antes.

![Cambio de forma de liquidar programado](/ayuda/img/admin/administrar-deportes-paso2.png)

---

## Activar o desactivar un deporte

En la tarjeta del deporte, el interruptor **Activo** / **Inactivo** lo habilita o lo saca de uso. Un deporte inactivo deja de ofrecerse al crear grupos, alumnos y profesores.

---

## Qué hago si…

### ¿Qué hago si Wings no me deja desactivar el deporte?
Wings no desactiva un deporte que todavía tiene alumnos activos o clases futuras sin cancelar. El aviso dice cuántos hay, por ejemplo *No se puede desactivar el deporte porque tiene 3 alumnos activos y 2 clases futuras*. Pasá esos alumnos a **Inactivo**: un alumno no cambia de deporte, y moverlo a otro grupo del mismo deporte no destraba. Cancelá o eliminá también las clases futuras; después vas a poder desactivarlo.

### ¿Qué hago si al pasar a comisión aparece «Para liquidar por comisión falta cargar un porcentaje válido a:»?
Algunos profesores de ese deporte no tienen cargado el **Porcentaje de comisión (%)**. El cambio se guarda igual, pero esos profesores no van a poder tener una liquidación por comisión hasta que les cargues el porcentaje en **Profesores** → **Editar**.

### ¿Qué hago si dice que no se puede cambiar la forma de liquidar porque hay liquidaciones abiertas?
La pantalla lo avisa arriba: el cambio requiere no tener liquidaciones abiertas de ese deporte. Si hay alguna, Wings la nombra en el aviso *No se puede cambiar la forma de liquidar: hay liquidaciones abiertas: …*. Cerrá o eliminá esas liquidaciones en **Liquidaciones** y volvé a guardar el cambio.

### ¿Qué hago si dice «Ya existe un deporte con ese nombre.»?
Ese deporte ya está cargado, quizás inactivo. Buscalo en la lista y activalo con el interruptor en lugar de crear otro.
