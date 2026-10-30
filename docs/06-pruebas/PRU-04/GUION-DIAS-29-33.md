# Prueba por días: días 29 a 33 (sábado 7 al miércoles 11 de noviembre de 2026) — tanda 2 de 2

Armado por Claude CAB el 29/10/2026 con el mismo método que la [tanda 1](GUION-DIAS-24-28.md): cada día se
corrió entero en una copia de `wingstest`, a continuación de la tanda 1 y en este orden. **Se corre solo
después de revisar la tanda 1.** Si la tanda 1 terminó distinta de su cierre del día 28, Claude rehace esta
simulación antes de empezar.

**Autorización de Carlos (25/10 y 28/10/2026):** la misma de la tanda 1 para todas las operaciones de plata
de este guion en test.gestionar-te.com.ar (base `wingstest`, datos ficticios), incluidos el cierre de una caja
ajena por el dueño, el rechazo de una caja, la pérdida de una deuda, la baja de una profesora y la
reasignación de sus clases. No hace falta volver a preguntar.

**Recargo por mora: Carlos lo prende (29/10).** El dueño pone **10%** por pantalla el día 32 (32.5) y el
11/11 a las 06:15 Wings lo aplica solo a las cuotas de noviembre impagas. La tanda se volvió a simular el 29/10
**sobre la base que dejó Codex al terminar la tanda 1** (no sobre la copia de Claude), con el recargo prendido
y con el motivo de «no se puede eliminar» que pidió Carlos (32.2). Los días 29 a 32 dieron lo mismo que antes.

## Punto de partida (cierre del día 28, viernes 6/11)

- Deuda **$2.119.000**; 106 activos: 47 Al día, 58 En plazo, 1 Deudor (Santino).
- **La caja de Rocío del 6/11 sigue abierta** (no la cerró): esperado $155.500.
- Inactivos con deuda: Julieta $77.000 y Candela $33.000. Verónica sigue activa.

## Cómo se trabaja

Igual que la tanda 1. Ojo con el orden de los días 29 y 31: **parte de la pantalla va antes de la rutina**
(está marcado en cada día).

## Día 29: sábado 7/11, la caja olvidada y se va Verónica

Reloj **09:00**. **Antes de la rutina:**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 29.1 | Pablo | Entra a Caja e intenta abrir su turno. | «Hay un turno abierto. Cerralo antes de abrir otro.» (CAJ-015 e, CAJ-009). |
| 29.2 | Dueño | Cajas → la de **Rocío del 6/11**: cuenta el efectivo y la cierra con **$155.000** de contado (faltan $500), dejando **$10.000** de cambio. | Esperado $155.500, contado $155.000, **faltante $500**; entrega $145.000; queda registrado que la cerró el dueño (CAJ-016 a, CAJ-019). |
| 29.3 | Pablo | Abre su caja aceptando lo propuesto. | Propone **$10.000**, el cambio que dejó el dueño (CAJ-016 b, CAJ-005). |

**Rutina** (`dia-29-manana.json`, después de 29.3): seis cobros de noviembre (en efectivo Tomás Giménez $48.000,
Lorenzo Morales $38.000 y Dante Ramos $48.000; por MP Franco Molina $38.000, Mateo Rodríguez $38.000 y
Bautista Ferrari $35.000); **Federadas 10–11 con las ocho alumnas ausentes** (lluvia) y Fútbol Avanzadas 11–12
(falta Simón Silva).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 29.4 | Pablo | Mira su **Inicio**. | Federadas 10–11 dice **«Todos ausentes»** (gris); Fútbol Avanzadas, 14 presentes; caja abierta con la hora (REP-016 g). |
| 29.5 | Dueño | Profesores → **Verónica** (su última clase fue ayer): desactivarla. | No lo deja: «Tiene 7 clases futuras o en curso. Reasignalas antes de desactivar a la profesora.», con acceso a Clases (PRO-004 a). |
| 29.6 | Dueño | Reasigna a **Lucía** cada una de esas siete clases (Intermedias: lunes 9, 16, 23 y 30 de 17 a 18; viernes 13, 20 y 27 de 16 a 17). Vuelve a desactivar a Verónica. | Cada cambio se guarda sin choque. Ahora: «Profesora inactiva. El usuario vinculado quedó desactivado y sus sesiones cerradas.» (PRO-004 c, CLA-009). |
| 29.7 | Verónica | Intenta entrar con su mail y su clave. | «Tu cuenta fue desactivada.» No entra (ACC-004). |
| 29.8 | Dueño | Liquidaciones → Nueva, noviembre. **No generar.** | Verónica aparece, inactiva, con sus **2** clases dictadas de noviembre (LIQ-006 a). |

