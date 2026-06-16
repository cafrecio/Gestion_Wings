# Wings Design System — Layout Canónico

> Archivo base: `resources/views/layouts/ds-app.blade.php`

---

## Estructura

```
┌─────────────────────────────────────────────────────┐
│  ds-sidebar (240px fijo)  │  ds-main (flex column)  │
│                           │  ┌─────────────────────┐│
│  [logo]                   │  │ ds-topbar            ││  (usuario + Salir)
│                           │  ├─────────────────────┤│
│  [nav links]              │  │ ds-module-header     ││  (color-chrome #4A4A4A)
│                           │  ├─────────────────────┤│
│                           │  │ ds-content           ││  (max-w 1200px)
│                           │  ├─────────────────────┤│
│                           │  │ ds-footer            ││
│                           │  └─────────────────────┘│
└─────────────────────────────────────────────────────┘
```

---

## Colores del layout

| Zona           | Token                 | Valor     |
|----------------|-----------------------|-----------|
| Sidebar        | `--color-surface`     | `#FFFFFF` |
| Topbar         | `--color-surface`     | `#FFFFFF` |
| Module Header  | `--color-chrome`      | `#4A4A4A` |
| Fondo global   | `--color-bg`          | `#F4F6F8` |
| Bordes         | `--color-border`      | `#D8E0EA` |
| Footer         | `--color-surface`     | `#FFFFFF` |

---

## Cómo usar el layout

### Vista nueva (forma canónica)

```blade
@extends('layouts.app')

@section('title', 'Módulo – Wings')
@php $title = 'Módulo'; @endphp  {{-- pasa el título al module-header --}}

@section('content')
    {{-- contenido aquí --}}
@endsection
```

### Vista legacy (compatibilidad durante migración)

```blade
@extends('layouts.panel')   {{-- shim: delega a ds-app --}}

@section('panel-content')
    {{-- contenido aquí --}}
@endsection
```
El module-header no mostrará título hasta que la vista sea migrada y se agregue `@php($title = '...')`.

---

## Cadena de layouts

```
ds-app.blade.php          → layout canónico (HTML + sidebar + topbar + module-header + content + footer)
  ↑
app.blade.php             → alias de ds-app (una línea)
  ↑
panel.blade.php           → shim de compatibilidad (pasa panel-content → content)
```

---

## Module Header

Componente: `<x-ds.module-header :title="$title" />`

- Fondo: `--color-chrome` (`#4A4A4A`)
- Texto: `#fff` (único literal blanco permitido fuera de tokens)
- Sin botones, sin breadcrumbs, sin acciones. Solo título.
- Slot `icon` opcional a la izquierda.

---

## Reglas

1. **Sin fondos oscuros en body**: el fondo global lo aporta `--color-bg`.
2. **Module Header sin botones**: solo título. Los botones van en FilterBar o en actions de cada card.
3. **`$title` en cada vista**: toda vista que use ds-app debe setear `@php($title = 'Nombre')`.
4. **Nav links**: usar `.ds-nav-link` y `.ds-nav-link--active`. No inline styles en la nav.
5. **Flash messages**: `ds-flash--success` y `ds-flash--error`, rendereados por el layout.

---

## Migración pendiente

Vistas que todavía usan `@extends('layouts.panel')` y deben migrarse a `@extends('layouts.app')`:
- `admin/dashboard.blade.php`
- `operativo/caja.blade.php` (y similares)
