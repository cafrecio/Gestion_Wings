@props([
    'id'       => null,
    'name'     => null,
    'checked'  => false,
    'disabled' => false,
    'labelOn'  => 'Activo',
    'labelOff' => 'Inactivo',
    'size'     => 'sm',       // solo sm en v1
])

@php
    $toggleId = $id ?? 'ds-toggle-' . uniqid();
@endphp

<label
    {{ $attributes->class([
        'ds-toggle',
        'ds-toggle--' . $size,
        $disabled ? 'ds-toggle--disabled' : null,
    ]) }}
    for="{{ $toggleId }}"
>
    {{-- Ícono opcional a la izquierda --}}
    @if(isset($icon) && $icon->isNotEmpty())
        <span class="ds-toggle__icon" aria-hidden="true">{{ $icon }}</span>
    @endif

    {{-- Texto: ON/OFF se alterna vía CSS (:has en label) --}}
    <span class="ds-toggle__text">
        <span class="ds-toggle__label is-on">{{ $labelOn }}</span>
        <span class="ds-toggle__label is-off">{{ $labelOff }}</span>
    </span>

    {{-- Input real: visualmente oculto pero focusable --}}
    <input
        type="checkbox"
        id="{{ $toggleId }}"
        class="ds-toggle__input"
        @if($name) name="{{ $name }}" @endif
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
    />

    {{-- Track visual (viene después del input para selectores :checked +) --}}
    <span class="ds-toggle__track" aria-hidden="true">
        <span class="ds-toggle__thumb"></span>
    </span>
</label>