**Noche** (`dia-29-noche.json`): Pablo cierra con el esperado, **$144.000**. **El dueño no valida hoy.**

**Cierre del día 29:** deuda **$1.874.000**; 106 activos: 53 Al día, 52 En plazo, 1 Deudor. Dos cajas sin validar (Rocío 6/11 y Pablo 7/11). Ingresos **$2.225.000**; egresos $2.667.780.

## Día 30: domingo 8/11, el dueño desde su casa

Reloj **11:00**. Sin caja ni clases.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 30.1 | Dueño | Reportes → Finanzas de noviembre, y Cashflow. | Lo de las cajas de Rocío y Pablo figura **pendiente**, separado de lo confirmado; en Cashflow, en la sección de cajas sin validar (REP-004 a). |
| 30.2 | Dueño | Cashflow: la cancha de **Centenera** era **$125.000**, no $120.000. Editar, motivo «La factura de octubre era de $125.000». Después toca el ícono **Historial** de ese renglón. | Queda en $125.000; el historial muestra antes, después, motivo, quién y cuándo; el acceso es el ícono junto a Editar (CSH-007 a, c, h). Efectivo baja $5.000 más. |
| 30.3 | Dueño | Cashflow: consulta por **Día, Semana, Mes y Año**, filtra por medio y por tipo, y Limpiar. | Cada período trae lo suyo; la semana es de lunes a domingo (2 al 8/11); el resultado del período no suma saldo inicial (CSH-019). |
| 30.4 | Dueño | Reportes → **Sueldos**, octubre y noviembre. | Además de las profesoras, lo pagado a **Sandra, Pablo y Rocío** por persona, sin repartir por deporte (REP-007 g, CSH-015 b). |
| 30.5 | Dueño | Grupos → **Fútbol Avanzadas**: el plan de **2 por semana** pasa de $48.000 a **$52.000**. | Guarda. Las cuotas de noviembre de ese plan (todas pagadas a $48.000) no cambian; el diciembre adelantado de Joaquín sigue en $48.000 (GRU-003 a, b, d). |
| 30.6 | Dueño | Valida las dos cajas pendientes (Rocío y Pablo) y vuelve a Reportes. | Lo pendiente pasa a confirmado; **el total de ingresos de noviembre no cambia** (REP-004 c, CAJ-025). |

**Noche** (`dia-30-noche.json`): **no se corre si el dueño ya validó en 30.6 por pantalla**; es la alternativa por consola si la pantalla falla.

**Cierre del día 30:** deuda **$1.874.000**; ingresos **$2.225.000**; egresos **$2.672.780**; disponible **$4.457.120,75**.

## Día 31: lunes 9/11, un cero de más y una caja rechazada

Reloj **09:00**. **Antes de la rutina:**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 31.1 | Sandra | Abre su caja y escribe **100000** por error. Primero sin motivo; después con motivo «Conté de nuevo el cajón» pero sin confirmar la diferencia; después confirmándola. | Sin motivo: «Indicá por qué recibiste un importe distinto del último cierre. El efectivo recibido difiere en más de un 50% del cierre anterior ($10,000.00)…». Con motivo pide confirmar la diferencia aparte. Confirmando, abre con $100.000 (CAJ-006, CAJ-007). |
| 31.2 | Sandra | Rectifica la apertura a **10000** con «Escribí un cero de más: recibí $10.000». | «Apertura rectificada correctamente.» El detalle muestra heredado $10.000, anterior $100.000, nuevo $10.000 y el motivo (CAJ-013 a, c). |

