# PRU-04 · Día 22 · entrega con diferencias

**Codex CyE · 26/10/2026 · TEST/wingstest · RECORRIDO, CAJA VALIDADA; cobertura limitada donde se indica.**

[Guion](GUION-DIAS-19-22.md) · [Autorización de Carlos](evidencia-dia-19/10-autorizacion-reanudacion.md) · [Primera corrida conservada](evidencia-dia-22/informe-primera-corrida.md).

El navegador volvió a responder tras «Fijate ahora». Se retomó20.8 sin repetir cobros, ventas ni mañana20. Último reloj del paquete: **31/10/2026 15:00**. No repetir operaciones ni reloj para revisar.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 22.1 · Pablo | **Berenice Sánchez** paga lo que debe, **$66.000 por MP**.<br>**Esperado:** Pasa a **Al día**. Cobranza queda sin deuda. | **Sí** | Cobro182 MP66.000 una vez, septiembre23.000/octubre43.000. Berenice Al día y Cobranza0:103activos/103AlDía. [TXT](evidencia-dia-22/22.1-berenice-al-dia.txt) · [TXT](evidencia-dia-22/22.1-cobranza0-103aldia.txt) |
| 22.2 · Hernán | Toma la lista de la **clase de recuperación de 14 a 15** (20.4): los 15 presentes.<br>**Esperado:** Se guarda. Si el 20.4 no se pudo hacer, se saltea. | **Sí** | Hernán ve y abre su recuperación82 del31/10 14–15. Guardado único15/15, confirmado tras recarga. Catorce exceden plan; se conservó opción EXTRA predeterminada (guion no indicó otro motivo), registrada para revisión. [TXT](evidencia-dia-22/22.2-hernan-ve-recuperacion82.txt) · [TXT](evidencia-dia-22/22.2-persistencia15.txt) |
| 22.3 · Dueño | Paga la **luz, $62.000** por Banco Galicia, desde Cashflow.<br>**Esperado:** Egreso directo a su nombre, con Editar y Eliminar. Galicia baja; el gasto del mes sube (CSH-002). | **Sí** | Movimiento directo135 Luz62.000 Galicia, Admin Prueba, Editar/Eliminar visibles. Galicia637.550→575.550; Dashboard egresos270.450→332.450. [TXT](evidencia-dia-22/22.3-luz135-62000-guardada.txt) · [TXT](evidencia-dia-22/22.3-despues-luz-dashboard.txt) |
| 22.4 · Dueño | La factura tenía descuento: corrige la luz a **$61.500**, motivo «Descuento por débito automático».<br>**Esperado:** Queda en $61.500. El detalle muestra el valor anterior, el nuevo, el motivo, quién y cuándo (CSH-007). | **Parcial** | Luz135 corregida62.000→61.500 con motivo Descuento por débito automático. Galicia576.050. Listado solo Editar/Eliminar, sin enlace a detalle/historial; anterior/nuevo/actor/fecha de corrección no comprobados por pantalla. [TXT](evidencia-dia-22/22.4-antes-guardar61500.txt) · [TXT](evidencia-dia-22/22.4-luz61500-confirmada.txt) |
| 22.5 · Dueño | Paga el **sueldo de Sandra**, $250.000 en efectivo, desde Cashflow (Sueldos → Op-Sandra Vidal).<br>**Esperado:** Gasto de Sueldos; el Reporte de Sueldos lo incluye. Sandra no lo ve en sus Movimientos (CSH-015). | **No** | No se pudo registrar sueldo250.000: Cashflow Nuevo EGRESO no ofrece rubro Sueldos ni subrubro Op-Sandra Vidal. No se creó rubro ni se usó otro. Reporte/invisibilidad Sandra sin gasto quedan sin probar; cierre esperado tendrá+250.000EFE y-250.000egresos respecto guion. Reporte Sueldos consultado: Sandra no figura; no demuestra la inclusión de un sueldo registrado. [TXT](evidencia-dia-22/22.5-sueldos-no-disponible.txt) · [TXT](evidencia-dia-22/37-reporte-sueldos-sin-sandra.txt) |
| 22.6 · Dueño | Liquidaciones → Nueva → **Lucía Gaitán, octubre**. La genera y después intenta **Cerrar**.<br>**Esperado:** Se genera: **17 clases × 1 h × $12.000 = $204.000**. No incluye la cancelada del 12/10 (LIQ-001). Al cerrar: «No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.» (LIQ-005). Queda abierta y sin pagar. | **No** | Formulario Nueva, Lucía/octubre2026, muestra20 clases liquidables; guion17×12.000=204.000. No muestra total previo. No se pulsó Generar por diferencia de cantidad; bloqueo de Cerrar antes01/11 queda sin probar. No se creó ni pagó liquidación. [TXT](evidencia-dia-22/22.6-lucia20clases-antes-generar.txt) |
| 22.7 · Pablo | Entra a **Liquidaciones** desde el menú o por la dirección `/liquidaciones`.<br>**Esperado:** Acceso restringido en castellano, sin importes, con vuelta a su Inicio (LIQ-016). | **Sí** | Pablo no tiene Liquidaciones en menú; ruta forzada muestra Sin permiso en castellano, sin importes. Volver regresa a Inicio. [TXT](evidencia-dia-22/22.7-pablo-menu-sin-liquidaciones.txt) · [TXT](evidencia-dia-22/22.7-liquidaciones-restringida.txt) |
| 22.8 · Dueño | Listado de **Liquidaciones**.<br>**Esperado:** Muestra la de Lucía: octubre, por hora, $204.000, Abierta (LIQ-018). | **No ejecutado** | Dependiente de22.6: no hay liquidación de Lucía creada porque formulario ofrece20clases contra17delguion. Listado0liquidaciones, sin cargo ni pago. [TXT](evidencia-dia-22/22.8-listado-sin-liquidacion.txt) |
| 22.9 · Dueño | **Después de la validación de la noche:** compara los saldos de Cashflow por medio con la cuenta del cierre de abajo, y abre el Reporte de Finanzas de octubre.<br>**Esperado:** Cada medio coincide con la cuenta y con el disponible de Reportes; ningún movimiento aparece dos veces (CSH-013). La liquidación abierta no suma como gasto. | **Parcial** | Tras noche22: Cashflow y Reportes coinciden por medio y disponible4.959.900,75; Galicia576.050/Nación128.000/MP2.638.550,75; EFE1.617.300 (+250.000 por sueldo no registrado). Reportes ingresos3.417.850,75(-121.000),egresos331.950(-250.000),resultado3.085.900,75(+129.000) respecto guion.138filas de octubre recorridas en5páginas,0repeticiones exactas texto+enlaces. No se generó liquidación por diferencia20/17, por pagar0; no prueba gasto de una liquidación abierta. [TXT](evidencia-dia-22/33-cierre-dashboard.txt) · [TXT](evidencia-dia-22/34-cashflow-octubre-final.txt) · [JSON](evidencia-dia-22/35-cashflow-filas-octubre.json) · [TXT](evidencia-dia-22/36-reportes-octubre-final.txt) |

