---
titulo: Crear y administrar grupos y sus precios
rol: admin
categoria: El club
orden: 42
resumen: Cómo armar un grupo con un deporte y un nivel, cargarle las frecuencias semanales con su precio y cuándo rige un cambio de precio.
pantalla: /grupos
---

# Crear y administrar grupos y sus precios

Un grupo es la combinación de un deporte y un nivel (por ejemplo, "Patín – Escuelita"). Cada grupo tiene una o más frecuencias semanales con su precio mensual: es lo que se le cobra al alumno según cuántas veces por semana viene. Wings no maneja cupos ni edades por grupo.

---

## Crear un grupo

1. En el menú lateral, sección *El club*, entrá a **Grupos** y tocá **Nuevo**.
2. Elegí **Deporte** y **Nivel**. Solo puede haber un grupo por cada combinación.
3. En **Precios por frecuencia**, tocá **Nuevo** para agregar una fila, elegí cuántas veces por semana (de 1 a 5) y escribí el precio mensual. Agregá tantas filas como frecuencias ofrezcas.
4. Tocá **Guardar**. Wings muestra «Grupo creado correctamente.».

![Formulario de nuevo grupo con sus frecuencias](/ayuda/img/admin/administrar-grupos-paso1.png)

---

## Cambiar un precio o agregar una frecuencia

1. En **Grupos**, tocá **Editar** en la tarjeta del grupo. El deporte no se puede cambiar.
2. Cambiá el precio de la fila, o tocá **Nuevo** para sumar una frecuencia.
3. Tocá **Guardar**. Wings muestra «Grupo actualizado correctamente.».

*Cuándo rige un precio nuevo.* La pantalla lo dice antes de guardar: los alumnos que se den de alta desde hoy pagan el precio nuevo desde su primer mes; los alumnos que ya estaban lo pagan desde el primer día del mes siguiente. Las cuotas ya generadas no cambian.

![Edición de precios con el aviso de cuándo rige](/ayuda/img/admin/administrar-grupos-paso2.png)

---

## Pasar un grupo a otro nivel

1. En **Grupos**, tocá **Editar** en la tarjeta del grupo.
2. Elegí el **Nivel** nuevo. La lista muestra los niveles activos.
3. Tocá **Guardar**. Wings muestra «Grupo actualizado correctamente.».

El grupo conserva sus alumnos, sus precios y sus clases. Su nombre cambia en todas las pantallas, también en clases y liquidaciones anteriores. Los recibos PDF ya generados no cambian.

---

## Activar o desactivar un grupo

En la tarjeta del grupo, el interruptor **Activo** / **Inactivo** lo saca de uso. Un grupo inactivo deja de ofrecerse al dar de alta alumnos y al crear clases. Para volver a activarlo, su nivel tiene que estar activo; si no, Wings avisa que no se puede activar porque el nivel está inactivo. Activá el nivel en **Niveles** o pasá el grupo a otro.

---

## Qué hago si…

### ¿Qué hago si dice «Ya existe un grupo con ese deporte y nivel.»?
Ese grupo ya existe, quizás inactivo. Buscalo en **Grupos** y activalo, o editá sus frecuencias. También sale si querés pasar un grupo a un nivel donde ese deporte ya tiene otro: Wings no une los dos grupos, así que el cambio no se hace.

### ¿Qué hago si dice «El nivel seleccionado no existe o está inactivo.»?
Ese nivel se inactivó mientras completabas el formulario. Elegí otro, o activalo en **Niveles**.

### ¿Qué hago si dice «Hay dos filas con la misma frecuencia. Dejá una sola.»?
Al crear el grupo, elegiste dos veces la misma frecuencia. Sacá una de las dos filas y tocá de nuevo **Guardar**.

### ¿Qué hago si dice «Ya hay una fila con esa frecuencia. Cambialas de a una.»?
Al editar el grupo, intentaste cambiar una fila a una frecuencia que ya tiene otra fila activa (por ejemplo, intercambiar 1x y 2x). Wings no permite intercambiarlas en el mismo paso: cambiá una primero a una frecuencia libre o guardá los cambios de a una fila.

### ¿Qué hago si dice «Las frecuencias del grupo cambiaron. Volvé a cargar el formulario.»?
Alguien modificó o desactivó alguna frecuencia mientras tenías la pantalla abierta. Volvé a entrar a **Editar** para ver las frecuencias actuales del grupo.

### ¿Qué hago si quiero sacar una frecuencia?
Podés sacarla editando el grupo (**Editar** y quitando la fila con **✕**) o desde su ficha (**Ver** y tocando **Eliminar** en la fila de la frecuencia, tras confirmar «¿Eliminar este plan?»):

- *Si nunca se usó:* se borra por completo y muestra «Plan eliminado.» (en la ficha) o «Grupo actualizado correctamente.» (en edición).
- *Si se usó antes pero no tiene alumnos hoy:* se desactiva y deja de ofrecerse para nuevas inscripciones o cobros, mostrando «Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.». El historial anterior (cuotas, deudas y pagos) se conserva intacto. Si más adelante volvés a agregar esa misma frecuencia, se reactiva con el nuevo precio.
- *Si tiene alumnos hoy:* Wings no la saca y avisa cuántos tiene, por ejemplo *2x por semana tiene 1 alumno: cambiales el plan antes.* (o *2x por semana tiene 3 alumnos: cambiales el plan antes.*). Si estabas editando el formulario, no se guarda ningún cambio hasta que les asignes otro plan.
- *Si es la última frecuencia activa:* el grupo no puede quedar vacío. En la ficha avisa «No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.», y al editar «El grupo debe tener al menos una frecuencia para poder cargar alumnos.».

Si editás una fila existente para cambiarla a una frecuencia que ya estaba desactivada, Wings avisa por ejemplo *Ya hay una 2x desactivada. Agregala con Nuevo en vez de cambiar esta fila.*. Tocá **Nuevo** para reactivarla con su nuevo precio en vez de modificar una fila existente.

### ¿Qué hago si no me deja desactivar el grupo?
Wings no desactiva un grupo con alumnos activos o clases futuras sin cancelar, y el aviso dice cuántos tiene, por ejemplo *No se puede desactivar el grupo porque tiene 1 alumno activo*. Movelos o pasalos a **Inactivo**, y cancelá las clases futuras.
