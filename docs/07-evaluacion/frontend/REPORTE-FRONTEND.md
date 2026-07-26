# Reporte de auditoría frontend — Wings

Auditoría de consistencia visual y UX sobre `resources/views/` contra `wings-design/SKILL.md`, `design-system/DESIGN-RULES.md` y los tokens de `resources/css/app.css`. Ordenado por severidad.

### F1.0 — El sistema de botones está fragmentado en 4 implementaciones distintas
**Severidad:** Alta
**Dónde:** `caja/show.blade.php:55,61,67,112,118,167,187`; `caja/resumen.blade.php:57-79`; `caja/index.blade.php:9-11,66-68,152-156`; `caja/detalle.blade.php:24-27`; `operativo/dashboard.blade.php:28-32,85-102,119-142`; `cashflow/index.blade.php:84-96`; `cobranza/index.blade.php:67-80`; `revision-cobranza/index.blade.php:55-60,165-193`; `clases/show.blade.php:100-157,179-204`; `rubros/index.blade.php:13-19`; `niveles/index.blade.php:9-15`; `tipos-caja/index.blade.php:9-15`; `usuarios/index.blade.php:9-13`; `liquidaciones/index.blade.php:9-15`; vs. `x-ds.button` en `alumnos/index`, `deportes/index`, `grupos/index`, `profesores/index`, `movimientos/index`, `cobrar-cuota`.
**Qué pasa:** El mismo botón se construye de 4 formas: (1) `<x-ds.button>`, (2) variables PHP `$btnB/$btnBSec/$btnBPrim` inline de 96px, (3) estilo inline crudo con ancho auto `padding:0 16px`, (4) `class="ds-btn"` + override inline de `background`. No hay fuente de verdad única y las variantes divergen (ver F2, F5, F9).
**Soluciones:**
- SF1.1 ⭐ Migrar TODAS las vistas a `<x-ds.button>` + `.ds-btn-row`, eliminando `$btnB*` e inline. El componente ya cubre primary/secondary/danger/ghost, href y submit.
- SF1.2 Si un caso no encaja (paneles inline), extender el componente con una prop en vez de reinventar.

