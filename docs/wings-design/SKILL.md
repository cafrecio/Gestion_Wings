---
name: wings-design
description: Design system completo para el proyecto Wings (Club Wings). Leer antes de crear o modificar cualquier vista Blade. Contiene tokens, componentes, reglas de layout y checklist de revisión.
---

# Wings — Design System

> Fuente de verdad para cualquier vista del panel Wings.
> Antes de entregar código de una vista, verificar cada punto del checklist final.
> Si algún punto falla, el código NO se entrega.

---

## Stack

- Laravel 11 + PHP 8.2 + MariaDB
- Blade puro + Tailwind CSS v4 + Vanilla JS (sin Alpine ni Livewire)
- Design system: clases `ds-*` + componentes Blade `x-ds.*`
- Timezone: America/Argentina/Buenos_Aires

---

## Tokens de color

> Prohibido hardcodear hex en Blade. Siempre usar `var(--token)`.

### Neutrales

| Token | Valor | Uso |
|-------|-------|-----|
| `--color-bg` | `#F4F6F8` | Fondo general |
| `--color-surface` | `#FFFFFF` | Cards y paneles |
| `--color-surface-alt` | `#EEF2F6` | Solo inputs y controles |
| `--color-border` | `#D8E0EA` | Bordes y divisores |
| `--color-text` | `#111827` | Texto principal |
| `--color-text-muted` | `#6B7280` | Texto secundario |

### Marca

| Token | Valor | Uso |
|-------|-------|-----|
| `--color-brand` | `#BE123C` | CTA principal, acento. Nunca como fondo de bloque. |
| `--color-wings-primary-hover` | `#A30F33` | Hover de brand |

### Semánticos

| Token | Valor | Uso |
|-------|-------|-----|
| `--color-success` | `#16A34A` | OK, activo, al día |
| `--color-warning` | `#F59E0B` | Alerta no crítica |
| `--color-info` | `#2563EB` | Info contextual |
| `--color-danger` | `#B91C1C` | Error, acción destructiva |

### Deportes (solo rail e íconos)

| Token | Valor | Deporte |
|-------|-------|---------|
| `--color-sport-patin` | `#FF1493` | Patín |
| `--color-sport-futbol` | `#00FFD6` | Fútbol |
| `--color-sport-otro` | `#5B5B5B` | Otros |

### Radius y spacing

| Token | Valor |
|-------|-------|
| `--radius-card` | `12px` |
| `--radius-btn` | `6px` |
| `--space-card-pad` | `16px` |
| `--space-card-gap` | `12px` |
| `--space-actions-gap` | `12px` |
| `--space-icon-gap` | `8px` |

---

## Layout canónico

```
ds-sidebar (240px) | ds-main
                   |  ds-topbar         → usuario + salir
                   |  ds-module-header  → color-chrome #4A4A4A, solo título
                   |  ds-content        → max-w 1200px
                   |  ds-footer
```

```blade
@extends('layouts.app')
@section('title', 'Módulo – Wings')
@php $title = 'Módulo'; @endphp

@section('content')
    {{-- contenido aquí --}}
@endsection
```

**Reglas de layout:**
- Module header: solo título. Sin botones, sin breadcrumbs.
- Toda vista debe setear `@php($title = 'Nombre')`.
- Nav links: usar `.ds-nav-link` y `.ds-nav-link--active`. Sin inline styles.

---

## Componente `x-ds.button`

### Variantes (solo estas 4)

| Variante | Fondo | Borde | Texto |
|----------|-------|-------|-------|
| `primary` | `--color-text` | `--color-text` | `--color-surface` |
| `secondary` | Transparente | `--color-border` | `--color-text` |
| `ghost` | Transparente | Sin borde | `--color-text` |
| `danger` | Transparente | `--color-danger` | `--color-danger` |

**Danger: SIEMPRE outline. Nunca sólido rojo.**

### Objetos de botón — tamaños fijos

**Objeto A — Stats-bar (acción primaria de página)**
- `width: 112px; height: 36px`
- Solo uno por sección, siempre a la derecha del stats-bar

**Objeto B — Acciones de card (alumno-actions)**
- `width: 96px; height: 32px; font-size: 0.82rem; font-weight: 600`
- Todos los botones del mismo card son idénticos en tamaño

**Objeto C — Acciones de fila de tabla**
- Texto: `height: 26px; width: 64px; font-size: 0.72rem`
- Ícono: `width: 28px; height: 28px`

### Reglas de botones

- Label: **una sola palabra en infinitivo**: Guardar · Cancelar · Cobrar · Editar · Eliminar · Ver · Volver · Nuevo · Registrar · Filtrar · Limpiar
- Sin redundancia de contexto: si estás en la página de Rubros, el botón es "Editar", no "Editar rubro"
- Ancho fijo: nunca remover `width` para acomodar texto largo — cambiá el label
- No mezclar Objeto B y C en la misma zona

