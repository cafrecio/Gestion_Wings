# Prueba por días — Días 13 a 16 (jueves 22 al domingo 25 de octubre de 2026)

Armado por Claude el 25/10/2026.

**Punto de partida:** el sitio al cierre del día 12, ya corregido en la base. El cobro de Joaquín se corrigió con contra asiento y la cuota de Tomás quedó cobrada.
- Deuda $804.000; 102 activos: 83 Al día, 7 Morosos y 12 Deudores.
- Ingresos de octubre $1.353.750,75; egresos $235.550.
- Disponible $2.803.200,75: Efectivo $859.700, MP $1.740.950,75, Galicia $137.550, Nación $65.000.
- «Sin asistencia»: 0.

**Requisito:** T81 (teléfono en Alumnos y en Cobranza) verificada y desplegada. Si todavía no lo está, el 13.6 se pasa al próximo paquete.

**Autorización de Carlos (25/10/2026):** todas las operaciones de plata de este guion, en test.gestionar-te.com.ar (base `wingstest`, datos ficticios), están autorizadas. Incluye cobros, cancelaciones, rechazos y validaciones de caja, perdones, altas, reactivaciones, cambios de profesora y de horario, y la corrida de los scripts de rutina. No hace falta volver a preguntar.

## Cómo se trabaja

- **Lo rutinario lo carga un script**, por consola en el servidor, como `wingstest` y en `/home/wingstest/app`. Primero se fija el reloj del día:
  ```
  /usr/bin/php82 artisan wings:fecha-simulada "2026-10-22 15:00"
  /usr/bin/php82 scripts/pruebas/rutina/rutina.php docs/06-pruebas/PRU-04/rutina/dia-13-manana.json
  ```
  - El script imprime un renglón por paso y al final la deuda de Cobranza. Si un paso falla, no guarda nada del plan y dice cuál falló. En ese caso **no se reintenta a mano**: se anota y se sigue con lo de pantalla.
  - Lo que imprime se copia al informe del día.
- **Codex recorre por pantalla solo lo de las tablas.** Es lo nuevo y lo pendiente.
  - Como una persona, en Chrome, por menús.
  - Al menos 60 segundos entre usuarios.
  - Importes **sin punto de miles**.
- **Antes de confirmar** un cobro, un cierre o una validación, mirar el total de la pantalla. Si no es el esperado, **no se confirma**: se anota. El error del día 8 no se repite.
- **Diferencias:** se anotan y no se reparan.
- **Si la herramienta de Chrome falla** en una confirmación nativa (pasó con Julieta), se reintenta una vez desde cero. Si vuelve a fallar, se anota como «no probado por la herramienta» y se sigue.

## Día 13 — jueves 22/10: hacer bien lo que el lunes se hizo mal

**Script de la mañana:** `dia-13-manana.json`.
- Sandra abre la caja con $10.000.
- Seis cobros:
  - Guadalupe Núñez, $22.000 en efectivo;
  - Lucía Ramírez, $25.000 por MP;
  - inscripción de Martina Castro, $5.000 en efectivo;
  - inscripción de Carla Morales, $5.000 en efectivo;
  - Liam Pereyra, $17.000 en efectivo;
  - Olivia Gutiérrez, $23.000 por MP.
- Gasto de Limpieza, $3.800.
- Listas:
  - #31: 12 alumnas, 8 presentes;
  - #42: 8 alumnas, 8 presentes;
  - #69: 15 alumnos, 9 presentes.
