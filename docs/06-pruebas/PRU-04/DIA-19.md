# PRU-04 · Día19 · reanudación autorizada

**Codex CyE · 26/10/2026 · TEST/wingstest · REANUDADO Y CERRADO; pendientes parciales de cobertura.**

## Autorización y trazabilidad

[Carlos autorizó](evidencia-dia-19/10-autorizacion-reanudacion.md) volver al28/10, repetir19.5 con confirmación manual suya, completar19.4a/19.6b/19.7a, cerrar y repetir noche19 y controles20–22. No se repiten cobros ni mañana19. [Primera corrida conservada](evidencia-dia-19/informe-primera-corrida.md). [Guion vigente](GUION-DIAS-19-22.md).

Último reloj del paquete reanudado: **31/10/2026 15:00**. El del día19 se inició en28/10 15:00. La primera corrida había llegado al31/10, con los días20–22 sin operar. No confundir ambos intentos.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 19.1 · Sandra | Abre la caja y escribe **100000** por error; el turno anterior dejó $10.000. Después corrige a **10000**.<br>**Esperado:** Con 100000: «El efectivo recibido difiere en más de un 50% del cierre anterior ($10.000)…», y pide confirmar aparte. No lo confirma. Con 10000 abre sin aviso (CAJ-007, CAJ-005). | **Parcial** | Se abrió caja #36 con $10.000. El intento de escribir 100000 mediante fill quedó en 10000.00 por interacción con el campo. No se obtuvo el aviso >50%, no se reabrió ni rectificó. Límite de ejecución, no rechazo de Wings demostrado. [TXT](evidencia-dia-19/19.1-apertura-10000-aviso-no-probado.txt) · [JPG](evidencia-dia-19/19.1-apertura-10000-aviso-no-probado.jpg) |
| 19.2 · Dueño | En la ficha de **Agustín Acosta**, condona la cuota de reingreso de octubre de **$14.000**: primero sin motivo, después con «Reingreso generado por error: nunca dejó el club».<br>**Esperado:** Sin motivo no condona. Con motivo queda condonada, sin mover plata. Agustín sale de Cobranza y pasa a Al día (CBZ-007). | **Sí** | Sin motivo: validación HTML «Completa este campo». Con el motivo del guion se condonaron $14.000. Cobranza pasó de $248.000 a $234.000; 100 Al día y 0 En plazo. No se generó un movimiento de plata visible por esta acción. [TXT](evidencia-dia-19/19.2-despues-condonar.txt) · [JPG](evidencia-dia-19/19.2-despues-condonar.jpg) |
| 19.3 · Sandra | La mamá de **Joaquín Vega** (debe octubre, $44.000) trae **$50.000** y Sandra los carga. Ve el aviso y **cancela**. Después cobra **$44.000** en efectivo.<br>**Esperado:** Esperado según el catálogo: con $50.000 avisa que no se pueden cargar otros conceptos como si fueran cuota, con Continuar y Cancelar. Cancelar no cobra nada. Con $44.000, Joaquín pasa a **Al día** (COB-010). | **Parcial** | Con el importe de fila $50.000, el resumen mostró $44.000. No se envió ese formulario ni se probó el diálogo Continuar/Cancelar. Se sustituyó por $44.000 y se cobró una vez en efectivo (CUOTA-133). Deuda total $190.000, 101 Al día: Joaquín dejó la lista de morosos. Diferencia frente al catálogo en la presentación del exceso; confirmación no probada. [TXT](evidencia-dia-19/19.3-cobranza-190000.txt) · [JPG](evidencia-dia-19/19.3-cobranza-190000.jpg) |
| 19.4a · Sandra | Antes de corregir, carga un gasto **sin elegir rubro** y después otro con **importe 0**. No los completa.<br>**Esperado:** Ninguno se guarda; cada error se explica en castellano y conserva lo demás. El efectivo esperado no cambia (MOV-005). | **Sí** | Sin rubro: Selecciona un elemento de la lista. Con monto0: El importe debe ser mayor que cero. Conserva campos; 13movimientos y esperado270500 sin cambios. [TXT](evidencia-dia-19/15-19.4a-sin-rubro-rechazado.txt) · [TXT](evidencia-dia-19/16-19.4a-cero-rechazado.txt) |
| 19.4 · Sandra | La limpieza costó **$3.500**, no $3.800: corrige el movimiento.<br>**Esperado:** Queda en $3.500 y el efectivo esperado se recalcula (MOV-007). | **Sí** | Movimiento #171 de Limpieza corregido $3.800 → $3.500 como exige este caso. Pantalla «Movimiento actualizado». Los gastos de la caja bajaron $6.300 → $6.000 y el esperado $267.700 → $268.000 (el primer esperado es cuenta inferida de los renglones, no captura anterior). [TXT](evidencia-dia-19/19.4-limpieza-3500-guardada.txt) · [JPG](evidencia-dia-19/19.4-limpieza-3500-guardada.jpg) |
| 19.5 · Sandra | El cuaderno de $2.500 lo pagó el dueño de su bolsillo: **elimina** el gasto de Librería.<br>**Esperado:** Desaparece de la caja, el esperado se recalcula y queda registro de qué se eliminó, quién y cuándo (MOV-008). | **Parcial** | Carlos confirmó manualmente; Librería172 desapareció y gastos3500/13movimientos. No se verificó por pantalla auditoría de quién/cuándo eliminó. [TXT](evidencia-dia-19/13-libreria-eliminada-confirmo-carlos.txt) · [MD](evidencia-dia-19/13-confirmacion-carlos.md) |
| 19.6 · Hernán | Llueve y no viene nadie a Fútbol Principiantes 18–19: toma la lista con **los 23 ausentes**.<br>**Esperado:** Se guarda y la clase sale de «Sin asistencia» aunque no haya presentes (ASI-003). | **Sí** | Hernán guardó la #60 con 23 ausentes, botón exacto btn-guardar-asistencias una vez. Su listado cambió Pendiente → Sin presentes. El 31/10 la lista filtrada Sin asistencia no contiene esa clase. Observación adicional: el Inicio de Sandra la rotuló «Sin lista» aun después del guardado; no se corrigió. [TXT](evidencia-dia-19/19.6-listado-despues.txt) · [JPG](evidencia-dia-19/19.6-listado-despues.jpg) |
| 19.6b · Sandra | Antes de cerrar, mira **su caja del día**.<br>**Esperado:** Cada movimiento con hora, concepto, medio e importe; el eliminado ya no está. Efectivo esperado **$270.500**; Mercado Pago **$146.000** aparte, sin sumarse al cajón (CAJ-017). | **Parcial** | Esperado270500, MP146000 separado y Librería ausente. El detalle muestra fecha, concepto, medio e importe, pero no hora por movimiento. [TXT](evidencia-dia-19/13-libreria-eliminada-confirmo-carlos.txt) · [TXT](evidencia-dia-19/17-19.6b-caja-270500-mp146000.txt) |
| 19.7a · Sandra | Empieza el cierre y, por error, pone **dejar de cambio $300.000** con contado $271.000.<br>**Esperado:** Esperado según el catálogo: no cierra y avisa que el cambio no puede superar lo contado; la caja sigue abierta (CAJ-021). | **Sí** | Contado271000/cambio300000 rechazado: El cambio no puede superar el efectivo contado. Caja siguió abierta. [TXT](evidencia-dia-19/18-19.7a-cambio-mayor-rechazado.txt) |
| 19.7 · Sandra | Cierra contando **$271.000**, $500 más que lo esperado. Deja $10.000.<br>**Esperado:** Esperado **$270.500** (10.000 + 202.000 en efectivo de la rutina + 44.000 de Joaquín + 18.000 de la remera − 3.500). Muestra **sobrante $500** y deja cerrar. No crea ningún ingreso por la diferencia (CAJ-020). | **Sí** | Contado271000, esperado270500, sobrante500, cambio10000. CERRADA y luego VALIDADA, ingresos caja410000; no ingreso extra por diferencia. [TXT](evidencia-dia-19/21-caja-validada-sobrante500.txt) · [TXT](evidencia-dia-19/20-noche-reanudacion.txt) |

