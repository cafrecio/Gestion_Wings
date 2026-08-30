# Wings — Contrato de reportes — V1

> Definido con Carlos el 30/08/2026.
> Cierra el hueco más grande del sistema: **hoy registra, pero no informa.**

## El problema que resuelve

El panel del administrador muestra hoy seis contadores —altas del mes, alumnos
activos, con deuda, deuda total, grupos, rubros— y accesos directos. Nada más.

No hay una sola pantalla que le diga al dueño cuánto entró, cuánto salió, cuál fue el
resultado, ni quiénes son los que deben. Para quien cobra y carga alumnos el sistema
está completo; **para quien decide, no le devuelve nada.**

**Los datos ya están todos.** No hay que capturar nada nuevo: están en los movimientos,
las deudas, las liquidaciones y las asistencias. Falta mostrarlos.

---

## 1. Quién ve qué

| Rol | Qué ve |
|---|---|
| **ADMIN** | Los tres reportes completos |
| **OPERATIVO** | **Solo la lista de deudas** —porque su trabajo es cobrar— y **su caja del día** |
| **PROFESOR** | Ningún reporte |

**El operativo no ve valores agregados, ni resultados, ni comparaciones, ni gastos de
estructura.** Tampoco su propio sueldo.

Esto no cambia lo que ya ve hoy en el historial de movimientos, que sigue rigiéndose
por `PERMISOS-ROLES.md`: ve los subrubros marcados `OPERATIVO` —cuotas, torneos,
indumentaria y gastos operativos chicos— y **no** ve sueldos, alquileres, servicios ni
intereses. Verificado el 30/08 contra la base.

La diferencia entre "ver un movimiento" y "ver un reporte" es deliberada: puede ver la
compra de artículos de limpieza que él mismo pagó, pero no cuánto gastó el club en
limpieza en el año.

---

## 2. Reporte de deudas — el que produce plata

**Lo ven admin y operativo.** Es el único que comparten.

Hoy existe un contador que dice "Con deuda: 12" y **no hay forma de saber quiénes son**.

### Cómo se agrupa

**Por antigüedad**, que es lo que define la urgencia del reclamo:

| Grupo | Qué incluye |
|---|---|
| Del mes en curso | La cuota de este mes, impaga |
| Del mes anterior | Un mes de atraso |
| Más antiguas | Dos meses o más |

**Y por deporte**, para poder repartir la cobranza.

### Qué muestra cada fila

Alumno, deporte y grupo, cuánto debe, de qué períodos, y **el teléfono**. Sin el
teléfono el reporte no sirve: la acción que sigue es llamar.

---

## 3. Cómo viene el mes — el resultado

**Solo admin.**

### Ingresos

**Las cuotas se abren por deporte, y dentro de cada deporte por grupo.** Es posible
porque el cobro queda atado al alumno, y del alumno salen el grupo y el deporte.

**El resto de los ingresos se abre por rubro y subrubro**: torneos, indumentaria,
intereses. No tienen alumno, así que no tienen deporte.

### Egresos

| Concepto | Cómo se agrupa |
|---|---|
| **Sueldos de profesores** | **Por deporte.** El subrubro ya se llama `Patín-Jorge Mitre`: trae el deporte adentro |
| Alquiler de canchas | **Gastos generales**, sin repartir. Ver la limitación abajo |
| Servicios, gastos operativos | Gastos generales, por rubro y subrubro |
| Sueldo del operativo | **No existe la categoría todavía.** Ver pendientes |

### Períodos

**Semanal y mensual**, los dos.

**El corte va por la fecha del movimiento**, no por la fecha de validación de la caja.
Es cuando la plata entró de verdad; la validación es un control administrativo
posterior.

### Lo no validado se marca

Si dentro del período hay cajas cerradas sin validar, el reporte **lo muestra
explícitamente**, con el monto involucrado.

**No es un detalle técnico, es un aviso al administrador**: hay plata registrada que
todavía no pasó por su control, y el número que está mirando puede cambiar. Sirve para
que note que le falta hacer su parte.

---

## 4. Cómo le va a cada grupo

**Solo admin.** Se mira una vez por mes.

Por cada grupo: cuántos alumnos tiene, cuánto factura, cuánto cuesta el profesor, y
**cuánto queda**.

Es la única forma de saber si un grupo con pocos alumnos conviene o cuesta plata. Hoy
esa pregunta no se puede responder con el sistema.

---

## 5. Limitaciones conocidas

### El alquiler de canchas no se puede repartir por deporte

Los subrubros de Alquileres son **canchas**, no deportes: `San Carlos`, `Centenera`,
`Eventos`. Cuando se paga San Carlos, el sistema no sabe si esa cancha se usó para
patín o para fútbol.

**Decisión: van a gastos generales.** No se reparten.

**Cómo se resolvería más adelante:** que la clase registre en qué cancha se dio. Con eso
alcanza — no hace falta saber cuánto costó cada clase. Con el total pagado a esa cancha
en el mes (que ya está en el sistema) y la cantidad de clases de cada deporte allí, el
reparto sale proporcional.

Eso necesita un ABM de canchas, que **solo requiere nombre y estado**: no hace falta una
tabla de precios por día y horario. Queda como pendiente futuro, fuera de alcance.

### El sueldo del operativo no tiene dónde registrarse

El rubro Sueldos tiene solo subrubros de profesores, creados automáticamente al dar de
alta cada uno. **Para el operativo hay que crear el subrubro a mano**, marcado como
`ADMIN` para que él mismo no lo vea.

---

## 6. Qué NO entra en esta versión

- Comparación contra el mes anterior o evolución histórica.
- Proyecciones.
- Exportar a Excel o PDF.
- Reportes de asistencia por alumno o por profesor.

Se puede sumar después. Primero hay que resolver que el dueño pueda contestar tres
preguntas: **cuánto entró, quién me debe, y qué grupo me conviene.**

---

## 7. Estado

**Definido, no implementado.** Se construye cuando cierre la prueba funcional
completa (`PRUEBA-HUMANA-V1.md`): no tiene sentido armar reportes sobre un sistema del
que todavía no sabemos si carga bien los datos.

El menú del administrador se reordena **después** de esto, porque estas pantallas lo
cambian igual.
