# Evaluación de usuario — Perfil ADMIN (dueña del negocio)

Soy la dueña. Uso esto para controlar la plata: cuánto entró, quién me debe, cuánto le pago a los profes, y que nadie se equivoque ni me robe. No sé de tecnología; quiero control sin perder tiempo y números en los que pueda confiar.

## Vista: Dashboard (`/admin/dashboard`)
Veo cuatro números: alumnos activos, con deuda, deuda total y altas del mes. Están bien, pero me falta el que más me importa: **cuánto entró hoy / este mes**. Veo lo que me deben pero no lo que cobré. Y los números no son clickeables: dice "con deuda: 5" pero no puedo tocar para ver quiénes son. Los accesos rápidos son Alumnos, Grupos y Rubros — Rubros es configuración, no algo de todos los días; me faltan accesos a lo que de verdad hago: validar cajas, cobranza, liquidaciones.

## Vista: Caja — listado (`/caja`)
Puedo filtrar por operativo y por mes, y ver las cajas con su ingreso/egreso/neto. Bien para controlar a cada operativo. Lo que necesito y no salta solo: qué cajas están **esperando que yo las valide**, que es mi tarea de control principal.

## Vista: Caja — resumen / detalle (`/caja/{id}/resumen`)
Entro a una caja y veo el desglose por medio de pago y por rubro, con ingresos, egresos y neto. Desde acá valido o rechazo. Está claro y me da control. (Los totales ahora cierran; antes desconfiaba de un número que sumaba todo junto.)

## Vista: Cashflow (`/cashflow`)
El historial financiero con ingresos y egresos. Me sirve para ver el movimiento de plata general. Puedo cargar un movimiento directo (un gasto que pago yo, un interés). Bien, aunque si me equivoco al cargar no lo puedo corregir ni borrar.

## Vista: Alumnos (`/alumnos`, ficha)
El listado con el semáforo de cobranza (verde/amarillo/rojo) me dice de un vistazo quién está al día y quién no. La ficha muestra pagos y asistencias. Muy útil para el control.

## Vista: Cobranza / Revisión (`/revision-cobranza`)
Veo los alumnos "posibles inactivos" para decidir si siguen o no. Está la pantalla y funciona. Me gustaría un aviso en el menú de cuántos hay pendientes para no tener que entrar a mirar.

## Vista: Liquidaciones (`/liquidaciones`)
Genero y pago lo de los profes. Funciona, pero me da miedo que al pagar falle sin avisar si algo del profe no está bien configurado (me pasó que un pago no salía y no entendía por qué). Quiero que me avise ANTES si algo falta, no que reviente al confirmar.

## Vista: Configuración / Usuarios / Deportes / Rubros / Niveles / Tipos de caja
Todo lo de configurar el sistema está y se maneja. Son cosas que toco poco, pero cuando las necesito están.

---

### UA1.0 — El dashboard no me muestra cuánto entró (solo lo que me deben)
**Vista:** Dashboard
**Severidad:** Alta
**Qué me pasa:** Como dueña, lo primero que quiero saber al entrar es cuánto se cobró hoy y en el mes. El dashboard me muestra la deuda pero no los ingresos. Tengo que ir a buscar la plata que entró caja por caja. Es el número más importante de mi negocio y no está.
**Soluciones:**
- SUA1.1 ⭐ Agregar al dashboard "Cobrado hoy" y "Cobrado en el mes" (y comparación con el mes anterior si se puede), bien grande arriba.
- SUA1.2 Un mini-gráfico de ingresos de los últimos meses para ver la tendencia.

### UA2.0 — No veo de una qué cajas tengo que validar
**Vista:** Dashboard / Caja
**Severidad:** Alta
**Qué me pasa:** Validar las cajas de los operativos es mi tarea de control principal, pero no hay ningún aviso de cuántas están esperándome. Tengo que entrar a Caja y buscar las cerradas. Si me olvido, se me acumulan sin que nadie me avise.
**Soluciones:**
- SUA2.1 ⭐ Aviso en el dashboard y un número en el menú: "Tenés N cajas para validar", que me lleve directo a la lista.
- SUA2.2 Un filtro rápido "pendientes de validar" destacado en la pantalla de Caja.

### UA3.0 — Los números del dashboard no son clickeables
**Vista:** Dashboard
**Severidad:** Media
**Qué me pasa:** Dice "con deuda: 5" pero no puedo tocar para ver quiénes son los cinco. Tengo que ir a Alumnos y filtrar a mano. El número me genera la pregunta y no me da la respuesta.
**Soluciones:**
- SUA3.1 ⭐ Que cada número lleve a la lista que representa (con deuda → alumnos deudores; altas del mes → esos alumnos).

### UA4.0 — Al pagar una liquidación puede fallar sin avisar antes
**Vista:** Liquidaciones
**Severidad:** Media
**Qué me pasa:** Cuando pago a un profe, si algo de su configuración no está bien, el pago falla al confirmar con un error que no entiendo, en vez de avisarme antes. Me deja sin saber si pagué o no.
**Soluciones:**
- SUA4.1 ⭐ Verificar antes de mostrar el botón de pagar que esté todo listo, y si falta algo avisarme con un mensaje claro y un link para arreglarlo.

### UA5.0 — No puedo corregir ni borrar un movimiento de cashflow mal cargado
**Vista:** Cashflow
**Severidad:** Media
**Qué me pasa:** Si cargo un gasto con un monto equivocado, no lo puedo editar ni borrar. Queda mal para siempre y me ensucia los números.
**Soluciones:**
- SUA5.1 ⭐ Permitir al menos eliminar un movimiento directo cargado por error (idealmente editar), con registro de quién y cuándo.

### UA6.0 — Accesos rápidos del dashboard no son los que uso
**Vista:** Dashboard
**Severidad:** Baja
**Qué me pasa:** Los accesos directos son Alumnos, Grupos y Rubros. Rubros casi no lo toco. Me faltan atajos a lo que hago seguido: validar cajas, cobranza, liquidaciones.
**Soluciones:**
- SUA6.1 ⭐ Reemplazar los accesos por los de uso diario del admin: Cajas a validar, Cobranza, Liquidaciones.

### UA7.0 — El menú no me avisa de la cobranza pendiente
**Vista:** Cobranza / menú
**Severidad:** Baja
**Qué me pasa:** Para saber si hay alumnos posibles inactivos para revisar tengo que entrar a la pantalla. Me gustaría un numerito en el menú que me lo diga sin entrar.
**Soluciones:**
- SUA7.1 ⭐ Badge con el número de revisiones pendientes en el ítem "Cobranza" del menú.

## Tabla resumen

| ID | Severidad | Vista | Problema |
|----|-----------|-------|----------|
| UA1 | Alta | Dashboard | No muestra cuánto entró (solo la deuda) |
| UA2 | Alta | Dashboard / Caja | No veo qué cajas tengo que validar |
| UA3 | Media | Dashboard | Los números no son clickeables |
| UA4 | Media | Liquidaciones | El pago puede fallar sin avisar antes |
| UA5 | Media | Cashflow | No puedo corregir/borrar un movimiento mal cargado |
| UA6 | Baja | Dashboard | Accesos rápidos no son los de uso diario |
| UA7 | Baja | Menú | Sin aviso de cobranza pendiente |
