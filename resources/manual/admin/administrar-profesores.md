---
titulo: Cargar y administrar profesores
rol: admin
categoria: El club
orden: 43
resumen: Cómo dar de alta a un profesor con su valor por hora o su comisión, editarlo y pasarlo a inactivo.
pantalla: /profesores
---

# Cargar y administrar profesores

Cada profesor pertenece a un deporte. Según cómo liquide ese deporte, Wings le pide el **Valor por hora** o el **Porcentaje de comisión (%)**. Al guardarlo, Wings crea solo el concepto de sueldo de ese profesor en **Rubros**, para registrar después sus pagos; no hay que cargarlo a mano.

Cargar un profesor no le da acceso a Wings. Para que entre a tomar lista, creale un usuario con rol de profesor en **Usuarios**.

---

## Dar de alta un profesor

1. En el menú lateral, sección *El club*, entrá a **Profesores** y tocá **Nuevo**.
2. Completá los datos obligatorios: **Nombre**, **Apellido**, **DNI**, **Fecha de nacimiento**, **Dirección**, **Localidad**, **Teléfono** y **Deporte**. **Email** y **CBU o alias** son opcionales.
3. Según el deporte elegido aparece **Valor por hora** o **Porcentaje de comisión (%)** (de 0 a 100). Completalo: Wings deja guardar sin ese dato, pero después no puede generarle la liquidación y avisa «El profesor no tiene valor por hora configurado.» o «El profesor no tiene porcentaje de comisión configurado.».
4. Tocá **Guardar**. Wings muestra «Profesor creado correctamente.».

![Formulario de nuevo profesor](/ayuda/img/admin/administrar-profesores-paso1.png)

---

## Ver y editar un profesor

1. En **Profesores**, buscá por nombre o apellido y tocá **Ver** para su ficha: datos, próximas clases y liquidaciones.
2. Tocá **Editar**, cambiá lo necesario y **Guardar**. Wings muestra «Profesor actualizado correctamente.».

---

## Pasar un profesor a inactivo

En la tarjeta del profesor, el interruptor **Activo** / **Inactivo**. Al pasarlo a inactivo, Wings también desactiva su usuario y cierra sus sesiones. Si después lo volvés a activar, su usuario queda como estaba: activalo en **Usuarios** si corresponde.

---

## Qué hago si…

### ¿Qué hago si no me deja pasarlo a inactivo?
Wings lo rechaza en tres casos:
- Si tiene clases futuras o en curso: el aviso dice cuántas, por ejemplo *Tiene 4 clases futuras o en curso*. Asigná esas clases a otro profesor (**Clases** → la clase → **Editar**) o cancelalas.
- Si su usuario tiene una caja abierta: «Primero cerrá la caja abierta del usuario vinculado.».
- Si su usuario es una cuenta protegida: «La cuenta vinculada está protegida. No se puede desactivar desde Profesores.».

### ¿Qué hago si dice «Ya existe un profesor con ese DNI.»?
Ese profesor ya está cargado, quizás inactivo. Buscalo y activalo en lugar de crear otro.

### ¿Qué pasa con sus liquidaciones si lo paso a inactivo?
Las clases que ya dictó se pueden seguir liquidando. Ver *Generar la liquidación mensual de profesores*.
