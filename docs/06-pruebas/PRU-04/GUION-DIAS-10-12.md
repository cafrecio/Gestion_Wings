# Prueba por días — Días 10, 11 y 12 (lunes 19, martes 20 y miércoles 21 de octubre de 2026)

Armado por Claude el 25/10/2026, sobre el estado real del sitio al cierre del día 9, leído en modo lectura:
- deuda $1.187.000;
- 75 Al día, 9 Morosos y 16 Deudores, de 100 activos;
- ingresos de octubre $921.000, egresos $86.400;
- disponible $2.484.600 (Efectivo $613.100, MP $1.718.000, Galicia $123.500, Nación $30.000);
- «Sin asistencia» 32.

Los esperados de las listas los simulé contra las marcas reales de cada semana.

**Autorización de Carlos (25/10/2026):** todas las operaciones de plata de este guion, en test.gestionar-te.com.ar (base `wingstest`, datos ficticios), están autorizadas. Incluye cobros, anulaciones y cancelaciones con motivo, condonaciones, gastos, movimientos de Cashflow, cierres, validaciones, altas, reactivaciones y cambios de plan y de clave.

## Reglas

- **Reloj:** fijarlo solo al empezar cada día, nunca durante el día:
  - día 10: `"2026-10-19 15:00"`;
  - día 11: `"2026-10-20 15:00"`;
  - día 12: `"2026-10-21 15:00"`.
- **Navegación:** como una persona, en Chrome. Al menos 60 segundos entre Salir y el siguiente ingreso.
- **Diferencias:** cada una se anota y no se repara. Si algo bloquea, se anota y se sigue.
- **Importes:** escribirlos **sin punto de miles** («4000», no «4.000»). Con punto, el campo lo toma como decimal.
- **Orden de usuarios:** respetar el orden de cada día. Algunas listas dependen de que todavía no haya pasado algo, como el cambio de plan de Helena o la vuelta de Julieta.

