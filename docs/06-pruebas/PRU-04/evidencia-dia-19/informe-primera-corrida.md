# PRU-04 · Día 19 · 2026-10-28

**Codex CyE · ejecución real 26/10/2026 · TEST / wingstest.**

**PARCIAL: caja de Sandra abierta; no se alcanzó el cierre del guion.**

## Fuente y alcance

[Guion inicial congelado antes de operar](evidencia-dia-19/guion-congelado-antes-de-operar.md), HEAD inicial `5b2f16a2`. Durante la ejecución `d66eba1e` amplió el guion. Se conserva también la [versión ampliada detectada](evidencia-dia-19/guion-ampliado-detectado-despues.md); no se atribuyen sus casos nuevos a la corrida anterior. El tablero compartido fue incorporado por `51c58857` mientras se trabajaba.

App remota leída antes de operar: `ec1b0c6e`, entorno `staging`. SSH `vps` respondió desde CyE. Se copió la rutina actual a `/tmp/wings-dias1922-20261026`, ejecutada como `wingstest`, `WINGS_APP=/home/wingstest/app`. Huella rutina SHA-256 `9376ea7fa1e71d50aeba6f4daabdee3a302d30be80c762fcf0c623857673ca70`. No despliegue ni escrituras por consola fuera de reloj y rutinas.

## Ejecución y resultados

