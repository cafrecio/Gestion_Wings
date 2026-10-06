# Quién hace qué — 06/10/2026, 19:30

Una sola hoja para saber, en cualquier momento, qué tiene cada uno y qué está esperando.
**La actualiza Claude** cada vez que se entrega un prompt, llega un resultado o Carlos
decide algo. Si esto no coincide con la realidad, lo que manda es la realidad y hay que
corregirlo acá en el momento.

## Esperando a Carlos

| Qué espera | De quién salió | Desde |
|---|---|---|
| **Nada por decidir de A25.** Reglas y capturas aprobadas; falta control independiente, no otra aprobación de Carlos | Codex | 06/10 |

## Gemini

| Tarea | Estado |
|---|---|
| **P2 Entrega 1 — Cobranza** | **Cerrada y aprobada** por Codex en la segunda vuelta |
| **Verificar A11, A43 y permisos** | **Hechas y aprobadas**; A29, A30, A31 y A43 quedan cerrados |
| **P2 Entrega 2** — cobrar desde la ficha (A17), recibos (A34), cobro adelantado (A3) | **Cerrada**: implementada por Gemini y verificada por Claude el 05/10 |
| **Verificar P1, la primera carga por Excel de Codex** | **Verificada y aprobada** el 05/10 (`LOG GEM CYE`). Informe en `VERIFICACION-P1.md` |
| P2 Entrega 3 — pantallas en celular que quedan (A27, A28, A33, A36, A37, A40, A53) | Sin asignar; lista para iniciar |

## Codex

| Tarea | Estado |
|---|---|
| **A11, A43 y permisos** | **Cerrados**, verificados por Gemini |
| **Verificar la Entrega 1 de Cobranza** | **Hecha**: aprobada en la segunda vuelta; de ahí salieron A53, A54 y A55 |
| **P1 — primera carga por Excel** | **Aprobada** por Gemini el 05/10. Falta retirar los dos importadores viejos y desplegar |
| **A4 y A5 — los formularios que frenan** | **CERRADOS el 06/10**, verificados por Gemini (`94e368e`), incluido el aviso al salir sin guardar que Codex no había podido probar |
| **A25 — declarar el efectivo y arquear el cajón** | **HECHO el 06/10** (`30f38f8`), con el contrato de Caja-Cashflow en V5. **Falta que otro agente lo verifique** |
| **Verificar A13, B1, A54 y A55 de Claude** | **Hecha el 06/10.** A54 cerrado; A55, A13 y B1 devueltos con observaciones |
| **A25 — apertura y arqueo** | **HECHO (Codex), a revisar**, commit 30f38f8. Diseño aprobado por Carlos; 468 pruebas aprobadas/2 omitidas, 3116 aserciones. Otro agente verifica; sin deploy |

## Claude

| Tarea | Estado |
|---|---|
| B4, B14, A44 | **Cerrados** |
| Una base de prueba por agente | **Hecho**, `AGENTS.md` §6-bis |
| El hook de push, que además pisaba la base local en cada `git pull` | **Arreglado dos veces**: volvió a aparecer el 05/10 y se sacó de nuevo |
| Verificar P0 de Codex | **Hecho**; de ahí salió A43 |
| **A54 y A55 — cada pantalla calcula la deuda por su cuenta** | **A54 cerrado.** A55 falla con dos deportes; Claude lo corrigió, **sin subir**: espera el OK de diseño |
| **Verificar la Entrega 2 de Gemini** | **Hecha el 05/10**: A17 y A3 correctos; A34 estaba mal descrito, el botón de recibo ya existía |
| **La lista de defectos y su tablero decían cosas distintas** | **Arreglado el 05/10**, con `DefectosNoDivergenTest` para que no se repita |
| **A13 y B1 — el dueño no tiene caja** | **Devueltos por Codex**: el historial anulado mostraba el mes del pago. Claude lo corrigió, **sin subir**: espera el OK de diseño de Carlos |

## Esperando una decisión de Carlos

| Qué | Desde |
|---|---|
| **El renglón de la inscripción en la ficha del segundo deporte** — "Figura en su registro de Patín, para no cobrarla dos veces". Es el único cambio visible de los arreglos de A55, A13 y B1, que están hechos y probados pero **sin subir** | 06/10 |
| **Qué hace Claude de ahora en más.** Carlos: "no vas a programar más". Queda en prompts, verificaciones, tableros y documentación | 06/10 |

## Cómo se evita el desorden

1. **Cada prompt dice a quién va en la primera línea**, y se anota acá antes de entregarlo.
0. **Lo visual no se aprueba por texto.** Sin capturas en el repositorio no se le pide a Carlos ninguna autorización de diseño (`AGENTS.md` §1).
2. **Lo que hace uno lo verifica el otro**, nunca el autor. Si se cruzó, se dice y se repite.
3. **Una tarea por agente a la vez.** Lo que está esperando a Carlos no se adelanta.
4. **Lo que espera una decisión de Carlos vive arriba de todo**, en esta hoja.
5. **Todo prompt termina con lo mismo, sin excepción:** dejar asentado qué se hizo en la
   bitácora propia y actualizar el seguimiento en los **dos** archivos, `DEFECTOS.md` y
   `DEFECTOS.html`, más el estado y el plan si cambió el número de pruebas. Si el prompt no
   lo pide, está mal escrito: lo que no queda anotado se vuelve a discutir y se paga dos veces.
6. **El que implementa marca `HECHO <fecha> (quién), a revisar`; CERRADO lo escribe el que
   verifica.** Nadie cierra lo suyo. Lo encontró Codex el 05/10 leyendo un prompt mal escrito
   por Claude que le pedía cerrar su propia tarea; Carlos confirmó el criterio. El avance de
   arriba cuenta solo los CERRADO: lo hecho y sin revisar todavía no es avance.
7. **Los bloques del plan (P0, P1, P2…) se marcan igual que los defectos.** Están en el mismo
   tablero y nadie los tocaba: P1 figuraba sin terminar un día después de estar verificada, y
   Carlos lo vio antes que los tres agentes.
8. **Un defecto corregido se marca en `DEFECTOS.md` y en `DEFECTOS.html` en el mismo commit
   que lo corrige.** Si no se marca, se vuelve a hablar de él como pendiente y se paga dos
   veces el mismo trabajo. Lo cubre `DefectosNoDivergenTest`, que pone la suite en rojo si
   los dos archivos dejan de coincidir o si el avance de arriba no es el real.
