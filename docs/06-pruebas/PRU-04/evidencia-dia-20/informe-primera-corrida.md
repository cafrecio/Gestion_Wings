# PRU-04 · Día 20 · 2026-10-29

**Codex CyE · ejecución real 26/10/2026 · TEST / wingstest.**

**NO OPERADO: falló el control previo; no hay casos aprobados de este día.**

## Fuente y alcance

[Guion inicial congelado antes de operar](evidencia-dia-19/guion-congelado-antes-de-operar.md), HEAD inicial `5b2f16a2`. Durante la ejecución `d66eba1e` amplió el guion. Se conserva también la [versión ampliada detectada](evidencia-dia-19/guion-ampliado-detectado-despues.md); no se atribuyen sus casos nuevos a la corrida anterior. El tablero compartido fue incorporado por `51c58857` mientras se trabajaba.

App remota leída antes de operar: `ec1b0c6e`, entorno `staging`. SSH `vps` respondió desde CyE. Se copió la rutina actual a `/tmp/wings-dias1922-20261026`, ejecutada como `wingstest`, `WINGS_APP=/home/wingstest/app`. Huella rutina SHA-256 `9376ea7fa1e71d50aeba6f4daabdee3a302d30be80c762fcf0c623857673ca70`. No despliegue ni escrituras por consola fuera de reloj y rutinas.

## Ejecución y resultados

| Caso | Quién | Tarea / esperado del guion ampliado | Resultado | Observado y evidencia |
|---|---|---|---|---|
| 20.1 | Rocío | Vende un par de **patines, $85.000 por MP** (Indumentaria → Patines) y una **remera, $18.000 en efectivo**.<br>**Esperado:** Los dos aparecen como ingresos de la caja; solo la remera sube el efectivo esperado (MOV-002). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.2 | Dueño | Se inunda la cancha: **cancela la clase de hoy de Fútbol Avanzadas 19–20** (#71). Primero sin motivo, después con «Cancha inundada».<br>**Esperado:** Sin motivo no cancela. Con motivo sigue en el listado de hoy, apagada, con «Cancelada» y el motivo (CLA-011). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.3 | Hernán | Entra a la #71 para tomar lista.<br>**Esperado:** La ve cancelada: aviso de bloqueo y Guardar deshabilitado; no se guarda ninguna marca (ASI-005). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.4 | Dueño | Crea una **clase única de recuperación**: Fútbol Avanzadas, **sábado 31/10**, con Hernán. Primero pone de **14:00 a 13:00**; después corrige a **14:00 a 15:00**.<br>**Esperado:** Con la hora de fin antes del inicio, rechaza con un aviso claro y conserva lo cargado (CLA-005). Con 14 a 15 se crea; solo ofrece profesores de Fútbol, y Hernán la ve en sus clases del 31 (CLA-002). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.5 | Mariela | Quiere adelantar la lista de la clase del **sábado 31, Federadas 10–11** (#50).<br>**Esperado:** No deja guardar una clase cuyo día no llegó, y lo explica (ASI-011). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.6 | Rocío | La familia de **Lucas Gómez** cambió de celular: lo corrige de 1145123401 a **1145123499**.<br>**Esperado:** La ficha y el listado muestran el número nuevo; sus pagos y cuotas no cambian (ALU-012). | **No ejecutado** | Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.7 | Rocío | En su **Inicio**, a mitad del turno.<br>**Esperado:** «Caja abierta», con la hora en que la abrió, Cobrar y Registrar (REP-016). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.8 | Rocío | En **Movimientos**, busca los cobros del miércoles.<br>**Esperado:** Ve los cobros de cuota de Sandra del 28/10, con quién los hizo, y puede abrir sus recibos. No ve gastos del dueño (MOV-011). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.9 | Rocío | En **Caja**, el listado de cajas.<br>**Esperado:** Esperado según el catálogo: ve solo sus cajas de los últimos 30 días (la del 26/10 y la de hoy), no las de Sandra ni Pablo (CAJ-035). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |
| 20.10 | Rocío | Quiere crear una clase de prueba para una amiga: Clases → Nueva (y, si no aparece el botón, entrando a la dirección `/clases/create`).<br>**Esperado:** No puede: crear clases es del dueño; forzando, acceso restringido (CLA-008). | **No ejecutado** | Agregado al guion durante la ejecución; no estaba en la versión congelada inicial. Día sin operar: la simulación de la mañana rechazó abrir por turno anterior abierto; no se ejecutaron pasos de pantalla ni rutinas reales. [TXT](evidencia-dia-20/00-reloj.txt) · [TXT](evidencia-dia-20/01-manana-simulacion.txt) |

## Control previo fallido

Reloj `2026-10-29 15:00`, una sola vez. Simulación `dia-20-manana.json --simular`, una sola vez, salida 1 en paso 1: «Hay un turno abierto. Cerralo antes de abrir otro.» Se revirtió. No se ejecutó la rutina real de mañana, la de noche ni ninguno de los casos de este día. No se reintentó ni se reparó el turno anterior. Los cierres esperados del guion **no son resultados observados**.

La causa de continuidad es la caja #36 de Sandra, día 19, que quedó abierta al no poder eliminar Librería por Chrome y diferir el esperado del guion. No es una vulnerabilidad ni un fallo nuevo de la apertura: es su rechazo previsto de un segundo turno.

## Salidas originales de consola

### 00-reloj.txt

[Archivo](evidencia-dia-20/00-reloj.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es jueves 29 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 01-manana-simulacion.txt

[Archivo](evidencia-dia-20/01-manana-simulacion.txt)

```text
Paso 1 falló, no se guardó NADA del plan: Hay un turno abierto. Cerralo antes de abrir otro.
```

## Continuidad

Último reloj ejecutado al terminar el paquete: **31/10/2026 15:00** (avanza solo hasta medianoche). No repetir relojes ni rutinas como si estos días no se hubieran intentado. Para continuar hay que resolver expresamente el cierre de Sandra y qué versión del guion retomar; no reconstruir datos ni borrar la evidencia.

T14 se entrega para revisión de Claude; no se cierra por el ejecutor. No se tocó `ESTADO-ACTUAL.md`, la tabla externa ni el producto. Sin suite, migraciones, cachés de vistas ni push por esta entrega documental.