## Día 10 — lunes 19/10: el club repara el cobro de Joaquín, Rocío hace su primer turno y llegan dos alumnos nuevos

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 10.1 | Dueño | Revisa Mercado Pago. Por Joaquín Vega entraron **$100.000**, pero Wings dice $144.000 (CUOTA-25, sábado 17/10). | El Inicio coincide con el cierre del día 9: deuda $1.187.000, ingresos $921.000, disponible $2.484.600. |
| 10.2 | Rocío | Su primer turno: abre caja. | Propone **$10.000**, lo que retuvo Pablo en la caja #7. Queda ABIERTA a su nombre. |
| 10.3 | Rocío | Abre la ficha de Joaquín para ver si puede corregir el cobro del sábado. | **No puede anularlo:** la caja de ese cobro ya está validada (COB-035 a). |
| 10.4 | Rocío | Alta de **Lara Quiroga**: menor, nacida el 12/03/2017, DNI 52888111, tutora Mariana Quiroga (11-5550-1212), Patín Principiantes, 2 veces por semana, ingreso hoy. | Guarda. La ficha dice: inscripción **$5.000**; octubre **$30.800**, el 70% de **$44.000** porque es el precio nuevo y entra en la segunda quincena; **En plazo**; **«Vence 26/10/2026»**; **Total adeudado $35.800**. |
| 10.5 | Rocío | Mariana paga todo, $35.800 en efectivo. | Un cobro con dos renglones. Lara pasa a **Al día**. El recibo desglosa inscripción y octubre. |
| 10.6 | Rocío | Alta del hermano, **Tomás Quiroga**: nacido el 02/08/2015, DNI 51777222, misma tutora, Fútbol Principiantes, 1 vez por semana, ingreso hoy. | Inscripción **$5.000**, propia, porque es otra persona. Octubre **$19.600**: el 70% de $28.000. En plazo, vence 26/10. |
| 10.7 | Rocío | Mariana paga **solo la cuota**, $19.600 por Mercado Pago. La inscripción la trae a fin de mes. | Se puede cobrar solo la cuota. Tomás queda **En plazo**, debiendo la inscripción $5.000. |
| 10.8 | Rocío | **Bianca Pereyra** (Patín Principiantes 2 veces, Moroso, octubre $40.000) paga $40.000 en efectivo. | Octubre sigue valiendo **$40.000**: el aumento no toca lo ya generado. Pasa a **Al día**. |
| 10.9 | Rocío | **Carla Pérez** (Patín Intermedias, Moroso, $33.000) paga por Banco Galicia. | Pasa a **Al día**. |
| 10.10 | Rocío | Venta de un par de **patines**: Indumentaria → Patines, $85.000 por Mercado Pago, «Patines talle 34». | Queda en su caja. |
| 10.11 | Rocío | Gasto en **Librería**: $2.700 en efectivo, «Planillas de asistencia en papel». | El efectivo esperado baja $2.700. |
| 10.12 | Lucía | Lista **#7**, Patín Principiantes 16–17 | **28 alumnas, Lara incluida.** Ausentes: Zoe Flores y Pilar Ramos. **26 presentes.** No propone «Clase adicional». |
| 10.13 | Hernán | Lista **#54**, Fútbol Principiantes 16–17 | **21 alumnos, Tomás incluido.** Ausente: Santino Romero. **20 presentes.** No propone «Clase adicional». |
| 10.14 | Verónica | Lista **#15**, Patín Intermedias 17–18 | **18 alumnas.** Ausentes: Alma Navarro y Carla Pérez. **16 presentes.** |
| 10.15 | Rocío | Cierra y cuenta **$83.100** | Esperado $83.100, diferencia $0. Retiene $10.000 y entrega $73.100. |
| 10.16 | Dueño | Valida la caja de Rocío | Validada y reflejada. |
| 10.17 | Dueño | **Anula** el cobro de Joaquín (CUOTA-25) con el motivo «Se cargó $144.000; por Mercado Pago entraron $100.000». | Las tres cuotas vuelven a deber. El recibo sale ANULADO. El asiento en contra lleva la fecha **17/10**. La caja #7 no cambia (COB-035 b, d). |
| 10.18 | Dueño | Le **cobra de nuevo** a Joaquín $100.000 por Mercado Pago, con **fecha de pago 17/10**: agosto $48.000, septiembre $48.000 y octubre **4000**. | Entra sin abrir caja. Recibo nuevo con los tres importes. Joaquín queda **Moroso**, debiendo octubre **$44.000** (COB-035 c, e). |
| 10.19 | Dueño | Paga **Internet**, $18.500 desde Banco Galicia (Servicios → Internet). | Egreso directo. |
| 10.20 | Dueño | Registra los **intereses de Mercado Pago**: Ingreso → Intereses Mercado Pago, $2.350,75. | Guarda con los centavos y los muestra igual en Cashflow y Reportes. |
| 10.21 | Dueño | **Valida a mano las 14 clases de septiembre** sin lista: son anteriores al sistema y las listas en papel se perdieron. | «Sin asistencia» baja de **32 a 18**. |
| 10.22 | Dueño | Cierre del día | Deuda **$1.163.000**; **102 activos: 77 Al día, 1 En plazo (Tomás), 8 Morosos, 16 Deudores**. Reportes de octubre: ingresos **$1.007.750,75**, egresos **$107.600**, resultado **$900.150,75**. Aportes **$100.000**: los patines suman a aportes, no al resultado. Disponible **$2.635.150,75**: Efectivo $686.200, MP $1.780.950,75, Galicia $138.000, Nación $30.000. El ingreso del sábado 17/10 quedó con $100.000 de Joaquín (COB-035 f). |

