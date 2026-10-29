---
titulo: Tomar lista de asistencia desde el mostrador
rol: operativo
categoria: Clases y asistencia
orden: 180
resumen: Cómo registrar presentes y ausentes en una clase desde la computadora de recepción si el docente no tiene dispositivo.
pantalla: /clases/{claseId}
du: CLA-002
---

# Tomar lista de asistencia desde el mostrador

Esta tarea te explica cómo tomar lista de los alumnos presentes en una clase desde el mostrador de recepción en caso de que la profesora no cuente con su teléfono celular o dispositivo a mano.

> [!NOTE]
> *Requisito:* La clase debe corresponder al día de la fecha (o a una fecha ya transcurrida). Wings no permite tomar asistencia en clases futuras y te advertirá con el mensaje: «Esta clase todavía no ocurrió. La asistencia se toma desde el día de la clase.».

---

## Pasos para tomar lista en el mostrador

### Paso 1. Ingresar a la clase
1. En el menú lateral, hacé clic en **Clases**.
2. En la sección **Clases de hoy**, localizá la clase del deporte y grupo correspondiente y hacé clic en el botón **Ver**.
3. Se abre la ficha completa de la clase con la nómina de alumnos inscriptos en el grupo.

![Ficha de clase en mostrador](/ayuda/img/operativo/tomar-lista-mostrador-paso1.png)

---

### Paso 2. Marcar los presentes y guardar
1. En la lista vas a ver a cada deportista con su apellido, nombre, condición de cuota (Al día, En plazo, Moroso o Deudor) y la relación de asistencias semanales respecto a su plan (por ejemplo: "1/2").
2. Al lado de cada alumno que asistió, marcá la casilla de verificación **Presente** (los que dejes sin tildar quedan registrados como ausentes).
3. Si el alumno ya asistió esa semana la cantidad de veces prevista en su plan (por ejemplo, si tiene 2/2 y marcás el presente), se despliega el aviso «Excede el plan — indicar motivo» con un selector que ofrece dos opciones: «Clase adicional (EXTRA)» y «Recupera clase perdida (RECUPERA)». Si no elegís ninguna opción, el sistema asigna por defecto EXTRA y no traba el guardado de la lista.
4. Si el alumno está Deudor o si es un alumno nuevo sin ningún pago de cuota y ya tiene 2 presentes registrados, Wings despliega el campo obligatorio **Motivo** con el mensaje correspondiente: «Tiene deuda vencida. Indicá el motivo para dejarlo entrar.» o «Todavía no registró un pago de cuota. Indicá el motivo para dejarlo entrar.». Completá la justificación para admitirlo en la clase (este motivo de deuda es independiente del motivo de exceso del plan).
5. Al pie del listado, tocá el botón **Guardar**. Wings registra las asistencias en la base de datos y muestra el aviso de confirmación «Asistencias guardadas.».

![Marcar casilla Presente y guardar](/ayuda/img/operativo/tomar-lista-mostrador-paso2.png)

---

## Qué hago si…

### ¿Qué hago si me equivoqué al marcar a un alumno?
Volvé a entrar a la clase, corregí las casillas de **Presente** y tocá **Guardar**.
- Si la clase es de hoy, los cambios se actualizan de inmediato.
- Si la clase ya pasó y ya tenía lista tomada, Wings te va a solicitar el motivo obligatorio de la corrección antes de guardar.

### ¿Qué pasa si una alumna que vino no figura en la lista del grupo?
Verificá en **Alumnos** si la alumna está registrada en ese grupo. Si pertenece a otro grupo o disciplina y vino a recuperar una clase, avisale al profesor o cargá la asistencia en su clase de origen según las pautas deportivas del club.

### ¿Qué hago si la clase ya integra una liquidación cerrada?
Si la clase ya fue liquidada y el dueño cerró el período de sueldos docente, Wings no permite modificar asistencias y muestra: «No se pueden modificar asistencias: la clase integra una liquidación cerrada.». En ese caso consultá con el dueño para resolver la liquidación correspondiente.

### ¿Qué hago si la clase figura cancelada?
Si la clase fue suspendida o cancelada por el club o el docente, Wings muestra la alerta «Esta clase está cancelada. No se pueden registrar asistencias.» y el botón **Guardar** permanece deshabilitado. No se pueden cargar asistencias sobre una actividad cancelada.
