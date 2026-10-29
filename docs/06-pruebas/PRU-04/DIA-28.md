# Día 28 — viernes 6/11/2026

Codex CAB, ejecución real 29/10/2026 en Chrome, `test.gestionar-te.com.ar`, wingstest ficticia. [Guion](GUION-DIAS-24-28.md). Reloj a 09:00 una vez; mañana simulada y guardada una vez. **Sin noche. Caja #53 de Rocío queda ABIERTA** para la tanda siguiente. No se ejecutaron días 29–33 ni se generaron liquidaciones nuevas.

| # | Caso | Resultado | Pantalla y evidencia |
|---|---|---|---|
| 28.1 | LIQ-019 e: noviembre | cumple | Nueva liquidación, noviembre 2026: Lucía 3 clases, Verónica 2, Mariela 4, Hernán 4, Martín 1, Vanina 0. Patín Por hora, Fútbol Por comisión. Sin aviso de clases sin asistencia. No se tocó Generar. [Captura](evidencia-dias-24-28/28.1-noviembre-contadores.jpg), [texto](evidencia-dias-24-28/28.1-noviembre-contadores.txt). |
| 28.1 | LIQ-019 e: octubre | no cumple | Octubre 2026: Lucía/Mariela/Verónica/Vanina 0, pero **Hernán 19 clases liquidables y Martín 1 clase liquidable**. El guion espera ninguna pendiente. Se volvió a abrir Nueva y elegir octubre para descartar una lectura transitoria; persistió. No se generó ninguna. [Segunda comprobación](evidencia-dias-24-28/28.1-octubre-contadores-confirmados.jpg), [texto íntegro](evidencia-dias-24-28/28.1-octubre-contadores-confirmados.txt). La captura inicial `28.1-octubre-cero` está nombrada por el esperado, pero también muestra la diferencia; no certifica cero. |
| 28.2 | ALU-025 a, f; ALU-017 a, b, i | no cumple | Doble toque rápido sobre interruptor de Candela en listado: queda Inactivo; segundo toque no la reactiva. Ficha mantiene solo noviembre $33.000. Cobranza pasa de $2.152.000/107 activos a **$2.119.000/106**, baja $33.000. **La ficha no muestra desde cuándo está inactiva**, aunque Cobranza y Reportes sí muestran «Inactivo desde 06/11/2026». No se corrigió. [Interruptor](evidencia-dias-24-28/28.2-candela-inactiva-doble-toque.jpg), [ficha completa](evidencia-dias-24-28/28.2-candela-ficha.txt), [Cobranza](evidencia-dias-24-28/28.2-cobranza-sin-candela.jpg). |
| 28.3 | ALU-025 b, d | cumple | Morena baja al instante desde listado, sin confirmación. Ficha inactiva: noviembre $44.000. Reactivada desde interruptor de ficha sin panel ni pregunta. Se recargó para confirmar persistencia: Activo y la misma única cuota de noviembre $44.000, sin cuota adicional. [Baja](evidencia-dias-24-28/28.3-morena-inactiva-listado.jpg), [antes](evidencia-dias-24-28/28.3-morena-inactiva-ficha-antes.txt), [resultado persistido](evidencia-dias-24-28/28.3-morena-activa-persistida.txt). |
| 28.4 | CBZ-023 a, b; ALU-017 b; REP-006 g | cumple | Cobranza y Reportes: «Deuda de alumnos inactivos», «Actual al 06/11/2026. Separada del por cobrar y de los totales de Inicio.», 2 alumnos, **$110.000**: Julieta $77.000 y Candela $33.000. Por cobrar separado $2.119.000. Los períodos de Julieta quedaron documentados en 26.3 (nov/dic 2025 y oct 2026). [Cobranza](evidencia-dias-24-28/28.4-cobranza-inactivos-dueno.jpg), [Reportes](evidencia-dias-24-28/28.4-reportes-inactivos-dueno.txt). |
| 28.5 | CBZ-023 e | cumple | Rocío ve las dos deudas inactivas y sus fichas, sin botón Perder. El dueño sí tenía Perder. No se perdió ninguna deuda en esta tanda. [Captura](evidencia-dias-24-28/28.5-rocio-inactivos-sin-perder.jpg), [texto](evidencia-dias-24-28/28.5-rocio-inactivos-sin-perder.txt). |
| 28.6 | REP-006 f | cumple | Reporte de alumnos, noviembre: «Altas del mes 3», «Ingresos por fecha de alta; no incluye reactivaciones»; «Pases a Inactivo del mes 2», «Cada alumno cuenta una vez, aunque vuelva a activo». Lucas eliminado no incrementa el total; las tres altas conservadas son las ejecutadas en 24.6/25.9. [Captura](evidencia-dias-24-28/28.6-reportes-alumnos-noviembre.jpg), [texto](evidencia-dias-24-28/28.6-reportes-alumnos-noviembre.txt). |
| 28.7 | Caja abierta para 29.1 | cumple | Se deja abierta, sin cerrar ni validar: «OPERATIVO Rocío Paz APERTURA 06/11/2026 09:00 CIERRE – ESTADO ABIERTA», 9 movimientos. Inicial $10.000; esperado $155.500, contado sin declarar. Ingresos $304.000, egresos $6.500, neto $297.500. [Estado final](evidencia-dias-24-28/28.7-rocio-caja-abierta.jpg), [texto](evidencia-dias-24-28/28.7-rocio-caja-abierta.txt). |

