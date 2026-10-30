---
titulo: Leer el reporte de sueldos
rol: admin
categoria: Reportes
orden: 39
resumen: Qué muestra el reporte Sueldos: el costo de los profesores del mes comparado con las cuotas que se les atribuyen.
pantalla: /reportes/sueldos
---

# Leer el reporte de sueldos

El reporte **Sueldos** compara lo que cuestan los profesores en un mes con las cuotas de los alumnos que asistieron a sus clases. Es una comparación económica, de solo lectura: no muestra la ganancia del club. Wings no exporta este reporte a Excel ni a otro archivo.

---

## Abrirlo

1. Entrá a **Reportes** y tocá **Sueldos** arriba.
2. Elegí el mes y el deporte y tocá **Filtrar**.

![Reporte de sueldos](/ayuda/img/admin/reportes-sueldos-paso1.png)

---

## Qué muestra

- **Costo de profesores**: lo que corresponde pagarles por ese mes, aunque se pague después.
- **Cuotas atribuidas**: el importe real de las cuotas del mismo mes, repartido entre los profesores según las asistencias.
- **Sueldos / cuotas** y **Costo por asistencia**.
- **Pagos de operativas**: los sueldos de la operativa registrados en Cashflow durante el mes, con su **Total pagado**.
- **Evolución: cuotas y sueldos**, **Por deporte** y **Por profesor**.

Al pie, **Cómo se calcula** explica las reglas: la comisión usa cuotas cobradas, un pago parcial cuenta como cobro y el monto final ajustado en la liquidación se respeta.

---

## Qué hago si…

### ¿Qué hago si un mes dice Sin historial?
Ese mes no tiene respaldo para calcular, y Wings no lo dibuja como cero.

### ¿Qué hago si aparecen Cuotas sin asistencias para atribuir?
Son cuotas del mes de alumnos que no tienen asistencias: no se pueden repartir entre profesores. Revisá que la lista de las clases esté tomada.
