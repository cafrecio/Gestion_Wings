---
titulo: Pasar a un alumno a Inactivo
rol: operativo
categoria: Alumnos y familias
orden: 70
resumen: Cómo cambiar la condición de un alumno entre Activo e Inactivo desde su ficha conservando su historial de deudas y pagos.
pantalla: /alumnos/{alumnoId}
du: ALU-005
---

# Pasar a un alumno a Inactivo

Esta tarea te explica cómo cambiar la condición a **Inactivo** de un deportista que deja la actividad (o pausó sus entrenamientos), y cómo reactivarlo si regresa.

> [!NOTE]
> *Inactivo vs Eliminación:* Pasar a un alumno a **Inactivo** es la acción normal cuando alguien deja de entrenar: conserva todo su historial de pagos, recibos y asistencias pasadas. La eliminación definitiva de un alumno cargado por error es una acción exclusiva del dueño, disponible solo si el alumno nunca tuvo cobros ni movimientos de dinero.

---

## Pasos para pasar a Inactivo

### Paso 1. Ingresar a la ficha del alumno
1. En el menú lateral, hacé clic en **Alumnos**.
2. Buscá al alumno y tocá su nombre o el botón **Ver** para abrir su legajo personal.
3. En la barra superior derecha vas a ver el interruptor con las opciones **Activo** e **Inactivo**.

![Ficha del alumno e interruptor](/ayuda/img/operativo/pasar-alumno-inactivo-paso1.png)

---

### Paso 2. Cambiar la condición a Inactivo
1. Hacé clic sobre el interruptor para cambiarlo de **Activo** a **Inactivo**.
2. Wings actualiza inmediatamente el estado del alumno y emite el aviso de confirmación en la parte superior.
3. A partir de este momento, el generador automático del día 1° ya no le creará cuotas mensuales y el alumno dejará de figurar en las listas de asistencia de las clases.

![Cambio a estado Inactivo](/ayuda/img/operativo/pasar-alumno-inactivo-paso2.png)

---

### Paso 3. Cómo reactivar al alumno si vuelve al club
1. Si el alumno regresa a entrenar en meses posteriores, localizá su ficha en **Alumnos**.
2. Hacé clic nuevamente en el interruptor para pasarlo a **Activo**.
3. Wings reactiva su ficha y genera automáticamente la cuota del mes en curso con el porcentaje de reingreso que corresponda según el día del mes, emitiendo el aviso de confirmación.

---

## Qué hago si…

### ¿Qué pasa con las cuotas que el alumno debía al momento de pasar a Inactivo?
Las deudas anteriores no se borran: quedan registradas en su cuenta corriente y en el panel de Cobranza. Si el alumno viene más adelante a saldar lo que quedó debiendo, podés cobrarle normalmente a través del botón **Cobrar**.

### ¿Qué hago si cobro a un alumno inactivo?
Al cobrarle a un alumno inactivo, Wings analiza qué períodos se están cobrando:
- Si el cobro incluye únicamente deuda vieja (meses anteriores): Wings pregunta «¿Pasa a activo?» con las opciones **Sí**, **No** y **Cancelar**. Si solo vino a pagar lo atrasado pero no va a retomar las clases, elegí **No**: Wings cobra la deuda, emite el recibo y el alumno permanece inactivo sin generarle la cuota del mes actual. Si retoma la actividad, elegí **Sí**.
- Si el cobro incluye el mes en curso o uno futuro: Pagar el mes actual significa retomar la actividad. En este caso no aparece la opción «No»: solo aparecen **Sí** y **Cancelar** con el mensaje: «El cobro incluye el mes en curso, así que para cobrarlo tiene que volver.». Si se intenta forzar un cobro sin reactivación, Wings lo rechaza indicando: «El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.».

### ¿Qué hago si cargué un alumno por error y quiero borrarlo completamente?
Si el alumno fue cargado por equivocación y nunca tuvo ningún cobro ni movimiento de dinero, avisale al dueño: la función de eliminación permanente de alumnos es una acción del dueño que borra el legajo de la base de datos.
