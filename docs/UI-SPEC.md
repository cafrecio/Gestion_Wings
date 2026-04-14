# UI-SPEC — Wings Gestión
> Decisiones de diseño definidas sesión a sesión. Leer antes de crear cualquier vista.
> Última actualización: 2026-03-07

---

## 1. TOKENS DE COLOR

```css
/* Neutrales */
--color-bg:           #F4F6F8   /* fondo global */
--color-surface:      #FFFFFF   /* fondo de cards y paneles */
--color-surface-alt:  #EEF2F6   /* fondo de inputs y hover suave */
--color-border:       #C4CED8   /* borde de cards, inputs, dividers */
--color-text:         #111827   /* texto principal */
--color-text-muted:   #6B7280   /* texto secundario, labels */

/* Marca — solo para chrome del sistema */
--color-brand:        #BE123C   /* header de módulo ÚNICAMENTE */
--color-chrome:       #4A4A4A   /* sidebar */

/* Botones de acción (paleta slate) */
--color-btn-primary:   #4A6880  /* acción principal */
--color-btn-secondary: #6888A0  /* acción secundaria */
--color-btn-danger:    #C07878  /* acción destructiva */

/* Semánticos */
--color-success:  #16A34A       /* mensajes de éxito */
--color-warning:  #F59E0B       /* alertas */
--color-danger:   #B91C1C       /* errores críticos */

/* Deportes — solo rail izquierdo e íconos */
--color-sport-patin:  #FF1493
--color-sport-futbol: #51D1F6
--color-sport-otro:   #5B5B5B
```

---

## 2. LAYOUT

```
┌─────────────────────────────────────────────┐
│  ds-sidebar (#4A4A4A)  │  ds-main           │
│  ─ Logo con fondo      │  ─ ds-topbar       │
│    blanco pill         │  ─ ds-module-header│  ← SIEMPRE presente
│  ─ Nav links blancos   │  ─ ds-content      │
│                        │  ─ ds-footer       │
└─────────────────────────────────────────────┘
```

