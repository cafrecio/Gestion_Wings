# Wings Design System — Tokens

> Fuente de verdad visual del proyecto. Los tokens se definen como CSS custom properties
> en `resources/css/app.css` dentro del bloque `@theme` (Tailwind v4).
> **Ningún valor hex puede aparecer hardcodeado en archivos Blade.**

---

## Neutrales fríos (base)

| Token                  | Valor     | Uso                                        |
|------------------------|-----------|--------------------------------------------|
| `--color-bg`           | `#F4F6F8` | Fondo general de la app                    |
| `--color-surface`      | `#FFFFFF` | Superficie de cards y paneles              |
| `--color-surface-alt`  | `#EEF2F6` | Superficie secundaria (filas alternas, etc.) |
| `--color-border`       | `#D8E0EA` | Bordes de cards, inputs y divisores        |
| `--color-text`         | `#111827` | Texto principal                            |
| `--color-text-muted`   | `#6B7280` | Texto secundario / ayudas / labels         |

---

## Marca (acento controlado)

| Token            | Valor     | Uso                                               |
|------------------|-----------|---------------------------------------------------|
| `--color-brand`  | `#BE123C` | CTA principal, links activos, highlights de marca |

Regla: usar como **acento puntual**, no como color de fondo en bloques grandes.

---

## Semánticos

| Token              | Valor     | Uso                                     |
|--------------------|-----------|------------------------------------------|
| `--color-success`  | `#16A34A` | Confirmaciones, estados OK               |
| `--color-warning`  | `#F59E0B` | Alertas, advertencias no críticas        |
| `--color-info`     | `#2563EB` | Información contextual, tooltips         |
| `--color-danger`   | `#B91C1C` | Errores, acciones destructivas           |

Regla: los semánticos se usan en texto, íconos o bordes de alerta. **No como fondos de secciones grandes.**

---

## Deportes / Categóricos

| Token                  | Valor     | Uso                                        |
|------------------------|-----------|--------------------------------------------|
| `--color-sport-patin`  | `#FF1493` | Identificación de patín en rail / íconos   |
| `--color-sport-futbol` | `#00FFD6` | Identificación de fútbol en rail / íconos  |
| `--color-sport-otro`   | `#5B5B5B` | Otras disciplinas                          |

Regla: **exclusivos para el rail de navegación y los íconos de deporte**. Nunca como color de fondo de bloques o cards.

---

## Radius

| Token            | Valor  | Uso                    |
|------------------|--------|------------------------|
| `--radius-card`  | `12px` | Cards y paneles        |
| `--radius-btn`   | `6px`  | Botones y badges       |

Nota: valores inspirados en el sistema CyE, ajustables tras verificar en pantalla real.

---

## Spacing de Card

| Token                 | Valor  | Uso                                      |
|-----------------------|--------|------------------------------------------|
| `--space-card-pad`    | `16px` | Padding interno de una card              |
| `--space-card-gap`    | `12px` | Gap entre elementos dentro de una card   |
| `--space-actions-gap` | `12px` | Gap entre botones de acción              |
| `--space-icon-gap`    | `8px`  | Gap entre ícono y texto adyacente        |

Nota: valores inspirados en el sistema CyE, ajustables tras verificar en pantalla real.

---

## Compatibilidad (aliases)

| Token                        | Resuelve a            | Motivo                                   |
|------------------------------|-----------------------|------------------------------------------|
| `--color-wings-primary`      | `var(--color-brand)`  | Alias para no romper referencias previas |
| `--color-wings-primary-hover`| `#A30F33`             | Derivado de brand para estados hover     |

---

## Reglas del sistema

1. **Prohibido hardcodear hex en Blade.** Todos los colores deben referenciarse vía token (`var(--color-brand)` o clase Tailwind generada desde el token).
2. **Tokens de deporte solo en rail e íconos.** Nunca como fondo de secciones.
3. **Semánticos sin bloques grandes.** Solo en texto, íconos, bordes de alerta.
4. **Brand como acento.** Reservar para CTAs y highlights, no pintar fondos enteros.
5. **Radius y spacing son base.** Vienen inspirados en CyE; se ajustan tras ver en pantalla.
