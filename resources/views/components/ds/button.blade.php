@props([
    'variant'  => 'secondary',  // primary | secondary | ghost | danger
    'type'     => 'button',     // button | submit
    'disabled' => false,
    'loading'  => false,
    'iconOnly' => false,
    'href'     => null,
])

@php
    $isDisabled = $disabled || $loading;

    $classes = implode(' ', array_filter([
        'ds-btn',
        'ds-btn--' . $variant,
        $iconOnly ? 'ds-btn--icon' : null,
        $loading  ? 'ds-btn--loading' : null,
    ]));
@endphp

{{-- Renderizar como <a role="button"> solo si hay href y no está deshabilitado --}}
@if($href && !$isDisabled)

    <a {{ $attributes->class([$classes]) }}
       href="{{ $href }}"
       role="button">

        @if($loading)
            <span class="ds-btn__spinner" aria-hidden="true"></span>
            <span class="sr-only">Cargando…</span>
        @else
            @if(isset($icon) && $icon->isNotEmpty())
                <span class="ds-btn__icon" aria-hidden="true">{{ $icon }}</span>
            @endif

            @if($iconOnly)
                <span class="sr-only">{{ $slot }}</span>
            @else
                {{ $slot }}
            @endif
        @endif

    </a>

@else

    <button {{ $attributes->class([$classes]) }}
            type="{{ $type }}"
            @if($isDisabled) disabled aria-disabled="true" @endif>

        @if($loading)
            <span class="ds-btn__spinner" aria-hidden="true"></span>
            <span class="sr-only">Cargando…</span>
        @else
            @if(isset($icon) && $icon->isNotEmpty())
                <span class="ds-btn__icon" aria-hidden="true">{{ $icon }}</span>
            @endif

            @if($iconOnly)
                <span class="sr-only">{{ $slot }}</span>
            @else
                {{ $slot }}
            @endif
        @endif

    </button>

@endif
