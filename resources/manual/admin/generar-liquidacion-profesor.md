---
titulo: Generar la liquidación mensual de profesores
rol: admin
categoria: Liquidaciones
orden: 32
resumen: Cómo calcular automáticamente los honorarios por horas o comisiones de un docente para un período cerrado.
pantalla: /liquidaciones/crear
---

# Generar la liquidación mensual de profesores

Esta tarea te explica cómo generar la liquidación mensual de un profesor en Wings para calcular de forma automática sus honorarios por clases dictadas (modalidad por hora) o sus comisiones por alumnos con cuota al día (modalidad por comisión).

> [!IMPORTANT]
> *Cálculo automático y tarifas congeladas:* Al generar la liquidación, Wings analiza todas las clases asistidas del mes (o las cuotas cobradas de sus deportistas) y congela el valor por hora o porcentaje de comisión vigente en ese momento. Si más adelante cambia la tarifa del profesor, las liquidaciones históricas conservan el valor con el que fueron calculadas.

---

## Pasos para generar la liquidación

### Paso 1. Entrar al módulo de liquidaciones
1. En el menú del administrador, ingresá a **Liquidaciones**.
2. Verás el listado de liquidaciones anteriores con sus estados (*Abierta*, *Cerrada*, *Pagada*).
3. En la barra superior, tocá el botón **Nuevo**. Se abrirá el formulario de generación de liquidación.

![Listado de liquidaciones y botón Nuevo](/ayuda/img/admin/generar-liquidacion-profesor-paso1.png)

---

### Paso 2. Seleccionar docente y período
1. **Profesor:** Elegí al docente en la lista desplegable. Wings te indicará su modalidad deportiva (Por hora o Por comisión).
2. **Mes** y **Año:** Seleccioná el período a liquidar (por ejemplo, "Octubre" de "2026").
3. Clases del día (opcional): Si estás generando la liquidación en el último día del mes y hubo clases hoy, podés tildar la opción para incluirlas.
4. Tocá el botón **Generar**.
5. Wings creará la liquidación en estado **Abierta** y te llevará directamente a la pantalla de desglose para auditar cada renglón.

![Formulario de generación de liquidación](/ayuda/img/admin/generar-liquidacion-profesor-paso2.png)

---

## Qué hago si…

### ¿Qué hago si Wings dice que ya existe una liquidación para ese mes?
Si aparece el mensaje «Ya existe una liquidación para el profesor en» indicando el mes y año seleccionado, Wings no permite crear dos liquidaciones activas para el mismo profesor en el mismo período. Volvé al listado de **Liquidaciones**, abrí la liquidación existente de ese mes y, si necesitás actualizarla por nuevas asistencias cargadas, tocá **Recalcular**.

### ¿Cómo se calculan las comisiones de profesores?
Para profesores cuya disciplina liquida por comisión, Wings computa el porcentaje pactado sobre las cuotas de los alumnos de su grupo cobradas en ese período. El importe definitivo se audita en el desglose de la liquidación generada.
