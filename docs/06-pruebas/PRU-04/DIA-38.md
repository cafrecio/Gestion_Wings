# Día 38 — 16/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Mañana: [simulación, salida 0](evidencia-dias-34-40/38.0-manana-simular.txt) → [real, GUARDADO](evidencia-dias-34-40/38.0-manana-real.txt). [Reloj](evidencia-dias-34-40/38.0-reloj.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 38.1 | Lara cambia de grupo antes de rutina | cumple | Sin frecuencia: «No se guardó.» / «Debe seleccionar la frecuencia semanal del nuevo grupo.» Con 2 veces/semana — $43.000: «Alumno actualizado correctamente.» Ficha: Intermedias, plan actual $44.000, Al día; noviembre pagado $48.400 con recargo. Vista previa de diciembre sugiere $43.000, se canceló sin cobrar. | [Captura](evidencia-dias-34-40/38.1-lara-antes.jpg) · [texto](evidencia-dias-34-40/38.1-lara-antes.txt)<br>[Captura](evidencia-dias-34-40/38.1-lara-sin-frecuencia.jpg) · [texto](evidencia-dias-34-40/38.1-lara-sin-frecuencia.txt)<br>[Captura](evidencia-dias-34-40/38.1-lara-frecuencia.jpg) · [texto](evidencia-dias-34-40/38.1-lara-frecuencia.txt)<br>[Captura](evidencia-dias-34-40/38.1-lara-grupo-actualizado.jpg) · [texto](evidencia-dias-34-40/38.1-lara-grupo-actualizado.txt)<br>[Captura](evidencia-dias-34-40/38.1-lara-vigencias.jpg) · [texto](evidencia-dias-34-40/38.1-lara-vigencias.txt)<br>[Captura](evidencia-dias-34-40/38.1-lara-diciembre-previsto.jpg) · [texto](evidencia-dias-34-40/38.1-lara-diciembre-previsto.txt) |
| 38.2 | Benicio paga solo recargo | cumple | Cobrar: «Noviembre 2026 Orig: $28,000.00 · Pagado: $28,000.00», «Cuota pagada Recargo por mora — Noviembre 2026» $2.800. Efectivo; ficha Al día, pendiente $0. Historial «16/11/2026 · Recargo por mora $2,800.00». CUOTA-389: un solo renglón de recargo. | [Captura](evidencia-dias-34-40/38.2-benicio-solo-recargo.jpg) · [texto](evidencia-dias-34-40/38.2-benicio-solo-recargo.txt)<br>[Captura](evidencia-dias-34-40/38.2-benicio-pago.jpg) · [texto](evidencia-dias-34-40/38.2-benicio-pago.txt)<br>[Captura](evidencia-dias-34-40/38.2-benicio-aldia.jpg) · [texto](evidencia-dias-34-40/38.2-benicio-aldia.txt) |
| 38.3 | Lara presente en Intermedias | cumple | Lunes 16/11 17:00–18:00; 18 alumnos, 17 presentes. Quiroga, Lara, Al día, Presente marcado. | [Captura](evidencia-dias-34-40/38.3-intermedias-lara-presente.jpg) · [texto](evidencia-dias-34-40/38.3-intermedias-lara-presente.txt) |
| 38.4 | Excepciones de hoy y cinco morosos | cumple | Inicio conserva excepciones históricas, última del 12/11, ninguna del 16/11. Cobranza 5 morosos: Alma, Liam, Pilar, Facundo y Valentina; deuda $177.100. | [Captura](evidencia-dias-34-40/38.4-inicio.jpg) · [texto](evidencia-dias-34-40/38.4-inicio.txt)<br>[Captura](evidencia-dias-34-40/38.4-morosos.jpg) · [texto](evidencia-dias-34-40/38.4-morosos.txt) |
| 38.1b | Fechas exactas de vigencia del grupo y planes | no probado | No se expuso una tabla con los límites 16/11, 30/11 y 01/12. Se comprobaron los precios actual y sugerido de diciembre. | [Ficha](evidencia-dias-34-40/38.1-lara-vigencias.txt) · [Diciembre](evidencia-dias-34-40/38.1-lara-diciembre-previsto.txt) |

## Diferencias y alcance

**38.1-vigencias — no probado:** se vieron grupo actual, precio actual $44.000 y sugerencia de diciembre $43.000. La ficha no mostró una tabla con límites exactos 16/11, 30/11 y 01/12; esas fechas no se certifican por código ni base. La rutina, posterior al cambio UI, dio 27 alumnos en Principiantes y 18 en Intermedias. El guardado sin motivo de Santino/Iván fue por rutina, no se reprodujo por pantalla.

## Cierre de caja

[Simulación](evidencia-dias-34-40/38.5-noche-simular.txt) → [real](evidencia-dias-34-40/38.5-noche-real.txt), salida 0 en ambas. Esperado y contado $39.300. Caja validada y reflejada en cashflow.

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/38.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/38.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/38.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/38.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/38.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/38.6-cierre-inicio.txt)

Deuda **$177,100.00**; **106 activos**: 101 Al día, 0 En plazo, 5 Morosos, 0 Deudores.

Ingresos **$4,032,400.00**; egresos **$2,735,780.00**; resultado **$1,296,620.00**; disponible **$6,246,520.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $163,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $2,709,400.00 |
| MP | $3,316,070.75 |

**Sin asistencia: 0. Cajas por validar: 0.** Candela permanece inactiva con $33.000, separada de la deuda activa.

Continúa [Día 39](DIA-39.md), ya recorrido en esta tanda.

[Recibo Benicio](evidencia-dias-34-40/38.2-benicio-recibo.pdf) · [vista comprobada](evidencia-dias-34-40/38.2-benicio-recibo.png).
