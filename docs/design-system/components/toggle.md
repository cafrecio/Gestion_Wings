# Wings Design System — Componente `ds-toggle`

> Ruta: `resources/views/components/ds/toggle.blade.php`
> Invocación: `<x-ds.toggle ...>`

---

## Propósito

Switch booleano compacto: **ON** (verde, `--color-success`) o **OFF** (gris neutro).

**Solo para estados de 2 valores (on/off).** Para 3 estados o indicadores de lectura, usar `ds-dot`.

---

## Anatomía

```
[ícono]  Activo  [●───]    ← ON  (verde)
[ícono]  Inactivo  [───○]  ← OFF (gris)
```

- Ícono: opcional, izquierda.
- Texto: alterna entre `labelOn` / `labelOff` según estado (CSS puro).
- Track: 34×18px, border-radius pill.
- Thumb: 14×14px, se desplaza 16px al activarse.

---

## Props

| Prop        | Tipo     | Default       | Descripción                                          |
|-------------|----------|---------------|------------------------------------------------------|
| `id`        | `string\|null` | autogenerado | ID del `<input>`. Se autogenera con `uniqid()`.  |
| `name`      | `string\|null` | `null`       | Atributo `name` del input (para formularios).    |
| `checked`   | `bool`   | `false`       | Estado inicial.                                      |
| `disabled`  | `bool`   | `false`       | Deshabilita interacción (opacity + no pointer events). |
| `labelOn`   | `string` | `"Activo"`    | Texto visible cuando está activado.                  |
| `labelOff`  | `string` | `"Inactivo"`  | Texto visible cuando está desactivado.               |
| `size`      | `string` | `"sm"`        | Tamaño. Solo `"sm"` disponible en v1.                |

## Slots

| Slot   | Descripción                             |
|--------|-----------------------------------------|
| `icon` | Ícono a la izquierda (opcional).        |

---

## Tamaños

| Size  | Track         | Thumb   | Estado actual  |
|-------|---------------|---------|----------------|
| `sm`  | 34×18px       | 14×14px | Único en v1    |

---

## Reglas del sistema

1. **ON = verde (`--color-success`), OFF = gris neutro.** Sin excepciones.
2. **Nunca usar colores de deporte** (`--color-sport-*`) ni brand (`--color-brand`) en el toggle.
3. **El label es afirmativo**: define qué significa ON (ej: "Activo", "Abierta", "Habilitado").
4. **Sin JS necesario para el CSS**: la alternancia de texto y el estilo ON/OFF son CSS puro.
5. **El layout no salta**: el texto ON y OFF tienen el mismo espacio reservado visualmente.
6. **`role="switch"` + `aria-checked`** presentes para accesibilidad.
7. **v1 solo `size="sm"`**: no inventar otros tamaños hasta revisión de pantalla.

---

## Ejemplos

```blade
{{-- Básico (usa defaults: Activo/Inactivo) --}}
<x-ds.toggle name="activo" :checked="$alumno->activo" />

{{-- Labels personalizados --}}
<x-ds.toggle
    name="caja_abierta"
    :checked="$caja->abierta"
    labelOn="Abierta"
    labelOff="Cerrada"
/>

{{-- Con ícono --}}
<x-ds.toggle name="habilitado" :checked="$item->habilitado" labelOn="Habilitado" labelOff="Inhabilitado">
    <x-slot:icon>⚙</x-slot:icon>
</x-ds.toggle>

{{-- Disabled --}}
<x-ds.toggle name="estado" :checked="true" :disabled="true" labelOn="Activo" labelOff="Inactivo" />

{{-- Dentro de formulario --}}
<form method="POST" action="...">
    @csrf
    <x-ds.toggle name="activo" :checked="old('activo', $registro->activo)" />
    <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
</form>
```

---

## Anti-ejemplos

```blade
{{-- ❌ MAL: toggle para 3 estados --}}
<x-ds.toggle labelOn="Al día" labelOff="Vencido" />
{{-- Si hay un tercer estado posible (ej: "Próximo"), usar ds-dot + texto, no toggle --}}

{{-- ❌ MAL: label de acción, no de estado --}}
<x-ds.toggle labelOn="Activar" labelOff="Desactivar" />
{{-- Los labels deben ser afirmativos de estado, no verbos de acción --}}

{{-- ❌ MAL: hex inline --}}
<x-ds.toggle style="color: #16A34A" />

{{-- ❌ MAL: size inventado --}}
<x-ds.toggle size="lg" />
{{-- En v1 solo existe size="sm" --}}

{{-- ❌ MAL: toggle como indicador de lectura (sin nombre/formulario) --}}
{{-- Si no interactúa con el usuario, usar ds-dot en su lugar --}}
```
