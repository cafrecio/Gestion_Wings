# PRU-04 · Día 22 · 2026-10-31

**Codex CyE · ejecución real 26/10/2026 · TEST / wingstest.**

**NO OPERADO: falló el control previo; no hay casos aprobados de este día.**

## Fuente y alcance

[Guion inicial congelado antes de operar](evidencia-dia-19/guion-congelado-antes-de-operar.md), HEAD inicial `5b2f16a2`. Durante la ejecución `d66eba1e` amplió el guion. Se conserva también la [versión ampliada detectada](evidencia-dia-19/guion-ampliado-detectado-despues.md); no se atribuyen sus casos nuevos a la corrida anterior. El tablero compartido fue incorporado por `51c58857` mientras se trabajaba.

App remota leída antes de operar: `ec1b0c6e`, entorno `staging`. SSH `vps` respondió desde CyE. Se copió la rutina actual a `/tmp/wings-dias1922-20261026`, ejecutada como `wingstest`, `WINGS_APP=/home/wingstest/app`. Huella rutina SHA-256 `9376ea7fa1e71d50aeba6f4daabdee3a302d30be80c762fcf0c623857673ca70`. No despliegue ni escrituras por consola fuera de reloj y rutinas.

## Ejecución y resultados

| Caso | Quién | Tarea / esperado del guion ampliado | Resultado | Observado y evidencia |
|---|---|---|---|---|
| 22.1 | Pablo | **Berenice Sánchez** paga lo que debe, **$66.000 por MP**.<br>**Esperado:** Pasa a **Al día**. Cobranza queda sin deuda. | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.2 | Hernán | Toma la lista de la **clase de recuperación de 14 a 15** (20.4): los 15 presentes.<br>**Esperado:** Se guarda. Si el 20.4 no se pudo hacer, se saltea. | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.3 | Dueño | Paga la **luz, $62.000** por Banco Galicia, desde Cashflow.<br>**Esperado:** Egreso directo a su nombre, con Editar y Eliminar. Galicia baja; el gasto del mes sube (CSH-002). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.4 | Dueño | La factura tenía descuento: corrige la luz a **$61.500**, motivo «Descuento por débito automático».<br>**Esperado:** Queda en $61.500. El detalle muestra el valor anterior, el nuevo, el motivo, quién y cuándo (CSH-007). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.5 | Dueño | Paga el **sueldo de Sandra**, $250.000 en efectivo, desde Cashflow (Sueldos → Op-Sandra Vidal).<br>**Esperado:** Gasto de Sueldos; el Reporte de Sueldos lo incluye. Sandra no lo ve en sus Movimientos (CSH-015). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.6 | Dueño | Liquidaciones → Nueva → **Lucía Gaitán, octubre**. La genera y después intenta **Cerrar**.<br>**Esperado:** Se genera: **17 clases × 1 h × $12.000 = $204.000**. No incluye la cancelada del 12/10 (LIQ-001). Al cerrar: «No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.» (LIQ-005). Queda abierta y sin pagar. | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.7 | Pablo | Entra a **Liquidaciones** desde el menú o por la dirección `/liquidaciones`.<br>**Esperado:** Acceso restringido en castellano, sin importes, con vuelta a su Inicio (LIQ-016). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.8 | Dueño | Listado de **Liquidaciones**.<br>**Esperado:** Muestra la de Lucía: octubre, por hora, $204.000, Abierta (LIQ-018). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |
| 22.9 | Dueño | **Después de la validación de la noche:** compara los saldos de Cashflow por medio con la cuenta del cierre de abajo, y abre el Reporte de Finanzas de octubre.<br>**Esperado:** Cada medio coincide con la cuenta y con el disponible de Reportes; ningún movimiento aparece dos veces (CSH-013). La liquidación abierta no suma como gasto. | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-22/00-reloj.txt) · [TXT](evidencia-dia-22/01-manana-simulacion.txt) |

## Control previo fallido

Reloj `2026-10-31 15:00`, una sola vez. Simulación `dia-22-manana.json --simular`, una sola vez, salida 1 en paso 1: «Hay un turno abierto. Cerralo antes de abrir otro.» Se revirtió. No se ejecutó la rutina real de mañana, la de noche ni ninguno de los casos de este día. No se reintentó ni se reparó el turno anterior. Los cierres esperados del guion **no son resultados observados**.

La causa de continuidad es la caja #36 de Sandra, día 19, que quedó abierta al no poder eliminar Librería por Chrome y diferir el esperado del guion. No es una vulnerabilidad ni un fallo nuevo de la apertura: es su rechazo previsto de un segundo turno.

## Salidas originales de consola

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

## Continuidad

Último reloj ejecutado al terminar el paquete: **31/10/2026 15:00** (avanza solo hasta medianoche). No repetir relojes ni rutinas como si estos días no se hubieran intentado. Para continuar hay que resolver expresamente el cierre de Sandra y qué versión del guion retomar; no reconstruir datos ni borrar la evidencia.

T14 se entrega para revisión de Claude; no se cierra por el ejecutor. No se tocó `ESTADO-ACTUAL.md`, la tabla externa ni el producto. Sin suite, migraciones, cachés de vistas ni push por esta entrega documental.

## Lectura final real del 31/10 (no es cierre del día 22)

Inicio conserva deuda $190.000, ingresos $2.373.850,75, egresos $257.150, resultado $2.116.700,75, disponible $3.887.700,75 y $404.000 por validar. El contador «Sin asistencia» muestra 4 de días pasados; las tres clases del 31 también siguen sin lista en su bloque de hoy. No se generó liquidación ni se pagó sueldo/luz ni se transfirió dinero.

[Inicio real](evidencia-dia-22/22-estado-final-sin-operaciones-del-dia.txt) · [Caja Sandra abierta](evidencia-dia-22/22-caja-sandra-abierta.txt) · [Clases pendientes](evidencia-dia-22/22-clases-pendientes.txt).