| Caso | Quién | Tarea / esperado del guion ampliado | Resultado | Observado y evidencia |
|---|---|---|---|---|
| 19.1 | Sandra | Abre la caja y escribe **100000** por error; el turno anterior dejó $10.000. Después corrige a **10000**.<br>**Esperado:** Con 100000: «El efectivo recibido difiere en más de un 50% del cierre anterior ($10.000)…», y pide confirmar aparte. No lo confirma. Con 10000 abre sin aviso (CAJ-007, CAJ-005). | **Parcial** | Se abrió caja #36 con $10.000. El intento de escribir 100000 mediante fill quedó en 10000.00 por interacción con el campo. No se obtuvo el aviso >50%, no se reabrió ni rectificó. Límite de ejecución, no rechazo de Wings demostrado. [TXT](evidencia-dia-19/19.1-apertura-10000-aviso-no-probado.txt) · [JPG](evidencia-dia-19/19.1-apertura-10000-aviso-no-probado.jpg) |
| 19.2 | Dueño | En la ficha de **Agustín Acosta**, condona la cuota de reingreso de octubre de **$14.000**: primero sin motivo, después con «Reingreso generado por error: nunca dejó el club».<br>**Esperado:** Sin motivo no condona. Con motivo queda condonada, sin mover plata. Agustín sale de Cobranza y pasa a Al día (CBZ-007). | **Sí** | Sin motivo: validación HTML «Completa este campo». Con el motivo del guion se condonaron $14.000. Cobranza pasó de $248.000 a $234.000; 100 Al día y 0 En plazo. No se generó un movimiento de plata visible por esta acción. [TXT](evidencia-dia-19/19.2-despues-condonar.txt) · [JPG](evidencia-dia-19/19.2-despues-condonar.jpg) |
| 19.3 | Sandra | La mamá de **Joaquín Vega** (debe octubre, $44.000) trae **$50.000** y Sandra los carga. Ve el aviso y **cancela**. Después cobra **$44.000** en efectivo.<br>**Esperado:** Esperado según el catálogo: con $50.000 avisa que no se pueden cargar otros conceptos como si fueran cuota, con Continuar y Cancelar. Cancelar no cobra nada. Con $44.000, Joaquín pasa a **Al día** (COB-010). | **Parcial** | Con el importe de fila $50.000, el resumen mostró $44.000. No se envió ese formulario ni se probó el diálogo Continuar/Cancelar. Se sustituyó por $44.000 y se cobró una vez en efectivo (CUOTA-133). Deuda total $190.000, 101 Al día: Joaquín dejó la lista de morosos. Diferencia frente al catálogo en la presentación del exceso; confirmación no probada. [TXT](evidencia-dia-19/19.3-cobranza-190000.txt) · [JPG](evidencia-dia-19/19.3-cobranza-190000.jpg) |
| 19.4a | Sandra | Antes de corregir, carga un gasto **sin elegir rubro** y después otro con **importe 0**. No los completa.<br>**Esperado:** Ninguno se guarda; cada error se explica en castellano y conserva lo demás. El efectivo esperado no cambia (MOV-005). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. No se recorrió: agregado al guion después de comenzar. No se rehízo el día ni se repitió el reloj.  |
| 19.4 | Sandra | La limpieza costó **$3.500**, no $3.800: corrige el movimiento.<br>**Esperado:** Queda en $3.500 y el efectivo esperado se recalcula (MOV-007). | **Sí** | Movimiento #171 de Limpieza corregido $3.800 → $3.500 como exige este caso. Pantalla «Movimiento actualizado». Los gastos de la caja bajaron $6.300 → $6.000 y el esperado $267.700 → $268.000 (el primer esperado es cuenta inferida de los renglones, no captura anterior). [TXT](evidencia-dia-19/19.4-limpieza-3500-guardada.txt) · [JPG](evidencia-dia-19/19.4-limpieza-3500-guardada.jpg) |
| 19.5 | Sandra | El cuaderno de $2.500 lo pagó el dueño de su bolsillo: **elimina** el gasto de Librería.<br>**Esperado:** Desaparece de la caja, el esperado se recalcula y queda registro de qué se eliminó, quién y cuándo (MOV-008). | **No probado por la herramienta** | Dos intentos desde pantallas nuevas. Confirmación nativa falló: primero Emulation.setFocusEmulationEnabled; segundo No dialog is showing. No se hizo un tercer intento ni se usó consola. Librería #172 continúa en la caja, $2.500; 14 movimientos. Historial de eliminación no probado. [TXT](evidencia-dia-19/19.5-esperado-final.txt) · [JPG](evidencia-dia-19/19.5-esperado-final.jpg) |
| 19.6 | Hernán | Llueve y no viene nadie a Fútbol Principiantes 18–19: toma la lista con **los 23 ausentes**.<br>**Esperado:** Se guarda y la clase sale de «Sin asistencia» aunque no haya presentes (ASI-003). | **Sí** | Hernán guardó la #60 con 23 ausentes, botón exacto btn-guardar-asistencias una vez. Su listado cambió Pendiente → Sin presentes. El 31/10 la lista filtrada Sin asistencia no contiene esa clase. Observación adicional: el Inicio de Sandra la rotuló «Sin lista» aun después del guardado; no se corrigió. [TXT](evidencia-dia-19/19.6-listado-despues.txt) · [JPG](evidencia-dia-19/19.6-listado-despues.jpg) |
| 19.6b | Sandra | Antes de cerrar, mira **su caja del día**.<br>**Esperado:** Cada movimiento con hora, concepto, medio e importe; el eliminado ya no está. Efectivo esperado **$270.500**; Mercado Pago **$146.000** aparte, sin sumarse al cajón (CAJ-017). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. No se recorrió: agregado al guion después de comenzar. No se rehízo el día ni se repitió el reloj.  |
| 19.7a | Sandra | Empieza el cierre y, por error, pone **dejar de cambio $300.000** con contado $271.000.<br>**Esperado:** Esperado según el catálogo: no cierra y avisa que el cambio no puede superar lo contado; la caja sigue abierta (CAJ-021). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. No se recorrió: agregado al guion después de comenzar. No se rehízo el día ni se repitió el reloj.  |
| 19.7 | Sandra | Cierra contando **$271.000**, $500 más que lo esperado. Deja $10.000.<br>**Esperado:** Esperado **$270.500** (10.000 + 202.000 en efectivo de la rutina + 44.000 de Joaquín + 18.000 de la remera − 3.500). Muestra **sobrante $500** y deja cerrar. No crea ningún ingreso por la diferencia (CAJ-020). | **No ejecutado** | El formulario muestra esperado $268.000 frente a $270.500 del guion. No se ingresó ni confirmó el contado $271.000; no se cerró. La diferencia deriva de Librería pendiente de eliminar. Se aplicó la regla de no confirmar con total distinto. [TXT](evidencia-dia-19/19.7-esperado-distinto-no-cierra.txt) · [JPG](evidencia-dia-19/19.7-esperado-distinto-no-cierra.jpg) |

## Incidentes y diferencias

