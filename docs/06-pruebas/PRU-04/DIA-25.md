# Día 25 — martes 3/11/2026

Codex CAB, ejecución real 29/10/2026 en Chrome, `test.gestionar-te.com.ar`, wingstest ficticia. [Guion](GUION-DIAS-24-28.md). Reloj 09:00 una vez; simulaciones sin guardar y rutinas reales mañana/noche una vez. Sin consultas directas a base ni reparaciones. Caja #47 cerrada y validada, esperado y contado $235.000; Adultos: dos presentes por rutina.

| # | Caso | Resultado | Pantalla y evidencia |
|---|---|---|---|
| 25.1 | LIQ-009 | cumple | #4 $105.000 y #5 $30.000 de Verónica: «Liquidación pagada. Movimiento registrado en cashflow.», Pagada, 03/11/2026, Banco Galicia. Galicia baja de $336.050 a $201.050. [Octubre](evidencia-dias-24-28/25.1-veronica-octubre-pagada.jpg), [septiembre](evidencia-dias-24-28/25.1-veronica-septiembre-pagada.jpg). |
| 25.2 | LIQ-020 b; LIQ-010 d | cumple | Selector muestra saldos; «Banco Nación — Saldo: $128,000.00 (descubierto permitido)». #6 Mariela octubre $270.000 Pagada por Banco Nación; saldo posterior −$142.000. [Pago](evidencia-dias-24-28/25.2-mariela-octubre-pagada.jpg), [saldo de cierre](evidencia-dias-24-28/25-cierre-reportes.txt). |
| 25.3 | LIQ-009 | cumple | #7 Mariela $54.000 y #8 Hernán $484.680 por MP; #9 Martín $186.600 en efectivo. Las ocho Pagadas; Inicio: profesores por pagar $0 y liquidaciones pendientes 0. [Listado](evidencia-dias-24-28/25.3-ocho-pagadas.txt), [Inicio](evidencia-dias-24-28/25.3-inicio-sin-pendientes.jpg). Capturas individuales `25.3-*-pagada.jpg`. |
| 25.4 | CSH-015 a | cumple | Sueldos ofrece Op-Sandra Vidal, Op-Pablo Ledesma y Op-Rocío Paz; no profesoras. Sandra $320.000 y Pablo $320.000 EFE; Rocío $280.000 MP, «Sueldo de octubre». Tres veces «Movimiento directo registrado en cashflow.». Reportes detalla los tres importes como egresos. [Sandra](evidencia-dias-24-28/25.4-sandra-registrado.txt), [Pablo](evidencia-dias-24-28/25.4-pablo-registrado.txt), [Rocío](evidencia-dias-24-28/25.4-rocio-registrado.txt). |
| 25.4 | CSH-015 e: reparto por deporte | cumple | Comparación posterior en Reportes: Patín egresos $699.000 (solo sus liquidaciones); Fútbol $671.280 (solo sus liquidaciones); «Gastos del club · $1,287,500.00» aparte. Incluye $920.000 de operativas más $300.000 de canchas y $67.500 de gastos, sin descontarlos de deportes. Texto: «Gastos generales separados: no descontados del deporte. Disponible y cajas corresponden a todo el negocio.». [Patín](evidencia-dias-24-28/25.4-patin-gastos-del-club.txt), [Fútbol](evidencia-dias-24-28/25.4-futbol-sin-sueldos-operativas.txt). |
| 25.5 | CSH-002 g; CSH-002 | cumple | Sin rubro, solo «Seleccionar rubro primero...» visible; Alquileres ofrece Centenera, Eventos y San Carlos. Dos egresos: San Carlos $180.000 MP y Centenera $120.000 EFE, «Alquiler de cancha, octubre»; «Movimiento directo registrado en cashflow.». [Opciones](evidencia-dias-24-28/25.5-opciones-sin-rubro.json), [San Carlos](evidencia-dias-24-28/25.5-san-carlos-registrado.txt), [Centenera](evidencia-dias-24-28/25.5-centenera-registrado.txt). |
| 25.6 | COB-009 a, b | cumple | Sin motivo abre panel: «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.». Se completa «Paga el resto el viernes» y cobra $20.000 EFE. «Pago registrado para Sanchez, Berenice.», saldo noviembre $23.000. Inicio del dueño conserva excepción con Pablo, fecha, hora y pago #225. [Panel](evidencia-dias-24-28/25.6-motivo-exigido.jpg), [saldo](evidencia-dias-24-28/25.6-saldo-23000.jpg), [excepción](evidencia-dias-24-28/25-cierre-inicio.txt). |
| 25.7 | MOV-004 a | cumple | Arreglo $250.000 EFE rechazado: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de efectivo y después el gasto.». No se agrega movimiento. El formulario se vacía al volver del rechazo. [Captura](evidencia-dias-24-28/25.7-gasto-rechazado.jpg). |
| 25.8 | MOV-004 d, e | cumple | Insumos $4.000 EFE, «Cinta y pilas», guardado. Edición a $300.000 rechazada con el mismo texto; Resumen conserva egresos $4.000 y efectivo esperado $198.000. Arreglo $60.000 MP aceptado; no cambia el efectivo. [Rechazo](evidencia-dias-24-28/25.8-edicion-300000-rechazada.jpg), [conservación](evidencia-dias-24-28/25.8-insumos-conserva-4000.jpg), [arreglo](evidencia-dias-24-28/25.8-arreglo-mp-registrado.txt). |
| 25.9 | ALU-001; COB-005 | cumple | Graciela 1/semana $32.000 + inscripción $5.000, cobro EFE $37.000; Marcelo 2/semana $42.000 + inscripción $5.000, MP $47.000. Dos renglones por cada pago, confirmados en Detalle. [Graciela total](evidencia-dias-24-28/25.9-graciela-total.txt), [Marcelo total](evidencia-dias-24-28/25.9-marcelo-total.txt), [Detalle](evidencia-dias-24-28/25.9-detalle-cuotas-inscripcion.jpg). |