## Cierre y diferencias

Caja42 VALIDADA: esperado/contado167.000, diferencia0, cambio10.000. Deuda0,103activos/103Al día. Disponible4.959.900,75: EFE1.617.300/MP2.638.550,75/Galicia576.050/Nación128.000. Por validar0 y sin asistencia0. Ingresos3.417.850,75/egresos331.950/resultado3.085.900,75. Frente al guion: ingresos−121.000, egresos−250.000, resultado+129.000, disponible/EFE+250.000. Los250.000 corresponden al sueldo de Sandra no registrado; diferencia de ingresos sin causa atribuida.

22.5 no ejecutado: el formulario EGRESO no ofrece Sueldos/Op-Sandra Vidal; no se creó rubro ni se usó concepto alternativo. Reporte Sueldos consultado sin Sandra; inclusión y ocultación del gasto no probadas.22.6: pantalla Lucía/octubre2026 ofrece20clases frente17; no muestra total previo. No se pulsó Generar. El rótulo20 no prueba por sí solo el cálculo final del período: revisión independiente pendiente. No se probaron Cerrar antes01/11 ni estado Abierta;22.8 salteado. Catorce excesos de plan en recuperación82 conservaron EXTRA predeterminado; revisar internamente.22.9:138filas de octubre en5páginas,0repeticiones exactas texto+enlaces; no auditoría de identidad interna. Cashflow y Reportes coinciden por medio/disponible; no se acredita gasto de una liquidación abierta porque no fue creada.

