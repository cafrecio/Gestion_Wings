---
titulo: Consultar el libro Cashflow y saldos por medio
rol: admin
categoria: Cashflow y finanzas
orden: 27
resumen: Cómo interpretar el disponible por cuenta o tipo de caja y auditar el resultado financiero del período.
pantalla: /cashflow
---

# Consultar el libro Cashflow y saldos por medio

Esta tarea te explica cómo consultar el libro de caja central de Wings (**Cashflow**), verificar cuánto dinero disponible hay en cada cuenta y analizar los ingresos, egresos y resultado neto del club.

> [!IMPORTANT]
> *Visión financiera integral:* A diferencia de la caja de turno de la operativa (que solo maneja el efectivo del mostrador de ese día), **Cashflow** consolida todas las cuentas del club: dinero en efectivo de cajas ya validadas, cuentas bancarias, billeteras virtuales y movimientos directos de administración.

---

## Pasos para consultar saldos y movimientos

### Paso 1. Revisar los saldos disponibles por cuenta
1. En el menú del administrador, ingresá a **Cashflow**.
2. En la parte superior de la pantalla, observá las tarjetas de saldos disponibles:
   - **Efectivo:** Dinero físico acumulado de turnos validados y aportes directos.
   - *Cuentas bancarias:* Saldo bancario disponible para transferencias o pagos de sueldos.
   - *Billeteras virtuales y otros medios:* Fondos ingresados por transferencias o cobros digitales.
3. El indicador general te muestra el total disponible consolidado de todo el club.

![Tarjetas de saldo por cuenta en Cashflow](/ayuda/img/admin/consultar-cashflow-paso1.png)

---

### Paso 2. Filtrar por período y analizar el resultado
1. Usá el selector de período en la barra superior para cambiar la vista:
   - **Día:** Movimientos registrados en una fecha puntual.
   - **Semana:** Ingresos y egresos de la semana en curso (lunes a domingo).
   - **Mes:** Vista mensual con totales consolidados (por ejemplo, Octubre 2026).
   - **Año:** Acumulado anual completo.
2. En la barra de totales, revisá:
   - **Ingresos** del período: Cobranzas de cuotas, inscripciones y otros ingresos del club.
   - **Egresos** del período: Pagos de liquidaciones, gastos operativos y compras.
   - **Resultado:** Diferencia neta (superávit en verde o déficit en rojo).
3. Usá los filtros por tipo de caja y tipo (ingresos o egresos) para revisar los movimientos. El de tipo de caja también cambia los totales del listado. Las transferencias entre cuentas propias no suman en estos totales, porque para el club no entra ni sale plata, pero sus filas se ven en el listado. Si filtrás una cuenta, sí suman: para esa cuenta entró o salió plata. Los aportes, los retiros y los ajustes de saldo también suman en estos totales: para ver el resultado del negocio sin ellos, mirá **Reportes**.

![Filtrar por año o mes y consultar el resultado del período](/ayuda/img/admin/consultar-cashflow-paso2.png)

---

## Qué hago si…

### ¿Por qué la plata que cobró la operativa hoy no aparece en mi saldo de Efectivo?
Porque la caja del turno todavía está en poder de la operativa (estado *Abierta* o *Cerrada*). Hasta que no revises el arqueo y toques **Validar**, ese dinero no ingresa a Cashflow.

### ¿Qué significan los movimientos con clasificación «Traspaso entre medios»?
Son transferencias internas entre cuentas propias del club (por ejemplo, cuando depositás efectivo en el banco). Tienen una salida en una cuenta y una entrada en la otra, por lo que no alteran el resultado del período.

### ¿Cómo reconozco una transferencia anulada?
Las dos filas originales muestran el número de transferencia y **Anulada**. Los dos asientos en contra dicen **Anulación de transferencia**, con el mismo número. Se ve el motivo y queda solo **Ver**. La fecha sigue siendo la de la transferencia original.

Para anular una transferencia vigente, el dueño con cuenta activa usa **Anular** en cualquiera de sus dos filas y completa el motivo obligatorio. El recorrido está en *Transferir entre medios propios*. Los inversos compensan los originales también al filtrar por su mes o su cuenta. Anular deja los totales y los saldos como si la transferencia no hubiera existido.
