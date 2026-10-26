# PRU-04 · Día 21 · 2026-10-30

**Codex CyE · ejecución real 26/10/2026 · TEST / wingstest.**

**NO OPERADO: falló el control previo; no hay casos aprobados de este día.**

## Fuente y alcance

[Guion inicial congelado antes de operar](evidencia-dia-19/guion-congelado-antes-de-operar.md), HEAD inicial `5b2f16a2`. Durante la ejecución `d66eba1e` amplió el guion. Se conserva también la [versión ampliada detectada](evidencia-dia-19/guion-ampliado-detectado-despues.md); no se atribuyen sus casos nuevos a la corrida anterior. El tablero compartido fue incorporado por `51c58857` mientras se trabajaba.

App remota leída antes de operar: `ec1b0c6e`, entorno `staging`. SSH `vps` respondió desde CyE. Se copió la rutina actual a `/tmp/wings-dias1922-20261026`, ejecutada como `wingstest`, `WINGS_APP=/home/wingstest/app`. Huella rutina SHA-256 `9376ea7fa1e71d50aeba6f4daabdee3a302d30be80c762fcf0c623857673ca70`. No despliegue ni escrituras por consola fuera de reloj y rutinas.

## Ejecución y resultados

| Caso | Quién | Tarea / esperado del guion ampliado | Resultado | Observado y evidencia |
|---|---|---|---|---|
| 21.1 | Verónica | Toma la lista de **Patín Intermedias 16–17** (#22): 18 alumnas; todas presentes menos **Berenice Sánchez**.<br>**Esperado:** Esperado según el catálogo: la lista indica que Berenice, que es Deudor, no puede entrar por deuda, sin mostrar importes (ASI-013). Guarda con 17 presentes. | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.2 | Pablo | **Berenice Sánchez** (debe septiembre y octubre, $86.000) paga **$20.000** de septiembre en efectivo. Primero sin motivo; si lo pide, «Paga el resto el sábado».<br>**Esperado:** Esperado según el catálogo: sin motivo no cobra (COB-009). Con motivo, septiembre queda con saldo $23.000, Berenice sigue Deudor y el total adeudado baja $20.000 (COB-008). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.3 | Pablo | **Felipe Sosa** paga agosto y octubre, **$76.000** en efectivo, y Pablo descarga el recibo.<br>**Esperado:** Pasa a **Al día**. El recibo detalla agosto $38.000 y octubre $38.000, total $76.000 (REC-002). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.4 | Pablo | **Santino Romero** deja el club debiendo octubre ($28.000). Pablo lo da de baja **con el interruptor del listado de Alumnos**.<br>**Esperado:** Queda Inactivo y su cuota pendiente sigue a la vista en la ficha (ALU-025). Sale de Cobranza: 103 activos y el total adeudado baja $28.000. Ya no aparece en el buscador de Cobrar (ALU-017). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.5 | Dueño | Lleva **$500.000** del efectivo al **Banco Galicia** (Cashflow → Transferir).<br>**Esperado:** Un solo formulario: salida Efectivo, destino Galicia. Efectivo baja y Galicia sube $500.000; los ingresos y gastos del mes no cambian (CSH-018). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.6 | Rocío | Pasa a mediodía y, desde Movimientos, intenta **editar el arreglo de $12.000** de la caja de Pablo (y, si no hay botón, entrando a su dirección de edición).<br>**Esperado:** Acceso restringido; la caja de Pablo no cambia (MOV-016). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.7 | Pablo | En **Movimientos**, busca el cobro de Joaquín que hizo Sandra el 28/10.<br>**Esperado:** Lo encuentra, con quién lo cobró. No ve la transferencia ni los gastos del dueño (MOV-012). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.8 | Pablo | Antes de que corra el cierre de la noche, mira su caja.<br>**Esperado:** El esperado, **$270.000**, cuenta solo el efectivo; los $171.000 de Mercado Pago figuran aparte (CAJ-036). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |

## Control previo fallido

Reloj `2026-10-30 15:00`, una sola vez. Simulación `dia-21-manana.json --simular`, una sola vez, salida 1 en paso 1: «Hay un turno abierto. Cerralo antes de abrir otro.» Se revirtió. No se ejecutó la rutina real de mañana, la de noche ni ninguno de los casos de este día. No se reintentó ni se reparó el turno anterior. Los cierres esperados del guion **no son resultados observados**.

La causa de continuidad es la caja #36 de Sandra, día 19, que quedó abierta al no poder eliminar Librería por Chrome y diferir el esperado del guion. No es una vulnerabilidad ni un fallo nuevo de la apertura: es su rechazo previsto de un segundo turno.

## Salidas originales de consola

### 00-reloj.txt

[Archivo](evidencia-dia-21/00-reloj.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es viernes 30 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 01-manana-simulacion.txt

[Archivo](evidencia-dia-21/01-manana-simulacion.txt)

```text
Paso 1 falló, no se guardó NADA del plan: Hay un turno abierto. Cerralo antes de abrir otro.
```

## Continuidad

Último reloj ejecutado al terminar el paquete: **31/10/2026 15:00** (avanza solo hasta medianoche). No repetir relojes ni rutinas como si estos días no se hubieran intentado. Para continuar hay que resolver expresamente el cierre de Sandra y qué versión del guion retomar; no reconstruir datos ni borrar la evidencia.

T14 se entrega para revisión de Claude; no se cierra por el ejecutor. No se tocó `ESTADO-ACTUAL.md`, la tabla externa ni el producto. Sin suite, migraciones, cachés de vistas ni push por esta entrega documental.
