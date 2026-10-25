# Prueba por días — Días 7, 8 y 9 (viernes 16, sábado 17 y domingo 18 de octubre de 2026)

Armado por Claude el 25/10/2026, sobre el sitio de prueba en el commit `b816d181`, que ya trae T77 a T80. Los esperados salen de los datos reales del sitio al cierre del día 6, leídos en modo lectura, y del código actual.

## Reglas

- Sitio: https://test.gestionar-te.com.ar (base `wingstest`).
- **Reloj:** fijarlo solo al empezar cada día, nunca durante el día:
  - día 7: `wings:fecha-simulada "2026-10-16 15:00"`;
  - día 8: `"2026-10-17 09:30"`;
  - día 9: `"2026-10-18 11:00"`.
- **Navegación:** como una persona, en Chrome, por menús y botones. No consultar datos de negocio en la base.
- **Diferencias:** cada diferencia con el esperado se anota y **no se repara**. Si algo bloquea, anotarlo y seguir con la próxima situación. No inventar un reemplazo.
- **Cambio de usuario:** esperar al menos 60 segundos entre Salir y el siguiente ingreso.
- **Fecha mínima del navegador:** sigue usando la fecha de la computadora, no la de Wings. Carlos lo dejó así.

## Día 7 — viernes 16/10 (Sandra, las profesoras y el dueño)

| # | Situación | Esperado |
|---|---|---|
| 7.1 | Sandra abre caja | Propone **$10.000**, lo que retuvo Pablo en la caja #5. Queda ABIERTA. |
| 7.2 | La mamá de **Agustín Acosta** (Fútbol Principiantes, 2 veces, $35.000) quiere pagar **noviembre** adelantado, $35.000 por **Mercado Pago**. Agustín vino de la primera carga «Al día»: octubre lo pagó antes del sistema. | Cobrar **no muestra ninguna cuota pendiente** y el «Total pendiente» es $0: octubre ya no aparece como deuda (T77). En «Mes sin deuda o cobro adelantado» elige Noviembre 2026 y sugiere $35.000. Cobra. Agustín sigue Al día y su ficha muestra el pago de noviembre. |
| 7.3 | **Sofía Gómez** (Patín Principiantes) debe solo la inscripción, $5.000. La paga en efectivo. | Cobrar muestra solo la inscripción, $5.000, sin ninguna cuota. Cobra. Sofía pasa de Deudor a **Al día**. El recibo dice Inscripción $5.000. |
| 7.4 | Sandra mira **Cobranza** con el filtro «Todos» y busca a **Camila Benítez**. Es la misma persona anotada en Patín Avanzadas y en Fútbol Principiantes. | Aparecen **dos filas**: Patín (Deudor, $50.000) y Fútbol (Al día). La fila de Fútbol avisa «también debe $50.000 en Patín». |
| 7.5 | El papá de Camila paga la inscripción y octubre de Patín: $50.000 por **Mercado Pago**. | Desde la ficha de Patín, Cobrar ofrece inscripción $5.000 y octubre $45.000. Un cobro con los dos renglones. Camila (Patín) pasa a **Al día** y la fila de Fútbol deja de decir «también debe». |
| 7.6 | La mamá de **Franco Molina** (Fútbol Avanzadas, debe octubre $38.000) paga $38.000 en efectivo. Sandra se equivoca y se lo carga a **Dante Ramos**, del mismo grupo, que también debe $38.000. Lo cancela con el motivo «Se cargó a Dante Ramos, pagó Franco Molina» y lo cobra bien a Franco. | Sin motivo no deja cancelar. Dante vuelve a Moroso con octubre $38.000 y su recibo sale **ANULADO** con el motivo. Franco pasa a **Al día**. En el **Detalle de caja**, el cobro cancelado aparece tachado y debajo «Cancelado: Se cargó a Dante Ramos, pagó Franco Molina» (T78). |
| 7.7 | **Bautista Ferrari** (Fútbol Principiantes, Moroso, le queda octubre $15.000) paga $15.000 en efectivo. | Pasa a **Al día**. |
| 7.8 | **Paulina Gutiérrez** (Patín Principiantes, Moroso, octubre $30.000) paga $30.000 por **transferencia al Banco Nación**. Es el primer uso de ese medio. | Pasa a **Al día**. El recibo dice Banco Nación. |
| 7.9 | Sandra registra un gasto en **Gastos Operativos → Insumos Varios**: $6.800 en efectivo, «Botiquín: gasas y cinta». | El movimiento queda en su caja y el efectivo esperado baja $6.800. |
| 7.10 | Sandra cierra y cuenta **$61.700** | Esperado **$61.200**. Muestra **sobrante $500** antes de confirmar y deja cerrar. Retiene $10.000 y entrega **$51.700**. No se crea ningún ingreso por el sobrante. |
| 7.11 | **Lucía** toma lista en la **#80**, Patín Avanzadas 18–19 (hoy). | **12 alumnas**. Ausentes: Martina Herrera y Valentina Suárez. **10 presentes.** Propone «Clase adicional» para **9** de ellas, porque ya vinieron esta semana las veces que pagan: Camila Álvarez, Milagros Benítez, Camila Benítez, Melina Castro, Catalina Castro, Delfina Giménez, Carla Morales, Zoe Pereyra y Lucía Ramírez. **Emma Fernández no**: paga 2 por semana y vino 1 vez. Se deja lo que propone. |
| 7.12 | **Mariela** carga la lista **atrasada** de la **#39**, Patín Federadas, martes 13/10 de 18 a 19. | **8 alumnas**. Ausente: Olivia Rossi. **7 presentes.** No propone «Clase adicional» para nadie. |
| 7.13 | **Hernán** carga la lista **atrasada** de la **#66**, Fútbol Avanzadas, martes 13/10 de 19 a 20. | **14 alumnos.** **Lucas Gómez no está**, porque se pasó a Avanzadas el 14/10 (T52). Ausentes: Joaquín Vega y Felipe Sosa. **12 presentes.** Propone «Clase adicional» para los 5 de plan 1 vez que ya vinieron el jueves: Thiago Díaz, Franco Molina, Lorenzo Morales, Mateo Rodríguez y León Rossi. Se deja lo que propone y se anota. |
| 7.14 | El dueño abre la caja de Sandra, tilda y valida | Ve el **sobrante de $500**, quién cerró y el cobro cancelado **con su motivo**. «Caja validada y reflejada en cashflow». |
| 7.15 | El dueño crea una **clase única**: sábado **31/10**, de 12 a 13, Patín Federadas, con Mariela | Solo ofrece profesoras de Patín. Se crea y aparece en Clases el 31/10. Su lista trae las 8 alumnas. Si el navegador la bloquea por la fecha de la computadora, se anota y se sigue. |
| 7.16 | Cierre del dueño | Cobranza: deuda **$1.474.000**; **74 Al día, 0 En plazo, 9 Morosos, 18 Deudores**. Reportes de octubre: ingresos **$745.000**, egresos **$80.500**, resultado **$664.500**. El cobro de noviembre de Agustín **no** suma a octubre. Disponible **$2.314.500**: Efectivo $587.000, MP $1.574.000, Galicia $123.500, Nación $30.000. El sobrante no figura. «Sin asistencia» baja de **34 a 32**. |