## Día 11 — martes 20/10: Pablo, regularización de listas, condonación y gastos del dueño

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 11.1 | Pablo | Abre caja | Propone **$10.000**, lo que retuvo Rocío. |
| 11.2 | Pablo | Mariana trae la inscripción de **Tomás Quiroga**, $5.000 en efectivo. | Tomás pasa a **Al día**. |
| 11.3 | Pablo | **Zoe Pereyra** (Deudor: septiembre y octubre, $45.000 cada uno) paga **septiembre**, $45.000 por Mercado Pago. | No pide motivo, porque paga lo más viejo. Pasa a **Moroso**, debiendo octubre $45.000. |
| 11.4 | Pablo | **Felipe Ramírez** (Moroso, $35.000) paga en efectivo. | Pasa a **Al día**. |
| 11.5 | Pablo | **Valentina Suárez** (Moroso, $35.000) paga por Banco Nación. | Pasa a **Al día**. |
| 11.6 | Pablo | **Morena Rossi** (Deudor: septiembre y octubre, $40.000 cada uno) paga septiembre, $40.000 en efectivo. | Pasa a **Moroso**, debiendo octubre $40.000. |
| 11.7 | Pablo | Ingreso por **Clase particular** (Otras clases → Clase particular): $15.000 en efectivo, «Clase particular de patín, Lucía, sábado 24». | Queda en su caja como ingreso. |
| 11.8 | Pablo | Gasto en **Limpieza**: $4.200 en efectivo, «Lavandina y trapos». | El efectivo esperado baja $4.200. |
| 11.9 | Pablo | Intenta **condonar** la deuda vieja de Santino Romero. | **No puede:** condonar es del dueño. |
| 11.10 | Lucía | Lista **#30**, Patín Avanzadas 17–18 | **12 alumnas.** Ausente: Martina Herrera. **11 presentes.** No propone «Clase adicional». |
| 11.11 | Lucía | Carga atrasada **#25**, 01/10, Patín Avanzadas | **12 alumnas.** Ausentes: Camila Álvarez, Delfina Giménez, Carla Morales y Valentina Suárez. **8 presentes.** Sin «Clase adicional». |
| 11.12 | Lucía | Carga atrasada **#3**, 05/10, Patín Principiantes | **26 alumnas, todas presentes.** Sin «Clase adicional». |
| 11.13 | Lucía | Carga atrasada **#26**, 06/10, Patín Avanzadas | **12, todas presentes.** Sin «Clase adicional». |
| 11.14 | Lucía | Carga atrasada **#4**, 07/10, Patín Principiantes | **26 alumnas.** Ausentes, las de plan 1 vez, 18: Victoria Acosta, Martina Castro, Renata Flores, Zoe Flores, Sofía Gómez, Paulina Gutiérrez, Clara Iglesias, Martina López, Milagros Molina, Agustina Morales, Julieta Morales, Pilar Ramos, Lola Ríos, Catalina Romero, Abril Silva, Paulina Silva, Juana Suárez y Luciana Torres. **8 presentes.** Sin «Clase adicional». |
| 11.15 | Lucía | Carga atrasada **#27**, 08/10, Patín Avanzadas | **12.** Ausentes: Camila Álvarez, Delfina Giménez, Carla Morales y Valentina Suárez. **8 presentes.** Sin «Clase adicional». |
| 11.16 | Mariela | Lista **#41**, Patín Federadas 18–19 | **8.** Ausente: Helena Iglesias. **7 presentes.** |
| 11.17 | Mariela | Carga atrasada **#36**, 01/10 | **8.** Ausente: Helena Iglesias. **7 presentes.** Sin «Clase adicional». |
| 11.18 | Mariela | Carga atrasada **#46**, 03/10 | **8.** Ausente: Helena Iglesias. **7 presentes.** Sin «Clase adicional». |
| 11.19 | Mariela | Carga atrasada **#37**, 06/10 | **8, todas presentes.** Propone «Clase adicional» **solo para Helena Iglesias**: paga 1 vez y ya vino el sábado 10. |
| 11.20 | Mariela | Carga atrasada **#38**, 08/10 | **8.** Ausente: Helena Iglesias. **7 presentes.** Propone «Clase adicional» para **Catalina Díaz, Martina Díaz, Guadalupe Núñez, Lucía Ramos y Berenice Rossi**. |
| 11.21 | Hernán | Lista **#68**, Fútbol Avanzadas 19–20 | **15.** Ausente: Felipe Sosa. **14 presentes.** Sin «Clase adicional». |
| 11.22 | Pablo | Cierra y cuenta **$100.800** | Esperado $100.800, diferencia $0. Retiene $10.000 y entrega $90.800. |
| 11.23 | Dueño | Valida la caja de Pablo. **Condona** a Santino Romero octubre, noviembre y diciembre de 2025 ($18.000 cada uno), con el motivo «Deuda anterior al sistema, acordada con la familia». | Validada. Santino sigue **Deudor** con agosto, septiembre y octubre ($84.000). |
| 11.24 | Dueño | Paga el **alquiler de San Carlos**, $120.000 desde Mercado Pago. Hace un **retiro de dueños** de $50.000 en efectivo. | El alquiler suma a egresos. El retiro suma a «Retiros» y no al resultado. |
| 11.25 | Dueño | Cierre del día | Deuda **$949.000**; **80 Al día, 0 En plazo, 8 Morosos, 14 Deudores**. Ingresos **$1.182.750,75**, egresos **$231.800**, resultado **$950.950,75**. Aportes $100.000, retiros $50.000. Disponible **$2.635.950,75**: Efectivo $727.000, MP $1.705.950,75, Galicia $138.000, Nación $65.000. «Sin asistencia» **9**. |

