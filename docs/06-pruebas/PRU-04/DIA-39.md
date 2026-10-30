# Día 39 — 17/11/2026

Codex CAB, 30/10/2026. Recorrido humano por Chrome en test.gestionar-te.com.ar / wingstest ficticia. [Guion](GUION-DIAS-34-40.md) · [Índice de evidencia](evidencia-dias-34-40/LEEME.md). Entrega para revisión independiente de Claude; sin cierre propio de T14.

Mañana: [simulación, salida 0](evidencia-dias-34-40/39.0-manana-simular.txt) → [real, GUARDADO](evidencia-dias-34-40/39.0-manana-real.txt). [Reloj](evidencia-dias-34-40/39.0-reloj.txt).

| # | Caso | Resultado | Lo observado en pantalla | Evidencia |
|---|---|---|---|---|
| 39.1 | Alta Bautista y segunda quincena | no cumple | Datos del guion, celular igual al de tutora, ingreso 17/11. Antes de Guardar no se vio el aviso «Segunda quincena» / 70% / $24.500. Sí se creó: «Alumno creado correctamente.» Ficha En plazo, «6 días de gracia», «Vence 24/11/2026»; cuota $24.500 + inscripción $5.000 = $29.500. Diferencia del aviso previo. | [Captura](evidencia-dias-34-40/39.1-bautista-alta-previa.jpg) · [texto](evidencia-dias-34-40/39.1-bautista-alta-previa.txt)<br>[Captura](evidencia-dias-34-40/39.1-bautista-previo-sin-aviso.jpg) · [texto](evidencia-dias-34-40/39.1-bautista-previo-sin-aviso.txt)<br>[Captura](evidencia-dias-34-40/39.1-bautista-creado.jpg) · [texto](evidencia-dias-34-40/39.1-bautista-creado.txt)<br>[Captura](evidencia-dias-34-40/39.1-bautista-deuda.jpg) · [texto](evidencia-dias-34-40/39.1-bautista-deuda.txt) |
| 39.2 | Primer cobro Bautista | cumple | Efectivo $29.500. «Pago registrado para Sosa, Bautista.»; Al día. CUOTA-390 con dos renglones: Noviembre 2026 $24.500 e Inscripción al club — por única vez $5.000. | [Captura](evidencia-dias-34-40/39.2-bautista-total.jpg) · [texto](evidencia-dias-34-40/39.2-bautista-total.txt)<br>[Captura](evidencia-dias-34-40/39.2-bautista-pago.jpg) · [texto](evidencia-dias-34-40/39.2-bautista-pago.txt)<br>[Captura](evidencia-dias-34-40/39.2-bautista-aldia.jpg) · [texto](evidencia-dias-34-40/39.2-bautista-aldia.txt) |
| 39.3 | Alta Emma Villalba sin pago | cumple | Primer Guardar: «No se guardó.» / «Fecha de alta: Esperá a verificar la cuota del alta.» Tras esperar y Tab, segundo Guardar crea sin cambiar datos: «Alumno creado correctamente.» En plazo, «6 días de gracia», «Vence 24/11/2026»; cuota $23.100 + inscripción $5.000 = $28.100. Sin pagos ni asistencias. Se registra la incidencia de espera. | [Captura](evidencia-dias-34-40/39.3-emma-alta-previa.jpg) · [texto](evidencia-dias-34-40/39.3-emma-alta-previa.txt)<br>[Captura](evidencia-dias-34-40/39.3-emma-espera-cuota.jpg) · [texto](evidencia-dias-34-40/39.3-emma-espera-cuota.txt)<br>[Captura](evidencia-dias-34-40/39.3-emma-creada.jpg) · [texto](evidencia-dias-34-40/39.3-emma-creada.txt)<br>[Captura](evidencia-dias-34-40/39.3-emma-enplazo.jpg) · [texto](evidencia-dias-34-40/39.3-emma-enplazo.txt)<br>[Captura](evidencia-dias-34-40/39.3-emma-cobranza.jpg) · [texto](evidencia-dias-34-40/39.3-emma-cobranza.txt) |
| 39.4 | Transferencia Efectivo a Galicia | cumple | EFE → BGA $1.500.000, «Depósito en el Galicia». «Transferencia #4 registrada: salida y entrada guardadas.» Dos renglones opuestos. El aviso breve consta en [transcripción de la observación inmediata](evidencia-dias-34-40/39.4-confirmacion-transcripta.txt); ya había desaparecido al guardar la captura. Antes/después: ingresos $4.079.900, egresos $2.735.780, resultado $1.344.120. EFE $2.756.900 → $1.256.900; BGA $163.050 → $1.663.050. | [Captura](evidencia-dias-34-40/39.4-antes-transferencia.jpg) · [texto](evidencia-dias-34-40/39.4-antes-transferencia.txt)<br>[Captura](evidencia-dias-34-40/39.4-transferencia-formulario.jpg) · [texto](evidencia-dias-34-40/39.4-transferencia-formulario.txt)<br>[Captura](evidencia-dias-34-40/39.4-transferencia-guardada.jpg) · [texto](evidencia-dias-34-40/39.4-transferencia-guardada.txt)<br>[Captura](evidencia-dias-34-40/39.4-despues-transferencia.jpg) · [texto](evidencia-dias-34-40/39.4-despues-transferencia.txt) |

## Diferencias y alcance

Diferencias conservadas: falta del aviso previo de segunda quincena (39.1) y espera requerida por la cuota en el primer intento de Emma (39.3). Los importes finales coinciden. «6 días de gracia» se transcribe tal cual; vencimiento visible 24/11. No se repararon datos.

## Cierre de caja

[Simulación](evidencia-dias-34-40/39.5-noche-simular.txt) → [real](evidencia-dias-34-40/39.5-noche-real.txt), salida 0 en ambas. Esperado y contado $57.500. Caja validada y reflejada en cashflow.

## Cifras finales de pantalla

[Cobranza](evidencia-dias-34-40/39.6-cierre-cobranza.jpg) · [texto](evidencia-dias-34-40/39.6-cierre-cobranza.txt) · [Reportes](evidencia-dias-34-40/39.6-cierre-reportes.jpg) · [texto](evidencia-dias-34-40/39.6-cierre-reportes.txt) · [Inicio / sin asistencia](evidencia-dias-34-40/39.6-cierre-inicio.jpg) · [texto](evidencia-dias-34-40/39.6-cierre-inicio.txt)

Deuda **$205,200.00**; **108 activos**: 102 Al día, 1 En plazo, 5 Morosos, 0 Deudores.

Ingresos **$4,079,900.00**; egresos **$2,735,780.00**; resultado **$1,344,120.00**; disponible **$6,294,020.75**. Formato de importes transcripto de Wings.

| Medio | Disponible |
|---|---:|
| BGA | $1,663,050.00 |
| BNA | $58,000.00 |
| Banco Nacion ahorro | $0.00 |
| EFE | $1,256,900.00 |
| MP | $3,316,070.75 |

**Sin asistencia: 0. Cajas por validar: 0.** Candela permanece inactiva con $33.000, separada de la deuda activa.

Continúa [Día 40](DIA-40.md), ya recorrido en esta tanda.

[Recibo Bautista](evidencia-dias-34-40/39.2-bautista-recibo.pdf) · [vista comprobada](evidencia-dias-34-40/39.2-bautista-recibo.png).
