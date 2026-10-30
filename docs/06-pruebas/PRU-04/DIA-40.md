# Día 40 — 18/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Mañana: [simulación, salida 0](evidencia-dias-34-40/40.0-manana-simular.txt) → [real, GUARDADO](evidencia-dias-34-40/40.0-manana-real.txt). [Reloj](evidencia-dias-34-40/40.0-reloj.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 40.1 | Cancelar Adultos jueves 19 | cumple | 19/11 20:30–21:30, Patín Adultos, Ocampo. Motivo «Mariela avisó que no puede: turno médico», Cancelar esta, sin confirmación adicional. Ficha Cancelada, 0 presentes, «Esta clase está cancelada. No se pueden registrar asistencias.» Filtro Cancelada + Adultos muestra la clase y el motivo exacto. No se validó ni eliminó la clase. | [Captura](evidencia-dias-34-40/40.1-adultos-antes.jpg) · [texto](evidencia-dias-34-40/40.1-adultos-antes.txt)<br>[Captura](evidencia-dias-34-40/40.1-adultos-motivo.jpg) · [texto](evidencia-dias-34-40/40.1-adultos-motivo.txt)<br>[Captura](evidencia-dias-34-40/40.1-adultos-cancelada.jpg) · [texto](evidencia-dias-34-40/40.1-adultos-cancelada.txt)<br>[Captura](evidencia-dias-34-40/40.1-adultos-listado-cancelada.jpg) · [texto](evidencia-dias-34-40/40.1-adultos-listado-cancelada.txt) |
| 40.2 | Primera clase Bautista | no cumple | 18/11 18:00–19:00 Fútbol Principiantes: 25 alumnos, 24 presentes. Sosa, Bautista, Presente marcado, 1/2, DNI …222. Etiqueta «Al día», no «Nuevo» como decía el guion. En su tarjeta no aparece motivo de deuda. Asistencia ya guardada por rutina; no se volvió a tomar lista. | [Captura](evidencia-dias-34-40/40.2-bautista-presente.jpg) · [texto](evidencia-dias-34-40/40.2-bautista-presente.txt) |
| 40.3 | Dante adelanta diciembre | cumple | Al elegir diciembre sugiere $52.000 sin editar importe; plan actual $48.000. MP y aviso «Estás cobrando por adelantado diciembre 2026, que todavía no empezó. El importe queda fijo aunque después cambie la cuota.» «Pago registrado para Ramos, Dante.» Ficha Al día; 18/11 · Dic 2026 $52.000. | [Captura](evidencia-dias-34-40/40.3-dante-precio-sugerido.jpg) · [texto](evidencia-dias-34-40/40.3-dante-precio-sugerido.txt)<br>[Captura](evidencia-dias-34-40/40.3-dante-formulario.jpg) · [texto](evidencia-dias-34-40/40.3-dante-formulario.txt)<br>[Captura](evidencia-dias-34-40/40.3-dante-aviso.jpg) · [texto](evidencia-dias-34-40/40.3-dante-aviso.txt)<br>[Captura](evidencia-dias-34-40/40.3-dante-pago.jpg) · [texto](evidencia-dias-34-40/40.3-dante-pago.txt)<br>[Captura](evidencia-dias-34-40/40.3-dante-diciembre.jpg) · [texto](evidencia-dias-34-40/40.3-dante-diciembre.txt) |
| 40.4 | Finanzas noviembre | cumple | Ingresos $4.094.900; egresos $2.738.780; resultado $1.356.120; disponible $6.358.020,75; por cobrar $205.200. Antes de cierre incluye EFE −$3.000 y MP $67.000 por validar; después se confirman sin cambiar totales. | [Captura](evidencia-dias-34-40/40.4-reportes.jpg) · [texto](evidencia-dias-34-40/40.4-reportes.txt)<br>[Captura](evidencia-dias-34-40/40.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/40.6-cierre-reportes.txt) |
| 40.1b | Texto breve exacto de éxito de cancelación | no probado | No quedó observado/capturado «Clase cancelada.»; el estado y el motivo sí se comprobaron. | [Estado](evidencia-dias-34-40/40.1-adultos-cancelada.txt) · [Motivo](evidencia-dias-34-40/40.1-adultos-listado-cancelada.txt) |
| 40.2b | Ausencia de motivo al guardar la lista por UI | no probado | La lista ya se guardó por rutina; solo se consultó, sin repetir asistencias. | [Consulta](evidencia-dias-34-40/40.2-bautista-presente.txt) |

## Diferencias y alcance

**40.1-mensaje — no probado:** el texto breve exacto «Clase cancelada.» no quedó observado/capturado; sí estado Cancelada, motivo y 0 presentes. **40.2-motivo-al-guardar — no probado por pantalla:** la lista fue guardada por rutina y no se repitió; al consultar, Bautista no tiene motivo de deuda. Diferencia verificada: etiqueta Al día, no Nuevo.

## Cierre de caja

[Simulación](evidencia-dias-34-40/40.5-noche-simular.txt) → [real](evidencia-dias-34-40/40.5-noche-real.txt), salida 0 en ambas. Esperado y contado $7.000. Caja validada y reflejada en cashflow.
Rocío deja $7.000 como cambio y entrega $0: [Resumen final](evidencia-dias-34-40/40.5-caja-validada-cambio.jpg) · [texto](evidencia-dias-34-40/40.5-caja-validada-cambio.txt).

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/40.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/40.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/40.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/40.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/40.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/40.6-cierre-inicio.txt)

Deuda **$205,200.00**; **108 activos**: 102 Al día, 1 En plazo, 5 Morosos, 0 Deudores.

Ingresos **$4,094,900.00**; egresos **$2,738,780.00**; resultado **$1,356,120.00**; disponible **$6,358,020.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $1,663,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $1,253,900.00 |
| MP | $3,383,070.75 |

**Sin asistencia: 0. Cajas por validar: 0.** Candela permanece inactiva con $33.000, separada de la deuda activa.

No avanzar al día 41 ni generar diciembre: tanda entregada para revisión independiente.
