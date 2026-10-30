# Prueba por días: días 34 a 40 (jueves 12 al miércoles 18 de noviembre de 2026) — tanda 3 de 5

Armado por Claude CAB el 30/10/2026 con el mismo método que las tandas [1](GUION-DIAS-24-28.md) y
[2](GUION-DIAS-29-33.md): cada día se corrió entero en una copia de `wingstest` tomada **después de la
verificación de Codex del 30/10** (reloj en el 12/11 07:58), a continuación de la tanda 2 y en este orden. Los
esperados son lo que Wings hizo en esa corrida. **Ningún día se saltea:** los siete llevan su rutina de vida real
(las clases de la grilla con sus listas, los cobros que caen ese día, la caja que abre, cierra y se valida); el
domingo no hay actividad. Lo nuevo va donde el calendario lo pone. Tandas que siguen: 4 (19 al 25/11) y 5
(26/11 al 2/12, con las liquidaciones, el cierre de noviembre y la generación del 1/12).

**Autorización de Carlos (25/10, 28/10, 29/10 y 30/10/2026):** la misma de las tandas 1 y 2 para todas las
operaciones de plata de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios), incluidos
los cobros con recargo, el cobro parcial, el cambio de grupo, las dos altas, la cancelación de una clase y la
transferencia al banco. No hace falta volver a preguntar.

## Punto de partida (después de la verificación del 30/10)

- Reloj en el **12/11 a las 07:58**; el resumen de las 08:00 ya llegó. **No volver el reloj atrás**: el día 34
  se fija a las 09:00 del 12/11.
- Deuda **$850.200**; 106 activos: 83 Al día, 22 Morosos, 1 Deudor (Santino, debe octubre). Ninguna caja abierta.
- 21 recargos de noviembre impagos y el de Guadalupe perdonado. Inactivos con deuda: Candela $33.000.
- Lo que no cambia en toda la tanda: **Pilar Ramos, Alma Navarro, Facundo Ríos y Valentina Suárez no vienen
  ni pagan** (son la Revisión del 1/12); **Liam Pereyra viene pero no paga** (va a ser Deudor el 1/12).

## Cómo se trabaja

Igual que las tandas anteriores: reloj del día una vez, rutina de la mañana con `--simular` y después real, las
tareas de la tabla por pantalla en orden, rutina de la noche. El turno de caja rota como siempre: Sandra los
lunes y jueves, Pablo los martes y sábados, Rocío los miércoles y viernes. **Ojo con el día 38: 38.1 va antes
de la rutina.**

## Día 34: jueves 12/11, vuelven a pagar los vencidos

Reloj **09:00**. **Rutina** (`dia-34-manana.json`): Sandra abre con $10.000; cinco morosos pagan noviembre con
su recargo (Mateo Castro MP $30.800, Santino Flores efectivo $30.800, Facundo Herrera MP $30.800, Morena Rossi
efectivo $48.400, Ignacio Suárez MP $38.500); las listas del jueves: Avanzadas 17 (faltan Valentina Suárez y
Catalina Castro), Federadas 18 (falta Olivia Rossi), Fútbol Avanzadas 19 (falta Felipe Sosa), Adultos 20:30
(los dos). Después de la rutina: Cobranza $670.900; 88 Al día, 17 Morosos, 1 Deudor.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 34.1 | Sandra | **Camila Benítez** está en dos deportes y tiene dos fichas. Cobra noviembre desde cada una, en efectivo. | Cada ficha muestra su cuota y, aparte, «Recargo por mora — Noviembre 2026»: Patín Avanzadas **$49.500** ($45.000 + $4.500) y Fútbol Principiantes **$30.800** ($28.000 + $2.800). Las dos quedan Al día (CBZ-006, PEN-001). |
| 34.2 | Sandra | **Benicio Torres** trae solo la cuota: escribe **28.000** en el importe y cobra en efectivo. | Cobra $28.000 sin ningún panel. La cuota queda Pagada y el recargo de **$2.800 sigue pendiente**: Benicio sigue Moroso, debiendo $2.800 (PEN-001). |
| 34.3 | Sandra | **Joaquín Rodríguez** (debe $35.000 + $3.500) entrega **$20.000** en efectivo. Primero sin motivo; después con «La mamá trae el resto el sábado». | Panel «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.» Sin motivo no cobra. Con motivo cobra $20.000: la cuota queda con saldo **$15.000**, el recargo intacto, sigue Moroso (COB-008, COB-009). |
| 34.4 | Dueño | Inicio, Cobranza con el filtro Morosos y Reportes → Finanzas. | Inicio: el parcial de Joaquín con su motivo y Sandra. Morosos: **15**. Ingresos de noviembre **$3.666.900** (REP-001, CBZ-001). |

