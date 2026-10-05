# Quién hace qué — 05/10/2026, 13:40

Una sola hoja para saber, en cualquier momento, qué tiene cada uno y qué está esperando.
**La actualiza Claude** cada vez que se entrega un prompt, llega un resultado o Carlos
decide algo. Si esto no coincide con la realidad, lo que manda es la realidad y hay que
corregirlo acá en el momento.

## Esperando a Carlos

| Qué espera | De quién salió | Desde |
|---|---|---|
| **Nada.** Todo lo entregado está verificado y subido. Se sigue desde CyE con `git pull` | — | 05/10 07:05 |

## Gemini

| Tarea | Estado |
|---|---|
| **P2 Entrega 1 — Cobranza** | **Cerrada y aprobada** por Codex en la segunda vuelta |
| **Verificar A11, A43 y permisos** | **Hechas y aprobadas**; A29, A30, A31 y A43 quedan cerrados |
| **P2 Entrega 2** — cobrar desde la ficha (A17), recibos (A34), cobro adelantado (A3) | **Implementada el 05/10**; pendiente de verificación por otro agente (§6a) |
| P2 Entrega 3 — formulario de alumno, clases, caja con arqueo | Sin asignar |

## Codex

| Tarea | Estado |
|---|---|
| **A11, A43 y permisos** | **Cerrados**, verificados por Gemini |
| **Verificar la Entrega 1 de Cobranza** | **Hecha**: aprobada en la segunda vuelta; de ahí salieron A53, A54 y A55 |
| **P1 — primera carga por Excel** | **Maqueta aprobada por Carlos el 05/10. Prompt entregado**: implementación en curso. Es lo que bloquea volver a probar |

## Claude

| Tarea | Estado |
|---|---|
| B4, B14, A44 | **Cerrados** |
| Una base de prueba por agente | **Hecho**, `AGENTS.md` §6-bis |
| El hook de push, que además pisaba la base local en cada `git pull` | **Arreglado dos veces**: volvió a aparecer el 05/10 y se sacó de nuevo |
| Verificar P0 de Codex | **Hecho**; de ahí salió A43 |
| **A54 y A55 — cada pantalla calcula la deuda por su cuenta** | **Hechos y subidos el 05/10**: un solo cálculo, `saldoDeAlumnos()`, para Cobranza, ficha y selector. **Esperan verificación de Codex o Gemini** |

## Cómo se evita el desorden

1. **Cada prompt dice a quién va en la primera línea**, y se anota acá antes de entregarlo.
0. **Lo visual no se aprueba por texto.** Sin capturas en el repositorio no se le pide a Carlos ninguna autorización de diseño (`AGENTS.md` §1).
2. **Lo que hace uno lo verifica el otro**, nunca el autor. Si se cruzó, se dice y se repite.
3. **Una tarea por agente a la vez.** Lo que está esperando a Carlos no se adelanta.
4. **Lo que espera una decisión de Carlos vive arriba de todo**, en esta hoja.
