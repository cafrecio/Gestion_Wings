# Día 26 — miércoles 4/11/2026

Codex CAB, ejecución real 29/10/2026 en Chrome, `test.gestionar-te.com.ar`, wingstest ficticia. [Guion](GUION-DIAS-24-28.md). Reloj a 09:00 una vez, mañana y noche simuladas antes de guardar una sola vez. Caja #49. Sin consultas directas a base, cambios de producto ni reparaciones fuera del guion.

| # | Caso | Resultado | Pantalla y evidencia |
|---|---|---|---|
| 26.1 | CSH-018 | cumple | Transferencia $200.000 MP → Banco Nación, «Cubrir el pago de Mariela». Nación $58.000. Reportes tras transferir: ingresos $1.189.000 y egresos $2.663.580; el traspaso no agrega ingresos ni gastos. [Transferencia](evidencia-dias-24-28/26.1-transferencia.jpg), [Reportes](evidencia-dias-24-28/26.1-reportes-tras-transferir.txt). |
| 26.2 | COB-015 a | cumple | Cobrar: buscar Julieta no ofrece resultado. «Ningún alumno activo coincide con la búsqueda.» [Captura](evidencia-dias-24-28/26.2-julieta-no-aparece.jpg). |
| 26.3 | COB-015 b, d; COB-007; COB-033 a, b | cumple | Desde ficha inactiva, agosto y septiembre 2026, $66.000 EFE. Panel «Alvarez, Julieta está inactivo. ¿Pasa a activo? Si vuelve, se le genera la cuota de noviembre» con Sí/No/Cancelar. No → «Quedan 2 meses anteriores impagos por $44,000.00 (noviembre 2025, diciembre 2025). ¿Cobrás igual solo los meses elegidos? Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.». Motivo «Paga lo de este año; lo de 2025 lo habla con el dueño». Pago #244 una vez; ficha Inactivo, sin noviembre, deuda restante $77.000. [Panel](evidencia-dias-24-28/26.3-panel-reactivar.jpg), [motivo](evidencia-dias-24-28/26.3-aviso-deuda-anterior.jpg), [resultado](evidencia-dias-24-28/26.3-julieta-inactiva-sin-noviembre.txt). |
| 26.4 | COB-015 c, e, f; COB-007 | cumple | Desde ficha de Santino, noviembre manual $28.000 EFE. Panel con Sí y Cancelar, sin No: «El cobro incluye el mes en curso, así que para cobrarlo tiene que volver». Cancelar deja formulario sin cobrar. Nuevo intento, Sí → «Quedan 1 meses anteriores impagos por $28,000.00 (octubre 2026)» y pide motivo. «Octubre lo paga a fin de mes». Pago #245 una vez. Ficha Activo y Deudor, octubre $28.000 pendiente, noviembre pagado. [Panel](evidencia-dias-24-28/26.4-panel-sin-no.jpg), [cancelación](evidencia-dias-24-28/26.4-cancelar-sin-cobro.jpg), [deuda anterior](evidencia-dias-24-28/26.4-deuda-anterior.jpg), [ficha](evidencia-dias-24-28/26.4-santino-activo-deudor.txt). |
| 26.5 | CAT-002 b, e | cumple | «Cambio programado desde el 01/12/2026.»; «Programado: Por comisión desde el 01/12/2026.»; aviso nombra Vanina Directora, Lucía Gaitán, Mariela Ocampo y Verónica Salinas sin porcentaje válido. [Captura](evidencia-dias-24-28/26.5-cambio-programado.jpg). |
| 26.6 | CAT-002 g; PRO-003 | cumple | Guardado 35% para Lucía y Mariela; el listado conserva valores hora $12.000 y $18.000. Aviso de Patín ya solo nombra Vanina y Verónica. Nueva liquidación: noviembre Por hora; diciembre Por comisión, sin generar nada. [Lucía](evidencia-dias-24-28/26.6-lucia-guardada.txt), [Mariela](evidencia-dias-24-28/26.6-mariela-guardada.txt), [aviso](evidencia-dias-24-28/26.6-aviso-solo-vanina-veronica.jpg), [noviembre](evidencia-dias-24-28/26.6-noviembre-hora.jpg), [diciembre](evidencia-dias-24-28/26.6-diciembre-comision.jpg). |
| 26.7 | CLA-013 a, c | cumple | Patín Principiantes miércoles 28/10, 16–17, Lucía. Primero se comprobó su inclusión en liquidación #2, Pagada. Dueño y Rocío intentaron «Cancelar esta», con motivo de prueba «Prueba de bloqueo por liquidación pagada». Ambos recibieron «✗ No se puede cancelar: la clase está en una liquidación cerrada o pagada.». Sigue Activa, 28 alumnos y 26 presentes. [Liquidación](evidencia-dias-24-28/26.7-liquidacion-pagada-incluye-28-oct.txt), [dueño](evidencia-dias-24-28/26.7-dueno-rechazo.jpg), [Rocío](evidencia-dias-24-28/26.7-rocio-rechazo.jpg). |
| 26.8 | MOV-007 d, f; MOV-008; CAJ-033 e, f | no cumple | Correcciones y trazabilidad sí cumplen: «Movimiento actualizado.» limpieza $3.800 → $3.500; «Movimiento eliminado.» resma $2.000. Historia con Antes/Después, Rocío Paz, 04/11/2026 12:13:18 y 12:13:25; cada fila tiene hora. El esperado observado pasa de **$261.200 a $263.500**, no de $233.200 a $235.500 como dice este paso del guion. La diferencia es $28.000, importe de Santino ya cobrado; el cierre de noche del propio guion sí espera $263.500. No se modificó nada para forzar esos valores intermedios. [Antes](evidencia-dias-24-28/26.8-caja-antes.jpg), [historia](evidencia-dias-24-28/26.8-historia-correcciones.jpg), [después](evidencia-dias-24-28/26.8-caja-despues.jpg). |
| 26.9 | REP-001 h; COB-009 b | cumple | Inicio muestra «Excepciones justificadas»: Santino, «Cobro con meses anteriores impagos», motivo exacto, Rocío Paz · 04/11/2026 09:05 · Pago #245; Julieta mismo tipo, motivo exacto, Rocío Paz · 04/11/2026 09:02 · Pago #244; Berenice «Cobro parcial», «Paga el resto el viernes», Pablo Ledesma · 03/11/2026 09:05 · Pago #225. [Captura](evidencia-dias-24-28/26.9-excepciones-inicio.jpg), [texto](evidencia-dias-24-28/26.9-excepciones-inicio.txt). |