**Noche** (`dia-34-noche.json`): Sandra cierra con el esperado, **$217.500**; el dueño valida.

**Cierre del día 34:** deuda **$542.600**; 106 activos: 90 Al día, 15 Morosos, 1 Deudor. Ingresos
**$3.666.900**; egresos $2.678.280; resultado $988.620; disponible **$5.893.520,75**. Inactivos: Candela $33.000.

## Día 35: viernes 13/11, los que pagan sin recargo

Reloj **09:00**. **Rutina** (`dia-35-manana.json`): Rocío abre con $10.000; **Santino paga octubre** ($28.000,
efectivo: es un mes cerrado, no lleva recargo ni aviso); **Guadalupe paga noviembre** ($50.000 por MP: su
recargo está perdonado); Valentín Ortega efectivo $38.500; Lara Quiroga efectivo $48.400; limpieza $4.000;
lista de Intermedias 16 (faltan Alma Navarro y Rocío Sosa). Después: Cobranza $377.700; 94 Al día, 12 Morosos,
0 Deudores.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 35.1 | Rocío | **Mora Benítez**, dos fichas: Patín Principiantes en efectivo y Fútbol Principiantes por MP. | **$48.400** ($44.000 + $4.400) y **$30.800** ($28.000 + $2.800); las dos Al día (CBZ-006). |
| 35.2 | Rocío | Abre las fichas de **Guadalupe** y de **Santino** después de la rutina. | Guadalupe: Al día; «Recargos por mora: Condonado $5.000»; el historial dice 13/11 · Nov 2026 $50.000; el recibo trae un solo renglón. Santino: Al día; Cobranza ya no tiene Deudores (PEN-001, CBZ-001). |
| 35.3 | Dueño | Cashflow → Nuevo: **Luz $38.000** por Banco Galicia, «Edesur, factura de noviembre». | «Movimiento directo registrado en cashflow.» Galicia baja a $163.050 (CSH-002). |
| 35.4 | Dueño | Reportes → Alumnos, noviembre. | 106 activos; **sin presentes en noviembre: Alma Navarro, Pilar Ramos, Facundo Ríos y Valentina Suárez**; presencia 91% (REP-006). |

**Noche** (`dia-35-noche.json`): Rocío cierra con el esperado, **$169.300**; el dueño valida.

**Cierre del día 35:** deuda **$298.500**; 96 Al día, 10 Morosos, 0 Deudores. Ingresos **$3.911.000**; egresos
$2.720.280; resultado $1.190.720; disponible **$6.095.620,75**.

## Día 36: sábado 14/11, el torneo suspendido

Reloj **09:00**. **Rutina** (`dia-36-manana.json`): Pablo abre con $10.000; Santiago Ramos efectivo $30.800;
Thiago Ruiz MP $30.800; **Joaquín trae el resto**: $18.500 en efectivo (los $15.000 de la cuota y el recargo de
$3.500); hojas $3.500; Federadas 10 (falta Catalina Díaz) y **Fútbol Avanzadas 11, la clase reactivada** (falta
Lucas Gómez). Después: Cobranza $218.400; 99 Al día, 7 Morosos.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 36.1 | Pablo | Ficha de **Joaquín Rodríguez**. | Al día; historial: 12/11 $20.000 y 14/11 $18.500 (COB-008). |
| 36.2 | Pablo | Inicio, y la ficha de la clase de Fútbol Avanzadas de las 11. | «14 presentes»; la clase conserva «Estuvo cancelada: Torneo: el grupo viaja» (CLA-015). |
| 36.3 | Pablo | La mamá de **Emma Fernández** (Patín Avanzadas, 2 por semana) paga **diciembre por adelantado**, $45.000 por MP. | Panel «Estás cobrando por adelantado diciembre 2026, que todavía no empezó. El importe queda fijo aunque después cambie la cuota.» Confirma; diciembre queda Pagada a $45.000 (COB-012). |

**13:30** (`dia-36-noche.json`): Pablo cierra con el esperado, **$55.800**. **El dueño no valida hoy.**

**Cierre del día 36:** deuda **$218.400**; 99 Al día, 7 Morosos. Ingresos **$3.991.100**; egresos $2.723.780;
resultado $1.267.320; disponible **$6.217.220,75** (con los $45.800 de la caja de Pablo por validar). Una caja
por validar.