- **Al terminar imprime:** Cobranza $707.000; 89 Al día, 5 Morosos y 8 Deudores.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 13.1 | Sandra | Entra primero con una **clave equivocada** y después con la buena. | Con la mala: un aviso en castellano que no dice si el mail existe, y no entra. Con la buena entra (ACC-002). |
| 13.2 | Sandra | Carga una **clase particular**: Otras clases → Clase particular, en efectivo, «Clase particular de patín, Lucía». Se equivoca y pone **25000**: eran $20.000. | Queda en su caja por $25.000. El error es a propósito y todavía no se nota. |
| 13.3 | Sandra | **Felipe Sosa** (Deudor: agosto y octubre, $38.000 cada uno) quiere pagar **noviembre**, $38.000 en efectivo, y dejar lo atrasado para fin de mes. Sandra confirma el adelanto y **sigue hasta el final**: si pide motivo, «La familia paga lo atrasado a fin de mes». | Avisa que debe meses anteriores y pide confirmar (COB-033). Confirmado, noviembre queda pagado **sin tocar** agosto ni octubre. Felipe sigue **Deudor** con $76.000. |
| 13.4 | Sandra | Cierra la caja contando **el efectivo real: $113.200**. | Esperado **$118.200**: incluye los $25.000 de la clase. Muestra **faltante $5.000** antes de confirmar y deja cerrar. Retiene $10.000. |
| 13.5 | Dueño | En su Inicio toca el aviso de **cajas por validar**. | Lo lleva a la caja de Sandra (REP-017). |
| 13.6 | Dueño | En **Cobranza**, la lista para llamar. | Cada fila muestra el **teléfono** y no el DNI. Primero aparece **Alma Navarro**, con deuda desde noviembre de 2025 (CBZ-019, T81). |
| 13.7 | Dueño | Ve el faltante de $5.000 y no valida. **Rechaza** la caja: primero sin motivo, después con «La clase particular fue de $20.000, no de $25.000». | Sin motivo no rechaza. Con motivo la caja queda **Rechazada**. Nada pasa a Cashflow (CAJ-029). |
| 13.8 | Sandra | Ve su caja rechazada con el motivo. **Corrige** la clase particular de 25000 a **20000**. Al volver a cerrar, primero intenta cambiar lo contado a 118200. | Con otro contado: «La corrección no puede cambiar el efectivo que ya se contó y entregó» (CAJ-031). |
| 13.9 | Sandra | Vuelve a cerrar con lo contado original, **$113.200**. | Ahora el esperado es **$113.200**, diferencia $0. Queda Cerrada (CAJ-030). |
| 13.10 | Dueño | Tilda y **valida**. | Validada y reflejada. La clase particular entra por **$20.000**. |
| 13.11 | Dueño | **Julieta Álvarez** vuelve: la reactiva desde su ficha y le cobra los **$143.000** por Mercado Pago, sin caja. | Vuelve con su deuda tal cual, Deudor, sin una cuota de octubre nueva. Con el cobro, recibo de cinco meses y **Al día** (ALU-018). |
| 13.12 | Dueño | Cierre del día | Deuda **$707.000**; **103 activos: 90 Al día, 0 En plazo, 5 Morosos, 8 Deudores**. Ingresos de octubre **$1.613.750,75** (el noviembre de Felipe no suma), egresos **$239.350**, resultado **$1.374.400,75**. Disponible **$3.097.400,75**: Efectivo $962.900, MP $1.931.950,75, Galicia $137.550, Nación $65.000. «Sin asistencia» 0. |

## Día 14 — viernes 23/10: alumnos y altas con problemas

**Script de la mañana:** `dia-14-manana.json`.
- Pablo abre la caja con $10.000.
- Cinco cobros:
  - Dante Ramos, $38.000 en efectivo;
  - Zoe Pereyra, $45.000 por MP;
  - Morena Rossi, $40.000 en efectivo;
  - Mateo Castro, $33.000 por Banco Nación;
  - Martina López, $35.000 por MP.
