# Wings — Reglas de Diseño de Vistas

> Este documento es la fuente de verdad para cualquier vista del panel.
> Antes de entregar código de una vista, un agente revisor debe verificar
> cada punto de esta lista. Si alguno falla, el código NO se entrega.

---

## 1. CARDS

### 1.1 Header del card = SOLO el título
- El `alumno-card-header` (o cualquier header de card) contiene **únicamente**:
  - El dot indicador de color/estado
  - El título/nombre del elemento
- **Prohibido en el header**: botones, badges, observaciones, subtítulos, íconos de acción, fechas.
- La observación/descripción va **debajo** del header como `<p>` separado, con el mismo indentado que el texto del título (`padding-left: 1.5rem`).

### 1.2 alumno-info — filas del card

- **NO agregar filas extra** si hay espacio en blanco disponible. Maximizar densidad horizontal antes de añadir una nueva fila.
- Si los datos caben en una sola fila, usar `grid-template-columns: repeat(N, 1fr)` explícito en el `div.alumno-info` (no depender del auto-fit).
- **No usar** `grid-column: 1 / -1` para forzar un dato a fila propia salvo que sea estrictamente necesario (ej: texto muy largo que no cabe en el grid).
- Ejemplo: 3 datos → `style="grid-template-columns: repeat(3, 1fr);"` en el `div.alumno-info`, sin span de columnas.

### 1.3 Acciones del card van abajo
- Las acciones (Editar, Eliminar, etc.) van en `alumno-actions` al pie del card.
- `alumno-actions` tiene `border-top: 1px solid var(--color-border)` como separador visual.
- El botón secundario de "crear hijo" (ej: "+ Subrubro") va alineado a la derecha dentro de `alumno-actions` usando `margin-left: auto`.

---

## 2. BOTONES — Regla absoluta de consistencia de tamaño

Hay **tres objetos de botón** en el sistema. Cada objeto tiene su propio tamaño fijo. Dentro de cada objeto, **todos los botones son idénticos** en dimensiones.

### Objeto A — Botón primario de página (stats-bar)
- Uso: única acción primaria de la página (ej: "Nuevo").
- Componente: `x-ds.button variant="primary"` → clase `.ds-btn` → `width: 112px; height: 36px`.
- **Solo uno por sección/módulo**, en el `stats-bar`.

### Objeto B — Botón de acción de card (alumno-actions)
- Uso: Editar, Eliminar, y similares al pie del card.
- Tamaño: `width: 96px; height: 32px; font-size: 0.82rem; font-weight: 600`. Ancho FIJO — igual para Editar, Eliminar y + Subrubro.
- Se implementa con `<a>` o `<button>` directo con `display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px`.
- Todos los botones del `alumno-actions` de un mismo card son **idénticos en tamaño**.
- Variantes de color: `secondary` para Editar, `danger` para Eliminar.

### Objeto C — Botón de acción de fila de tabla
- Uso: Editar/Eliminar sobre una fila dentro de una tabla.
- Dos sub-variantes permitidas:
  - **Texto**: `height: 26px; width: 64px; font-size: 0.72rem; font-weight: 600; border: 1px solid; background: none; border-radius: var(--radius-btn)`. Editar = color-btn-secondary, Eliminar = color-btn-danger. **Ambos deben tener el mismo `width` fijo (64px).**
  - **Ícono** (lápiz/tacho): `width: 28px; height: 28px; padding: 0; display:inline-flex; align-items:center; justify-content:center; border-radius: var(--radius-btn); border: 1px solid; background: none`. Todos los íconos del mismo tipo tienen el mismo tamaño entre sí.
- Los botones ícono son un objeto distinto a los botones texto — pueden coexistir en la misma vista siempre que dentro de cada tipo sean consistentes.

### Lo que NO se hace nunca
- Mezclar Objeto B y Objeto C en la misma fila/acción.
- Usar `ds-btn` de 112px para acciones de fila de tabla.
- Dejar que el texto cambie el ancho del botón (sin `width` fijo o `min-width` fijo).

