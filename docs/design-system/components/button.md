# Wings Design System — Componente `ds-button`

> Ruta: `resources/views/components/ds/button.blade.php`
> Invocación: `<x-ds.button ...>`

---

## Propósito

Botón canónico del DS. 4 variantes, 2 tamaños (texto / icon-only), soporte para loading,
disabled, ícono a la izquierda y render como `<a>` cuando se pasa `href`.

---

## Variantes (solo estas 4)

| Variante    | Fondo            | Borde               | Color texto         | Hover                          |
|-------------|------------------|---------------------|---------------------|--------------------------------|
| `primary`   | `--color-text`   | `--color-text`      | `--color-surface`   | Overlay negro sutil (inset)    |
| `secondary` | Transparente     | `--color-border`    | `--color-text`      | Fondo `--color-surface-alt`    |
| `ghost`     | Transparente     | Sin borde           | `--color-text`      | Fondo `--color-surface-alt`    |
| `danger`    | Transparente     | `--color-danger`    | `--color-danger`    | Tint rojo muy suave (8%)       |

**Danger: SIEMPRE outline rojo. Nunca sólido rojo.**

---

## Tamaños

| Modo        | Ancho  | Alto  | Clase adicional  |
|-------------|--------|-------|------------------|
| Con texto   | 112px  | 36px  | _(ninguna)_      |
| Icon-only   | 36px   | 36px  | `.ds-btn--icon`  |

Los tamaños son fijos. No se auto-expanden ni se truncan.

---

## Props

| Prop       | Tipo            | Default      | Valores                                 |
|------------|-----------------|--------------|-----------------------------------------|
| `variant`  | `string`        | `secondary`  | `primary \| secondary \| ghost \| danger` |
| `type`     | `string`        | `button`     | `button \| submit`                      |
| `disabled` | `bool`          | `false`      | —                                       |
| `loading`  | `bool`          | `false`      | Muestra spinner, bloquea interacción    |
| `iconOnly` | `bool`          | `false`      | Solo ícono, label como `sr-only`        |
| `href`     | `string \| null` | `null`       | Renderiza `<a role="button">` si existe |

## Slots

| Slot      | Descripción                                          |
|-----------|------------------------------------------------------|
| _(default)_ | Label del botón. Una sola palabra (obligatorio).   |
| `icon`    | Ícono a la izquierda del label (opcional).           |

---

## Reglas del sistema

1. **Una sola palabra en el label**: Guardar, Cancelar, Cobrar, Finalizar, Volver, Editar, Ver, Anular, Eliminar.
2. **Sin truncado**: el ancho es fijo (112px); si el texto no entra, cambiá el label — no el ancho.
3. **Sin auto-width**: nunca remover `width: 112px` para acomodar texto largo.
4. **Danger siempre outline**: `variant="danger"` es rojo-borde, jamás rojo sólido.
5. **Loading mantiene layout**: el spinner ocupa el mismo espacio que el label; el botón no salta.
6. **Icon-only + accesibilidad**: pasá el label igual en el slot (se rendea como `sr-only`).
7. **href + disabled**: si el botón está disabled o loading, se renderiza `<button>` aunque se pase `href`.

---

## Ejemplos

```blade
{{-- Primary (acción principal de formulario) --}}
<x-ds.button variant="primary" type="submit">Guardar</x-ds.button>

{{-- Secondary con link (default) --}}
<x-ds.button href="{{ route('alumnos.show', $alumno) }}">Ver</x-ds.button>

{{-- Ghost (acción liviana) --}}
<x-ds.button variant="ghost">Cancelar</x-ds.button>

{{-- Danger (acción destructiva) --}}
<x-ds.button variant="danger">Eliminar</x-ds.button>

{{-- Loading (procesando pago) --}}
<x-ds.button variant="primary" :loading="$procesando">Cobrar</x-ds.button>

{{-- Con ícono a la izquierda --}}
<x-ds.button variant="secondary">
    <x-slot:icon>↩</x-slot:icon>
    Volver
</x-ds.button>

{{-- Icon-only (36×36, label accesible por sr-only) --}}
<x-ds.button variant="ghost" :iconOnly="true">
    <x-slot:icon>✕</x-slot:icon>
    Cerrar
</x-ds.button>

{{-- Submit de formulario, deshabilitado --}}
<x-ds.button variant="primary" type="submit" :disabled="!$form->isValid()">
    Finalizar
</x-ds.button>
```

---

## Anti-ejemplos

```blade
{{-- ❌ MAL: label de dos palabras --}}
<x-ds.button>Ver detalle</x-ds.button>

{{-- ❌ MAL: danger sólido (el DS no lo permite) --}}
{{-- No existe variante "danger-solid"; usar variant="danger" que es outline --}}

{{-- ❌ MAL: variante inventada --}}
<x-ds.button variant="warning">Atención</x-ds.button>

{{-- ❌ MAL: hex inline --}}
<x-ds.button style="background: #BE123C">Guardar</x-ds.button>

{{-- ❌ MAL: texto largo que rompe el ancho fijo --}}
<x-ds.button>Guardar cambios</x-ds.button>

{{-- ❌ MAL: icon-only sin texto en el slot (rompe accesibilidad) --}}
<x-ds.button variant="ghost" :iconOnly="true">
    <x-slot:icon>✕</x-slot:icon>
    {{-- Falta el label para sr-only --}}
</x-ds.button>
```
