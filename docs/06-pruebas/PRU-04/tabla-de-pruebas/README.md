# Tabla de pruebas — copia para los tres agentes

**Solo lectura para Codex y Gemini** (AGENTS.md §5-ter, Carlos 20/10/2026): no cambian la tabla viva ni
esta copia; proponen el cambio en su informe y Claude lo pasa.

La tabla viva es un artifact privado de Carlos y Claude
(https://claude.ai/artifact/Dc1Ya2fCaxGGiZxGcDgA46). El acceso depende de la sesión autenticada del navegador; esta
carpeta permite consultar un código como `CBZ-005` o `DU-54` desde el repositorio.

**Desde el 28/10/2026 el catálogo manda acá (decisión de Carlos).** La tabla viva ya no guarda los casos
en su base: lee los archivos de `catalogo/` que Claude publica en el artifact con `publish_files`
(`as: catalogo/<MÓDULO>.json`), sin copiar a mano. Para cambiar un caso: editar el JSON de esta carpeta,
commitear y volver a publicar los módulos tocados. Si la página no encuentra los archivos, cae a la base
vieja (colección `catalogo`, congelada en la versión del 25/10). Registro, días y dudas siguen en la base
de la tabla viva. Un módulo nuevo requiere agregar su prefijo a la lista `MODULOS` de la página.
Codex y Gemini siguen sin cambiar nada de esta carpeta: proponen el cambio en su informe y lo pasa Claude.

**Actualización 28/10/2026 (Claude CAB), despliegue `b271e8dd`:** 334 casos (+CBZ-023 deuda de inactivos y
CBZ-024 deuda dada por perdida); 14 módulos con casos actualizados por T25, T45, T46, T51, T54, T62, T65,
T70/T71, T84 a T93 y B3, incluidos los cuatro textos propuestos por Codex (ALU-017.b, ALU-025.b, REP-006,
PRO-004.c). Cada caso tocado lo dice en su `fuente` («Actualizado por Claude CAB el 28/10/2026…»).

**Copia tomada el 16/10/2026 por Claude CyE.** (Histórico: hasta el 28/10 la que mandaba era la tabla viva.) Desde el 16/10 ningún caso está «a definir»: los que Carlos decidió y falta programar figuran «no implementado», con su tarea; los que aceptó como están, «vigente». Si la tabla cambia, esta copia queda vieja
hasta que Claude la vuelva a bajar. La que mandaba era la tabla viva (hasta el 28/10; ver arriba).

**Sincronizada el 17/10/2026 por Claude CAB** en ALU, CAJ, COB y PEN (las dos copias
quedaron iguales):
- PEN-001 (B11, verificada el 16/10) y CAJ-029 (T37, verificada el 17/10): lo que Codex
  había cargado solo en esta copia pasó a la tabla viva, con el estado verificado. CAJ-029
  sigue `no_implementado` por su punto c (Inicio), que Carlos dejó afuera.
- ALU-018 pasa a `vigente` con T38, con una línea nueva para el plazo del reingreso.
- COB-028 ya no incluye al reactivado: desde T38 tiene su cuota al reactivar.

## Qué hay

**Actualización puntual 20/10/2026, T42:** Carlos autorizó a Codex enviar al editor de Claude los cambios de COB-012 e/h. Ingreso económico y comisión pasan a noviembre; Caja/Cashflow conservan octubre. Copia del repo actualizada y relectura de ArtifactData observada en el editor: catálogo COB, versión 10, solo COB-012 distinto. [Salida real de relectura](../evidencia-t42/tabla-viva-relectura.txt) y [captura de criterios](../evidencia-t42/tabla-viva-criterios.jpg). La vista pública quedó cargando; no se certifica su renderizado por heredar el mensaje de Claude. No se modificaron respuestas de dudas ni otros casos.

**Actualización puntual 20/10/2026, T43:** Carlos autorizó el mensaje al mismo editor de Claude. LIQ-002: e/h y fuente guardados y releídos en versión 5; después se alineó a con DU-19/T42 y la copia del repo, releído en versión 6. Solo LIQ-002 distinto; estado ya vigente, sin modificar registros ni dudas. [Relectura e/h](../evidencia-t43/tabla-viva-relectura.txt) · [Relectura final de a](../evidencia-t43/tabla-viva-punto-a-relectura.txt). No equivale a aprobación visual, verificación independiente ni despliegue.

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