## Cierre verificado tras la reanudación

- Carlos respondió «Ya lo confirmé»; Codex comprobó Librería ausente,13movimientos y gastos3500. [Constancia](evidencia-dia-19/13-confirmacion-carlos.md). No se aplicó el escenario alternativo de conservar Librería.
- Caja36 VALIDADA: esperado270500, contado271000, sobrante500, cambio10000, retiro261000. Ingresos410000, gastos3500. La diferencia no generó ingreso.
- Deuda190000;104activos:101Al día,0En plazo,1Moroso,2Deudores.
- Disponible3.890.200,75: efectivo1.532.100, MP2.092.550,75, Galicia137550, Nación128000; por validar0. Coincide con el guion.
- Inicio muestra ingresos2.373.850,75 (guion2.391.850,75), egresos254650 y resultado2.119.200,75 (guion2.137.200,75). Persiste diferencia18000 en ingresos/resultado; no se reparó ni se atribuye causa. [Pantalla](evidencia-dia-19/22-cierre-reanudado-dashboard.txt).
- 19.6b muestra fecha pero no hora por movimiento. Auditoría del borrado pendiente de revisión interna por Claude; confirmación manual de Carlos registrada.

## Salidas de consola

### 11-reloj-reanudacion.txt

[Archivo](evidencia-dia-19/11-reloj-reanudacion.txt)

```text
srv1753195.hstgr.cloud

   INFO  The application environment is [staging].

9376ea7fa1e71d50aeba6f4daabdee3a302d30be80c762fcf0c623857673ca70  /tmp/wings-dias1922-20261026/rutina.php

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es miércoles 28 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 20-noche-reanudacion.txt

[Archivo](evidencia-dia-19/20-noche-reanudacion.txt)

```text
 1. Admin Prueba valida la caja #36 (13 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $190.000,00 · {"AL_DIA":101,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":2}
GUARDADO.
```

## Entrega y límites

Paquete recorrido hasta noche22 con diferencias en22.5/22.6; no cierre deT14. Tabla externa y ESTADO-ACTUAL.md intactos. Sin producto ni despliegue. Se conserva evidencia del intento anterior; el control documental independiente anterior vale solo para febabcd6. Sin push.
