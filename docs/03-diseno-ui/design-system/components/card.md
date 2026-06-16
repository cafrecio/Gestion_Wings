# Wings Design System — Componente `ds-card`

> Componente Blade canónico para listados de entidades (alumnos, clases, deudas, etc.).
> Ruta: `resources/views/components/ds/card.blade.php`
> Invocación: `<x-ds.card ...>`

---

## Propósito

Card horizontal, compacta, full-width del contenedor. Inspirada en el Card de Clientes (CyE):
rail izquierdo de deporte, semáforo de estado, grid de info, acciones abajo-izquierda.

---

## Anatomía

```
┌─────────────────────────────────────────────────────────┐
│ ▌  ●  Nombre Apellido                                   │  ← rail | dot | title (header)
│    Disciplina    Categoría    Cuota       Estado        │  ← info grid (4 cols)
│    [Ver]  [Pagar]                                       │  ← actions
└─────────────────────────────────────────────────────────┘
  ▌ = rail (6px, color de deporte)
  ● = dot semáforo (10px, color semántico)
```

---

## Props

| Prop    | Tipo          | Default | Valores permitidos                        |
|---------|---------------|---------|-------------------------------------------|
| `href`  | `string\|null` | `null`  | Cualquier URL; si existe, card es clickeable |
| `rail`  | `string\|null` | `null`  | `patin` \| `futbol` \| `otro`              |
| `dot`   | `string\|null` | `null`  | `success` \| `warning` \| `danger` \| `neutral` \| `active` |
| `cols`  | `int`         | `4`     | `3` \| `4` \| `5`                          |
| `lines` | `int`         | `1`     | `1` \| `2`                                |

## Slots

| Slot      | Requerido | Descripción                                       |
|-----------|-----------|---------------------------------------------------|
| `title`   | Sí        | Nombre/título principal (texto plano o HTML mínimo) |
| `info`    | No        | Celdas del grid; cada hijo es una columna          |
| `actions` | No        | Botones de acción (quedan encima del overlay link) |

---

## Ejemplos de uso

### Básico (sin rail, sin dot)

```blade
<x-ds.card href="{{ route('alumnos.show', $alumno) }}" cols="4">
    <x-slot:title>{{ $alumno->nombre }} {{ $alumno->apellido }}</x-slot:title>

    <x-slot:info>
        <div><span class="text-xs text-[--color-text-muted]">Disciplina</span><p>{{ $alumno->disciplina }}</p></div>
        <div><span class="text-xs text-[--color-text-muted]">Categoría</span><p>{{ $alumno->categoria }}</p></div>
        <div><span class="text-xs text-[--color-text-muted]">Cuota</span><p>{{ $alumno->cuota }}</p></div>
        <div><span class="text-xs text-[--color-text-muted]">Estado</span><p>{{ $alumno->estado }}</p></div>
    </x-slot:info>

    <x-slot:actions>
        <a href="{{ route('alumnos.show', $alumno) }}" class="btn-secondary">Ver</a>
        <button class="btn-primary">Pagar</button>
    </x-slot:actions>
</x-ds.card>
```

### Con rail y dot de estado

```blade
<x-ds.card
    href="{{ route('alumnos.show', $alumno) }}"
    rail="{{ $alumno->disciplina === 'patín' ? 'patin' : 'futbol' }}"
    dot="{{ $alumno->al_dia ? 'success' : 'danger' }}"
    cols="4"
>
    <x-slot:title>{{ $alumno->nombre_completo }}</x-slot:title>
    ...
</x-ds.card>
```

### Sin link (solo display)

```blade
<x-ds.card cols="3" lines="2">
    <x-slot:title>Sin enlace</x-slot:title>
    <x-slot:info>...</x-slot:info>
</x-ds.card>
```

---

## Reglas de uso

1. **No vertical stacking de info**: el slot `info` es un grid horizontal. No uses listas verticales dentro de una celda.
2. **Máx 2 líneas por celda**: `lines="1"` (default) trunca con `…`; `lines="2"` clampea a 2 líneas. No usar más.
3. **Presets de columnas**: solo `cols="3"`, `cols="4"` o `cols="5"`. No valores intermedios.
4. **Rail solo para deporte**: `rail` mapea a los colores de deporte (`--color-sport-*`). No usarlo como indicador genérico.
5. **Dot = indicador de estado**: la semántica la define la vista. El dot es solo el helper visual.
6. **Acciones fuera del overlay**: los elementos en el slot `actions` son clicables independientemente porque tienen `z-index: 2` sobre el overlay link.

---

## Anti-ejemplos

```blade
{{-- ❌ MAL: hex hardcodeado en el slot --}}
<x-slot:title><span style="color: #BE123C">Nombre</span></x-slot:title>

{{-- ❌ MAL: rail como indicador de deuda --}}
<x-ds.card rail="patin"> {{-- rail es SOLO para disciplina deportiva --}}

{{-- ❌ MAL: más de 5 columnas --}}
<x-ds.card cols="6">

{{-- ❌ MAL: info vertical dentro de una celda --}}
<x-slot:info>
    <ul><li>A</li><li>B</li><li>C</li></ul>  {{-- se rompe el grid --}}
</x-slot:info>

{{-- ❌ MAL: inline styles --}}
<x-ds.card style="background: red;">
```
