# Prueba por días: días 19 a 22 (miércoles 28 al sábado 31 de octubre de 2026)

Armado por Claude el 27/10/2026, **contra la base del sitio de prueba**: usuarios, clases, deudas, precios
y rubros salen de una lectura de `wingstest`, y cada rutina se simuló entera, día por día y con su fecha,
sobre esa base (`--simular --fecha=…`: se revierte y no toca el reloj). Los importes de cada cobro son los
que dio Wings en esa simulación.

**Punto de partida** (lectura del 27/10 y simulación del 28/10 a las 15:00):
- Deuda **$248.000**; 104 activos: 99 Al día, **1 En plazo**, 2 Morosos (Joaquín Vega $44.000, Santino
  Romero $28.000) y 2 Deudores (Berenice Sánchez $86.000, Felipe Sosa $76.000).
- El «En plazo» es **Agustín Acosta, $14.000**: una cuota de reingreso de octubre (40%) que Wings le
  generó el 27/10 a las 15:53, durante el día 18, cuando alguien lo dio de baja y lo reactivó. No figura en
  ningún informe: fue un toque del interruptor del listado. Se resuelve en el 19.2.
- Ingresos de octubre $1.981.850,75; egresos $251.150.
- Disponible $3.483.700,75: Efectivo $1.271.600, MP $1.946.550,75, Galicia $137.550, Nación $128.000.
- «Sin asistencia» 0. Julieta sigue inactiva: espera T84 (interruptor en la ficha) y no entra en estos días.

**Autorización de Carlos (25/10/2026):** todas las operaciones de plata de este guion en
test.gestionar-te.com.ar (base `wingstest`, datos ficticios), incluidas condonaciones, bajas,
transferencias, sueldos, clases nuevas y canceladas, liquidaciones y las rutinas. No hace falta volver a preguntar.

## Cómo se trabaja

