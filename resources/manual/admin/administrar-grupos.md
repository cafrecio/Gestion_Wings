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

1. En **Grupos**, tocá **Editar** en la tarjeta del grupo. El deporte y el nivel no se pueden cambiar.
2. Cambiá el precio de la fila, o tocá **Nuevo** para sumar una frecuencia.
3. Tocá **Guardar**. Wings muestra «Grupo actualizado correctamente.».

*Cuándo rige un precio nuevo.* La pantalla lo dice antes de guardar: los alumnos que se den de alta desde hoy pagan el precio nuevo desde su primer mes; los alumnos que ya estaban lo pagan desde el primer día del mes siguiente. Las cuotas ya generadas no cambian.

![Edición de precios con el aviso de cuándo rige](/ayuda/img/admin/administrar-grupos-paso2.png)

---

## Activar o desactivar un grupo

En la tarjeta del grupo, el interruptor **Activo** / **Inactivo** lo saca de uso. Un grupo inactivo deja de ofrecerse al dar de alta alumnos y al crear clases.

---

## Qué hago si…

### ¿Qué hago si dice «Ya existe un grupo con ese deporte y nivel.»?
Ese grupo ya existe, quizás inactivo. Buscalo en **Grupos** y activalo, o editá sus frecuencias.

### ¿Qué hago si dice «Hay dos filas con la misma frecuencia. Dejá una sola.»?
Al crear el grupo, elegiste dos veces la misma frecuencia. Sacá una de las dos filas y tocá de nuevo **Guardar**.

### ¿Qué hago si quiero sacar una frecuencia?
Solo se puede sacar una frecuencia que nunca tuvo alumnos. Si borrás la fila de una frecuencia con alumnos activos y guardás, Wings no la elimina y no muestra ningún aviso. Y si la frecuencia tuvo alumnos alguna vez (aunque ya los hayas pasado a otra), Wings guarda esa historia y al guardar el grupo muestra una página de error. En esos casos, dejá la frecuencia en el grupo.

También podés sacar una frecuencia desde la ficha del grupo (**Ver**), con **Eliminar** en su fila: pide confirmar «¿Eliminar este plan?» y, si se puede, muestra «Plan eliminado.». Wings avisa si el grupo quedaría sin frecuencias («No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.») o si tiene alumnos activos («No se puede eliminar: hay alumnos con este plan activo.»). Si la frecuencia tuvo alumnos alguna vez, el resultado es el mismo error de antes. Un grupo tiene que conservar al menos una frecuencia: si las borrás todas, Wings responde «El grupo debe tener al menos una frecuencia para poder cargar alumnos.».

### ¿Qué hago si no me deja desactivar el grupo?
Wings no desactiva un grupo con alumnos activos o clases futuras sin cancelar, y el aviso dice cuántos tiene, por ejemplo *No se puede desactivar el grupo porque tiene 1 alumno activo*. Movelos o pasalos a **Inactivo**, y cancelá las clases futuras.
