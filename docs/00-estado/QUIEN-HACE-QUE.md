# Quién hace qué — 06/10/2026, 14:00

Una sola hoja para saber, en cualquier momento, qué tiene cada uno y qué está esperando.
**La actualiza Claude** cada vez que se entrega un prompt, llega un resultado o Carlos
decide algo. Si esto no coincide con la realidad, lo que manda es la realidad y hay que
corregirlo acá en el momento.

Avance: **30 cerrados de 72**. Frenan dos, A13 y B1.

## Esperando a Carlos

| Qué espera | De quién salió | Desde |
|---|---|---|
| **Nada.** El permiso para publicar A15 y A16 ya se dio: están en main (`05dd962`) | — | — |

La redacción del renglón de inscripción (A55) y el diseño de A25 y de A15/A16
ya están aprobados.

## Lo que frena: A13 y B1

Arreglados, pero en la rama `a55-inscripcion`, tres commits sin integrar a main
(`31194c2`, `459ec42`, `bbe722d`). Carlos aprobó la redacción el 06/10. **Falta que Codex
aplique el texto aprobado e integre la rama**, y que otro agente verifique.

## Esperando verificación de otro agente

| Qué | Lo hizo | Quién puede verificar |
|---|---|---|
| **A25** — declarar el efectivo y arquear el cajón (`30f38f8`) | Codex | **Gemini**, prompt entregado el 06/10 |
| **Paquete de celular** — A14, A20, A27, A28, A33, A36, A40, A41, A53 (`26a67c6`, retoque `20dc5ae`) | Gemini | Codex o Claude |
| **A15 y A16** — aviso al programar clases y horarios por día (`05dd962`) | Codex | **Gemini**, mismo prompt, después de A25 |
| **A13, B1 y A55** — segunda vuelta | Claude | Codex, al integrar la rama |

## Gemini

| Tarea | Estado |
|---|---|
| **Paquete de celular** (A14, A20, A27, A28, A33, A36, A40, A41, A53) | **HECHO el 06/10, a revisar.** Incluye lo que era la Entrega 3 de P2 |
| **Verificar A25 y después A15/A16, de Codex** | **Prompt entregado el 06/10.** Dos informes separados; cierra solo lo que apruebe |
| **Completar las capturas de su cambio en `app.css`** | **Pendiente, después de las verificaciones.** El cambio alcanza a muchas más pantallas que las capturadas; sin eso no se puede verificar el paquete |
| Verificar A4 y A5 de Codex | **Hecha el 06/10** (`94e368e`): cerrados |
| A37 — ficha del alumno en celular | **Cerrado**, verificado por Claude el 06/10 |
| P2 Entregas 1 y 2, A11, A43, permisos, P1 | Cerradas |

## Codex

| Tarea | Estado |
|---|---|
| **Aplicar el texto aprobado e integrar `a55-inscripcion`** | **Pendiente.** Es lo que destraba A13 y B1 |
| **A15 y A16 — las clases** | **HECHO el 06/10, a revisar.** Publicado en main (`05dd962`). Suite propia: 486 aprobadas y 2 omitidas. Lo verifica Gemini |
| **Verificar A56 de Claude** | **Hecha el 06/10** (`ad7f9fb`): cerrado |
| **A25 — apertura y arqueo** | **HECHO el 06/10** (`30f38f8`), contrato de Caja-Cashflow en V5. Falta que otro agente lo verifique |
| P1 — primera carga por Excel | Aprobada por Gemini el 05/10. Falta retirar los dos importadores viejos y desplegar |
| A4, A5, A11, A43, permisos, A54 | Cerrados |

## Claude

**Desde el 06/10 Claude no programa.** Escribe los prompts, verifica, y mantiene tableros
y documentación. Las capturas las miran Codex o Gemini.

| Tarea | Estado |
|---|---|
| **A56 — Cashflow en celular** | **Cerrado**, verificado por Codex el 06/10 |
| **A13, B1 y A55** | Corregidos en `a55-inscripcion`; redacción aprobada. Esperan que Codex integre y verifique |
| Verificar A37 de Gemini | **Hecha el 06/10**: cerrado |
| B4, B14, A44, A54 | Cerrados |

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
9. **Las capturas de celular van en un marco de 375**, no achicando la ventana de Chrome:
   Windows no deja ventanas de menos de 500 píxeles y recorta. El control es capturar el
   login: si sale cortado, el que mide mal es el método. Usar
   `docs/06-pruebas/PRU-02/capturas-cashflow/marco-375.html`.
10. **Los tres agentes comparten la carpeta.** Antes de commitear, mirar que no se esté
    llevando trabajo ajeno sin commitear.
