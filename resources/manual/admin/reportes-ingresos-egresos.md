---
titulo: Leer el reporte de ingresos y egresos
rol: admin
categoria: Reportes
orden: 37
resumen: Qué muestra el reporte Ingresos y egresos, cómo elegir el mes y el deporte, y qué significa cada parte.
pantalla: /reportes
---

# Leer el reporte de ingresos y egresos

**Reportes** muestra cómo le fue al club en un mes: cuánto entró, cuánto salió, de dónde vino y adónde fue, y lo que queda por cobrar y por pagar. Casi todo es de lectura; la única acción es **Perder**, en la deuda de alumnos inactivos (ver abajo). Tiene tres partes, que se eligen arriba: **Ingresos y egresos**, **Alumnos** y **Sueldos**.

---

## Elegir el mes y el deporte

1. En el menú lateral, sección *Plata*, entrá a **Reportes**. Se abre **Ingresos y egresos** del mes en curso.
2. Arriba, elegí el mes (no hay meses futuros) y el deporte, o dejá *Todo el negocio*.
3. Tocá **Filtrar**.

![Reporte de ingresos y egresos](/ayuda/img/admin/reportes-ingresos-egresos-paso1.png)

---

## Qué muestra

- *El último mes cerrado contra el anterior*: **Ingresos**, **Egresos**, **Resultado** y **Egresos / ingresos**, con la variación respecto del mes previo.
- **Evolución mensual** y **Cambio en el resultado**: cómo vienen los últimos meses y qué conceptos explican la diferencia.
- **El mes elegido**: ingresos, egresos y resultado hasta hoy (si es el mes en curso) o del mes completo. **Origen de ingresos** y **Destino de gastos** los muestran por rubro; en **Detalle** se separa lo *Confirmado* de lo *Por validar*.
- **Saldos acumulados**: lo **Disponible** en las cuentas, lo **Por cobrar** a los alumnos y lo **Por pagar** a los profesores, cada uno con su detalle.

Los cobros de una caja que todavía no validaste cuentan como *Por validar*. Los aportes y retiros de los dueños y los traspasos entre cuentas se muestran aparte y no cambian el resultado.

---

## Deuda de alumnos inactivos

Al final del reporte (y también en **Cobranza**) está *Deuda de alumnos inactivos*: lo que deben los alumnos que pasaron a **Inactivo**, separado del por cobrar. Cada alumno tiene **Ver** y, solo para el dueño, **Perder**. **Perder** da esa deuda por perdida: pide confirmar con un aviso que termina en «No vuelve a cobrarse al reactivar.» y no se puede deshacer. Usalo solo cuando sabés que esa plata no va a volver.

---

## Qué hago si…

### ¿Qué hago si aparece «El resultado es parcial: falta clasificar movimientos del negocio.»?
Hay movimientos con subrubros a los que nunca se les dijo si son plata del club o de los dueños. En **Rubros**, editá esos subrubros y completá **Qué es**. Mientras tanto, los importes son parciales.

### ¿Qué hago si un número no coincide con lo que veo en Cashflow?
El reporte suma cobros y pagos confirmados y por validar. **Cashflow** muestra los movimientos de cada cuenta. Si querés ver solo lo confirmado, mirá el **Detalle** de cada bloque.