- Librería, $1.500.
- Lista #21: 19 alumnas, Julieta incluida; 12 presentes.
- **Al terminar imprime:** Cobranza $516.000.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 14.1 | Pablo | Alta de **Valentín Ortega**: nacido el 10/02/2010, Fútbol Principiantes 2 veces, ingreso hoy. Primero mal: DNI «45A12» y nacimiento 10/02/2030. | Rechaza con avisos en castellano en cada campo, sin perder lo cargado (ALU-003). |
| 14.2 | Pablo | Lo corrige: DNI 50123987, nacido el 10/02/2010, tutor Raúl Ortega (11-5551-3434). Guarda. | Inscripción $5.000; octubre **$24.500** (el 70% de $35.000); En plazo, vence 30/10; total $29.500. |
| 14.3 | Pablo | Raúl paga **$29.500** en efectivo. | **Al día**. |
| 14.4 | Pablo | Intenta dar de alta otra vez a **Camila Benítez** en Patín, con su DNI 51200700. | No la deja: ya está anotada en Patín (ALU-004). |
| 14.5 | Pablo | **Mora Benítez** se anota también en **Fútbol Principiantes**, 1 vez, ingreso hoy. | No le cobra otra inscripción: ya la pagó en Patín. Octubre **$19.600** (el 70% de $28.000) (ALU-005). |
| 14.6 | Pablo | Laura, la mamá de Mora, paga $19.600 por Mercado Pago. | La inscripción de Fútbol de Mora pasa a **Al día**. |
| 14.7 | Dueño | **Perdona la inscripción** de Valentina López («Beca de inscripción por hermana»). | Con motivo la perdona. Le queda octubre $40.000 y pasa de Deudor a **Moroso** (CBZ-010). |
| 14.8 | Pablo | Valentina López paga octubre, $40.000 en efectivo. | **Al día**. |
| 14.9 | Pablo | La familia de **Zoe Flores** había pagado **agosto** ($30.000) por Banco Nación el **30/09**, y nadie lo cargó. Pablo lo cobra con **fecha de pago 30/09/2026**. | Pide confirmar que es una fecha de un mes cerrado. Entra en la caja de hoy con fecha 30/09. El dueño recibe el aviso. El ingreso cuenta en **septiembre**, no en octubre (COB-022). Zoe pasa a **Al día**. |
| 14.10 | Dueño | Carga mal un movimiento directo en Cashflow: Luz con importe **0**; después con fecha **30/10** (futura). No lo corrige: lo deja sin guardar. | Rechaza los dos con avisos claros. No se guarda nada (CSH-006). |

**Script de la noche:** `dia-14-noche.json`. Pablo cierra con el efectivo esperado, **$156.000**, deja $10.000 y el dueño valida.

**Cierre del día 14:**
- Deuda **$441.000**; **105 activos: 99 Al día, 2 Morosos (Joaquín y Berenice Rossi), 4 Deudores**.
- Ingresos de octubre **$1.893.850,75**; egresos **$240.850**; resultado **$1.653.000,75**.
- Disponible **$3.406.000,75**: Efectivo $1.108.900, MP $2.031.550,75, Galicia $137.550, Nación $128.000.
- **Septiembre** suma $30.000 de ingresos.

## Día 15 — sábado 24/10: caja y clases fuera de lo normal