## Día 8 — sábado 17/10 (Pablo, profesoras y dueño)

| # | Situación | Esperado |
|---|---|---|
| 8.1 | Pablo abre caja | Propone **$10.000**, lo que retuvo Sandra. Queda ABIERTA. |
| 8.2 | Pablo cobra la **inscripción al torneo de primavera** de 4 patinadoras de Federadas, $8.000 cada una: un movimiento de ingreso **Torneos → Inscripciones** por $32.000 en efectivo, «Torneo de primavera: 4 inscripciones». | Queda en su caja como ingreso. |
| 8.3 | **Joaquín Vega** (Fútbol Avanzadas, Deudor: agosto, septiembre y octubre, $48.000 cada uno) paga **$100.000** por Mercado Pago: agosto y septiembre completos, y $4.000 a cuenta de octubre. | Un solo cobro. El recibo trae agosto $48.000, septiembre $48.000 y octubre $4.000. Le queda octubre $44.000 y pasa de **Deudor a Moroso**: ya no debe meses anteriores, pero octubre está vencido. No pide motivo, porque paga lo más viejo primero. |
| 8.4 | Pablo registra un gasto en **Mantenimiento y arreglos → Reparaciones menores**: $9.500 en efectivo, «Ruedas del carro de conos». Se equivocó: eran **$5.900**, y lo corrige. | Queda **un solo** gasto, de $5.900. El efectivo esperado lo refleja. |
| 8.5 | **Mariela** toma lista en la **#48**, Patín Federadas 10–11 | **8 alumnas.** Presentes: Helena Iglesias, Olivia Rossi, Guadalupe Núñez y Berenice Rossi (**4**). Ausentes: Martina Díaz, Catalina Díaz, Lucía Ramos y Florencia Vega. Propone «Clase adicional» para **Guadalupe Núñez y Berenice Rossi**, que ya fueron martes y jueves. Para Helena y Olivia no. Entre sus clases figura la nueva del **31/10 12–13**, si se pudo crear. |
| 8.6 | **Hernán** toma lista en la **#75**, Fútbol Avanzadas 11–12 | **15 alumnos, Lucas Gómez incluido.** Presentes: Joaquín Vega, Felipe Sosa, Lucas Gómez y Tomás Giménez (**4**). Propone «Clase adicional» para **Lucas** (paga 1 y vino el jueves) y **Tomás** (paga 2 y vino martes y jueves). Para Joaquín y Felipe no. |
| 8.7 | Pablo cierra y cuenta **$36.100** | Esperado **$36.100**, diferencia $0. Retiene $10.000 y entrega $26.100. |
| 8.8 | El dueño valida la caja de Pablo | Validada y reflejada. |
| 8.9 | Cierre del sábado | Deuda **$1.374.000**; **74 Al día, 0 En plazo, 10 Morosos, 17 Deudores**. Reportes de octubre: ingresos **$877.000**, que incluyen agosto y septiembre de Joaquín, porque los cobros atrasados cuentan en el mes en que se cobran. Egresos **$86.400**, resultado **$790.600**. Disponible **$2.440.600**: Efectivo $613.100, MP $1.674.000, Galicia $123.500, Nación $30.000. «Sin asistencia» **32**. |

