# Día 36 — 14/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Mañana: [simulación, salida 0](evidencia-dias-34-40/36.0-manana-simular.txt) → [real, GUARDADO](evidencia-dias-34-40/36.0-manana-real.txt). [Reloj](evidencia-dias-34-40/36.0-reloj.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 36.1 | Joaquín salda el resto | cumple | Ficha Al día; recargo pendiente $0.00. Historial: 14/11/2026 · Nov 2026 $18.500 y 12/11/2026 · Nov 2026 $20.000. | [Captura](evidencia-dias-34-40/36.1-joaquin-aldia.jpg) · [texto](evidencia-dias-34-40/36.1-joaquin-aldia.txt) |
| 36.2 | Clase reactivada del sábado | cumple | Inicio: Fútbol Avanzadas 11, «14 presentes». Clase sábado 14/11 11:00–12:00 Activa, «(Estuvo cancelada: Torneo: el grupo viaja)»; 15 alumnos, 14 presentes. | [Captura](evidencia-dias-34-40/36.2-inicio-presentes.jpg) · [texto](evidencia-dias-34-40/36.2-inicio-presentes.txt)<br>[Captura](evidencia-dias-34-40/36.2-clase-reactivada.jpg) · [texto](evidencia-dias-34-40/36.2-clase-reactivada.txt) |
| 36.3 | Emma adelanta diciembre | cumple | Diciembre $45.000, MP. Panel: «Estás cobrando por adelantado diciembre 2026, que todavía no empezó. El importe queda fijo aunque después cambie la cuota.» Confirmado: «Pago registrado para Fernandez, Emma.» Ficha Al día, 14/11 · Dic 2026 $45.000. | [Captura](evidencia-dias-34-40/36.3-emma-adelanto-formulario.jpg) · [texto](evidencia-dias-34-40/36.3-emma-adelanto-formulario.txt)<br>[Captura](evidencia-dias-34-40/36.3-emma-aviso.jpg) · [texto](evidencia-dias-34-40/36.3-emma-aviso.txt)<br>[Captura](evidencia-dias-34-40/36.3-emma-pago.jpg) · [texto](evidencia-dias-34-40/36.3-emma-pago.txt)<br>[Captura](evidencia-dias-34-40/36.3-emma-diciembre-pagado.jpg) · [texto](evidencia-dias-34-40/36.3-emma-diciembre-pagado.txt) |

## Diferencias y alcance

Precisión del esperado: el guion menciona $45.800 de la caja por validar; Inicio/Reportes muestran $121.600: EFE $45.800 + MP $75.800 (incluye el adelanto de diciembre). El disponible coincide. Se dejó sin validar el sábado. Se avanzó de 09:00 a [13:30](evidencia-dias-34-40/36.5-reloj-cierre.txt) para el cierre indicado, sin volver atrás.

## Cierre de caja

[Simulación](evidencia-dias-34-40/36.5-noche-simular.txt) → [real](evidencia-dias-34-40/36.5-noche-real.txt), salida 0 en ambas. Esperado y contado $55.800. Pablo queda CERRADA, sin validar hasta el domingo.

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/36.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/36.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/36.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/36.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/36.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/36.6-cierre-inicio.txt)

Deuda **$218,400.00**; **106 activos**: 99 Al día, 0 En plazo, 7 Morosos, 0 Deudores.

Ingresos **$3,991,100.00**; egresos **$2,723,780.00**; resultado **$1,267,320.00**; disponible **$6,217,220.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $163,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $2,680,100.00 |
| MP | $3,316,070.75 |

**Sin asistencia: 0. Cajas por validar: 1.** Candela permanece inactiva con $33.000, separada de la deuda activa.

Continúa [Día 37](DIA-37.md), ya recorrido en esta tanda.
