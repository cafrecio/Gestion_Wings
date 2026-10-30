# Prueba por días — tanda 3, días 34 a 40

Codex CAB, 30/10/2026. **Siete jornadas recorridas y entregadas para revisión independiente de Claude.** No se cierra T14 ni se cambia el catálogo/tablero.

[Guion](../GUION-DIAS-34-40.md) · [Prompt](../PROMPT-CODEX-DIAS-34-40.md)

| Día | Informe | Deuda | Ingresos | Egresos | Disponible | Cajas pendientes |
|---|---|---:|---:|---:|---:|---:|
| 34 | [Informe](../DIA-34.md) | $542,600.00 | $3,666,900.00 | $2,678,280.00 | $5,893,520.75 | 0 |
| 35 | [Informe](../DIA-35.md) | $298,500.00 | $3,911,000.00 | $2,720,280.00 | $6,095,620.75 | 0 |
| 36 | [Informe](../DIA-36.md) | $218,400.00 | $3,991,100.00 | $2,723,780.00 | $6,217,220.75 | 1 |
| 37 | [Informe](../DIA-37.md) | $218,400.00 | $3,991,100.00 | $2,723,780.00 | $6,217,220.75 | 0 |
| 38 | [Informe](../DIA-38.md) | $177,100.00 | $4,032,400.00 | $2,735,780.00 | $6,246,520.75 | 0 |
| 39 | [Informe](../DIA-39.md) | $205,200.00 | $4,079,900.00 | $2,735,780.00 | $6,294,020.75 | 0 |
| 40 | [Informe](../DIA-40.md) | $205,200.00 | $4,094,900.00 | $2,738,780.00 | $6,358,020.75 | 0 |

Los siete cierres coinciden con el guion; las cifras provienen de Inicio, Cobranza y Reportes de cada día y se cruzaron entre sí. [Cierres transcritos y verificados](CIERRES-VERIFICADOS.json). Sin asistencia: 0 al final de cada jornada.

## Diferencias y límites

- **39.1, no cumple:** no apareció el aviso previo de segunda quincena / 70%; el alta sí generó $24.500 + inscripción $5.000 y quedó En plazo. Capturas antes y después conservadas.
- **40.2, no cumple:** Bautista aparece presente con etiqueta Al día, no Nuevo. La asistencia ya estaba guardada por la rutina.
- **39.3, incidencia:** primer guardado de Emma pidió esperar la cuota; tras esperar, segundo guardado correcto sin cambiar datos. Emma quedó sin pago, deuda $28.100, vence 24/11.
- **36, precisión del esperado:** $45.800 pendientes es efectivo; el total visible por validar era $121.600, sumando MP $75.800. Se validó el domingo por UI una sola vez, no por rutina.
- **No probado:** fechas exactas de vigencia de Lara no listadas en la ficha; texto breve «Clase cancelada.» no capturado; guardado UI sin motivo de Bautista no reproducido. Los estados y operaciones visibles sí están documentados. No se certifican internals por inferencia.

Las tablas tienen 25 pasos principales (23 cumple, 2 no cumple) y tres filas complementarias no probado. Son resultados de esta prueba humana, sujetos a revisión independiente, no cierres del catálogo.

## Continuación

Reloj dejado en **18/11/2026**. Caja #72 de Rocío VALIDADA, contado y cambio $7.000, entrega $0. Deuda $205.200; 108 activos: 102 Al día, 1 En plazo, 5 Morosos. Sin cajas pendientes ni clases sin asistencia. EFE $1.253.900, MP $3.383.070,75, BGA $1.663.050, BNA $58.000, Banco Nacion ahorro $0.

Conservar: Emma Villalba sin pago ni presentes, plazo hasta 24/11; cinco morosos; Candela inactiva con $33.000; Lara en Intermedias con $43.000 sugeridos para diciembre; Emma Fernández y Dante con diciembre pagado; Adultos del 19/11 cancelada con motivo. No repetir días 34–40 ni generar diciembre. Sigue la revisión de Claude y luego la tanda 4 por pedido de Carlos.

## Método y origen

Chrome y menús visibles, cuentas ficticias. Consola remota solo reloj y rutinas; mañana simulada antes de ejecutar y noches simuladas antes de ejecutar. Sin consultas directas a base, reparaciones, producto, vistas, catálogo/tablero, suite documental, despliegue ni push propios. Fechas de captura de archivos son reales del 30/10; las pantallas muestran fechas simuladas de noviembre. Los cierres llamados «noche» son el paso de cierre del guion, no una certificación de horario nocturno: salvo el sábado 13:30 no se fijó una segunda hora por día.

Se conserva el antecedente de detención del día 34: [error](34.0-manana-simular.txt), [origen de la transcripción](34.0-origen-error.txt), [reloj original](34.0-reloj.txt), control local y tres capturas 00-*. Carlos informó la causa y carga de rutinas; la nueva simulación fue correcta. No se volvió a fijar el reloj del 12/11 ni se repitieron cobros anteriores. El commit f294cf21 conserva el informe inicial íntegro.

Cada captura de Chrome tiene TXT con el árbol completo que registra también contenido fuera del encuadre. Las JPG se conservan sin modificar; los PNG de recibos son renderizados de PDF descargados desde los enlaces visibles: [Guadalupe](35.2-guadalupe-recibo.pdf), [Benicio](38.2-benicio-recibo.pdf), [Bautista](39.2-bautista-recibo.pdf). No son capturas de pantallas inventadas.

[Control documental](control-documental.json) · [Manifiesto SHA256](MANIFIESTO-SHA256.csv). Las huellas del manifiesto excluyen el propio manifiesto; los bytes originales iniciales fueron contrastados antes de actualizar el índice.
