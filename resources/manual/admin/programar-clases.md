---
titulo: Programar, editar y eliminar clases
rol: admin
categoria: Clases y asistencia
orden: 50
resumen: Cómo crear una clase suelta o una serie semanal, cambiarle el horario o las profesoras, y cuándo se puede cancelar o eliminar.
pantalla: /clases/create
---

# Programar, editar y eliminar clases

Las clases las programa el dueño. Cada clase es de un grupo, en una fecha y un horario, con una o más profesoras. Sobre esas clases se toma la lista y se liquida a los profesores.

---

## Crear una clase o una serie

1. En el menú lateral, entrá a **Clases** y tocá **Nuevo**.
2. Elegí el tipo:
   - **Clase única**: completá **Fecha**, **Hora inicio** y **Hora fin**.
   - **Clases recurrentes**: completá **Fecha desde** y **Fecha hasta**, marcá los **Días de la semana** y, para cada día marcado, su **Hora inicio** y **Hora fin**. Wings crea una clase por cada día entre esas fechas.
3. Elegí el **Grupo** y marcá en **Profesores** quiénes la dan: solo aparecen profesoras activas del deporte del grupo.
4. Tocá **Guardar**. Wings muestra cuántas creó, por ejemplo *12 clase(s) creada(s)*.

Wings rechaza la carga si una profesora ya tiene otra clase que se superpone en ese horario (el aviso nombra la clase), o si elegiste una profesora que no está activa o no es del deporte: «Solo se pueden asignar profesores activos del deporte de la clase.».

No se pueden crear clases en fechas pasadas.

![Formulario de nueva clase](/ayuda/img/admin/programar-clases-paso1.png)

### El aviso de bloques de alquiler
Si un horario no empieza o no termina en punto (por ejemplo, de 18:30 a 19:30), Wings avisa antes de guardar: «El alquiler se cuenta por bloques del reloj.» y detalla cuántos bloques de una hora ocupa. Es solo un aviso para el alquiler de la cancha. Tocá **Revisar** para cambiar el horario o **Confirmar** para guardar igual.

---

## Editar una clase

1. En **Clases**, tocá **Editar** en la tarjeta de la clase. En la ficha de la clase, **Editar** aparece solo mientras la clase no tiene presentes ni está validada.
2. Cambiá la **Fecha**, la **Hora inicio**, la **Hora fin** o las profesoras (en **Profesores**), y tocá **Guardar**. Wings muestra «Clase actualizada correctamente.».

Reglas para una clase que ya pasó:
- La fecha no se cambia, y una clase futura no se puede mover hacia el pasado.
- Si cambiás el horario, Wings pide el **Motivo del cambio de horario**.
- Las profesoras de una clase pasada no se cambian desde **Editar**: se cambian en la ficha de la clase, con el botón **Editar** que está junto a sus nombres. Si la clase ya tenía profesora, Wings pide el motivo: «Esta clase ya tenía profesor asignado: indicá el motivo del cambio.». Si la clase integra una liquidación pagada, no se pueden cambiar; si está en una liquidación cerrada, Wings pide cancelar primero esa liquidación.

---

## Cancelar o eliminar

- **Cancelar** (también lo puede hacer la operativa) deja la clase en la lista como cancelada, con un motivo, por ejemplo "Feriado" o "Lluvia". Una clase cancelada no cuenta para liquidar ni admite asistencia. Si la clase ya tenía la lista tomada, Wings pide confirmar: las marcas se guardan, pero la clase deja de contar para liquidaciones y comisiones. En una serie, el panel que abre **Cancelar** le ofrece al dueño **Cancelar esta** o **Cancelar serie**, que cancela las clases de la serie desde hoy en adelante; si alguna ya tiene lista, Wings pide confirmarlo.
- **Eliminar** borra la clase del todo. Solo aparece si la clase no tiene asistencias, no está validada para liquidación y no integra una liquidación. Pide confirmar: «¿Confirmás la eliminación permanente de esta clase?».
- En una clase que es parte de una serie, el panel que abre **Eliminar** ofrece también **Eliminar serie**: borra todas las clases de la serie, pasadas y futuras. Si una sola no se puede eliminar, Wings no borra ninguna y dice cuál.

![Ficha de la clase con sus acciones](/ayuda/img/admin/programar-clases-paso2.png)

---

## Qué hago si…

### ¿Qué hago si en lugar de Eliminar aparece «No se puede eliminar:»?
La clase ya tiene historia. El motivo es uno de estos: *Tiene asistencias registradas: debe cancelarse con motivo.*, *Está validada para liquidación.* o *Integra una liquidación.* Usá **Cancelar** en su lugar. Si la clase está en una liquidación cerrada o pagada, tampoco se puede cancelar: Wings responde «No se puede cancelar: la clase está en una liquidación cerrada o pagada.». Si la liquidación está cerrada y sin pagar, primero cancelá la liquidación.

### ¿Qué hago si dice «No se puede cambiar el horario: la clase integra una liquidación cerrada.»?
El horario de esa clase ya se usó para pagarle a la profesora. Si de verdad hay que corregirlo, primero cancelá la liquidación cerrada en **Liquidaciones** (solo si no está pagada) y después editá la clase.

### ¿Qué hago si no aparece una profesora para elegir?
Solo aparecen las profesoras activas del deporte del grupo. Revisá en **Profesores** que esté activa y en ese deporte.