```blade
<x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
<x-ds.button variant="danger">Eliminar</x-ds.button>
<x-ds.button variant="ghost">Cancelar</x-ds.button>
<x-ds.button href="{{ route('alumnos.show', $alumno) }}">Ver</x-ds.button>
```

---

## Componente `x-ds.card`

### Anatomía

```
┌──────────────────────────────────────────┐
│ ▌  ●  Nombre Apellido                    │  ← rail | dot | título (SOLO esto en header)
│    Dato1    Dato2    Dato3    Dato4      │  ← info grid horizontal
│    [Editar]                  [Eliminar]  │  ← alumno-actions (border-top)
└──────────────────────────────────────────┘
```

### Props

| Prop | Valores | Default |
|------|---------|---------|
| `rail` | `patin` \| `futbol` \| `otro` | null |
| `dot` | `success` \| `warning` \| `danger` \| `neutral` \| `active` | null |
| `cols` | `3` \| `4` \| `5` | `4` |
| `href` | URL | null |

### Reglas de cards

- Header: **SOLO** dot + título. Prohibido: botones, badges, subtítulos, fechas en el header.
- Info: grid horizontal, no listas verticales. Maximizar densidad horizontal antes de agregar fila.
- Acciones en `alumno-actions` al pie, con `border-top: 1px solid var(--color-border)`.
- Botón secundario (ej: "+ Subrubro") con `margin-left: auto` dentro de `alumno-actions`.
- Rail: solo para identificar deporte, nunca como indicador genérico.

---

## Componente `x-ds.toggle`

Switch booleano ON/OFF. Solo para 2 estados. Para 3 estados, usar `ds-dot`.

- ON: `--color-success` (verde)
- OFF: gris neutro
- Track: 34×18px · Thumb: 14×14px
- Labels: afirmativos de estado ("Activo/Inactivo"), nunca verbos ("Activar/Desactivar")

```blade
<x-ds.toggle name="activo" :checked="$alumno->activo" />
<x-ds.toggle name="estado" :checked="$caja->abierta" labelOn="Abierta" labelOff="Cerrada" />
```

---

## Helpers CSS

| Clase | Uso |
|-------|-----|
| `.ds-surface` | Cards y paneles |
| `.ds-surface-alt` | **SOLO** inputs y controles |
| `.ds-border` | Borde neutral |
| `.ds-dot .ds-dot--{success\|warning\|danger\|neutral}` | Semáforo de estado |
| `.ds-rail .ds-rail--{patin\|futbol\|otro}` | Rail de deporte (6px) |
| `.ds-nav-link` / `.ds-nav-link--active` | Links de sidebar |

---

## Stats-bar

```
[conteo / info del módulo]          [Botón primario →]
```

- `display: flex; justify-content: space-between; align-items: center`
- Un stats-bar por sección
- Si una subsección tiene acción primaria, tiene su propio stats-bar

---

## Tablas

- Siempre `table-layout: fixed` con `<colgroup>` explícito
- Columna Nombre: `auto` (toma el resto)
- Columna acciones: ancho fijo suficiente para los botones
- Celdas de texto: `overflow: hidden; text-overflow: ellipsis; white-space: nowrap`
- El borde derecho de los botones de tabla debe alinearse con los botones del pie del card

---

## Vista canónica de referencia

`resources/views/alumnos/index.blade.php` — toda vista nueva debe verse idéntica en estructura.

---

## Checklist del agente revisor

Antes de entregar cualquier vista, verificar:

- [ ] ¿El header de cada card tiene SOLO dot + título?
- [ ] ¿Las acciones están en `alumno-actions` al pie, con `border-top`?
- [ ] ¿El botón secundario ("+ hijo") tiene `margin-left: auto`?
- [ ] ¿Todos los botones del mismo objeto (A, B o C) tienen el mismo tamaño?
- [ ] ¿Los labels de botones son una sola palabra, sin redundancia de contexto?
- [ ] ¿Las tablas usan `table-layout: fixed` con `<colgroup>`?
- [ ] ¿Los botones de fila tienen `width` fijo?
- [ ] ¿El borde derecho de tabla y pie de card están alineados?
- [ ] ¿Datos de tipos distintos están en secciones separadas con su propio stats-bar?
- [ ] ¿Hay un único stats-bar por sección con un único botón primario a la derecha?
- [ ] ¿Ningún hex está hardcodeado en Blade?
- [ ] ¿`surface-alt` se usa solo en inputs y controles?
- [ ] ¿Los colores de deporte se usan solo en rail e íconos?
- [ ] ¿Toda vista tiene `@php($title = '...')`?
