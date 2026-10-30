# Día 37 — 15/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Domingo sin actividad ni rutina; reloj a las 20:00. Validación única por UI. [Reloj](evidencia-dias-34-40/37.0-reloj.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 37.1 | Inicio y siete morosos | cumple | Una caja por validar; Clases sin asistencia 0. Alma $36.300, Iván $38.500, Liam $38.500, Pilar $33.000, Facundo $30.800, Valentina $38.500, Benicio $2.800. Deuda $218.400. | [Captura](evidencia-dias-34-40/37.1-inicio.jpg) · [texto](evidencia-dias-34-40/37.1-inicio.txt)<br>[Captura](evidencia-dias-34-40/37.1-morosos.jpg) · [texto](evidencia-dias-34-40/37.1-morosos.txt) |
| 37.2 | Validación única por pantalla | cumple | Pablo CERRADA, esperado/contado $55.800, cambio $10.000, entrega $45.800. Primer Validar: «Faltan 8 movimientos por verificar antes de validar la caja.» Tildados 496–503; Validar una vez: «Caja validada y reflejada en cashflow.» Detalle final VALIDADA, 8 movimientos. No se corrió dia-37-noche.json. El aviso se conservó como [transcripción de la observación inmediata](evidencia-dias-34-40/37.2-confirmacion-transcripta.txt): ya había desaparecido al guardar la captura. | [Captura](evidencia-dias-34-40/37.2-caja-antes.jpg) · [texto](evidencia-dias-34-40/37.2-caja-antes.txt)<br>[Captura](evidencia-dias-34-40/37.2-ocho-sin-verificar.jpg) · [texto](evidencia-dias-34-40/37.2-ocho-sin-verificar.txt)<br>[Captura](evidencia-dias-34-40/37.2-ocho-verificados.jpg) · [texto](evidencia-dias-34-40/37.2-ocho-verificados.txt)<br>[Captura](evidencia-dias-34-40/37.2-caja-validada.jpg) · [texto](evidencia-dias-34-40/37.2-caja-validada.txt)<br>[Captura](evidencia-dias-34-40/37.2-detalle-validado.jpg) · [texto](evidencia-dias-34-40/37.2-detalle-validado.txt) |

## Diferencias y alcance

Se usó validación UI y no la rutina. La captura «ocho-verificados» todavía muestra 7 de 8 durante la actualización del último contador. Después fue posible validar y el detalle final muestra VALIDADA y 8 movimientos; aquella captura intermedia no acredita 8 de 8 visibles.

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/37.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/37.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/37.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/37.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/37.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/37.6-cierre-inicio.txt)

Deuda **$218,400.00**; **106 activos**: 99 Al día, 0 En plazo, 7 Morosos, 0 Deudores.

Ingresos **$3,991,100.00**; egresos **$2,723,780.00**; resultado **$1,267,320.00**; disponible **$6,217,220.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $163,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $2,680,100.00 |
| MP | $3,316,070.75 |

**Sin asistencia: 0. Cajas por validar: 0.** Candela permanece inactiva con $33.000, separada de la deuda activa.

Continúa [Día 38](DIA-38.md), ya recorrido en esta tanda.