## Cierre por pantalla

Cobranza: deuda **$2.119.000**, **106 activos**, **47 Al día**, **58 En plazo**, **0 Morosos**, **1 Deudor**. [Captura](evidencia-dias-24-28/28-cierre-cobranza.jpg).

Reportes: ingresos **$1.980.000**, egresos **$2.667.780**, resultado **−$687.780**, disponible **$4.217.120,75**, por pagar $0. Coinciden con el cierre del guion. [Texto](evidencia-dias-24-28/28-cierre-reportes.txt).

| Medio | Disponible, incluye pendiente | Confirmado | Por validar |
|---|---:|---:|---:|
| Banco Galicia | $201.050 | $201.050 | $0 |
| Banco Nación | $58.000 | $58.000 | $0 |
| Banco Nacion ahorro | $0 | $0 | $0 |
| Efectivo | $1.591.200 | $1.445.700 | $145.500 |
| Mercado Pago | $2.366.870,75 | $2.214.870,75 | $152.000 |

Total por validar **$297.500**, neto de la caja abierta de Rocío. [Medios](evidencia-dias-24-28/28-cierre-reportes-medios.txt). Inicio: **Clases sin asistencia 0**, liquidaciones pendientes 0 y cajas cerradas por validar 0; esto último no significa que la caja abierta esté validada. Inicio explicita «Incluye arrastre · $297,500.00 por validar». [Captura](evidencia-dias-24-28/28-cierre-inicio-sin-asistencia.jpg).

Rutina de mañana [GUARDADO](evidencia-dias-24-28/28.0-manana-real.txt): 8 pagos $304.000, Insumos $6.500, lista de Intermedias 18 alumnos/16 presentes. [Simulación](evidencia-dias-24-28/28.0-manana-simular.txt), [reloj](evidencia-dias-24-28/28.0-reloj.txt). Sin rutina de noche. La revisión de Claude y la autorización para continuar con la tanda 2 quedan pendientes.

Durante los cambios de usuario hubo dos ingresos de Rocío rechazados con «Las credenciales ingresadas no son válidas.»; reingresando las mismas credenciales conocidas se accedió. En la recarga de Morena quedó el aviso «Tu sesión expiró. Iniciá sesión nuevamente.» junto a la ficha Activo y noviembre sin duplicar. Se conserva el texto; no se atribuye una causa ni se cambió ninguna clave.
