# Quién hace qué — 04/10/2026, 15:40

Una sola hoja para saber, en cualquier momento, qué tiene cada uno y qué está esperando.
**La actualiza Claude** cada vez que se entrega un prompt, llega un resultado o Carlos
decide algo. Si esto no coincide con la realidad, lo que manda es la realidad y hay que
corregirlo acá en el momento.

## Esperando a Carlos

| Qué espera | De quién salió | Desde |
|---|---|---|
| **A43 y permisos ya autorizados por Carlos** con línea Diseno-autorizado; no esperan aprobación. Ver entregas en la sección Codex | Codex | 04/10 |

## Gemini

| Tarea | Estado |
|---|---|
| **P2 Entrega 1 — Cobranza** | **Verificada y aprobada por Codex el 05/10.** `abc346a`; segunda vuelta en `VERIFICACION-ENTREGA1.md`, código/pantalla, filtros 375 y suite propia 380/2242. A53–A55 nuevos, fuera de la entrega, sin corregir |
| **Verificar A11 — Configuración** | **Hecha y aprobada**, informe en `VERIFICACION-A11.md` |
| **P2 Entrega 2** — cobrar desde la ficha, recibos, cobro adelantado | Destrabada: Entrega 1 aprobada por Codex el 05/10; puede continuar |
| P2 Entrega 3 — formulario de alumno, clases, caja con arqueo | Sin asignar |

## Codex

| Tarea | Estado |
|---|---|
| **A11 — Configuración** | **Cerrada**, verificada y aprobada por Gemini |
| **A43** — el alta avisa cuando el ingreso cae en un mes cerrado | Commiteada en `218ffc5`; falta que Gemini la verifique |
| **Permisos** (A29, A30, A31) | Entregados en `97cf933`; suite propia 380/2242, tres roles normal/375 px. [Informe](../06-pruebas/PRU-02/IMPLEMENTACION-PERMISOS.md). Falta que Gemini verifique |
| **Verificar la Entrega 1 de Cobranza** | **Hecha y aprobada 05/10**, segunda vuelta en código/pantalla y suite 380/2242; no bloquea a Gemini |
| **P1 — primera carga por Excel** | Maqueta aprobada; la implementación no arrancó |

## Claude

| Tarea | Estado |
|---|---|
| B14 — el despliegue no limpiaba la caché de rutas | **Cerrado** |
| A44 — el correo salía firmado por Laravel | **Cerrado** |
| B4 — el aviso diario no incluía las clases sin lista tomada | **Cerrado** |
| Una base de prueba por agente, para que las suites no se traben | **Hecho**, en `AGENTS.md` §6-bis |
| El hook que sube a GitHub, roto y además pisaba la base local en cada `git pull` | **Arreglado** |
| Verificar P0 de Codex | **Hecho**; de ahí salió A43 |
| Verificar las entregas de los otros | Permanente |

## Cómo se evita el desorden

1. **Cada prompt dice a quién va en la primera línea**, y se anota acá antes de entregarlo.
0. **Lo visual no se aprueba por texto.** Sin capturas en el repositorio no se le pide a Carlos ninguna autorización de diseño (`AGENTS.md` §1).
2. **Lo que hace uno lo verifica el otro**, nunca el autor. Si se cruzó, se dice y se repite.
3. **Una tarea por agente a la vez.** Lo que está esperando a Carlos no se adelanta.
4. **Lo que espera una decisión de Carlos vive arriba de todo**, en esta hoja.
