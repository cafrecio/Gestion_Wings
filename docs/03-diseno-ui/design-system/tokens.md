# Wings Design System — Tokens

> Fuente de verdad visual del proyecto. Los tokens se definen como CSS custom properties
> en `resources/css/app.css`: base en `@theme` (Tailwind v4), y ajustes móviles
> en `:root` dentro de `@media (max-width: 640px)`.
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

## Acciones en celular — T75

Decisión de Carlos del 18/10/2026. Estos tokens rigen hasta 640 px; escritorio conserva sus medidas.

| Token | Valor | Uso |
|---|---|---|
| `--mobile-action-width` | `96px` | Todos los botones de acción con texto |
| `--mobile-action-height` | `32px` | Alto común |
| `--mobile-action-gap` | `var(--space-icon-gap)` = `8px` | Separación entre acciones |
| `--mobile-page-pad` | `var(--space-card-pad)` = `16px` | Margen lateral del contenido |
| `--mobile-actions-inset` | `calc(var(--space-card-pad) + var(--space-icon-gap))` = `24px` | Referencia interior común para el borde derecho |

Botón: padding `0`, `box-sizing:border-box`, fuente `var(--font-sans)`, tamaño `0.82rem`,
peso `600`, interlínea `1`. La referencia es el Cobrar de la ficha del alumno.
El padding efectivo de una superficie descuenta su borde o riel; los grupos anidados
no suman otro margen. Los pies fijos de formulario suman 16 + 24 px para llegar al
mismo borde del contenido. Los controles de ícono y los interruptores conservan su tipo.

[Reglas de posición y aceptación](DESIGN-RULES.md#celular--consistencia-y-ubicación-predecible-decisión-de-carlos-18102026) ·
[Implementación y capturas](../../06-pruebas/PRU-04/IMPLEMENTACION-T75-CONSISTENCIA.md).

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


## Cifras comparables — T75, 18/10/2026

`ds-money-value`: familia `var(--font-sans)`, `0.9rem`, peso `700`, interlínea `1.5`,
color `var(--color-text)` y cifras tabulares. Se aplica a los importes comparables de Liquidación,
incluidos encabezado, resumen, detalles y pie, sin modificar el valor ni su formato.
`ds-count-value`: `0.85rem` y peso `600`; el conteo no supera visualmente al dinero.
El criterio del resto de los módulos sigue en consulta: no extender estos tamaños a tablas
compactas o indicadores de forma automática. [Alcance comprobado](../../06-pruebas/PRU-04/IMPLEMENTACION-T75-OBSERVACIONES.md).
