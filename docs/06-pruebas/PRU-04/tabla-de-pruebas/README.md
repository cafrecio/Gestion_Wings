# Tabla de pruebas — copia para los tres agentes

La tabla viva es un artifact privado de Carlos y Claude
(https://claude.ai/artifact/Dc1Ya2fCaxGGiZxGcDgA46). Codex y Gemini no pueden abrirlo: esta
carpeta es su copia, para que un código como `CBZ-005` o `DU-54` se pueda leer.

**Copia tomada el 16/10/2026 por Claude CyE.** Desde el 16/10 ningún caso está «a definir»: los que Carlos decidió y falta programar figuran «no implementado», con su tarea; los que aceptó como están, «vigente». Si la tabla cambia, esta copia queda vieja
hasta que Claude la vuelva a bajar. La que manda es la tabla viva.

## Qué hay

- `catalogo/` — un archivo por módulo (ACC, ALU, CAJ, COB…). Adentro, `casos`: cada caso
  tiene `codigo`, `titulo`, `accion` (qué se hace), `requiere` (qué tiene que existir),
  `esperado` (una línea por cada cosa que tiene que pasar: efecto directo e indirectos, con
  `donde` se mira), `estado` (`vigente`, `a_definir`, `no_implementado`), `tarea` del
  tablero si la tiene y `duda` asociada.
- `dudas/` — una pregunta a Carlos por archivo (`DU-xx`): `pregunta`, `contexto` (qué pasa
  hoy), `opciones`, `recomendacion` de Claude y `respuesta` de Carlos.
  **La respuesta es una decisión de Carlos.**

## Cómo se usa con el tablero

Cada tarea T32 a T72 y T74 del tablero empieza su «paso» con «Tabla: DU-xx · casos …». Antes de
programar o verificar:

1. Leé la duda en `dudas/DU-xx.json`: ahí está la regla, con las palabras de Carlos.
2. Leé los casos nombrados en `catalogo/<módulo>.json`: ahí está, línea por línea, lo que
   tiene que pasar en todo el sistema.
3. El «qué pasa hoy» de la duda lo escribió Claude leyendo el código; puede estar
   desactualizado o mal. Comprobalo en el código antes de darlo por cierto.

El orden propuesto y el motivo de cada tarea están en
[`../DUDAS-RESPONDIDAS-2026-10-13.md`](../DUDAS-RESPONDIDAS-2026-10-13.md).