### F2.0 — Dos rojos distintos para la misma acción destructiva
**Severidad:** Alta
**Dónde:** `--color-btn-danger` (#C07878 rosa apagado) en `caja/show:67,118,191`, `rubros/index`, `niveles/index`, `tipos-caja/index`, `liquidaciones/index`, `clases/show:105,184`. `--color-danger` (#B91C1C rojo intenso) en `caja/resumen:64,69,208`, `caja/detalle:260`, `caja/cancelar-movimiento:53`, `operativo/dashboard:31`, `revision-cobranza/index:191`.
**Qué pasa:** Acciones equivalentes (Cerrar, Rechazar, Eliminar, Cancelar, Inactivo) aparecen en dos intensidades de rojo sin criterio. El DS define `.ds-btn--danger` con `--color-btn-danger`; `--color-danger` es token de error/texto, no de fondo de botón.
**Soluciones:**
- SF2.1 ⭐ Unificar todo destructivo en `.ds-btn--danger` (`--color-btn-danger`), como dicta el DS.
- SF2.2 Si se reserva el rojo intenso para acciones críticas de caja, documentarlo en DESIGN-RULES y aplicarlo consistente.

### F3.0 — Etiquetas de botón con más de una palabra (regla dura)
**Severidad:** Alta
**Dónde:** `operativo/dashboard:31` "Cerrar caja", `:123` "Nueva caja"; `clases/show:185` "Cancelar esta", `:194` "Cancelar serie". Además verbos divergentes para la MISMA acción (agregar movimiento): "Nuevo" (`caja/index:66`), "Agregar" (`caja/index:153`), "Movimiento" (`operativo:95`), "Nuevo" (`caja/resumen:59`).
**Qué pasa:** La regla es "una palabra, sin redundancia de contexto". Varios botones la violan y la misma operación usa verbos distintos entre vistas.
**Soluciones:**
- SF3.1 ⭐ Renombrar a una palabra ("Cerrar", "Nueva", "Cancelar") y reemplazar "Cancelar serie" por un checkbox modificador. Unifica verbos.
- SF3.2 Definir glosario de verbos canónicos en DESIGN-RULES.

### F4.0 — Dot de estado con clases CSS inexistentes: no pinta
**Severidad:** Alta
**Dónde:** `profesores/index.blade.php:77` — `alumno-dot--activo`/`alumno-dot--inactivo`.
**Qué pasa:** En `app.css` solo existen `.alumno-dot--success|warning|danger|neutral|active`. Las clases `--activo/--inactivo` no existen → el dot queda sin `background` (bug visible: semáforo apagado). Único listado con el dot roto.
**Soluciones:**
- SF4.1 ⭐ Usar `alumno-dot--success`/`--neutral` como `usuarios/index:38`. Corrige con clases existentes.
- SF4.2 Agregar los alias al CSS (peor: multiplica nombres).

### F5.0 — "secondary" rellena en casi todo, contorneada en Caja
**Severidad:** Alta
**Dónde:** Relleno slate en `.ds-btn--secondary` (app.css:317) y todos los `$btnBSec`. Contorno (borde + transparente) en `caja/show:55,112,167,187`, `revision-cobranza/index:171-177`.
**Qué pasa:** "Ver" es relleno en `alumnos/index`/`grupos/index`/`liquidaciones/index` pero contorneado en `caja/show`. Misma jerarquía, dos apariencias. Nota: `SKILL.md:104-111` define secondary transparente/borde, pero el CSS lo implementa relleno — doc e implementación se contradicen.
**Soluciones:**
- SF5.1 ⭐ Unificar en el relleno del CSS (mayoritario) vía `x-ds.button variant="secondary"`.
- SF5.2 Alternativa: redefinir el DS a contorneado (toca CSS + todas las vistas; más caro).
- SF5.3 En cualquier caso corregir la tabla de variantes del SKILL.

### F6.0 — Sin responsividad: layout de 2 columnas fijas sin breakpoint — ✅ RESUELTO (2026-07-21)
**Severidad:** Alta
**Resolución (SF6.1):** debajo de 768px el sidebar pasa a ser un drawer off-canvas con botón hamburguesa en el topbar, overlay de fondo, y se cierra solo al navegar. `.ds-layout` pasa a una columna. Arriba de 768px sin cambios. Ver detalle en `funcionalidad/REPORTE-PROFESOR.md` UP1.0 (mismo fix).
**Alcance no incluido (parte de SF6.2):** no se auditó `overflow-x:auto` en las tablas anchas (`movimientos`, `cashflow`, `caja/detalle`) — esas vistas las usa admin/operativo, no el profesor (que solo ve `clases`, basado en cards, no tablas), así que quedaron fuera del alcance de UP1.0. Si en el futuro admin/operativo reportan el mismo problema en celular, retomar ahí.
**Dónde:** `app.css:558-562` `.ds-layout { grid-template-columns: 240px 1fr }`. `app.css` no contiene **ninguna** `@media` (verificado: 0). `.ds-sidebar` siempre 240px.
**Qué pasa:** En celular el sidebar de 240px queda fijo sin colapsar ni hamburguesa, comiéndose el ancho y forzando scroll horizontal. Afecta de lleno a `clases/index` y `clases/show` (asistencias), justo lo que usa el PROFESOR desde el teléfono. Las tablas anchas (`movimientos`, `cashflow`, `caja/detalle`) agravan el desborde.
**Soluciones:**
- SF6.1 ⭐ `@media (max-width:768px)` que colapse el sidebar a drawer/hamburguesa y pase el layout a una columna.
- SF6.2 Mínimo: sidebar off-canvas + `grid-template-columns:1fr` en móvil y `overflow-x:auto` garantizado en todas las tablas.

### F7.0 — Estado/badge dentro del header del card (viola regla 1.1)
**Severidad:** Media
**Dónde:** `profesores/index:80-86`; `liquidaciones/index:119-124`; `revision-cobranza/index:105-109`; `operativo/dashboard:227-232`.
**Qué pasa:** DESIGN-RULES 1.1 prohíbe badges en el `alumno-card-header` (solo dot + título). Varias vistas meten un badge de estado a la derecha del header, desviándose de la referencia (`alumnos/index`).
**Soluciones:**
- SF7.1 ⭐ Mover el estado al `alumno-info` o codificarlo en el color del dot (ya hay precedente en `caja/index`).
- SF7.2 Si el badge es imprescindible, permitirlo en DESIGN-RULES y aplicarlo en TODOS los listados igual.

### F8.0 — Dos estilos de barra de filtros conviviendo
**Severidad:** Media
**Dónde:** Estilo A (`.filtros-control`, 48px, borde 2px): `alumnos`, `grupos`, `profesores`, `clases`, `movimientos`, `caja/index`, `caja/historial`. Estilo B (grid + `.wings-input` con label mayúsculas, ~40px): `cobranza:38-82`, `revision-cobranza:35-63`, `cashflow:17-59`, `liquidaciones:40-95`.
**Qué pasa:** El mismo componente conceptual tiene dos alturas, dos bordes y dos tratamientos de label según la vista.
**Soluciones:**
- SF8.1 ⭐ Estandarizar en `.filtros-control` (patrón de la referencia) y migrar las 4 vistas del Estilo B.
- SF8.2 Crear `x-ds.filterbar` para no volver a divergir.

### F9.0 — Altura del botón primario inconsistente (32 vs 36 vs 38 px)
**Severidad:** Media
**Dónde:** `.ds-btn`=32px (app.css:274). 38px inline en `cobranza:68`, `revision-cobranza:166`. 32px ancho-auto en `cashflow:85`, `operativo:28`. `SKILL.md:118` / DESIGN-RULES Objeto A dicen 112×36.
**Qué pasa:** "Filtrar"/"Nuevo"/"Confirmar" miden distinto según la vista. El tamaño documentado (112×36) ni siquiera existe: `x-ds.button` primario renderiza 96×32 igual que un botón de card, sin jerarquía visual.
**Soluciones:**
- SF9.1 ⭐ Implementar el Objeto A real (p.ej. `.ds-btn--page` 112×36) en los stats-bar y unificar el resto en 32px.
- SF9.2 Si todo es 32px, quitar la referencia a 112×36 de SKILL/DESIGN-RULES.

### F10.0 — `color:#fff` y hex hardcodeados en Blade
**Severidad:** Media
**Dónde:** `#fff` en `caja/index:66`, `caja/show:61,67,118,191`, `caja/resumen:64,69,73,208`, `caja/detalle:26-27,260`, `caja/cancelar-movimiento:53`, `operativo/dashboard:30,88,122,139`, `cashflow:94`, `cobranza:70`, `revision-cobranza:168,185,191`, `layouts/ds-app:20,40,108`. `#E6252F` fuera de paleta en `auth/login:19,62`. (Los `pdfs/*` usan hex pero son DomPDF, fuera del DS.)
**Qué pasa:** El SKILL prohíbe hex en Blade; existe `--color-surface` para el blanco. `#E6252F` del login no es el rojo de marca (`--color-brand` #BE123C).
**Soluciones:**
- SF10.1 ⭐ `#fff`→`var(--color-surface)`, `#E6252F`→`var(--color-brand)`.
- SF10.2 Excluir `pdfs/*` del alcance del DS en la doc (DomPDF no resuelve `var()`).

### F11.0 — `ds-content` sin `max-width`: "océanos vacíos" en monitores anchos
**Severidad:** Media
**Dónde:** `app.css:592-596` `.ds-content` sin `max-width` (SKILL:82 dice 1200px). Único parche: `caja/historial:18` fuerza 980px a mano. Sin tope: `movimientos`, `cashflow`, `caja/detalle`, `caja/resumen`, `cobranza`.
**Qué pasa:** Contradicción conocida SKILL(1200)↔CSS(sin límite); cada vista la resuelve distinto (una con 980px, el resto nada). Ancho de contenido no homogéneo.
**Soluciones:**
- SF11.1 ⭐ `max-width` en `.ds-content` (o wrapper interno) y borrar el parche de historial.
- SF11.2 Decidir el valor (1200 vs 980) y dejarlo escrito.

### F12.0 — Module header: implementación (gradiente rojo) contradice la doc (chrome gris)
**Severidad:** Media
**Dónde:** `app.css:528-531` usa `linear-gradient(135deg, var(--color-brand) 0%, #9A0F30 100%)`. `SKILL.md:81,96` dice `--color-chrome #4A4A4A`. `#9A0F30` hardcodeado.
**Qué pasa:** El header (en todas las vistas) es un degradé rojo, no el gris documentado. Consistente entre vistas, pero la doc induce a error a quien programe una vista nueva.
**Soluciones:**
- SF12.1 ⭐ Actualizar el SKILL al gradiente real y tokenizar `#9A0F30`.
- SF12.2 Alternativa: volver al gris chrome (más disruptivo).

### F13.0 — Filtros: auto-submit vs botón "Filtrar" inconsistente
**Severidad:** Media
**Dónde:** Auto-submit: `cashflow`, `revision-cobranza`, `clases/index`, `alumnos/index`. Botón "Filtrar": `cobranza`, `movimientos`, `caja/historial`, `liquidaciones`, `caja/index`.
**Qué pasa:** En unas pantallas el select recarga solo; en otras hay que apretar "Filtrar". Comportamiento impredecible.
**Soluciones:**
- SF13.1 ⭐ Un solo modelo: auto-submit en selects + botón solo para el texto de búsqueda.
- SF13.2 Documentar la regla en DESIGN-RULES.

### F14.0 — `caja/cobrar`: submit deshabilitado reinventado
**Severidad:** Baja
**Dónde:** `caja/cobrar.blade.php:200-204` (`class="ds-btn"` + `opacity:0.4`/`cursor:not-allowed` inline, togglado por JS con `style.opacity`).
**Qué pasa:** El DS ya define `.ds-btn:disabled` (app.css:354). El botón lo duplica a mano.
**Soluciones:**
- SF14.1 ⭐ Usar `<x-ds.button variant="primary" type="submit" :disabled>` y togglear `btn.disabled`, dejando el estilo al CSS.

### F15.0 — Detalles menores de formato/tokens
**Severidad:** Baja
**Dónde:** Signo negativo `−` (U+2212) en `caja/detalle:229`, `movimientos:69`, `caja/index:145` vs `–` (en dash) en `caja/resumen:185`. `ds-btn--ghost` usa `--color-text-muted` en CSS pero `SKILL.md:110` dice `--color-text`. (Fecha `d/m/Y` y dinero `$ number_format(...,0,',','.')` SÍ son consistentes en todo el sistema — fortaleza a preservar.)
**Soluciones:**
- SF15.1 ⭐ Estandarizar el signo en `−` y alinear la doc de ghost con el CSS.

## Tabla resumen

| ID | Severidad | Título | Recomendación |
|----|-----------|--------|---------------|
| F1 | Alta | Botones reinventados en 4 implementaciones | Migrar todo a `x-ds.button`/`.ds-btn-row` (SF1.1) |
| F2 | Alta | Dos rojos para acción destructiva | Unificar en `.ds-btn--danger` (SF2.1) |
| F3 | Alta | Etiquetas multi-palabra + verbos divergentes | Renombrar a 1 palabra + glosario (SF3.1) |
| F4 | Alta | Dot con clases inexistentes (profesores) | Usar `--success/--neutral` (SF4.1) |
| F5 | Alta | "secondary" relleno vs contorneado | Unificar en relleno del DS (SF5.1) |
| F6 | Alta | Sin responsividad / sidebar fijo 240px | ✅ Resuelto — sidebar drawer con hamburguesa (SF6.1) |
| F7 | Media | Badge de estado en header de card | Mover a info-grid o al dot (SF7.1) |
| F8 | Media | Dos estilos de barra de filtros | Estandarizar en `.filtros-control` (SF8.1) |
| F9 | Media | Altura de primario 32/36/38 | Implementar Objeto A real (SF9.1) |
| F10 | Media | Hex hardcodeados (`#fff`, `#E6252F`) | Reemplazar por tokens (SF10.1) |
| F11 | Media | `ds-content` sin max-width | max-width en `.ds-content` (SF11.1) |
| F12 | Media | Module header rojo vs doc gris | Alinear SKILL + tokenizar (SF12.1) |
| F13 | Media | Filtros auto-submit vs botón | Un solo modelo (SF13.1) |
| F14 | Baja | Submit disabled reinventado | Usar `disabled` del DS (SF14.1) |
| F15 | Baja | Signo `−`/`–` y token de ghost | Barrido de normalización (SF15.1) |