## Día 12 — miércoles 21/10: Sandra, cambio de plan, adelanto con deuda vieja, vuelve Julieta y se termina la regularización

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 12.1 | Verónica | Carga atrasada **#18**, 02/10, Patín Intermedias | **18.** Ausentes, las de plan 1 vez, 9: Constanza Cabrera, Josefina Ferrari, Juana Flores, Delfina Martínez, Alma Navarro, Carla Pérez, Florencia Romero, Rocío Sosa y Candela Vega. **9 presentes.** |
| 12.2 | Verónica | Carga atrasada **#13**, 05/10 | **18, todas presentes.** |
| 12.3 | Verónica | Carga atrasada **#19**, 09/10 | **18.** Ausentes: las mismas 9 que en la #18. **9 presentes.** |
| 12.4 | Sandra | Abre caja | Propone **$10.000**, lo que retuvo Pablo. |
| 12.5 | Sandra | **Helena Iglesias** (Patín Federadas 1 vez, Moroso, octubre $40.000) pasa a **2 veces** ($50.000), desde su ficha. | Como debe octubre, el plan nuevo **rige este mes**: octubre pasa a **$50.000**. |
| 12.6 | Sandra | Helena paga $50.000 en efectivo. | Pasa a **Al día**. |
| 12.7 | Sandra | **Ignacio Suárez** (Deudor: septiembre y octubre, $35.000 cada uno) quiere pagar **noviembre** dejando lo viejo. | Avisa que debe meses anteriores y pide confirmar. Sandra no confirma. |
| 12.8 | Sandra | Ignacio paga septiembre y octubre, $70.000 en efectivo. | Pasa a **Al día**. |
| 12.9 | Sandra | **Paulina Silva** (Deudor: inscripción $5.000 y octubre $30.000) paga $35.000 por Mercado Pago, con **doble clic** en Cobrar. | **Un solo** cobro. Pasa a **Al día**. |
| 12.10 | Sandra | Ingreso **Torneos → Inscripciones**: $16.000 en efectivo, «Torneo de primavera: 2 inscripciones más». | En su caja. |
| 12.11 | Sandra | Gasto en **Insumos Varios**: $3.300 en efectivo, «Bidón de agua». | El efectivo esperado baja $3.300. |
| 12.12 | Lucía | Lista **#8**, Patín Principiantes 16–17 | **28, Lara incluida.** Presentes: las de plan 2 veces más Zoe Flores y Pilar Ramos, que el lunes faltaron: **12**. Ausentes, 16: Victoria Acosta, Martina Castro, Renata Flores, Sofía Gómez, Paulina Gutiérrez, Clara Iglesias, Martina López, Milagros Molina, Agustina Morales, Julieta Morales, Lola Ríos, Catalina Romero, Abril Silva, Paulina Silva, Juana Suárez y Luciana Torres. Sin «Clase adicional». |
| 12.13 | Hernán | Lista **#59**, Fútbol Principiantes 18–19 | **21.** Presentes: los de plan 2 veces más Santino Romero, que faltó el lunes: **8**. Ausentes, 13: Camila Benítez, Bruno Castro, Mateo Castro, Santino Flores, Tomás Gutiérrez, Facundo Herrera, Bautista Martínez, Lautaro Medina, Santiago Ramos, Facundo Ríos, Thiago Ruiz, Benicio Torres y **Tomás Quiroga**. Sin «Clase adicional». |
| 12.14 | Hernán | Carga atrasada **#63**, 01/10, Fútbol Avanzadas | **14.** Ausentes: Thiago Díaz, Franco Molina, Lorenzo Morales, Mateo Rodríguez, León Rossi y Felipe Sosa. **8 presentes.** |
| 12.15 | Hernán | Carga atrasada **#73**, 03/10 | **14.** Ausentes: los mismos 6. **8 presentes.** |
| 12.16 | Hernán | Carga atrasada **#64**, 06/10 | **14, todos presentes.** Propone «Clase adicional» para **Thiago Díaz, Franco Molina, Lorenzo Morales, Mateo Rodríguez y Felipe Sosa**. |
| 12.17 | Hernán | Carga atrasada **#65**, 08/10 | **14.** Ausentes: los mismos 6 de la #63. **8 presentes.** Propone «Clase adicional» para **Benjamín Fernández, Nicolás Ferrari, Tomás Giménez, Julián Navarro, Tobías Ortiz, Simón Silva y Joaquín Vega**. |
| 12.18 | Hernán | Carga atrasada **#52**, 05/10, Fútbol Principiantes | **20, todos presentes.** |
| 12.19 | Hernán | Carga atrasada **#57**, 07/10 | **20.** Ausentes, 13: Camila Benítez, Bruno Castro, Mateo Castro, Santino Flores, Tomás Gutiérrez, Facundo Herrera, Bautista Martínez, Lautaro Medina, Santiago Ramos, Facundo Ríos, Santino Romero, Thiago Ruiz y Benicio Torres. **7 presentes.** |
| 12.20 | Sandra | Cierra y cuenta **$142.700** | Esperado $142.700, diferencia $0. Retiene $10.000 y entrega $132.700. |
| 12.21 | Dueño | Valida la caja de Sandra. **Corrige** el gasto de Internet del lunes: era **$18.950** y no $18.500. | Queda **un solo** movimiento de Internet, de $18.950, con fecha 19/10. |
| 12.22 | Dueño | **Julieta Álvarez** vuelve a Patín Intermedias: la reactiva desde su ficha. | Vuelve con su deuda tal cual: **$143.000**, Deudor. No se genera otra cuota de octubre. |
| 12.23 | Dueño | La familia de Julieta paga los **$143.000** por Mercado Pago. | Un cobro de cinco meses. Recibo con los cinco. **Al día**. |
| 12.24 | Dueño | Rocío olvidó su clave: el dueño le pone una nueva. Rocío ingresa con ella y sale. | Ingresa con la clave nueva. La anterior ya no sirve. |
| 12.25 | Dueño | Cierre del día | Deuda **$804.000**; **103 activos: 84 Al día, 0 En plazo, 7 Morosos, 12 Deudores**. Ingresos **$1.496.750,75**, egresos **$235.550**, resultado **$1.261.200,75**. Aportes $100.000, retiros $50.000. Disponible **$2.946.200,75**: Efectivo $859.700, MP $1.883.950,75, Galicia $137.550, Nación $65.000. **«Sin asistencia» 0.** |

## Entrega

- `DIA-10.md`, `DIA-11.md` y `DIA-12.md`, con el formato de los días anteriores.
- Evidencia en `evidencia-dia-10/`, `evidencia-dia-11/` y `evidencia-dia-12/`.
- Entrada en `LOG-CODEX.md`.
- La tabla de pruebas no se toca.
- **Commit, solo de lo tuyo:**
  - `git status`, y agregar por nombre. Nunca `git add -A`.
  - Si un archivo tuyo tiene cambios de otro agente, frenar y avisar.
  - Sin push.
