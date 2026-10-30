# Día 35 — 13/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Mañana: [simulación, salida 0](evidencia-dias-34-40/35.0-manana-simular.txt) → [real, GUARDADO](evidencia-dias-34-40/35.0-manana-real.txt). [Reloj](evidencia-dias-34-40/35.0-reloj.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 35.1 | Mora: dos fichas | cumple | Patín $48.400 ($44.000 + $4.400), efectivo; Fútbol $30.800 ($28.000 + $2.800), MP. «Pago registrado para Benítez, Mora.» por cada cobro. Ambas Al día, recargo pendiente $0. | [Captura](evidencia-dias-34-40/35.1-mora-patin-formulario.jpg) · [texto](evidencia-dias-34-40/35.1-mora-patin-formulario.txt)<br>[Captura](evidencia-dias-34-40/35.1-mora-patin-aldia.jpg) · [texto](evidencia-dias-34-40/35.1-mora-patin-aldia.txt)<br>[Captura](evidencia-dias-34-40/35.1-mora-futbol-formulario.jpg) · [texto](evidencia-dias-34-40/35.1-mora-futbol-formulario.txt)<br>[Captura](evidencia-dias-34-40/35.1-mora-futbol-aldia.jpg) · [texto](evidencia-dias-34-40/35.1-mora-futbol-aldia.txt) |
| 35.2 | Guadalupe y Santino tras rutina | cumple | Guadalupe Al día; «Pendiente: $0.00 · Condonado: $5,000.00», 13/11/2026 · Nov 2026 $50.000. CUOTA-375: un renglón Noviembre 2026 $50.000, MP. Santino Al día, 13/11 · Oct 2026 $28.000. Cobranza: DEUDORES 0. No se atribuyen paneles UI a cobros realizados por consola. | [Captura](evidencia-dias-34-40/35.2-guadalupe-ficha.jpg) · [texto](evidencia-dias-34-40/35.2-guadalupe-ficha.txt)<br>[Captura](evidencia-dias-34-40/35.2-santino-aldia.jpg) · [texto](evidencia-dias-34-40/35.2-santino-aldia.txt)<br>[Captura](evidencia-dias-34-40/35.2-cobranza-sin-deudores.jpg) · [texto](evidencia-dias-34-40/35.2-cobranza-sin-deudores.txt) |
| 35.3 | Luz por Galicia | cumple | EGRESO, Servicios / Luz, BGA $38.000, «Edesur, factura de noviembre». «Movimiento directo registrado en cashflow.» Galicia $163.050. | [Captura](evidencia-dias-34-40/35.3-luz-formulario.jpg) · [texto](evidencia-dias-34-40/35.3-luz-formulario.txt)<br>[Captura](evidencia-dias-34-40/35.3-luz-registrada.jpg) · [texto](evidencia-dias-34-40/35.3-luz-registrada.txt) |
| 35.4 | Reportes Alumnos | cumple | 106 activos, «Presencia registrada 91,0%», clases sin registros 0. «Activos hoy sin presentes en noviembre · 4»: Navarro, Alma; Ramos, Pilar; Rios, Facundo; Suarez, Valentina. | [Captura](evidencia-dias-34-40/35.4-alumnos-indicadores.jpg) · [texto](evidencia-dias-34-40/35.4-alumnos-indicadores.txt)<br>[Captura](evidencia-dias-34-40/35.4-sin-presentes.jpg) · [texto](evidencia-dias-34-40/35.4-sin-presentes.txt) |

## Cierre de caja

[Simulación](evidencia-dias-34-40/35.5-noche-simular.txt) → [real](evidencia-dias-34-40/35.5-noche-real.txt), salida 0 en ambas. Esperado y contado $169.300. Caja validada y reflejada en cashflow.

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/35.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/35.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/35.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/35.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/35.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/35.6-cierre-inicio.txt)

Deuda **$298,500.00**; **106 activos**: 96 Al día, 0 En plazo, 10 Morosos, 0 Deudores.

Ingresos **$3,911,000.00**; egresos **$2,720,280.00**; resultado **$1,190,720.00**; disponible **$6,095,620.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $163,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $2,634,300.00 |
| MP | $3,240,270.75 |

**Sin asistencia: 0. Cajas por validar: 0.** Candela permanece inactiva con $33.000, separada de la deuda activa.

Continúa [Día 36](DIA-36.md), ya recorrido en esta tanda.

[Recibo Guadalupe](evidencia-dias-34-40/35.2-guadalupe-recibo.pdf) · [vista comprobada](evidencia-dias-34-40/35.2-guadalupe-recibo.png).