**Rutina** (`dia-31-manana.json`, después de 31.2): ocho cobros de noviembre, limpieza $3.500 y las listas del
lunes. **Santino** entra a Fútbol Principiantes con el motivo «Vuelve a entrenar; octubre lo paga a fin de mes,
lo autorizó el dueño» (Deudor, B3); **Iván** tiene su segunda clase: todavía sin motivo. Intermedias ya la da
Lucía.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 31.3 | Dueño | Su **Inicio**. | En el historial de excepciones aparece la **asistencia de Santino** con el motivo, Hernán como autor y la clase (REP-001 h, ASI-013 b, f). |
| 31.4 | Sandra | Pagó una resma de **$2.000** con el cajón y no la anotó. A la noche cierra contando **$122.500** (el esperado es $124.500), deja $10.000. Es la rutina de la noche (`dia-31-noche.json`). | Faltante $2.000; caja Cerrada (CAJ-019). |
| 31.5 | Dueño | Rechaza la caja de Sandra: primero sin motivo; después con «Faltan $2.000 en el cajón: ¿es la resma del lunes? Cargala». | Sin motivo: «El motivo del rechazo es obligatorio.» Con motivo, Rechazada (CAJ-029 a, b). |

**Cierre del día 31:** deuda **$1.638.000**; 106 activos: 61 Al día, 44 En plazo, 1 Deudor. Ingresos **$2.461.000**; egresos $2.676.280.

## Día 32: martes 10/11, último día del plazo

**Rutina de la mañana** (`dia-32-manana.json`): Pablo abre; doce cobros de noviembre; listas de Avanzadas (falta Valentina Suárez), Federadas y Fútbol Avanzadas con Martín.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 32.1 | Dueño | Cobranza → Deuda de alumnos inactivos: **da por perdida** la de **Julieta ($77.000)**. Después mira su ficha. | El renglón sale y el total del apartado baja a $33.000 sin recargar. La ficha muestra **$77.000 perdidos**, quién y cuándo, aparte de pagos y condonaciones. No crea pago, recibo, ingreso ni egreso (CBZ-024 a, b, c). |
| 32.2 | Dueño | Abre la ficha de **Julieta** y busca cómo eliminarla. | **No hay botón Eliminar**; en su lugar, abajo a la derecha: «No se puede eliminar: Tiene deuda dada por perdida registrada.» (sin ofrecer la baja: ya está inactiva). En la ficha de **Victoria Acosta**: «No se puede eliminar: Tiene pagos registrados. Puede darlo de baja cambiando su estado a inactivo.» Sandra, en esas mismas fichas, no ve ni el botón ni el texto (CBZ-024 g, ALU-024 b, e). No forzar nada. Cambiado por pedido de Carlos el 29/10, después de la tanda 1 (27.4). |
| 32.3 | Dueño | Se suspende el torneo: **reactiva** Fútbol Avanzadas del **sábado 14/11, 11–12**. Ve el choque, **elimina el amistoso** de Principiantes de esa hora y vuelve a reactivar. | Primero: «No se puede reactivar: la profesora Hernán Quintana ya tiene asignada la clase 'Fútbol — Principiantes' que se solapa en fecha 2026-11-14 entre 11:00 y 12:00.» Sin el amistoso, «Clase reactivada.»; el motivo «Torneo: el grupo viaja» queda guardado (CLA-015 a, b, c; CLA-021 a). |
| 32.4 | Pablo | La mamá de **Iván** paga **solo la inscripción**, $5.000 en efectivo, sin marcar la cuota. | Cobra la inscripción; la cuota de noviembre sigue pendiente (COB-031). Iván sigue siendo «nuevo» para la lista: una inscripción no es el primer pago (ASI-013 d). |
| 32.5 | Dueño | Configuración → **Recargo por mora: porcentaje**. Primero escribe **150** y guarda; después **10** y guarda. El día queda en 10. | Con 150: «Ingresá un porcentaje de 0 a 100, con coma o punto y hasta dos decimales.» y conserva 0. Con 10: guardado sin recargar la página. **Hoy no cambia nada**: el recargo corre desde el día 11 (CFG-006 b, CFG-002, PEN-001). Cobranza sigue en $1.163.000 al cerrar el día. |

**Noche** (`dia-32-noche.json`): Adultos 20:30; Pablo cierra con el esperado, **$258.000**, y el dueño valida.

