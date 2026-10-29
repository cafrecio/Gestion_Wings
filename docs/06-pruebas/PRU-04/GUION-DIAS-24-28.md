# Prueba por días: días 24 a 28 (lunes 2 al viernes 6 de noviembre de 2026) — tanda 1 de 2

Armado por Claude CAB el 28-29/10/2026 **contra una copia de la base del sitio de prueba** (`wingstest` del
1/11 a las 23:59, sitio en `899a8079`). Cada día —rutina y tareas de pantalla— se corrió entero en esa copia,
con su fecha y en este orden, enviando lo mismo que envía la pantalla. Los importes, mensajes y totales de
abajo son los que dio Wings en esa corrida, y dos días (24 y 25) se cuadraron además a mano. La tanda 2
(días 29 a 33) está en [GUION-DIAS-29-33.md](GUION-DIAS-29-33.md) y se corre después de revisar esta.

**Autorización de Carlos (25/10 y 28/10/2026):** todas las operaciones de plata de este guion en
test.gestionar-te.com.ar (base `wingstest`, datos ficticios): cobros, cobros parciales y de alumnos
inactivos, reactivaciones, bajas y eliminaciones, cierre y pago de liquidaciones, sueldos, canchas,
transferencias, cambios de profesoras, clases nuevas, canceladas y eliminadas, y las rutinas. No hace falta
volver a preguntar.

## Punto de partida (1/11, 23:59)

- Deuda **$3.783.000**: 103 activos, **3 Al día** (pagaron noviembre por adelantado) y **100 En plazo**.
- Inactivos: Julieta Álvarez (debe 2025-11, 2025-12, 2026-08, 2026-09 y 2026-10: $143.000) y Santino Romero
  (debe octubre, $28.000).