## Día 9 — domingo 18/10 (solo el dueño, desde su casa)

| # | Situación | Esperado |
|---|---|---|
| 9.1 | El dueño mira su Inicio | Coincide con el cierre del sábado. No hay cajas por validar. «Sin asistencia» **32**. |
| 9.2 | **Reportes de octubre filtrado por deporte** | Patín: ingresos **$439.000**. Fútbol: ingresos **$406.000**. Entre los dos, $845.000. Los **$32.000 del torneo** no son de ningún deporte: por eso el total da $877.000. En ninguno de los dos filtros aparecen los egresos, porque son gastos del club. |
| 9.3 | La familia de **Julieta Álvarez** (Patín Intermedias, Deudor, $143.000 de noviembre y diciembre de 2025, y agosto, septiembre y octubre de 2026) avisa que deja. El dueño la **da de baja**. | Queda Inactiva y su deuda **no se borra**: sigue en su ficha. Sale de Cobranza: deuda **$1.231.000**, **100 activos: 74 Al día, 10 Morosos, 16 Deudores**. El Inicio y Reportes dicen la misma deuda. |
| 9.4 | El dueño sube **Patín Principiantes, 2 veces por semana**, de $40.000 a **$44.000** | Lo deja guardar. Lo ya generado no cambia: **Bianca Pereyra** sigue debiendo octubre **$40.000**. En el Cobrar de **Mora Benítez** (Patín Principiantes, 2 veces, octubre pagado), Noviembre 2026 sugiere **$44.000**. No cobrar: Volver. |
| 9.5 | El dueño da de alta una operativa nueva, **Rocío Paz** (rocio.paz@example.com), con una clave que elija | Queda activa en Usuarios. En Rubros → Sueldos aparece «Op-Rocío Paz». Rocío ingresa y ve el menú de la operativa, sin Cashflow ni Reportes. No abre caja: es domingo. Sale. |
| 9.6 | Cierre del domingo | Deuda **$1.231.000**; **74 Al día, 0 En plazo, 10 Morosos, 16 Deudores**. Reportes de octubre: ingresos **$877.000**, egresos **$86.400**, resultado **$790.600**, aportes $15.000. Disponible **$2.440.600**. «Sin asistencia» **32**. |

## Qué casos cubre

- T77 y T78 en uso real. COB-031, CBZ-005, CBZ-006, COB-006, COB-017 y REC-003.
- Primer uso de Banco Nación. CAJ-020 (sobrante), CAJ-033 y ASI-007 (clase adicional).
- ASI-009 y T52 en una lista vieja. CLA-002, MOV-003 y MOV-007.
- REP-011 (por deporte), ALU-017 (baja con deuda), GRU-003 y T44 (aumento de precio) y USU-001.

## Entrega

- `DIA-07.md`, `DIA-08.md` y `DIA-09.md`, con el mismo formato que los días 5 y 6.
- Capturas y recibos en `evidencia-dia-07/`, `evidencia-dia-08/` y `evidencia-dia-09/`.
- Entrada en `LOG-CODEX.md`.
- La tabla de pruebas no se toca: lo que haya que cambiar se propone en el informe.
- **Commit, solo de lo tuyo:**
  - `git status`, y agregar por nombre solo tus archivos. Nunca `git add -A`.
  - Si un archivo tuyo tiene cambios de otro agente, frenar y avisar.
  - No hacer `git push`.