- 19.1: control automático rechazó inicialmente el intento de importe incorrecto. Tras leer el rechazo previo a `CajaOperativa::create` se permitió probar, pero el campo terminó en $10.000 y abrió. El aviso de $100.000 queda sin verificar. Desde 19.3 los importes se sustituyeron con Control+A, Backspace y teclado, comprobando la cifra antes del envío.
- 19.4: control automático confundió la edición prevista con reparar diferencias. Aportado el texto específico de 19.4, permitió guardar. No queda pendiente por ese control.
- 19.5: dos fallos de la confirmación nativa; [intento 1](evidencia-dia-19/19.5-dialogo-intento-1.txt) y [2](evidencia-dia-19/19.5-dialogo-intento-2.txt). Se conservó Librería. Esto resta $2.500 al efectivo esperado respecto del guion.
- Reportes e Inicio muestran ingresos $2.373.850,75 frente a $2.391.850,75 esperados: diferencia **$18.000**, además del gasto Librería pendiente. Caja contiene la remera de $18.000 y total ingresos $410.000; no se investigó por consola ni se atribuye una causa. Claude debe contrastar el tratamiento de Indumentaria en Reportes.
- 19.6: guardado y salida de pendientes comprobados. La etiqueta «Sin lista» en Inicio de Sandra contradice «Sin presentes» del listado de Hernán; observación para revisión, sin cambio de producto.
- Noche: se llamó una sola vez; no encontró caja CERRADA de Sandra y revirtió. Caja #36 permanece ABIERTA. No se validó una caja antigua como sustituto.

## Números observados antes de avanzar al día 20

| Dato | Esperado al cierre | Observado |
|---|---:|---:|
| Deuda | $190.000 | $190.000 |
| Activos / Al día / En plazo / Morosos / Deudores | 104 / 101 / 0 / 1 / 2 | 104 / 101 / 0 / 1 / 2 |
| Ingresos octubre | $2.391.850,75 | $2.373.850,75 |
| Egresos octubre | $254.650 | $257.150 |
| Resultado octubre | $2.137.200,75 | $2.116.700,75 |
| Disponible | $3.890.200,75 | $3.887.700,75 |
| Efectivo | $1.532.100 | $1.529.600 |
| MP | $2.092.550,75 | $2.092.550,75 |
| Galicia / Nación | $137.550 / $128.000 | $137.550 / $128.000 |
| Caja Sandra | CERRADA y validada | ABIERTA, inicial $10.000, esperado $268.000 |
| Por validar (dinero) | $0 | $404.000 |
| Sin asistencia (contador dashboard a las 15 h) | 0 | 0 |

[Inicio](evidencia-dia-19/19-cierre-dashboard-parcial.txt) · [Reportes](evidencia-dia-19/19-cierre-reportes-parcial.txt) · [Caja](evidencia-dia-19/19.7-esperado-distinto-no-cierra.txt). El cero del contador a las 15 h no prueba el estado de clases que todavía no terminaron.

## Salidas originales de consola

### 00-reloj-y-runtime.txt

[Archivo](evidencia-dia-19/00-reloj-y-runtime.txt)

```text
ab9862309b8d52661c7060789d94d456203fef8ae75041cc8cbd903d5b670139  /tmp/wings-dias1922-20261026/dia-19-manana.json
3ee2843bc9dc1119229088b1c96053f9b59d404af6a95e68d42593cf80cf14dc  /tmp/wings-dias1922-20261026/dia-19-noche.json
ccd1eeddf3838f19f5abefe5e1502528061bc9022e8734028c754bf8cce7652f  /tmp/wings-dias1922-20261026/dia-20-manana.json
4b8376e83afa8dab6705582ca902f8b76cf3aaa6828fc4f580eed7ce61ac92b2  /tmp/wings-dias1922-20261026/dia-20-noche.json
5bb31ffdfbc3f7214cc904df5f939d8b7be555370ad1d3a6d6034932b4d46d64  /tmp/wings-dias1922-20261026/dia-21-manana.json
0c8aa91a440010a6ff508dd6618956ac03933d489d900e72205ee4e59460d381  /tmp/wings-dias1922-20261026/dia-21-noche.json
99ba76197f2dee8962ea2ae896a8ff2112fac555ca8607eee35f9e8fce24691c  /tmp/wings-dias1922-20261026/dia-22-manana.json
e05eb69fc5ed41cf16ac44eab277df718b6fd51a952fbfe9b0625984a9d616a4  /tmp/wings-dias1922-20261026/dia-22-noche.json
9376ea7fa1e71d50aeba6f4daabdee3a302d30be80c762fcf0c623857673ca70  /tmp/wings-dias1922-20261026/rutina.php

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es miércoles 28 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 01-manana-simulacion.txt

[Archivo](evidencia-dia-19/01-manana-simulacion.txt)

```text
 1. Sandra Vidal cobra a Acosta, Victoria $30.000,00 por Efectivo — Pago registrado para Acosta, Victoria.
 2. Sandra Vidal cobra a Flores, Renata $30.000,00 por Mercado Pago — Pago registrado para Flores, Renata.
 3. Sandra Vidal cobra a Herrera, Alma $40.000,00 por Efectivo — Pago registrado para Herrera, Alma.
 4. Sandra Vidal cobra a Medina, Emilia $40.000,00 por Mercado Pago — Pago registrado para Medina, Emilia.
 5. Sandra Vidal cobra a Benitez, Paulina $43.000,00 por Efectivo — Pago registrado para Benitez, Paulina.
 6. Sandra Vidal cobra a Cabrera, Constanza $33.000,00 por Efectivo — Pago registrado para Cabrera, Constanza.
 7. Sandra Vidal cobra a Castro, Bruno $28.000,00 por Efectivo — Pago registrado para Castro, Bruno.
 8. Sandra Vidal cobra a Diaz, Thiago $38.000,00 por Mercado Pago — Pago registrado para Diaz, Thiago.
 9. Sandra Vidal cobra a Torres, Benicio $28.000,00 por Efectivo — Pago registrado para Torres, Benicio.