Marcelo, primer Guardar: «No se guardó. Revisá estos datos; lo que cargaste se conserva.», «Fecha de alta: Esperá a verificar la cuota del alta.». Tras esperar y volver a Guardar, «Alumno creado correctamente.». No se duplicó alta ni pago. [Captura](evidencia-dias-24-28/25.9-marcelo-espera-cuota.jpg). El guion no indica celulares, requeridos por el formulario: se completaron ficticios 1155500025 y 1155500026, sin email; fechas de nacimiento exactamente según guion. Los avisos previos mostraron inscripción y precios del selector; no se afirma un aviso adicional que no se observó.

## Cierre por pantalla

Cobranza: deuda **$3.155.000**, **106 activos**, **22 Al día**, **84 En plazo**, **0 Morosos**, **0 Deudores**. [Captura](evidencia-dias-24-28/25-cierre-cobranza.jpg).

Reportes: ingresos **$855.000**, egresos **$2.657.780**, resultado **−$1.802.780**, disponible **$3.054.120,75**, por pagar $0. Coinciden con el guion. [Texto íntegro](evidencia-dias-24-28/25-cierre-reportes.txt).

| Medio | Disponible | Por validar |
|---|---:|---:|
| Banco Galicia | $201.050 | $0 |
| Banco Nación | −$142.000 | $0 |
| Banco Nacion ahorro | $0 | $0 |
| Efectivo | $1.027.200 | $0 |
| Mercado Pago | $1.967.870,75 | $0 |

Inicio: **Clases sin asistencia 0**, cajas por validar 0, liquidaciones pendientes 0. [Texto](evidencia-dias-24-28/25-cierre-inicio.txt). Rutina de noche [guardada](evidencia-dias-24-28/25.10-noche-real.txt).

No repetir operaciones ni reloj de este día. Pendiente: revisión independiente de Claude.