---

## 3. TEXTO DE BOTONES — Sin redundancia

- El texto del botón se escribe en el contexto mínimo necesario.
- Si el usuario ya está en la página de Rubros, el botón es "Editar", no "Editar rubro".
- Si el usuario ya está dentro del card de un Rubro específico, "Eliminar" es suficiente.
- Regla general: si el contexto visual hace obvio el objeto de la acción, **no se repite en el texto**.
- Ejemplos correctos: "Nuevo", "Editar", "Eliminar", "+ Subrubro".
- Ejemplos incorrectos: "Nuevo rubro", "Editar rubro", "Eliminar rubro".

---

## 4. TABLAS

### 4.1 Proporciones de columnas
- **Siempre** usar `table-layout: fixed` en tablas con columnas de ancho controlado.
- Definir `<colgroup>` con `<col style="width:Xpx">` para todas las columnas excepto la columna de contenido principal (Nombre), que toma el espacio restante.
- La columna de acciones tiene ancho fijo suficiente para contener los botones sin que se compriman ni desborden.
- Ejemplo de distribución para tabla de subrubros:
  - Nombre: auto (toma el resto)
  - Permitido para: 140px
  - Caja: 70px
  - Acciones: 80px (íconos) o 150px (texto)

### 4.2 Alineación del borde derecho
- La tabla debe tener el mismo `padding` lateral que el card que la contiene.
- Los botones de la última columna deben terminar en la misma línea vertical que los botones del `alumno-actions` del pie del card.
- Para lograrlo: la tabla ocupa `width: 100%` dentro del card, y el padding del card aplica uniformemente a tabla y a `alumno-actions`.

### 4.3 Overflow de texto en celdas
- Celdas de contenido textual: `overflow: hidden; text-overflow: ellipsis; white-space: nowrap`.

---

## 5. SEPARACIÓN DE DATOS POR TIPO

- Cuando un listado agrupa elementos de tipos opuestos (ej: INGRESO vs EGRESO), se presentan en **secciones separadas**, cada una con su propio `stats-bar`.
- No mezclar tipos en un mismo listado plano.
- Cada sección tiene su propio color semántico (INGRESO = success, EGRESO = danger).

---

## 6. STATS-BAR

- Estructura: `display: flex; justify-content: space-between; align-items: center`.
- Izquierda: conteo/info del módulo (puede incluir badge de tipo si aplica).
- Derecha: único botón primario de la sección (Objeto A).
- Un `stats-bar` por sección, no por card individual.

---

## 7. ALINEACIÓN GENERAL

- Todos los elementos interactivos de una misma "zona" (ej: pie de card) terminan en el mismo borde derecho.
- No mezclar elementos con `margin-left: auto` que desalineen el grupo visual de acciones.
- El `alumno-actions` es `justify-content: flex-start` con el botón secundario ("+ hijo") en `margin-left: auto`.

---

## 8. CHECKLIST DEL AGENTE REVISOR

Antes de aprobar cualquier vista, verificar:

- [ ] ¿El header de cada card tiene SOLO dot + título?
- [ ] ¿Las acciones del card están en `alumno-actions` al pie, con `border-top`?
- [ ] ¿El botón "hijo" (+ Subrubro, etc.) está en `margin-left:auto` dentro de `alumno-actions`?
- [ ] ¿Todos los botones del mismo objeto (B o C) tienen exactamente el mismo tamaño?
- [ ] ¿Los textos de botones son mínimos y sin redundancia de contexto?
- [ ] ¿Las tablas usan `table-layout:fixed` con `<colgroup>`?
- [ ] ¿La columna Nombre no roba todo el ancho?
- [ ] ¿Los botones de acción de fila tienen `width` fijo (no dependen del texto)?
- [ ] ¿El borde derecho de los botones de tabla y del pie del card están alineados?
- [ ] ¿Los datos de tipos distintos están en secciones separadas?
- [ ] ¿Hay un único `stats-bar` por sección con un único botón primario a la derecha?