**Cierre del día 32:** deuda **$1.163.000**; 106 activos: 73 Al día, **32 En plazo**, 1 Deudor. Deuda de inactivos: Candela $33.000. Ingresos **$2.936.000**; egresos $2.676.280; disponible **$5.164.620,75**.

## Día 33: miércoles 11/11, primer día vencido

**Reloj a las 06:13, no a las 09:00**, y esperar a que pasen las **06:15**: a esa hora corre solo el
**recargo por mora**. Después, reloj a las **09:00** y se sigue. (Si se fija directo a las 09:00, el recargo de
hoy no corre.) **Antes de la rutina:**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 33.1 | Dueño o Sandra | Cobranza al empezar el día. Abre la ficha de **Liam Pereyra**. | Los 32 que estaban En plazo pasan solos a **Moroso**; En plazo 0 (CBZ-002). **El total sube $115.500 por los recargos: $1.278.500.** Cada moroso tiene un recargo del 10% de su cuota (Liam: $35.000 → $3.500), en renglón aparte; Santino no (debe octubre, un mes cerrado), Candela tampoco (inactiva). Berenice lleva $4.300: el 10% se calcula sobre la cuota entera aunque ya pagó una parte (PEN-001 a). El Inicio de Sandra muestra los vencidos (CBZ-022). |
| 33.2 | Sandra | Corrige su **caja rechazada del lunes**: agrega la resma (Librería, $2.000, efectivo, fecha 09/11). Prueba antes **sin observación** y con fecha **12/11**. | Sin observación y con fecha futura no guarda. **Esperado según el catálogo:** avisos en castellano. **En la simulación el servidor respondió en inglés** («The observaciones field is required.», «The fecha field must be a date before or equal to today.»): anotar lo que muestre la pantalla. Con todo bien, se guarda (MOV-010 a, b, d; SIS-006). |
| 33.3 | Sandra | Vuelve a cerrar esa caja: primero con contado **$124.500**, después con el mismo de antes, **$122.500**. | Con otro contado: «La corrección no puede cambiar el efectivo que ya se contó y entregó.» Con el mismo: esperado $122.500, **diferencia $0**; vuelve a Cerrada, pendiente de validar (CAJ-031, CAJ-030). |

**Rutina** (`dia-33-manana.json`, después de 33.3): Sandra abre; nueve cobros de noviembre de alumnos que ya son Morosos, **cada uno con su recargo** (Zoe $33.000, Delfina $38.500, Helena $55.000…), incluidos los **$23.000** que le faltaban a Berenice **más su recargo, $27.300**; lista de Principiantes con morosas presentes (entran sin motivo). Cobranza queda en **$910.200**.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 33.4 | Hernán | Toma **por pantalla** la lista de **Fútbol Principiantes, 18–19**: todos presentes menos Facundo Ríos y Santiago Ramos. Primero guarda sin escribir motivos; después con los motivos de **Santino** («Octubre lo paga el viernes; lo habló la mamá con Sandra») e **Iván** («Pagó la inscripción; la cuota la trae el viernes»). | La lista muestra la condición de cada uno sin importes. Sin motivos no guarda nada («Tiene deuda vencida. Indicá el motivo para dejarlo entrar.»). Piden motivo **solo Santino** (Deudor) e **Iván** (nuevo, tercer presente); los **13 morosos** entran sin motivo. Con los dos motivos: 22 presentes de 24 (ASI-013 a, b, c, d, h). |
| 33.5 | Dueño | Valida la caja corregida del lunes. Mira su Inicio. | Validada. Excepciones: dos de asistencia de deudor (Santino, 9 y 11/11), una de alumno nuevo (Iván), dos de deuda anterior y un parcial (REP-001 h). |
| 33.6 | Dueño | Reportes → Alumnos, noviembre. | Activos **106**; altas **3**; pases a Inactivo **2**; activos sin ningún presente en el mes: **Alma Navarro, Pilar Ramos, Facundo Ríos y Valentina Suárez** (REP-006). |
| 33.7 | Sandra | Por pantalla, cobra noviembre a **Florencia Vega** (Federadas) por Mercado Pago. Después abre el recibo. | Cobrar muestra noviembre **$50.000** y, aparte, «Recargo por mora — Noviembre 2026» **$5.000**; total **$55.000**. «Pago registrado para Vega, Florencia.» El recibo lista los dos renglones (PEN-001 a, COB-034). |
| 33.8 | Dueño | En la ficha de **Guadalupe Núñez** perdona su recargo ($5.000): primero con el motivo «Avisó»; después con «Avisó el lunes que pagaba el miércoles; se le respeta». | Con «Avisó»: «El motivo debe tener al menos 10 caracteres.» y no cambia nada. Con el motivo largo: «Recargo condonado.»; en «Recargos por mora» de la ficha: «Condonado: $5.000» (anotar si muestra quién y por qué); Guadalupe sigue debiendo la cuota de $50.000, sin recargo. Sandra no ve Condonar en esa ficha. |
| 33.9 | Dueño | Reportes → Finanzas, noviembre. Mira la deuda y los ingresos. | En la deuda, cada recargo pendiente es un renglón aparte, «Recargo por mora — Noviembre 2026». Lo cobrado de recargos hoy suma **$40.300** (diez cobros); anotar cómo lo muestra Reportes en los ingresos, separado de las cuotas o no (PEN-001 c). |

