# Día 24 — lunes 2/11/2026

Ejecución humana: Codex CAB, 29/10/2026, Chrome sobre `test.gestionar-te.com.ar`. Guion: [tanda 1](GUION-DIAS-24-28.md). Datos ficticios de wingstest. Consola limitada a reloj, rutinas y 24.8; sin consultas directas a la base ni reparaciones. Tanda 2 no ejecutada.

Evidencia: [carpeta](evidencia-dias-24-28/). Las capturas son del recorrido real; los TXT conservan los textos de accesibilidad y la salida de consola. El reloj y cada rutina real se ejecutaron una vez. Las simulaciones terminaron con «SIMULADO: no se guardó nada.»; la ejecución real, con «GUARDADO.». Caja #45 cerrada y validada, contado y esperado $141.500.

| # | Caso | Resultado | Pantalla y evidencia |
|---|---|---|---|
| 24.1 | CAT-002 a | cumple | «No se puede cambiar la forma de liquidar: hay liquidaciones abiertas: #5 Verónica Salinas 09/2026; #6 Mariela Ocampo 10/2026; #7 Mariela Ocampo 09/2026.» Cambio rechazado. [Captura](evidencia-dias-24-28/24.1-bloqueo-patin.jpg). |
| 24.2 | LIQ-008; LIQ-018 c | cumple | #5, #6, #7, #8 y #9: «Liquidación cerrada correctamente.», estado Cerrada. Inicio y Reportes: por pagar $1.370.280. [Inicio](evidencia-dias-24-28/24.2-inicio-total.jpg), [Reportes y texto](evidencia-dias-24-28/24.2-reportes-total.txt). Cinco capturas individuales `24.2-liquidacion-N-cerrada.jpg`. |
| 24.3 | LIQ-010 b; LIQ-009; LIQ-020 | cumple | 03/11 rechazada: «La fecha de pago no puede ser futura.»; 02/11: «Liquidación pagada. Movimiento registrado en cashflow.». #2 Pagada, 02/11/2026, Banco Galicia. Cashflow: un egreso $204.000, «Sueldos — Patín-Lucía Gaitán», autor Admin Prueba; Galicia $372.050. [Pago](evidencia-dias-24-28/24.3-lucia-octubre-pagada.jpg), [Cashflow](evidencia-dias-24-28/24.3-cashflow-egreso-unico.txt). La captura del rechazo quedó arriba del formulario: el texto exacto se conserva en el TXT. |
| 24.4 | LIQ-011 | cumple | Doble clic en Pagar para #3: Pagada $36.000, 02/11, Galicia. Cashflow: un egreso de #3, saldo Galicia $336.050. [Pago](evidencia-dias-24-28/24.4-septiembre-pagada.jpg), [Cashflow](evidencia-dias-24-28/24.4-cashflow-sin-duplicado.txt). |
| 24.4 | REC-005 | no probado | Se pulsó Recibo de #2. Chrome abrió una pestaña que desapareció antes de poder inspeccionarla; no se acredita contenido ni lectura del PDF. No se repitió ningún pago. |
| 24.5 | CLA-003; CLA-004 | cumple | Serie Adultos 03–30/11, martes y jueves 20:30–21:30, Mariela. Antes de crear: «El alquiler se cuenta por bloques del reloj.» y para ambos días «dura 1 hora, pero ocupa 2 bloques de alquiler: 20:00–21:00, 21:00–22:00.» Confirmar: «8 clase(s) creada(s).». [Aviso](evidencia-dias-24-28/24.5-aviso-dos-bloques.jpg), [Resultado](evidencia-dias-24-28/24.5-ocho-clases-creadas.txt). |
| 24.6 | ALU-001 | no cumple | Iván creado sin cobrar, Fútbol Principiantes 2/semana. Antes: «Corresponde inscripción por única vez: $5,000.00», selector $35.000; no se observó aviso explícito de cuota de noviembre al 100%. Ficha: «En plazo 8 días de gracia Vence 11/11/2026», frente a 7 esperados; deuda $40.000, cuota noviembre $35.000 e inscripción $5.000, sin pagos ni asistencias. Para los 9 años indicados se usó nacimiento 02/11/2017; celular igual al de la tutora. [Antes](evidencia-dias-24-28/24.6-ivan-previo.jpg), [Ficha](evidencia-dias-24-28/24.6-ivan-ficha.jpg). |
| 24.7 | ALU-020 e–h | cumple | «valentin» encuentra Ortega, Valentín; «ortega valentin» también; «gomez» muestra Lucas y Sofía. «q» ofrece Quiroga Lara/Tomás, Rodriguez Joaquin y Vega Joaquin. En Cobrar, «lucas gomez» encuentra Gomez, Lucas. Capturas `24.7-*.jpg`. [Cobrar](evidencia-dias-24-28/24.7-cobrar-lucas.jpg). |
| 24.8 | CBZ-012 d | cumple | Consola: «Total alumnos activos: 104», «Deudas creadas: 0», «Enviados a revisión: 0», «Ya existían (skipped): 104». [Salida](evidencia-dias-24-28/24.8-generacion.txt). |
| 24.8 | CBZ-012 d: constancia interna | no probado | Conservación de la constancia mensual pendiente de Claude; este recorrido no consultó la base. |

## Cierre leído por pantalla

Cobranza: **$3.525.000**, **104 activos**, **11 Al día**, **93 En plazo**, **0 Morosos**, **0 Deudores**. [Captura](evidencia-dias-24-28/24-cierre-cobranza.jpg).

Reportes: ingresos **$401.000**, egresos **$243.500**, resultado **$157.500**, disponible **$5.014.400,75**. Coinciden con el guion. [Texto](evidencia-dias-24-28/24-cierre-reportes.txt), [medios](evidencia-dias-24-28/24-cierre-reportes-medios.jpg).

| Medio | Disponible | Por validar |
|---|---:|---:|
| Banco Galicia | $336.050 | $0 |
| Banco Nación | $128.000 | $0 |
| Banco Nacion ahorro | $0 | $0 |
| Efectivo | $1.748.800 | $0 |
| Mercado Pago | $2.801.550,75 | $0 |

Inicio: **Clases sin asistencia 0**, cajas por validar 0, seis liquidaciones pendientes por $1.130.280. [Texto](evidencia-dias-24-28/24-cierre-inicio.txt).

Pendientes: contenido del recibo #2 y control interno de la constancia por Claude. Diferencia del plazo de Iván conservada. No repetir operaciones del día 24.
