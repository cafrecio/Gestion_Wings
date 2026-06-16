# Wings Design System — Helpers CSS

> Clases utilitarias del DS definidas en `resources/css/app.css`, sección "Base + Helpers".
> Todas usan tokens de `@theme`; **ninguna hardcodea hex**.

---

## Base global

El `body` ya recibe automáticamente:

| Propiedad     | Token usado           |
|---------------|-----------------------|
| `background`  | `--color-bg`          |
| `color`       | `--color-text`        |
| `font-family` | `--font-sans`         |

Los `<a>` usan `--color-brand` como color y `--color-wings-primary-hover` en hover.

---

## Superficies y bordes

| Clase            | Cuándo usarla                                            |
|------------------|----------------------------------------------------------|
| `.ds-surface`    | Cards, paneles, modales — la superficie principal blanca |
| `.ds-surface-alt`| **SOLO** inputs, selects y controles tipo FilterBar      |
| `.ds-border`     | Añadir `border-color` neutral a cualquier elemento       |

### Regla: `surface-alt` solo en controles

`.ds-surface-alt` está reservada para inputs y controles (FilterBar, dropdowns).
**No usar para paneles, cards ni secciones grandes** — para eso existe `.ds-surface`.

### Propiedades de `.ds-surface`

```
background:    var(--color-surface)       → #FFFFFF
border:        1px solid var(--color-border)
border-radius: var(--radius-card)          → 12px
box-shadow:    0 1px 4px rgba(17,24,39,.08)  (negro del sistema al 8%)
```

---

## Rail por deporte

El rail es un indicador visual de **6px de ancho** que identifica la disciplina.
Se coloca como elemento hijo izquierdo de una card; **nunca como fondo**.

### Clases de fondo (rail)

| Clase              | Token                    | Deporte  |
|--------------------|--------------------------|----------|
| `.ds-rail--patin`  | `--color-sport-patin`    | Patín    |
| `.ds-rail--futbol` | `--color-sport-futbol`   | Fútbol   |
| `.ds-rail--otro`   | `--color-sport-otro`     | Otros    |

Uso típico:
```html
<div class="ds-surface flex gap-3">
  <div class="ds-rail ds-rail--patin h-full"></div>
  <div>...</div>
</div>
```

### Clases de color para íconos

| Clase               | Token                  |
|---------------------|------------------------|
| `.ds-icon--patin`   | `--color-sport-patin`  |
| `.ds-icon--futbol`  | `--color-sport-futbol` |
| `.ds-icon--otro`    | `--color-sport-otro`   |

---

## Semáforo dot

Circulito de estado de 10×10px. La semántica (qué significa cada color) la define la vista, no el helper.

**Tooltips no implementados aún** — se agregarán en el componente cuando corresponda.

### Clase base

```css
.ds-dot { display: inline-block; width: 10px; height: 10px; border-radius: 999px; }
```

### Modificadores de paleta

| Clase               | Token usado             | Uso típico                    |
|---------------------|-------------------------|-------------------------------|
| `.ds-dot--success`  | `--color-success`       | Activo, al día, OK            |
| `.ds-dot--warning`  | `--color-warning`       | Próximo a vencer, atención    |
| `.ds-dot--danger`   | `--color-danger`        | Vencido, error, bloqueado     |
| `.ds-dot--neutral`  | `--color-sport-otro`    | Inactivo, sin estado          |
| `.ds-dot--active`   | `--color-success`       | Booleano: activo = verde      |

Uso típico:
```html
<span class="ds-dot ds-dot--success"></span>
```

---

## Reglas generales del sistema

1. **Prohibido hex en Blade y en CSS** (salvo dentro del bloque `@theme` en `app.css`).
2. **`surface-alt` solo en controles** — inputs, selects, FilterBar. Nunca en paneles.
3. **Rail y íconos de deporte** — los colores de sport son solo para `.ds-rail--*` y `.ds-icon--*`.
4. **Semáforo sin texto** — el dot es visual; el significado lo da contexto/tooltip (futuro).
5. **Brand como acento** — no pintar fondos enteros con `--color-brand`.