**Noche** (`dia-33-noche.json`): Sandra cierra con el esperado, **$196.800**, y el dueño valida. (Sin recargos habría sido $178.000: la diferencia, $18.800, son los recargos cobrados en efectivo.)

**Cierre del día 33:** deuda **$850.200**; 106 activos: 83 Al día, **22 Morosos**, 1 Deudor. Ingresos **$3.359.300**; egresos **$2.678.280**; resultado **$681.020**; disponible **$5.585.920,75**. Recargos: 32 aplicados por $115.500; cobrados $40.300; perdonado $5.000.

## Preparado para lo que viene

Lo mismo que dejó la tanda 1 (Revisión del 1/12 con los cuatro sin presentes válidos, diciembre de Joaquín,
Patín por comisión, B3), más:
- **Verónica inactiva con 2 clases de noviembre sin liquidar**: la liquidación de noviembre (desde el 1/12)
  tiene que ofrecerla y liquidarle por hora (LIQ-006, PRO-004 b).
- **Federadas del sábado 7/11 sin presentes**: por hora no se paga salvo que el dueño la valide a mano; es la
  decisión del dueño al liquidar a Mariela (ASI-003 c, CLA-017).
- **Fútbol Avanzadas 2 por semana a $52.000**: la generación del 1/12 sale a $52.000 (salvo Joaquín, que ya
  pagó $48.000).
- **Santino sigue debiendo octubre** e **Iván noviembre**: el 1/12 los dos pasan a Deudor si no pagan.
- **Recargo prendido (10%)**: 21 recargos de noviembre siguen impagos y el de Guadalupe está perdonado. En
  diciembre se prueba que un mes cerrado no recibe recargo nuevo y que cobrar noviembre en diciembre lleva su
  recargo (PEN-001), y el dueño puede reactivar el recargo perdonado.
- **Candela inactiva con $33.000**: queda para probar «al volver, lo perdido sigue perdido» si se pierde y
  vuelve, o el cobro con No (T25) si paga sin volver.
- Falta para el 1/12: **un alta entre el 17 y el 30/11 que no venga** (con y sin pago: CBZ-017). Va en la
  tanda 3.

## Erratas (revisión del 30/10, después de la corrida de Codex)

- **29.1:** la pantalla dice «Hay un turno abierto de Rocío Paz. Debe cerrarse antes de abrir el siguiente.»
  (`caja/apertura.blade.php:18`). El texto que cité es el del envío forzado (`CajaService.php:96`).
- **31.5:** sin motivo, frena el navegador («Completa este campo»); «El motivo del rechazo es obligatorio.» es
  lo que dice el servidor si se saltea el formulario.
- **33.3:** por pantalla no se puede poner otro contado: queda fijo. El rechazo de CAJ-031 solo se ve forzando.
- **33.8:** la ficha no muestra quién ni por qué perdonó; queda en el registro interno del cargo.
- **30.5:** el precio nuevo rige desde el día del cambio (8/11) para altas nuevas y desde el 1/12 para los
  existentes; la pantalla lo informa.