**Script de la mañana:** `dia-15-manana.json`.
- Pablo abre la caja con $10.000.
- Cobra a Berenice Rossi $50.000 en efectivo.
- Inscripciones al torneo: $24.000 en efectivo.
- Lista #49 de Mariela: 8 alumnas; presentes Helena Iglesias y Berenice Rossi.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 15.1 | Mariela | Se equivocó en la #49: no vino **Helena**, vino **Olivia Rossi**. Corrige la lista. | Pide motivo porque ya tenía lista tomada. Con «Me confundí de alumna» guarda: Helena ausente y Olivia presente. Propone «Clase adicional» para **Olivia** y **Berenice**, que ya vinieron martes y jueves (ASI-004). |
| 15.2 | Rocío | Llega para un turno corto e intenta **abrir caja**. | «Hay un turno abierto. Cerralo antes de abrir otro.» Muestra la caja abierta de Pablo. No puede cobrar (CAJ-009). |
| 15.3 | Dueño | Alta de un profesor de fútbol: **Martín Acosta**, por hora, $15.000. | Queda activo. En Sueldos aparece «Fútbol-Martín Acosta» (PRO-001). |
| 15.4 | Dueño | Hernán avisó que no viene: cambia la profesora de la **#76** (Fútbol Avanzadas 11–12, hoy) a **Martín Acosta**, con el motivo «Hernán enfermo». | Solo ofrece profesores de Fútbol. Queda Martín y el motivo guardado (CLA-009). |
| 15.5 | Martín | Toma lista en la **#76**: 15 alumnos; presentes **Lucas Gómez y Tomás Giménez**. | Propone «Clase adicional» para los dos: Lucas ya vino el martes y Tomás, martes y jueves. |
| 15.6 | Dueño | Cambia el horario de la **#77** (sábado 31/10, Fútbol Avanzadas) de 11–12 a **12–13**, con motivo. | Se guarda. Hernán la ve en el horario nuevo (CLA-016). |
| 15.7 | — | **Pablo se va sin cerrar la caja.** No se cierra. | Queda abierta para el domingo. |

**Cierre del día 15:**
- Deuda **$391.000**; 105 activos: **100 Al día, 1 Moroso (Joaquín), 4 Deudores**.
- Ingresos **$1.967.850,75**; egresos **$240.850**; resultado **$1.727.000,75**.
- Disponible **$3.480.000,75**, con $74.000 por validar en la caja abierta.

## Día 16 — domingo 25/10: solo el dueño

| # | Tarea | Esperado |
|---|---|---|
| 16.1 | En su Inicio ve el turno de Pablo **abierto desde ayer**. Lo cierra él, contando **$84.000** y dejando $10.000. | Lo deja cerrar. Queda guardado que lo cerró el dueño. Los cobros siguen a nombre de Pablo (CAJ-016). |
| 16.2 | Valida esa caja. | Validada. |
| 16.3 | Reportes: comparación con septiembre y evolución. | Septiembre muestra los $30.000 de Zoe del 30/09. La evolución dibuja los meses con datos (REP-012). |
| 16.4 | Reportes de Alumnos: cómo le va a cada grupo. | Cada grupo con sus activos. Fútbol Principiantes cuenta a Valentín y a Mora (REP-015). |
| 16.5 | Liquidaciones → **Nueva**, con octubre sin terminar. | La pantalla abre y lista a las profesoras con sus clases. Anotar si deja generar octubre antes de que termine el mes (LIQ-019). **No generar.** |
| 16.6 | En Usuarios, intenta cambiarse su propio rol a Operativa y desactivarse. | No lo deja: la cuenta del dueño está protegida (USU-006, USU-009). |
| 16.7 | En Configuración, días de gracia «-3» y después «abc». | Rechaza los dos. Queda el valor anterior, 10 (CFG-002). |
| 16.8 | Crea el grupo **Patín Adultos** con dos planes: 1 vez $32.000 y 2 veces $42.000. | Se guarda, activo y sin alumnos (GRU-001). |
| 16.9 | Crea el subrubro **Botiquín** en Gastos Operativos, para la operativa. | Aparece para la operativa al cargar un gasto (CAT-007). |
| 16.10 | Cierre del domingo | Igual que el sábado, ahora sin nada por validar: deuda **$391.000**; ingresos **$1.967.850,75**; disponible **$3.480.000,75**: Efectivo $1.182.900, MP $2.031.550,75, Galicia $137.550, Nación $128.000. «Sin asistencia» 0. |

## Entrega

- `DIA-13.md` a `DIA-16.md`, con lo que imprimió cada script.
- Evidencia en `evidencia-dia-13/` a `evidencia-dia-16/`.
- Entrada en `LOG-CODEX.md`.
- **Commit, solo de lo tuyo:**
  - agregar por nombre, nunca `git add -A`;
  - si un archivo tuyo tiene cambios de otro agente, frenar y avisar;
  - sin push.
