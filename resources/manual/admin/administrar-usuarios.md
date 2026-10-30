---
titulo: Crear y administrar usuarios
rol: admin
categoria: Sistema
orden: 49
resumen: Cómo darle acceso a Wings a una persona con su rol, cambiarle la contraseña y desactivarla.
pantalla: /usuarios
---

# Crear y administrar usuarios

Un usuario es la cuenta con la que alguien entra a Wings. El rol define qué ve: **Administrador** (acceso total), **Operativo** (caja, alumnos y clases) o **Profesor** (solo clases y asistencias).

---

## Crear un usuario

1. En el menú lateral, sección *Sistema*, entrá a **Usuarios** y tocá **Nuevo**.
2. Completá **Nombre** y **Email**: el email es con el que la persona entra.
3. Escribí la **Contraseña** (mínimo 12 caracteres) y repetila en **Confirmar contraseña**.
4. Elegí el **Rol**.
   - Para **Operativo** podés cargar su **CBU o alias**, para pagarle.
   - Para **Profesor** elegí el **Profesor vinculado**: tiene que estar cargado antes en **Profesores** y no tener otro usuario.
5. Tocá **Crear**. Wings muestra «Usuario creado correctamente.».

Pasale a la persona su email y su contraseña por un medio privado.

![Formulario de nuevo usuario](/ayuda/img/admin/administrar-usuarios-paso1.png)

---

## Cambiar la contraseña o el rol

Wings no tiene la opción "olvidé mi contraseña": si alguien la olvida, se la cambiás vos.

1. En **Usuarios**, tocá **Editar** en la tarjeta de la persona.
2. Escribí la contraseña nueva en **Contraseña** y en **Confirmar contraseña**. Si los dejás vacíos, la contraseña no cambia.
3. Cambiá el **Rol** si hace falta y tocá **Guardar**. Wings muestra «Usuario actualizado correctamente.».

Al cambiar la contraseña, Wings cierra las sesiones abiertas de esa persona: tiene que volver a entrar con la nueva.

---

## Desactivar un usuario

El interruptor de la tarjeta lo desactiva: la persona deja de poder entrar. Tu propia tarjeta dice *(vos)*.

---

## Qué hago si…

### ¿Qué hago si dice «Este usuario tiene una caja abierta. Primero hay que cerrar la caja.»?
No se puede desactivar ni cambiarle el rol a una operativa con su caja abierta. Primero tiene que cerrarla (o la cerrás vos desde **Caja**).

### ¿Puedo desactivarme o cambiarme el rol a mí mismo?
No. Tu propia tarjeta no tiene interruptor, y si intentás cambiarte el rol Wings responde «No podés cambiar tu propio rol.». Si hace falta, que lo haga otro administrador.

### ¿Qué hago si no aparece el profesor en Profesor vinculado?
Solo aparecen los profesores activos que todavía no tienen usuario. Si no está, cargalo primero en **Profesores** (o activalo), o fijate si ya tiene un usuario creado.

### ¿Qué hago si dice «Las contraseñas no coinciden.»?
Lo que escribiste en **Confirmar contraseña** no es igual a **Contraseña**. Escribilas de nuevo.