10. Sandra Vidal cobra a Rossi, Leon $38.000,00 por Mercado Pago — Pago registrado para Rossi, Leon.
11. Sandra Vidal Limpieza $3800 por Efectivo — Movimiento registrado.
12. Sandra Vidal Librería $2500 por Efectivo — Movimiento registrado.
13. Sandra Vidal Indumentaria institucional $18000 por Efectivo — Movimiento registrado.
14. Lucía Gaitán lista de la #10: 28 alumnos, 26 presentes — Asistencias guardadas.
Cobranza: $248.000,00 · {"AL_DIA":99,"EN_PLAZO":1,"MOROSO":2,"DEUDOR":2}
SIMULADO: no se guardó nada.
```

### 02-manana-real.txt

[Archivo](evidencia-dia-19/02-manana-real.txt)

```text
 1. Sandra Vidal cobra a Acosta, Victoria $30.000,00 por Efectivo — Pago registrado para Acosta, Victoria.
 2. Sandra Vidal cobra a Flores, Renata $30.000,00 por Mercado Pago — Pago registrado para Flores, Renata.
 3. Sandra Vidal cobra a Herrera, Alma $40.000,00 por Efectivo — Pago registrado para Herrera, Alma.
 4. Sandra Vidal cobra a Medina, Emilia $40.000,00 por Mercado Pago — Pago registrado para Medina, Emilia.
 5. Sandra Vidal cobra a Benitez, Paulina $43.000,00 por Efectivo — Pago registrado para Benitez, Paulina.
 6. Sandra Vidal cobra a Cabrera, Constanza $33.000,00 por Efectivo — Pago registrado para Cabrera, Constanza.
 7. Sandra Vidal cobra a Castro, Bruno $28.000,00 por Efectivo — Pago registrado para Castro, Bruno.
 8. Sandra Vidal cobra a Diaz, Thiago $38.000,00 por Mercado Pago — Pago registrado para Diaz, Thiago.
 9. Sandra Vidal cobra a Torres, Benicio $28.000,00 por Efectivo — Pago registrado para Torres, Benicio.
10. Sandra Vidal cobra a Rossi, Leon $38.000,00 por Mercado Pago — Pago registrado para Rossi, Leon.
11. Sandra Vidal Limpieza $3800 por Efectivo — Movimiento registrado.
12. Sandra Vidal Librería $2500 por Efectivo — Movimiento registrado.
13. Sandra Vidal Indumentaria institucional $18000 por Efectivo — Movimiento registrado.
14. Lucía Gaitán lista de la #10: 28 alumnos, 26 presentes — Asistencias guardadas.
Cobranza: $248.000,00 · {"AL_DIA":99,"EN_PLAZO":1,"MOROSO":2,"DEUDOR":2}
GUARDADO.
```

### 03-noche-real.txt

[Archivo](evidencia-dia-19/03-noche-real.txt)

```text
Paso 1 falló, no se guardó NADA del plan: No query results for model [App\Models\CajaOperativa].
```

## Continuidad

Último reloj ejecutado al terminar el paquete: **31/10/2026 15:00** (avanza solo hasta medianoche). No repetir relojes ni rutinas como si estos días no se hubieran intentado. Para continuar hay que resolver expresamente el cierre de Sandra y qué versión del guion retomar; no reconstruir datos ni borrar la evidencia.

T14 se entrega para revisión de Claude; no se cierra por el ejecutor. No se tocó `ESTADO-ACTUAL.md`, la tabla externa ni el producto. Sin suite, migraciones, cachés de vistas ni push por esta entrega documental.