Igual que en los días 17 y 18 ([guion](GUION-DIAS-17-18.md#cómo-se-trabaja)): reloj y rutina por consola
en el servidor, una sola vez cada uno; lo de las tablas por pantalla, como una persona; importes sin punto
de miles; mirar el total antes de confirmar; las diferencias se anotan y no se reparan.

- **Antes de cada día:** fijar el reloj y correr la rutina de la mañana con `--simular`. Si falla, no
  operar ese día: anotar y avisar.
- **Los cobros de la rutina son de octubre de alumnos que todavía no tienen la cuota guardada** (los 63 de
  la decisión pendiente D4-OBS-01). Por eso cada uno lleva su importe y va como mes elegido a mano, igual
  que en la pantalla.
- **Cuidado con los interruptores del listado de Alumnos:** cada toque cambia el estado al instante y, al
  reactivar, genera una cuota. Solo se tocan en el 21.4.
- **«Esperado según el catálogo»** marca lo que no pude confirmar en el código. Si Wings no lo hace, es un
  hallazgo de Wings, no un error de ejecución: se anota tal cual.

## Día 19: miércoles 28/10, la caja de Sandra con errores de todos los días

Sandra abre **por pantalla** (19.1) y **después** se corre la rutina de la mañana (`dia-19-manana.json`):
- Diez cobros de octubre. En efectivo: Victoria Acosta $30.000, Alma Herrera $40.000, Paulina Benítez $43.000,
  Constanza Cabrera $33.000, Bruno Castro $28.000 y Benicio Torres $28.000. Por MP: Renata Flores $30.000,
  Emilia Medina $40.000, Thiago Díaz $38.000 y León Rossi $38.000.
- Limpieza $3.800 (se corrige en el 19.4); Librería $2.500 (se elimina en el 19.5); venta de una remera,
  $18.000 en efectivo.
- Lista #10 de Lucía, Patín Principiantes 16–17: 28 alumnas, 26 presentes (faltan Pilar Ramos y Abril Silva).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 19.1 | Sandra | Abre la caja y escribe **100000** por error; el turno anterior dejó $10.000. Después corrige a **10000**. | Con 100000: «El efectivo recibido difiere en más de un 50% del cierre anterior ($10.000)…», y pide confirmar aparte. No lo confirma. Con 10000 abre sin aviso (CAJ-007, CAJ-005). |
| 19.2 | Dueño | En la ficha de **Agustín Acosta**, condona la cuota de reingreso de octubre de **$14.000**: primero sin motivo, después con «Reingreso generado por error: nunca dejó el club». | Sin motivo no condona. Con motivo queda condonada, sin mover plata. Agustín sale de Cobranza y pasa a Al día (CBZ-007). |
| 19.3 | Sandra | La mamá de **Joaquín Vega** (debe octubre, $44.000) trae **$50.000** y Sandra los carga. Ve el aviso y **cancela**. Después cobra **$44.000** en efectivo. | Esperado según el catálogo: con $50.000 avisa que no se pueden cargar otros conceptos como si fueran cuota, con Continuar y Cancelar. Cancelar no cobra nada. Con $44.000, Joaquín pasa a **Al día** (COB-010). |
| 19.4 | Sandra | La limpieza costó **$3.500**, no $3.800: corrige el movimiento. | Queda en $3.500 y el efectivo esperado se recalcula (MOV-007). |
| 19.5 | Sandra | El cuaderno de $2.500 lo pagó el dueño de su bolsillo: **elimina** el gasto de Librería. | Desaparece de la caja, el esperado se recalcula y queda registro de qué se eliminó, quién y cuándo (MOV-008). |
| 19.6 | Hernán | Llueve y no viene nadie a Fútbol Principiantes 18–19: toma la lista con **los 23 ausentes**. | Se guarda y la clase sale de «Sin asistencia» aunque no haya presentes (ASI-003). |
| 19.7 | Sandra | Cierra contando **$271.000**, $500 más que lo esperado. Deja $10.000. | Esperado **$270.500** (10.000 + 202.000 en efectivo de la rutina + 44.000 de Joaquín + 18.000 de la remera − 3.500). Muestra **sobrante $500** y deja cerrar. No crea ningún ingreso por la diferencia (CAJ-020). |

**Noche** (`dia-19-noche.json`): el dueño valida la caja de Sandra.

**Cierre del día 19:**
- Deuda **$190.000**; 104 activos: **101 Al día**, 0 En plazo, 1 Moroso (Santino) y 2 Deudores.
- Ingresos de octubre **$2.391.850,75**; egresos **$254.650**; resultado **$2.137.200,75**.
- Disponible **$3.890.200,75**: Efectivo $1.532.100, MP $2.092.550,75, Galicia $137.550, Nación $128.000.
  El sobrante de $500 no suma.
- «Sin asistencia» 0.

## Día 20: jueves 29/10, clases que cambian

**Rutina de la mañana** (`dia-20-manana.json`):
- Rocío abre la caja; hereda $10.000.
- Nueve cobros de octubre. En efectivo: Clara Iglesias $30.000, Josefina Ferrari $33.000, Camila Álvarez
  $35.000, Santino Flores $28.000 y Facundo Herrera $28.000. Por MP: Milagros Molina $30.000, Juana Flores
  $33.000, Milagros Benítez $45.000 y Benjamín Fernández $48.000.
- Limpieza $3.800.
- Listas: #33 de Lucía, Patín Avanzadas 17–18 (12, falta Delfina Giménez: 11 presentes) y #44 de Mariela,
  Patín Federadas 18–19 (8, falta Helena Iglesias: 7 presentes).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 20.1 | Rocío | Vende un par de **patines, $85.000 por MP** (Indumentaria → Patines) y una **remera, $18.000 en efectivo**. | Los dos aparecen como ingresos de la caja; solo la remera sube el efectivo esperado (MOV-002). |
| 20.2 | Dueño | Se inunda la cancha: **cancela la clase de hoy de Fútbol Avanzadas 19–20** (#71). Primero sin motivo, después con «Cancha inundada». | Sin motivo no cancela. Con motivo sigue en el listado de hoy, apagada, con «Cancelada» y el motivo (CLA-011). |
| 20.3 | Hernán | Entra a la #71 para tomar lista. | La ve cancelada: aviso de bloqueo y Guardar deshabilitado; no se guarda ninguna marca (ASI-005). |
| 20.4 | Dueño | Crea una **clase única de recuperación**: Fútbol Avanzadas, **sábado 31/10**, con Hernán. Primero pone de **14:00 a 13:00**; después corrige a **14:00 a 15:00**. | Con la hora de fin antes del inicio, rechaza con un aviso claro y conserva lo cargado (CLA-005). Con 14 a 15 se crea; solo ofrece profesores de Fútbol, y Hernán la ve en sus clases del 31 (CLA-002). |
| 20.5 | Mariela | Quiere adelantar la lista de la clase del **sábado 31, Federadas 10–11** (#50). | No deja guardar una clase cuyo día no llegó, y lo explica (ASI-011). |
| 20.6 | Rocío | La familia de **Lucas Gómez** cambió de celular: lo corrige de 1145123401 a **1145123499**. | La ficha y el listado muestran el número nuevo; sus pagos y cuotas no cambian (ALU-012). |

**Noche** (`dia-20-noche.json`): Rocío cierra con el esperado, **$178.200** (10.000 + 154.000 + 18.000 − 3.800),
deja $10.000 y el dueño valida.

**Cierre del día 20:**
- Deuda **$190.000**; 104 activos: 101 Al día, 1 Moroso y 2 Deudores.
- Ingresos **$2.804.850,75**; egresos **$258.450**; resultado **$2.546.400,75**.
- Disponible **$4.299.400,75**: Efectivo $1.700.300, MP $2.333.550,75, Galicia $137.550, Nación $128.000.
- «Sin asistencia» 0: la #71 está cancelada.

## Día 21: viernes 30/10, los que deben

**Rutina de la mañana** (`dia-21-manana.json`):
- Pablo abre la caja; hereda $10.000.
- Nueve cobros de octubre. En efectivo: Agustina Morales $30.000, Melina García $43.000, Catalina Castro
  $45.000, Tomás Gutiérrez $28.000 y Lola Ríos $30.000. Por MP: Micaela Ortiz $40.000, Delfina Martínez
  $33.000, Catalina Díaz $50.000 y Nicolás Ferrari $48.000.
- Reparaciones menores, $12.000 en efectivo.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 21.1 | Verónica | Toma la lista de **Patín Intermedias 16–17** (#22): 18 alumnas; todas presentes menos **Berenice Sánchez**. | Esperado según el catálogo: la lista indica que Berenice, que es Deudor, no puede entrar por deuda, sin mostrar importes (ASI-013). Guarda con 17 presentes. |
| 21.2 | Pablo | **Berenice Sánchez** (debe septiembre y octubre, $86.000) paga **$20.000** de septiembre en efectivo. Primero sin motivo; si lo pide, «Paga el resto el sábado». | Esperado según el catálogo: sin motivo no cobra (COB-009). Con motivo, septiembre queda con saldo $23.000, Berenice sigue Deudor y el total adeudado baja $20.000 (COB-008). |
| 21.3 | Pablo | **Felipe Sosa** paga agosto y octubre, **$76.000** en efectivo, y Pablo descarga el recibo. | Pasa a **Al día**. El recibo detalla agosto $38.000 y octubre $38.000, total $76.000 (REC-002). |
| 21.4 | Pablo | **Santino Romero** deja el club debiendo octubre ($28.000). Pablo lo da de baja **con el interruptor del listado de Alumnos**. | Queda Inactivo y su cuota pendiente sigue a la vista en la ficha (ALU-025). Sale de Cobranza: 103 activos y el total adeudado baja $28.000. Ya no aparece en el buscador de Cobrar (ALU-017). |
| 21.5 | Dueño | Lleva **$500.000** del efectivo al **Banco Galicia** (Cashflow → Transferir). | Un solo formulario: salida Efectivo, destino Galicia. Efectivo baja y Galicia sube $500.000; los ingresos y gastos del mes no cambian (CSH-018). |

**Noche** (`dia-21-noche.json`): Pablo cierra con el esperado, **$270.000** (10.000 + 176.000 + 20.000 +
76.000 − 12.000), deja $10.000 y el dueño valida.

**Cierre del día 21:**
- Deuda **$66.000** (Berenice: septiembre $23.000 y octubre $43.000); **103 activos: 102 Al día**, 0
  Morosos, 1 Deudor.
- Ingresos **$3.247.850,75**; egresos **$270.450**; resultado **$2.977.400,75**.
- Disponible **$4.730.400,75**: Efectivo $1.460.300, MP $2.504.550,75, Galicia $637.550, Nación $128.000.
- «Sin asistencia» 0.

## Día 22: sábado 31/10, fin de mes

**Rutina de la mañana** (`dia-22-manana.json`):
- Pablo abre la caja; hereda $10.000.
- Cinco cobros de octubre. En efectivo: Malena Navarro $43.000, Martina Díaz $50.000 y Tobías Ortiz
  $48.000. Por MP: Mía Pérez $40.000 y Lautaro Medina $28.000.
- Torneo de primavera, 2 inscripciones: $16.000 en efectivo.
- Listas: #50 de Mariela, Federadas 10–11 (8, falta Florencia Vega: 7); #81 de Mariela, Federadas 12–13
  (8, faltan Catalina y Martina Díaz: 6); #77 de Hernán, Fútbol Avanzadas 12–13 (15, falta Simón Silva: 14).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 22.1 | Pablo | **Berenice Sánchez** paga lo que debe, **$66.000 por MP**. | Pasa a **Al día**. Cobranza queda sin deuda. |
| 22.2 | Hernán | Toma la lista de la **clase de recuperación de 14 a 15** (20.4): los 15 presentes. | Se guarda. Si el 20.4 no se pudo hacer, se saltea. |
| 22.3 | Dueño | Paga la **luz, $62.000** por Banco Galicia, desde Cashflow. | Egreso directo a su nombre, con Editar y Eliminar. Galicia baja; el gasto del mes sube (CSH-002). |
| 22.4 | Dueño | La factura tenía descuento: corrige la luz a **$61.500**, motivo «Descuento por débito automático». | Queda en $61.500. El detalle muestra el valor anterior, el nuevo, el motivo, quién y cuándo (CSH-007). |
| 22.5 | Dueño | Paga el **sueldo de Sandra**, $250.000 en efectivo, desde Cashflow (Sueldos → Op-Sandra Vidal). | Gasto de Sueldos; el Reporte de Sueldos lo incluye. Sandra no lo ve en sus Movimientos (CSH-015). |
| 22.6 | Dueño | Liquidaciones → Nueva → **Lucía Gaitán, octubre**. La genera y después intenta **Cerrar**. | Se genera: **17 clases × 1 h × $12.000 = $204.000**. No incluye la cancelada del 12/10 (LIQ-001). Al cerrar: «No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.» (LIQ-005). Queda abierta y sin pagar. |
| 22.7 | Dueño | Reporte de Finanzas de octubre. | Muestra los números del cierre de abajo. La liquidación abierta no suma como gasto (REP-003). |

**Noche** (`dia-22-noche.json`): Pablo cierra con el esperado, **$167.000** (10.000 + 141.000 + 16.000),
deja $10.000 y el dueño valida.

**Cierre del día 22 (fin de octubre):**
- Deuda **$0**; **103 activos, todos Al día**.
- Ingresos de octubre **$3.538.850,75**; egresos **$581.950**; resultado **$2.956.900,75**.
- Disponible **$4.709.900,75**: Efectivo $1.367.300, MP $2.638.550,75, Galicia $576.050, Nación $128.000.
- «Sin asistencia» 0.

## Casos nuevos (27)

| Día | Casos que nunca se habían ejecutado |
|---|---|
| 19 | CAJ-007, CBZ-007, COB-010, MOV-007, MOV-008, ASI-003, CAJ-020 |
| 20 | MOV-002, CLA-011, ASI-005, CLA-005, CLA-002, ASI-011, ALU-012 |
| 21 | ASI-013, COB-009, COB-008, REC-002, ALU-025, ALU-017, CSH-018 |
| 22 | CSH-002, CSH-007, CSH-015, LIQ-001, LIQ-005, REP-003 |

## Entrega

- `DIA-19.md` a `DIA-22.md`, con lo que imprimió cada script.
- Evidencia en `evidencia-dia-19/` a `evidencia-dia-22/`.
- Entrada en `LOG-CODEX.md` y tablero T14 con `tiene=Claude`.
- **Commit, solo de lo tuyo:**
  - agregar por nombre, nunca `git add -A`;
  - si un archivo tuyo tiene cambios de otro agente, frenar y avisar;
  - sin push.