En 26.7 la revisión automática bloqueó el primer envío por falta de evidencia verificada de la protección. No hubo envío en ese intento. Se abrió por pantalla la liquidación #2 pagada y se comprobó que incluía la clase; después se ejecutaron los dos intentos autorizados y se obtuvieron los rechazos de Wings. No quedó ese caso pendiente.

## Cierre por pantalla

Cobranza: deuda **$2.849.000**, **107 activos**, **30 Al día**, **76 En plazo**, **0 Morosos**, **1 Deudor**: Santino, octubre $28.000. [Captura](evidencia-dias-24-28/26-cierre-cobranza.jpg).

Reportes: ingresos **$1.283.000**, egresos **$2.661.280**, resultado **−$1.378.280**, disponible **$3.478.620,75**, por pagar $0. Coinciden los totales finales del guion. [Texto](evidencia-dias-24-28/26-cierre-reportes.txt).

| Medio | Disponible | Por validar |
|---|---:|---:|
| Banco Galicia | $201.050 | $0 |
| Banco Nación | $58.000 | $0 |
| Banco Nacion ahorro | $0 | $0 |
| Efectivo | $1.280.700 | $0 |
| Mercado Pago | $1.938.870,75 | $0 |

[Detalle de medios](evidencia-dias-24-28/26-cierre-reportes-medios.txt). Inicio: **Clases sin asistencia 0**, cajas por validar 0, liquidaciones pendientes 0, revisión de alumnos 0. [Captura](evidencia-dias-24-28/26-cierre-inicio-sin-asistencia.jpg).

Noche: [simulación](evidencia-dias-24-28/26-noche-simular.txt), [GUARDADO](evidencia-dias-24-28/26-noche-real.txt): esperado y contado **$263.500**, 11 renglones validados. Mañana [GUARDADO](evidencia-dias-24-28/26.0-manana-real.txt), [reloj](evidencia-dias-24-28/26.0-reloj.txt).