## Salidas de consola de la reanudación

### 10-reloj-reanudacion.txt

[Archivo](evidencia-dia-22/10-reloj-reanudacion.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es sábado 31 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 11-manana-simulacion-reanudacion.txt

[Archivo](evidencia-dia-22/11-manana-simulacion-reanudacion.txt)

```text
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Pablo Ledesma cobra a Perez, Mia $40.000,00 por Mercado Pago — Pago registrado para Perez, Mia.
 3. Pablo Ledesma cobra a Navarro, Malena $43.000,00 por Efectivo — Pago registrado para Navarro, Malena.
 4. Pablo Ledesma cobra a Diaz, Martina $50.000,00 por Efectivo — Pago registrado para Diaz, Martina.
 5. Pablo Ledesma cobra a Medina, Lautaro $28.000,00 por Mercado Pago — Pago registrado para Medina, Lautaro.
 6. Pablo Ledesma cobra a Ortiz, Tobias $48.000,00 por Efectivo — Pago registrado para Ortiz, Tobias.
 7. Pablo Ledesma Inscripciones $16000 por Efectivo — Movimiento registrado.
 8. Mariela Ocampo lista de la #50: 8 alumnos, 7 presentes — Asistencias guardadas.
 9. Mariela Ocampo lista de la #81: 8 alumnos, 6 presentes — Asistencias guardadas.
10. Hernán Quintana lista de la #77: 15 alumnos, 14 presentes — Asistencias guardadas.
Cobranza: $66.000,00 · {"AL_DIA":102,"EN_PLAZO":0,"MOROSO":0,"DEUDOR":1}
SIMULADO: no se guardó nada.
```

### 12-manana-real-reanudacion.txt

[Archivo](evidencia-dia-22/12-manana-real-reanudacion.txt)

```text
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Pablo Ledesma cobra a Perez, Mia $40.000,00 por Mercado Pago — Pago registrado para Perez, Mia.
 3. Pablo Ledesma cobra a Navarro, Malena $43.000,00 por Efectivo — Pago registrado para Navarro, Malena.
 4. Pablo Ledesma cobra a Diaz, Martina $50.000,00 por Efectivo — Pago registrado para Diaz, Martina.
 5. Pablo Ledesma cobra a Medina, Lautaro $28.000,00 por Mercado Pago — Pago registrado para Medina, Lautaro.
 6. Pablo Ledesma cobra a Ortiz, Tobias $48.000,00 por Efectivo — Pago registrado para Ortiz, Tobias.
 7. Pablo Ledesma Inscripciones $16000 por Efectivo — Movimiento registrado.
 8. Mariela Ocampo lista de la #50: 8 alumnos, 7 presentes — Asistencias guardadas.
 9. Mariela Ocampo lista de la #81: 8 alumnos, 6 presentes — Asistencias guardadas.
10. Hernán Quintana lista de la #77: 15 alumnos, 14 presentes — Asistencias guardadas.
Cobranza: $66.000,00 · {"AL_DIA":102,"EN_PLAZO":0,"MOROSO":0,"DEUDOR":1}
GUARDADO.
```

### 31-noche-reanudacion.txt

[Archivo](evidencia-dia-22/31-noche-reanudacion.txt)

```text
 1. Pablo Ledesma cierra la caja #42: esperado $167.000,00, contado $167.000,00 — Caja cerrada.
 2. Admin Prueba valida la caja #42 (7 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $0,00 · {"AL_DIA":103,"EN_PLAZO":0,"MOROSO":0,"DEUDOR":0}
GUARDADO.
```

## Entrega

T14 sigue en curso, tiene=Claude: revisión independiente, control interno y velocidad pendientes. No aceptación integral. Tabla externa, producto y ESTADO-ACTUAL.md intactos; sin suite, despliegue ni push. [Resumen](evidencia-dia-19/resumen-reanudacion.json).