- Liquidaciones: 3 Cerradas ($345.000: Lucía oct #2 y sep #3, Verónica oct #4) y **5 Abiertas** ($1.025.280:
  Verónica sep #5, Mariela oct #6 y sep #7, Hernán oct #8, Martín oct #9). Ninguna pagada.
- Disponible **$4.959.900,75**: Efectivo $1.617.300, Banco Nación $128.000, Mercado Pago $2.638.550,75,
  Banco Galicia $576.050. Ingresos de noviembre $103.000 (los tres adelantados).
- **No hay ninguna clase cargada desde el 1/11**: las series terminaban el 31/10. La rutina del día 24 carga
  el calendario de noviembre.

## Cómo se trabaja

Como en los días 19 a 23: reloj y rutina por consola en el servidor, una sola vez cada uno; lo de las tablas
por pantalla, como una persona; importes sin punto de miles; mirar el total antes de confirmar; **las
diferencias se anotan y no se reparan**.

```
cd /home/wingstest/app
sudo -u wingstest /usr/bin/php82 artisan wings:fecha-simulada "2026-11-02 09:00"
sudo -u wingstest /usr/bin/php82 scripts/pruebas/rutina/rutina.php docs/06-pruebas/PRU-04/rutina/dia-24-manana.json --simular
sudo -u wingstest /usr/bin/php82 scripts/pruebas/rutina/rutina.php docs/06-pruebas/PRU-04/rutina/dia-24-manana.json
```

- **Cada día:** fijar el reloj a las 09:00 de ese día; correr la rutina de la mañana primero con `--simular`
  y, si da GUARDADO-SIMULADO, sin `--simular`; después las tareas de la tabla en orden; al final la rutina de
  la noche (cuando el día la tiene). Si una rutina falla, **no guarda nada**: no operar ese día, anotar el
  mensaje y avisar.
- **Confirmaciones del navegador:** desde el 29/10 el sitio de prueba las acepta solas
  (`PRUEBA_SIN_CONFIRMACIONES`, pedido de Carlos): cerrar o eliminar una liquidación, eliminar un movimiento
  de caja, dar por perdida una deuda, cancelar una serie. Si igual aparece un cuadro del navegador, aceptarlo
  una vez; si traba, anotarlo y seguir con la tarea siguiente. **Los paneles propios de Wings** (¿pasa a
  activo?, motivo de un cobro parcial o de deuda anterior, aviso de cancha, lista ya tomada) **no** se
  aceptan solos: se completan como dice la tabla.
- **Las clases se nombran por grupo, día y hora**, nunca por número: los números de la copia no son los del
  sitio.
- **«Esperado según el catálogo»** marca lo que el catálogo pide y la simulación no pudo mostrar (es
  pantalla). Si Wings no lo hace, es un hallazgo: se anota tal cual.
- **Lo que se prepara para más adelante** (sección final) no se toca: si una tarea no dice que un alumno
  falte, igual hay cuatro que no vienen nunca en noviembre.

## Día 24: lunes 2/11, arranca noviembre

**Rutina de la mañana** (`dia-24-manana.json`):
- El dueño carga el calendario de noviembre: **siete series** del 2 al 30/11, iguales a octubre
  (Principiantes lun y mié 16–17 con Lucía; Intermedias lun 17–18 y vie 16–17 con Verónica; Avanzadas mar y
  jue 17–18 con Lucía; Federadas mar y jue 18–19 y sáb 10–11 con Mariela; Fútbol Principiantes lun 16–17 y
  mié 18–19 con Hernán; Fútbol Avanzadas mar 19–20 con Hernán y Martín; Fútbol Avanzadas jue 19–20 y sáb
  11–12 con Hernán). 59 clases.
- Sandra abre (hereda $10.000) y cobra noviembre. En efectivo: Victoria Acosta $30.000, Alma Herrera $44.000,
  Constanza Cabrera $33.000 y Bruno Castro $28.000. Por MP: Emilia Medina $44.000, Delfina Martínez $33.000,
  Thiago Díaz $38.000 y Benjamín Fernández $48.000. Limpieza $3.500.
- Listas del lunes: Principiantes (faltan Pilar Ramos y Abril Silva), Fútbol Principiantes (faltan Facundo
  Ríos y Benicio Torres) e Intermedias (falta Alma Navarro).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 24.1 | Dueño | Deportes → Patín → Editar: elige **Por comisión** y guarda. | No lo deja: «No se puede cambiar la forma de liquidar: hay liquidaciones abiertas: #5 Verónica Salinas 09/2026; #6 Mariela Ocampo 10/2026; #7 Mariela Ocampo 09/2026.» Patín sigue Por hora (CAT-002 a). |
| 24.2 | Dueño | Liquidaciones: **cierra las cinco abiertas** (#5, #6, #7, #8 y #9), una por una. | Cada una pasa a Cerrada. El «por pagar» del Inicio y de Reportes llega a **$1.370.280** (las ocho), igual a la suma de las cerradas del listado (LIQ-008, LIQ-018 c). |
| 24.3 | Dueño | Paga la de **Lucía de octubre (#2, $204.000)** por Banco Galicia: primero con fecha **03/11/2026** y después con la de hoy. | Con mañana: «La fecha de pago no puede ser futura.» Con hoy: Pagada, con fecha, medio y quién. En Cashflow un solo egreso en «Patín-Lucía Gaitán»; Galicia baja $204.000 (LIQ-010 b, LIQ-009, LIQ-020). |
| 24.4 | Dueño | Paga la de **Lucía de septiembre (#3, $36.000)** por Galicia con **doble clic** en Pagar. | Queda pagada una vez; Cashflow tiene un solo egreso de $36.000 (LIQ-011). Abre el recibo de la #2 (REC-005). |
| 24.5 | Dueño | Abre el grupo **Patín Adultos** (hoy sin alumnos): Clases → Nueva, serie del **3 al 30/11**, martes y jueves de **20:30 a 21:30**, con **Mariela**. | Avisa que cada día ocupa dos bloques de cancha (20:00–21:00 y 21:00–22:00) y no crea nada hasta confirmar. Confirmando: «8 clase(s) creada(s).» (CLA-003, CLA-004). |
| 24.6 | Sandra | Alta de **Iván Paredes**, 9 años, DNI **53444777**, tutora Marina Paredes 1156781234, **Fútbol Principiantes 2 por semana**, ingreso hoy. **No paga hoy.** | Antes de guardar avisa cuota de noviembre al 100%, **$35.000**, e inscripción $5.000. Queda En plazo con 7 días (ALU-001). La lista de hoy ya se tomó: su primera clase es el miércoles. |
| 24.7 | Sandra | Alumnos: busca **«valentin»**, después **«ortega valentin»**, después **«gomez»**; en el buscador de arriba escribe solo **«q»**. En Cobrar busca **«lucas gomez»**. | Encuentra a Valentín Ortega aunque esté escrito con tilde; las dos palabras en cualquier orden; «gomez» trae a Sofía y Lucas; con una letra ya ofrece opciones; Cobrar encuentra a Lucas igual (ALU-020 e a h). |
| 24.8 | Consola | Segunda corrida de la generación: `php82 artisan cobranza:generar-deudas`. | «Deudas creadas: 0», «Ya existían: 104». La constancia de noviembre (hora de fin, creadas y existentes) no cambia; lo mira Claude en la base (CBZ-012 d). |

**Noche** (`dia-24-noche.json`): Sandra cierra con el esperado, **$141.500** (10.000 + 135.000 en efectivo − 3.500), deja $10.000 y el dueño valida.

**Cierre del día 24:** deuda **$3.525.000**; 104 activos: 11 Al día, 93 En plazo. Ingresos de noviembre **$401.000**, egresos **$243.500**, resultado $157.500. Disponible **$5.014.400,75**. «Sin asistencia» 0.

## Día 25: martes 3/11, se pagan los sueldos

**Rutina de la mañana** (`dia-25-manana.json`):
- Pablo abre y cobra noviembre. En efectivo: Paulina Benítez $43.000, Josefina Ferrari $33.000, Lucas Gómez
  $38.000, Tomás Gutiérrez $28.000 y Clara Iglesias $30.000. Por MP: Camila Álvarez $35.000, Martina Díaz
  $50.000, Nicolás Ferrari $48.000 y Lucía Ramírez $45.000.
- Listas: Avanzadas 17–18 (falta Delfina Giménez; **Valentina Suárez viene**), Federadas 18–19 completa y
  Fútbol Avanzadas 19–20 que toma **Martín** (falta Lorenzo Morales).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 25.1 | Dueño | Paga **Verónica oct (#4, $105.000)** y **sep (#5, $30.000)** por Galicia. | Pagadas; Galicia baja $135.000 (LIQ-009). |
| 25.2 | Dueño | Abre la de **Mariela de octubre (#6, $270.000)** y mira el selector de medios antes de pagar. Paga por **Banco Nación**. | Cada medio muestra su saldo; Banco Nación ($128.000) aclara que **permite descubierto**. Se paga y Nación queda en **−$142.000** (LIQ-020 b, LIQ-010 d). |
| 25.3 | Dueño | Paga **Mariela sep (#7, $54.000)** y **Hernán oct (#8, $484.680)** por MP, y **Martín oct (#9, $186.600)** en efectivo. | Las ocho quedan Pagadas; el «por pagar» llega a $0 y desaparece el aviso del Inicio (LIQ-009). |
| 25.4 | Dueño | Cashflow → Nuevo: paga los **sueldos de octubre** de las operativas, en Sueldos: **Sandra $320.000** y **Pablo $320.000** en efectivo, **Rocío $280.000** por MP. Observación «Sueldo de octubre». | El selector de Sueldos ofrece las tres operativas y **no** ofrece a las profesoras. Cada pago queda como egreso del negocio, sin repartirse por deporte (CSH-015 a, e). |
| 25.5 | Dueño | Cashflow → Nuevo, **sin elegir rubro** abre el selector de subrubro; después elige Alquileres y paga las **canchas de octubre**: **San Carlos $180.000** por MP y **Centenera $120.000** en efectivo, «Alquiler de cancha, octubre». | Sin rubro no ofrece ningún subrubro; con Alquileres, solo los suyos (CSH-002 g). Dos egresos directos del dueño (CSH-002). |
| 25.6 | Pablo | **Berenice Sánchez** paga **$20.000** de los $43.000 de noviembre, en efectivo: primero sin motivo; después con «Paga el resto el viernes». | Sin motivo: «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.» Con motivo cobra; noviembre queda con saldo **$23.000** (COB-009 a, b). |
| 25.7 | Pablo | Quiere pagar con el cajón un arreglo de **$250.000** (Reparaciones menores, efectivo). | «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.» No se guarda (MOV-004 a). |
| 25.8 | Pablo | Carga Insumos Varios **$4.000** en efectivo («Cinta y pilas») y después lo edita a **$300.000**. Al final paga el arreglo por **MP, $60.000**. | Los $4.000 entran; la edición a $300.000 se rechaza con el mismo aviso y el movimiento queda en $4.000; el arreglo por MP se acepta y no cambia el cajón (MOV-004 d, e). |
| 25.9 | Pablo | Altas en **Patín Adultos**, ingreso hoy, que **pagan en el momento** cuota más inscripción: **Graciela Funes** (DNI 28444555, 2/3/1988, 1 por semana) en efectivo y **Marcelo Ibañez** (DNI 25111222, 19/7/1981, 2 por semana) por MP. | Avisos de **$32.000** y **$42.000** más $5.000 de inscripción. Cobros de $37.000 y $47.000, con dos renglones cada uno (ALU-001, COB-005). |

**Noche** (`dia-25-noche.json`): Mariela toma la lista de **Adultos 20:30** (Graciela y Marcelo, presentes); Pablo cierra con el esperado, **$235.000**, y el dueño valida.

**Cierre del día 25:** deuda **$3.155.000**; 106 activos: 22 Al día, 84 En plazo. Ingresos **$855.000**, egresos **$2.657.780** (liquidaciones $1.370.280 en total entre ayer y hoy, sueldos $920.000, canchas $300.000), resultado **−$1.802.780**. Disponible **$3.054.120,75**, con Banco Nación en −$142.000.

## Día 26: miércoles 4/11, los que vuelven

**Rutina de la mañana** (`dia-26-manana.json`):
- Rocío abre y cobra noviembre. En efectivo: Martina Herrera $45.000, Milagros Benítez $45.000, Felipe Ramírez
  $35.000 y León Rossi $38.000. Por MP: Melina García $43.000, Catalina Castro $45.000, Manuel Pereyra
  $35.000 y Simón Silva $48.000. Limpieza **$3.800** (era $3.500) y una resma de **$2.000** (no correspondía).
- Listas: Principiantes (faltan Pilar Ramos y Morena Rossi) y Fútbol Principiantes 18–19 (faltan Facundo
  Ríos y Lautaro Medina). **Iván Paredes viene por primera vez.**

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 26.1 | Dueño | Cashflow → Transferir: **$200.000** de Mercado Pago a **Banco Nación**, «Cubrir el pago de Mariela». | Nación vuelve a **$58.000**; ingresos y gastos del mes no cambian (CSH-018). |
| 26.2 | Rocío | En **Cobrar** busca a Julieta Álvarez. | No aparece: el buscador solo trae activos (COB-015 a). |
| 26.3 | Rocío | **Julieta Álvarez** (inactiva) viene a pagar **agosto y septiembre de 2026** ($66.000, efectivo). Rocío entra por su **ficha** → Cobrar, marca esos dos meses. Responde **No** a la pregunta y completa el motivo «Paga lo de este año; lo de 2025 lo habla con el dueño». | Primero: «Alvarez, Julieta está inactivo. ¿Pasa a activo? Si vuelve, se le genera la cuota de noviembre», con Sí, No y Cancelar, en un panel de la página. Con No: «Quedan 2 meses anteriores impagos por $44,000.00 (noviembre 2025, diciembre 2025)…» y pide motivo. Con motivo cobra; Julieta **sigue inactiva** y no se le genera noviembre (COB-015 b, d; COB-007; COB-033 a, b). |
| 26.4 | Rocío | **Santino Romero** (inactivo, debe octubre) vuelve y paga **solo noviembre, $28.000** en efectivo: desde su ficha, mes de noviembre a mano con $28.000. Primero toca **Cancelar**; después **Sí**, y como motivo «Octubre lo paga a fin de mes». | Pregunta si pasa a activo **sin ofrecer No** («El cobro incluye el mes en curso, así que para cobrarlo tiene que volver»). Cancelar no cobra nada. Con Sí pide el motivo de la deuda anterior («Quedan 1 meses anteriores impagos por $28,000.00 (octubre 2026)…»). Queda **activo y Deudor** por octubre, con noviembre pagado (COB-015 c, e, f; COB-007). |
| 26.5 | Dueño | Deportes → Patín → **Por comisión** y guarda. | «Cambio programado desde el 01/12/2026.» La pantalla dice «Programado: Por comisión desde el 01/12/2026» y avisa en rojo que falta porcentaje a Lucía, Verónica, Mariela y Vanina (CAT-002 b, e). |
| 26.6 | Dueño | Profesores → **Lucía** y **Mariela**: porcentaje de comisión **35** y guarda (sin tocar el valor hora). | Guardan; el valor hora queda. El aviso del deporte ya no las nombra. Liquidaciones → Nueva: con **noviembre** Patín sigue Por hora; con **diciembre**, Por comisión (CAT-002 g, PRO-003). |
| 26.7 | Dueño y Rocío | Quieren cancelar la clase de **Patín Principiantes del miércoles 28/10** (está en la liquidación de Lucía, pagada). | «No se puede cancelar: la clase está en una liquidación cerrada o pagada.» Igual para los dos (CLA-013 a, c). |
| 26.8 | Rocío | En su caja corrige la limpieza de **$3.800 a $3.500** y **elimina la resma** de $2.000. Abre el detalle de su caja. | El cajón pasa de $233.200 a **$235.500**. Debajo de los movimientos, la historia: antes y después de la limpieza, la resma eliminada, con autor, fecha y hora. Cada movimiento muestra su hora (MOV-007 d, f; MOV-008; CAJ-033 e, f). |
| 26.9 | Dueño | Mira su **Inicio**. | Historial de excepciones: el parcial de Berenice y las deudas anteriores de Julieta y Santino, con tipo, alumno, motivo, autor y fecha (REP-001 h, COB-009 b). |

**Noche** (`dia-26-noche.json`): Rocío cierra con el esperado, **$263.500**, y el dueño valida.

**Cierre del día 26:** deuda **$2.849.000**; 107 activos: 30 Al día, 76 En plazo, **1 Deudor** (Santino, $28.000 de octubre). Ingresos **$1.283.000**; egresos $2.661.280; disponible **$3.478.620,75**.

## Día 27: jueves 5/11, errores que se corrigen

**Reloj a las 07:58, no a las 09:00**, y esperar a que pasen las 08:00 antes de correr la rutina: a esa hora
corre solo el **resumen diario** (mail y Telegram a Carlos). Esperado: llega una sola vez por canal, sin cajas,
liquidaciones ni revisiones pendientes, y con «Excepciones justificadas (últimas 24 horas): 2», las dos deudas
anteriores del 4/11 (Julieta y Santino), con enlace al Inicio del dueño. El parcial de Berenice del 3/11 ya quedó
afuera de las 24 horas. Si no hubiera nada para avisar, no se manda (CFG-005 a, b; COB-009 b; REP-001 h). Carlos
confirma en su teléfono que llegó.

**Rutina de la mañana** (`dia-27-manana.json`):
- Sandra abre y cobra noviembre. En efectivo: Agustina Morales $30.000, Bianca Pereyra $44.000, Sol Ruiz
  $43.000 y Julián Navarro $48.000. Por MP: Micaela Ortiz $44.000, Paula Ledesma $43.000, Emma Fernández
  $45.000 y Tobías Ortiz $48.000.
- Listas: Avanzadas (falta **Valentina Suárez**), Federadas (falta Helena Iglesias) y Fútbol Avanzadas
  completa.

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 27.1 | Sandra | Por error carga a **Lucas Gómez** (ya es de Fútbol Avanzadas) como alumno de **Patín Principiantes, 1 por semana**, con su mismo DNI y datos. | Se permite (otro deporte). El aviso no anuncia otra inscripción. Queda con cuota de noviembre $30.000 (ALU-005). |
| 27.2 | Sandra | Busca cómo eliminar ese alta. | No tiene Eliminar (ALU-024 e). |
| 27.3 | Dueño | En la ficha de Lucas en **Patín**: Eliminar. Después mira la ficha de Lucas en Fútbol. | «Alumno eliminado correctamente.» Desaparece de Alumnos y Cobranza; la inscripción de Fútbol sigue igual; el DNI queda libre en Patín (ALU-024 a, c, d). |
| 27.4 | Dueño | Intenta eliminar a **Victoria Acosta**. | «No se puede eliminar el alumno: Tiene pagos registrados. Puede darlo de baja cambiando su estado a inactivo.» (ALU-024 b). |
| 27.5 | Dueño | Crea por error una serie de **Fútbol Avanzadas**, sábados **14:00–15:00** del 14 al 28/11, con Hernán (era para Principiantes). Sandra busca cómo borrarla. El dueño, desde una de esas clases, **elimina la serie**. | Se crean 3. Sandra no ve Eliminar. El dueño: «Serie de 3 clases eliminada correctamente.»; no quedan en el listado ni en los filtros (CLA-021 a, c, e). |
| 27.6 | Dueño | La clase de **Avanzadas del martes 3/11** no se dictó (corte de luz), pero la lista se cargó. Primero intenta **eliminarla**; después la **cancela** con «Corte de luz: la clase no se dictó». | Eliminar: «No se puede eliminar la clase: Tiene asistencias registradas: debe cancelarse con motivo.» Cancelar: «Esta clase ya tiene la lista tomada. Las marcas quedarán guardadas pero la clase dejará de contar para liquidaciones y comisiones. ¿Confirmás la cancelación?» en un panel; Confirmar la cancela y conserva las 12 marcas (CLA-021 b, CLA-012 a, b). |
| 27.7 | Sandra | En **Movimientos**: mira la columna de quién registró y abre un recibo; toca la **caja de Rocío del 4/11** y vuelve. | Cada renglón dice quién lo registró y su Recibo abre ese pago. La caja de Rocío se abre **solo para consultar**, con movimientos y totales de los rubros de la operativa, sin Editar, Cancelar ni Cerrar; Volver regresa a Movimientos (MOV-011 e, MOV-018 b, CAJ-034 a, d). |
| 27.8 | Sandra | **Joaquín Vega** paga **noviembre y diciembre** juntos por MP: noviembre $48.000 y, en Adelanto, diciembre **$48.000**. | Avisa en un panel que está cobrando por adelantado diciembre y que el importe queda fijo aunque después cambie la cuota. Confirmando, las dos quedan pagadas a $48.000; la deuda baja solo los $48.000 de noviembre (COB-012, COB-033 a). |
| 27.9 | Dueño | Cancela la clase de **Fútbol Avanzadas del sábado 14/11, 11–12**, con «Torneo: el grupo viaja». Crea una **clase única de Fútbol Principiantes** el **sábado 14/11 de 11:00 a 12:00** con Hernán (un amistoso). | Las dos se guardan (CLA-011, CLA-002). Se usan en la tanda 2 (32.3). |

**Noche** (`dia-27-noche.json`): Mariela toma Adultos 20:30; Sandra cierra con el esperado, **$175.000**, y el dueño valida.

**Cierre del día 27:** deuda **$2.456.000**; 107 activos: 39 Al día, 67 En plazo, 1 Deudor. Ingresos **$1.676.000**; egresos $2.661.280; disponible **$3.919.620,75**.

## Día 28: viernes 6/11, se va una alumna y otra por error

**Rutina de la mañana** (`dia-28-manana.json`):
- Rocío abre y cobra noviembre. En efectivo: Juana Flores $33.000, Florencia Romero $33.000, Helena Giménez
  $43.000 y Malena Navarro $43.000. Por MP: Carla Pérez $33.000, Rocío Sosa $33.000, Mía Rodríguez $43.000 y
  Renata Cabrera $43.000. Insumos $6.500.
- Lista de Intermedias 16–17, **la última clase de Verónica** (faltan Alma Navarro y Candela Vega).

| # | Quién | Tarea | Esperado |
|---|---|---|---|
| 28.1 | Dueño | Liquidaciones → Nueva: mira el número de clases al lado de cada profesora con **noviembre**, y después con **octubre**. | Con noviembre: **Lucía 3** (la del martes 3 está cancelada), **Verónica 2**, **Mariela 4**, **Hernán 4**, **Martín 1**, Vanina 0, y ningún aviso de clases sin asistencia. Con octubre, ninguna pendiente. El número cambia al cambiar el mes (LIQ-019 e). **No generar nada.** |
| 28.2 | Rocío | **Candela Vega** se cambia de club: Rocío la da de baja con el **interruptor del listado** de Alumnos, con **dos toques seguidos y rápidos**. | Queda **Inactiva** (el segundo toque no se toma) y no se genera ninguna cuota. Sale de Cobranza: 106 activos y el total baja $33.000. Su ficha dice desde cuándo está inactiva (ALU-025 a, f; ALU-017 a, b, i). |
| 28.3 | Rocío | Por error toca el interruptor de **Morena Rossi** en el listado. Entra a su **ficha** y la vuelve a activar con el interruptor de la ficha. | Inactiva al instante y sin aviso; desde la ficha vuelve a Activa al instante, sin confirmación. No se le genera otra cuota: ya tiene noviembre (ALU-025 b, d). |
| 28.4 | Dueño | Cobranza y Reportes: busca la **deuda de los alumnos inactivos**. | Aparte, «Deuda de alumnos inactivos»: Julieta ($77.000: 2025-11, 2025-12 y 2026-10) y Candela ($33.000). No suma al total adeudado ni al «por cobrar» (CBZ-023 a, b; ALU-017 b; REP-006 g). |
| 28.5 | Rocío | Mira el mismo apartado en Cobranza. | Lo ve, sin botón para darla por perdida (CBZ-023 e). |
| 28.6 | Dueño | Reportes → Alumnos, noviembre. | Altas del mes **3** (Iván, Graciela, Marcelo; el Lucas eliminado no cuenta) y pases a Inactivo **2** (Candela y Morena: el pase de Morena cuenta aunque haya vuelto, regla de T70) (REP-006 f). |
| 28.7 | Rocío | **No cierra la caja.** Se va. | Queda abierta para el sábado (se usa en 29.1). |

**Sin noche.**

**Cierre del día 28:** deuda **$2.119.000**; 106 activos: 47 Al día, 58 En plazo, 1 Deudor. Ingresos **$1.980.000**; egresos $2.667.780; disponible **$4.217.120,75**, que ya incluye la caja de Rocío sin validar (+$297.500: cobros $304.000 menos insumos $6.500), marcada como pendiente.

## Preparado para lo que viene (no tocar)

| Para | Qué quedó | Cómo se ve después |
|---|---|---|
| Revisión del 1/12 (CBZ-013 a 016) | **Pilar Ramos** (Principiantes), **Alma Navarro** (Intermedias) y **Facundo Ríos** (Fútbol Principiantes) faltan a **todas** las clases de noviembre. **Valentina Suárez** (Avanzadas) vino solo el martes 3, clase cancelada en 27.6 | El 1/12 los cuatro van a Revisión sin cuota de diciembre (lo de Valentina es la consecuencia de CLA-012 c). En la tanda 3 tienen que seguir faltando hasta el 30/11 |
| Generación del 1/12 | Joaquín Vega con **diciembre pagado** a $48.000 | No se le genera otra cuota ni cambia el importe |
| Liquidaciones de diciembre | Patín pasa a **comisión desde el 1/12**; Lucía y Mariela tienen 35%, Verónica y Vanina no | La liquidación de diciembre de Patín es por comisión; a Verónica y Vanina no se les puede generar por comisión |
| Deuda de inactivos | Julieta (debe 2025 y octubre) y Candela | Se pierde lo de Julieta en la tanda 2 (32.1) |
| Clases del 14/11 | Fútbol Avanzadas cancelada y un amistoso de Principiantes a la misma hora | Reactivar con choque en la tanda 2 (32.3) |
| B3 | **Santino** es Deudor por octubre; **Iván** no pagó ninguna cuota | Santino necesita motivo en cada lista; Iván, desde su tercer presente (tanda 2) |

## Lo que esta tanda no prueba, y por qué

- **Un pago de liquidación rechazado por saldo insuficiente (LIQ-010 d):** los dos medios sin descubierto
  (Efectivo y Mercado Pago) tienen saldo de sobra toda la semana; Banco Nación y Galicia permiten descubierto.
- **ASI-013 e** (alumno de la primera carga que nunca pagó en Wings): no queda ninguno en la base.
- **Forzar por fuera de la pantalla** lo nuevo (rutas de operativa y profesora, montos adulterados): lo hace
  Claude en la prueba técnica de esta tanda.
