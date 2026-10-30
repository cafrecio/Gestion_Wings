# Día 34 — 12/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Mañana: [simulación, salida 0](evidencia-dias-34-40/34.0-reanudacion-manana-simular.txt) → [real, GUARDADO](evidencia-dias-34-40/34.0-reanudacion-manana-real.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 34.1 | Camila: dos fichas y recargos | cumple | Patín efectivo $49.500 ($45.000 + $4.500); Fútbol efectivo $30.800 ($28.000 + $2.800). Ambas fichas: «ESTADO DE COBRANZA Al día», recargo pendiente $0.00; historial del 12/11 con cada importe. Cobros desde cada ficha. | [Captura](evidencia-dias-34-40/34.1-camila-patin-cobrar.jpg) · [texto](evidencia-dias-34-40/34.1-camila-patin-cobrar.txt)<br>[Captura](evidencia-dias-34-40/34.1-camila-patin-aldia.jpg) · [texto](evidencia-dias-34-40/34.1-camila-patin-aldia.txt)<br>[Captura](evidencia-dias-34-40/34.1-camila-futbol-cobrar.jpg) · [texto](evidencia-dias-34-40/34.1-camila-futbol-cobrar.txt)<br>[Captura](evidencia-dias-34-40/34.1-camila-futbol-aldia.jpg) · [texto](evidencia-dias-34-40/34.1-camila-futbol-aldia.txt) |
| 34.2 | Benicio paga cuota y deja recargo | cumple | Importe que entrega 28000. «Pago registrado para Torres, Benicio.» sin panel. Ficha «Moroso Total adeudado: $2,800.00»; «Recargo por mora — Noviembre 2026», pendiente $2.800; historial de cuota $28.000. | [Captura](evidencia-dias-34-40/34.2-benicio-importe.jpg) · [texto](evidencia-dias-34-40/34.2-benicio-importe.txt)<br>[Captura](evidencia-dias-34-40/34.2-benicio-pago-sin-panel.jpg) · [texto](evidencia-dias-34-40/34.2-benicio-pago-sin-panel.txt)<br>[Captura](evidencia-dias-34-40/34.2-benicio-recargo-pendiente.jpg) · [texto](evidencia-dias-34-40/34.2-benicio-recargo-pendiente.txt) |
| 34.3 | Joaquín parcial sin/con motivo | cumple | Panel: «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en Inicio y en el resumen diario.» Botón con motivo vacío: no sale del panel ni cobra. Con «La mamá trae el resto el sábado»: «Pago registrado para Rodriguez, Joaquin.» $20.000. Moroso, cuota $15.000 y recargo $3.500 pendientes. | [Captura](evidencia-dias-34-40/34.3-parcial-sin-motivo.jpg) · [texto](evidencia-dias-34-40/34.3-parcial-sin-motivo.txt)<br>[Captura](evidencia-dias-34-40/34.3-parcial-con-motivo.jpg) · [texto](evidencia-dias-34-40/34.3-parcial-con-motivo.txt)<br>[Captura](evidencia-dias-34-40/34.3-parcial-pago.jpg) · [texto](evidencia-dias-34-40/34.3-parcial-pago.txt)<br>[Captura](evidencia-dias-34-40/34.3-joaquin-saldo.jpg) · [texto](evidencia-dias-34-40/34.3-joaquin-saldo.txt) |
| 34.4 | Inicio, filtro Moroso y Reportes | cumple | Inicio: «Rodriguez, Joaquin», «Cobro parcial», motivo exacto y «Sandra Vidal · 12/11/2026 09:22 · Pago #369». Filtro Moroso: 15 alumnos. Reportes: ingresos $3.666.900. | [Captura](evidencia-dias-34-40/34.4-inicio-parcial.jpg) · [texto](evidencia-dias-34-40/34.4-inicio-parcial.txt)<br>[Captura](evidencia-dias-34-40/34.4-morosos.jpg) · [texto](evidencia-dias-34-40/34.4-morosos.txt)<br>[Captura](evidencia-dias-34-40/34.4-reportes.jpg) · [texto](evidencia-dias-34-40/34.4-reportes.txt) |

## Diferencias y alcance

La primera simulación había fallado con salida 1: «Plan inválido: docs/06-pruebas/PRU-04/rutina/dia-34-manana.json». Carlos informó que faltaban los planes remotos y autorizó retomar una vez cargados (d488b3f5). Causa informada por Carlos, no inspeccionada por consola propia. La nueva simulación y ejecución real pasaron sin volver a fijar el reloj del 12/11 ni repetir días anteriores. Se conservan [error inicial](evidencia-dias-34-40/34.0-manana-simular.txt), [origen](evidencia-dias-34-40/34.0-origen-error.txt), [reloj inicial](evidencia-dias-34-40/34.0-reloj.txt) y capturas 00-* del punto de partida.

## Cierre de caja

[Simulación](evidencia-dias-34-40/34.5-noche-simular.txt) → [real](evidencia-dias-34-40/34.5-noche-real.txt), salida 0 en ambas. Esperado y contado $217.500. Caja validada y reflejada en cashflow.

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/34.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/34.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/34.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/34.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/34.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/34.6-cierre-inicio.txt)

Deuda **$542,600.00**; **106 activos**: 90 Al día, 0 En plazo, 15 Morosos, 1 Deudores.

Ingresos **$3,666,900.00**; egresos **$2,678,280.00**; resultado **$988,620.00**; disponible **$5,893,520.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $201,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $2,475,000.00 |
| MP | $3,159,470.75 |

**Sin asistencia: 0. Cajas por validar: 0.** Candela permanece inactiva con $33.000, separada de la deuda activa.

Continúa [Día 35](DIA-35.md), ya recorrido en esta tanda.
