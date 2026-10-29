---
titulo: Tomar asistencia en una clase
rol: profesor
categoria: Clases y asistencia
orden: 30
resumen: Cómo abrir la lista de una clase y registrar presentes o ausentes de los alumnos.
pantalla: /clases/{id}
---

# Tomar asistencia en una clase

Esta tarea te explica cómo registrar la presencia o ausencia de los alumnos asignados a tu clase del día.

---

## Pasos para tomar asistencia

### Paso 1. Entrar a la clase
1. Desde el menú lateral, tocá **Clases**.
2. Vas a ver el listado de tus clases programadas para hoy.
3. En la tarjeta de la clase correspondiente, tocá el botón **Ver**.

![Entrar a la clase desde el listado](/ayuda/img/profesor/tomar-asistencia-paso1.png)

---

### Paso 2. Marcar las presencias
1. En la ficha de la clase vas a ver la lista de todos los alumnos inscriptos en el grupo.
2. Marcá la casilla **Presente** de cada alumno que asistió (los alumnos que dejes sin tildar quedan registrados como ausentes).

![Marcar presentes en la lista de alumnos](/ayuda/img/profesor/tomar-asistencia-paso2.png)

---

### Paso 3. Guardar la lista
1. Al final del listado, tocá el botón **Guardar**.
2. Wings registra las asistencias y muestra el aviso de confirmación «Asistencias guardadas.».

![Guardar asistencias y ver confirmación](/ayuda/img/profesor/tomar-asistencia-paso3.png)

---

## Qué hago si…

### ¿Qué hago si un alumno vino pero no está en la lista?
Si un alumno no figura en la lista de la clase, no está formalmente inscripto en el grupo en el sistema. Avisale a la operativa en el mostrador para que verifique su ficha e inscripción. No lo agregues a mano porque no podés crear alumnos desde tu usuario.

### ¿Qué hago si la clase figura cancelada?
Si la clase fue cancelada, el botón **Guardar** estará deshabilitado. Si intentás registrar asistencia, Wings bloquea la acción informando «La clase está cancelada. No se pueden registrar asistencias.». En ese caso no se toma asistencia; si la cancelación fue un error, avisale al dueño para que la reactive.

### ¿Qué hago si me equivoqué al marcar a un alumno?
Podés volver a entrar a la clase y modificar las marcas:
- Si la clase es de hoy, cambiá las casillas que hagan falta y tocá **Guardar**.
- Si la clase ya pasó y ya tenía lista tomada, Wings te va a mostrar el aviso `Estás corrigiendo una lista ya tomada — indicá el motivo`. Escribí el motivo en el campo y tocá **Guardar**. Si lo dejás vacío, Wings no guarda y te avisa «Indicá el motivo de la corrección.».
- Si la clase ya integra una liquidación cerrada, Wings no permite modificar asistencias y te avisa «No se pueden modificar asistencias: la clase integra una liquidación cerrada.». En ese caso, consultá con el dueño.

### ¿Qué hago si quiero tomar lista antes del día de la clase?
Wings permite tomar lista únicamente desde el día en que la clase se dicta. Si intentás tomar asistencia antes, la casilla **Presente** estará deshabilitada y Wings mostrará «La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.».

### ¿Qué hago si un alumno tiene cuota vencida o requiere autorización?
Si un alumno debe cuotas o excede su plan, Wings te avisa en su tarjeta y abre el campo para indicar el motivo de admisión (*El dueño lo verá en Inicio y en el resumen diario.*). Escribí el motivo en el campo para admitirlo y tocá **Guardar**. Si lo dejás en blanco, Wings te avisa «Indicá el motivo para admitir al alumno.».