## Día 37: domingo 15/11, el dueño desde su casa

Reloj **20:00**. Sin actividad en el club.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 37.1 | Dueño | Inicio y Cobranza. | Una caja por validar; clases sin lista 0. Morosos, siete: Alma Navarro $36.300, Iván Paredes $38.500, Liam Pereyra $38.500, Pilar Ramos $33.000, Facundo Ríos $30.800, Valentina Suárez $38.500 y Benicio Torres $2.800 (CBZ-001). |
| 37.2 | Dueño | Valida la caja de Pablo del sábado (es la rutina `dia-37-noche.json`; también se puede hacer por pantalla, pero no las dos). | «Caja validada y reflejada en cashflow.»; 8 renglones tildados (CAJ-025). |

**Cierre del día 37:** igual al sábado, con 0 cajas por validar: deuda $218.400; disponible $6.217.220,75.

## Día 38: lunes 16/11, Lara cambia de grupo

Reloj **09:00**. **Antes de la rutina:**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 38.1 | Dueño | Ficha de **Lara Quiroga** (Patín Principiantes, 2 por semana) → Editar: grupo **Patín Intermedias**. Primero sin elegir la frecuencia; después **2 por semana**. | Sin frecuencia: «Debe seleccionar la frecuencia semanal del nuevo grupo.» Con 2 por semana: «Alumno actualizado correctamente.» La ficha dice Intermedias desde el 16/11; el plan de Principiantes rige hasta el 30/11 y el de Intermedias ($43.000) **desde el 1/12**: noviembre, ya pagada, sigue en $44.000 (ALU-013). |

**Rutina** (`dia-38-manana.json`, después de 38.1): Sandra abre con $10.000; **la mamá de Iván trae la cuota
con su recargo** ($38.500, efectivo); el cerrajero $12.000; las tres listas del lunes: Fútbol Principiantes 16
(faltan Facundo Ríos y Lautaro Medina; **Santino e Iván ya no piden motivo**), Patín Principiantes 16 (faltan
Pilar Ramos y Renata Flores; **Lara ya no figura: 27 alumnos**), Intermedias 17 (falta Alma Navarro; **Lara
figura: 18 alumnos**). Después: Cobranza $179.900; 100 Al día, 6 Morosos.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 38.2 | Sandra | **Benicio Torres** trae el recargo que había dejado: $2.800 en efectivo. | Cobrar muestra solo «Recargo por mora — Noviembre 2026» $2.800; cobra; Al día. El recibo trae un solo renglón, de recargo (PEN-001). |
| 38.3 | Sandra | Ficha de la clase de Intermedias de las 17. | Lara figura y está presente (ASI-009). |
| 38.4 | Dueño | Inicio y Cobranza. | Sin excepciones hoy. Morosos, cinco: Alma, Liam, Pilar, Facundo y Valentina (CBZ-001). |

**Noche** (`dia-38-noche.json`): Sandra cierra con el esperado, **$39.300**; el dueño valida.

**Cierre del día 38:** deuda **$177.100**; 101 Al día, 5 Morosos. Ingresos **$4.032.400**; egresos $2.735.780;
resultado $1.296.620; disponible **$6.246.520,75**.

## Día 39: martes 17/11, dos altas tardías

Reloj **09:00**. **Rutina** (`dia-39-manana.json`): Pablo abre con $10.000; nadie viene a pagar la cuota a la
mañana, pero Federadas paga la **inscripción al torneo de diciembre** ($18.000, efectivo); las cuatro listas del martes: Avanzadas 17 (falta Valentina Suárez), Federadas 18 (falta Martina Díaz),
**Fútbol Avanzadas 19 la toma Martín** (falta León Rossi), Adultos 20:30 (falta Graciela Funes).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 39.1 | Pablo | Alta de **Bautista Sosa**: nacido el 3/9/2016, DNI 54111222, tutora Carolina Sosa (11-6789-0123), **Fútbol Principiantes, 2 por semana**, ingreso 17/11. | El alta avisa que la cuota de noviembre es el **70%** («Segunda quincena»): **$24.500**. «Alumno creado correctamente.» Ficha: noviembre pendiente $24.500 e inscripción $5.000; En plazo (ALU-002, COB-025). |
| 39.2 | Pablo | Cobra a Bautista en efectivo: inscripción y noviembre. | Total **$29.500** ($5.000 + $24.500); «Pago registrado…»; Al día. Recibo con dos renglones (COB-005, REC-002). |
| 39.3 | Pablo | Alta de **Emma Villalba**: nacida el 21/1/2015, DNI 54333444, tutora Natalia Villalba (11-4567-8901), **Patín Intermedias, 1 por semana**, ingreso 17/11. **No paga.** | Cuota de noviembre **$23.100** (70% de $33.000). Cobranza: **En plazo**, deuda $28.100 (cuota más inscripción); su plazo vence el **24/11** (7 días desde el alta). Es el alta tardía que no va a venir (CBZ-017; se cierra el 1/12). |
| 39.4 | Dueño | Cashflow → Transferir: **Efectivo → Banco Galicia, $1.500.000**, «Depósito en el Galicia». | «Transferencia #4 registrada: salida y entrada guardadas.» El resultado del mes no cambia; al cerrar el día, Efectivo $1.256.900 y Galicia $1.663.050 (CSH-018). |

