# PRU-04 · Día22 · reanudación autorizada

**Codex CyE · 26/10/2026 · TEST/wingstest · PENDIENTE DE REANUDAR.**

## Autorización y trazabilidad

[Carlos autorizó](evidencia-dia-19/10-autorizacion-reanudacion.md) volver al28/10, repetir19.5 con confirmación manual suya, completar19.4a/19.6b/19.7a, cerrar y repetir noche19 y controles20–22. No se repiten cobros ni mañana19. [Primera corrida conservada](evidencia-dia-22/informe-primera-corrida.md). [Guion vigente](GUION-DIAS-19-22.md).

Último reloj de esta reanudación: **29/10/2026 15:00**. La primera corrida había llegado al31/10, con los días20–22 sin operar. No confundir ambos intentos.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 22.1 · Pablo | **Berenice Sánchez** paga lo que debe, **$66.000 por MP**.<br>**Esperado:** Pasa a **Al día**. Cobranza queda sin deuda. | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.2 · Hernán | Toma la lista de la **clase de recuperación de 14 a 15** (20.4): los 15 presentes.<br>**Esperado:** Se guarda. Si el 20.4 no se pudo hacer, se saltea. | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.3 · Dueño | Paga la **luz, $62.000** por Banco Galicia, desde Cashflow.<br>**Esperado:** Egreso directo a su nombre, con Editar y Eliminar. Galicia baja; el gasto del mes sube (CSH-002). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.4 · Dueño | La factura tenía descuento: corrige la luz a **$61.500**, motivo «Descuento por débito automático».<br>**Esperado:** Queda en $61.500. El detalle muestra el valor anterior, el nuevo, el motivo, quién y cuándo (CSH-007). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.5 · Dueño | Paga el **sueldo de Sandra**, $250.000 en efectivo, desde Cashflow (Sueldos → Op-Sandra Vidal).<br>**Esperado:** Gasto de Sueldos; el Reporte de Sueldos lo incluye. Sandra no lo ve en sus Movimientos (CSH-015). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.6 · Dueño | Liquidaciones → Nueva → **Lucía Gaitán, octubre**. La genera y después intenta **Cerrar**.<br>**Esperado:** Se genera: **17 clases × 1 h × $12.000 = $204.000**. No incluye la cancelada del 12/10 (LIQ-001). Al cerrar: «No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.» (LIQ-005). Queda abierta y sin pagar. | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.7 · Pablo | Entra a **Liquidaciones** desde el menú o por la dirección `/liquidaciones`.<br>**Esperado:** Acceso restringido en castellano, sin importes, con vuelta a su Inicio (LIQ-016). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.8 · Dueño | Listado de **Liquidaciones**.<br>**Esperado:** Muestra la de Lucía: octubre, por hora, $204.000, Abierta (LIQ-018). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.9 · Dueño | **Después de la validación de la noche:** compara los saldos de Cashflow por medio con la cuenta del cierre de abajo, y abre el Reporte de Finanzas de octubre.<br>**Esperado:** Cada medio coincide con la cuenta y con el disponible de Reportes; ningún movimiento aparece dos veces (CSH-013). La liquidación abierta no suma como gasto. | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |

## Salidas de consola

**Estos dos archivos corresponden a la primera corrida fallida; no se repitieron aún en la reanudación.**

### 00-reloj.txt

[Archivo](evidencia-dia-22/00-reloj.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es sábado 31 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 01-manana-simulacion.txt

[Archivo](evidencia-dia-22/01-manana-simulacion.txt)

```text
Paso 1 falló, no se guardó NADA del plan: Hay un turno abierto. Cerralo antes de abrir otro.
```

## Entrega y límites

Objetivo pendiente, no cierre deT14. Tabla externa y ESTADO-ACTUAL.md intactos. Sin producto ni despliegue. Se conserva evidencia del intento anterior; el control documental independiente anterior vale solo para febabcd6. Sin push.