### Reglas de layout
- El sidebar nunca tiene color de marca. Es siempre `--color-chrome` (#4A4A4A).
- El `ds-module-header` lleva el gradiente de marca (rojo). Es la única zona roja del contenido.
- `ds-content` sin `max-width`: las cards ocupan todo el ancho disponible.
- El header **nunca tiene botones** bajo ninguna circunstancia.

---

## 3. MODULE HEADER

Cada vista debe declarar:
```blade
@section('module-title', 'Título de la pantalla')
```

- Título: nombre del módulo en texto plano, sin íconos, sin subtítulos.
- Para vistas dinámicas: `@section('module-title', $alumno->apellido . ', ' . $alumno->nombre)`
- Sin botones, sin acciones, sin nada más.

---

## 4. BOTONES

### Sistema: todos los botones son SÓLIDOS

| Variante | Token | Color | Uso |
|----------|-------|-------|-----|
| `primary` | `--color-btn-primary` | #4A6880 | Acción principal: Guardar, Nuevo, Ver, Cobrar |
| `secondary` | `--color-btn-secondary` | #6888A0 | Acción secundaria: Editar, Filtrar, Limpiar |
| `danger` | `--color-btn-danger` | #C07878 | Acción destructiva: Eliminar, Rechazar |
| `ghost` | transparente | — | Solo chrome del sistema (topbar "Salir") |

### Reglas
- Hover: `inset 0 0 0 999px rgba(0,0,0,0.12)` — oscurece el sólido levemente
- Disabled: `opacity: 0.45` — botones que aún no tienen backend wired
- **Nunca** usar "Nuevo + contexto" (ej: "Nuevo alumno"). El contexto lo da la pantalla. Solo "Nuevo".
- Los botones de filtrar/limpiar van en `.filtros-actions` dentro del `.filtros-card`.
- El botón "Nuevo" va en la `.stats-bar`, alineado a la derecha.

---

## 5. TOGGLE

- Estado ON: `--color-btn-primary` (#4A6880) — NO verde
- Estado OFF: gris neutro (`--color-border`)
- El toggle representa estado real del objeto (activo/inactivo). Si el estado no está wired, va `disabled`.

---

## 6. PATRÓN INDEX (vistas de listado)

Estructura canónica — replicar en TODOS los listados:

```blade
{{-- 1. Filtros --}}
<form method="GET" action="{{ route(...) }}">
    <div class="filtros-card">
        <div class="filtros-row">
            {{-- inputs de búsqueda y selects --}}
            <div class="filtros-actions">
                <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>
                <x-ds.button variant="secondary" href="{{ route(...) }}">Limpiar</x-ds.button>
            </div>
        </div>
    </div>
</form>

{{-- 2. Stats bar --}}
<div class="stats-bar mb-3">
    <div class="stats-info">Mostrando X a Y de Z items</div>
    @if(Auth::user()->rol === 'ADMIN')
        <x-ds.button variant="primary" href="{{ route(...create) }}">Nuevo</x-ds.button>
    @endif
</div>

{{-- 3. Cards / listado --}}
@forelse($items as $item)
    {{-- card --}}
@empty
    {{-- empty-state --}}
@endforelse

{{-- 4. Paginación --}}
```

### Reglas del filtros-card
- Fondo blanco, `border: 1px solid var(--color-border)`, `border-radius: 12px`
- Los filtros que aplican se envían por GET, no JS/AJAX (formulario estándar)

---

## 7. CARDS DE ENTIDAD (alumno-card)

```
┌─[ rail deporte ]──────────────────────────────────┐
│  ● Apellido, Nombre                               │
│  🗂 DNI: —   ⚡ Deporte: Fútbol   👥 Grupo: X   👤 Tutor: —│
│  [ Cobrar ]  [ Ver ]  [ Editar ]  Activo ○        │
└───────────────────────────────────────────────────┘
```

- Rail izquierdo: 4px sólido con `--color-sport-*`
- Borde resto: `1px solid var(--color-border)` (por orden, DESPUÉS de border-left)
- El dot `●` toma el color del estado de cobranza cuando esté wired (AL_DIA, MOROSO, DEUDOR)
- Hover: sombra + `translateY(-4px)`

---

## 8. COLORES DE DEPORTE

| Deporte | Token | Hex | Aplica a |
|---------|-------|-----|----------|
| Patín | `--color-sport-patin` | #FF1493 | Rail + íconos |
| Fútbol | `--color-sport-futbol` | #51D1F6 | Rail + íconos |
| Otro | `--color-sport-otro` | #5B5B5B | Rail + íconos |

> Los colores de deporte NO se usan como fondos ni como color de botones.

---

## 9. FORMULARIOS (patrón create/edit) — PENDIENTE

> A definir en la sesión de `alumnos/create`. Ver punto en progreso.

---

## 10. VISTAS COMPLETADAS

| Vista | Estado |
|-------|--------|
| `alumnos/index` | ✅ Referencia canónica |
| `alumnos/create` | ✅ Completada |
| `alumnos/edit` | ✅ Completada |
| `alumnos/show` | ✅ Completada |
| `operativo/caja` | ✅ Completada — lista deudores + cobrar |
| `operativo/cobrar` | ✅ Completada — form cobro FIFO |
| `admin/dashboard` | ✅ Completada — KPIs + accesos rápidos |

---

## REGLAS GENERALES

1. **Cero improvización**: si un elemento no está en este doc, pausar y definirlo.
2. **Cero hardcode de colores**: siempre tokens (`var(--color-*)`).
3. **Cero Tailwind utilitario en componentes nuevos**: usar clases DS o crear clase nueva en `app.css`.
4. **Todos los layouts**: `@extends('layouts.app')` + `@section('module-title', '...')`.
5. **Rojo de marca** (`--color-brand`): solo en `ds-module-header`. En ningún otro lugar del contenido.