**Noche** (`dia-39-noche.json`): Pablo cierra con el esperado, **$57.500**; el dueño valida.

**Cierre del día 39:** deuda **$205.200**; **108 activos**: 102 Al día, 1 En plazo (Emma Villalba), 5 Morosos.
Ingresos **$4.079.900**; egresos $2.735.780; resultado $1.344.120; disponible **$6.294.020,75**. Efectivo
$1.256.900; Galicia $1.663.050.

## Día 40: miércoles 18/11, Mariela avisa que falta

Reloj **09:00**. **Rutina** (`dia-40-manana.json`): Rocío abre con $10.000; pilas $3.000; Fútbol Avanzadas paga la
inscripción al torneo de diciembre ($15.000 por MP); Patín Principiantes 16 (faltan Pilar Ramos y Martina López),
Fútbol Principiantes 18 (falta Facundo Ríos; **Bautista Sosa va por primera vez**).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 40.1 | Dueño | Clases → la de **Adultos del jueves 19 a las 20:30**: cancelar con el motivo «Mariela avisó que no puede: turno médico». | «Clase cancelada.», sin pedir confirmación (no tiene lista). Queda cancelada con su motivo (CLA-011). |
| 40.2 | Rocío | Ficha de la clase de Fútbol Principiantes de las 18. | Bautista figura presente, como «Nuevo», y no pidió motivo: ya hizo su primer pago (ASI-013). |
| 40.3 | Rocío | La mamá de **Dante Ramos** (Fútbol Avanzadas, 2 por semana) paga **diciembre por adelantado** por MP. | Cobrar propone diciembre a **$52.000**: es el precio nuevo del plan, que rige desde el 1/12 para los que ya estaban. Confirma el adelanto; diciembre queda Pagada a $52.000 (GRU-003, COB-012). |
| 40.4 | Dueño | Reportes → Finanzas, noviembre. | Ingresos **$4.094.900**; egresos **$2.738.780**; resultado $1.356.120; disponible $6.358.020,75; deuda $205.200 (CBZ-018). |

**Noche** (`dia-40-noche.json`): Rocío cierra con el esperado, **$7.000**, y **deja los $7.000 como cambio**
(no cobró nada: no puede dejar más de lo que contó); el dueño valida.

**Cierre del día 40:** deuda **$205.200**; 108 activos: 102 Al día, 1 En plazo, 5 Morosos. Ingresos
**$4.094.900**; egresos **$2.738.780**; resultado **$1.356.120**; disponible **$6.358.020,75**. Efectivo
$1.253.900; Mercado Pago $3.383.070,75; Galicia $1.663.050.

## Preparado para lo que viene

- **Emma Villalba** (alta del 17/11, sin pago y sin presentes): su plazo vence el 24/11; el 25/11 a las 06:15
  le corresponde el recargo ($2.310) y el 1/12 va a Revisión (CBZ-017, PEN-001).
- **Liam, Alma, Pilar, Facundo y Valentina** siguen debiendo noviembre con su recargo: el 1/12 pasan a Deudor;
  los cuatro sin presentes van a Revisión (CBZ-003, CBZ-013).
- **Candela** inactiva con $33.000 de noviembre: en diciembre su mamá paga sin que vuelva («No», T25).
- **Lara** paga Intermedias a $43.000 desde la generación del 1/12; **Emma Fernández** ($45.000) y **Dante Ramos**
  ($52.000, el precio nuevo) ya tienen diciembre pagado: la generación del 1/12 no les crea otra cuota.
- La clase de **Adultos del 19/11** quedó cancelada y la de **Federadas del 7/11** sin presentes: las dos
  cuentan para la liquidación de noviembre de Mariela (tanda 5).
