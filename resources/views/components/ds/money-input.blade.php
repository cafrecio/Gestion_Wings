@props([
    'id'          => null,
    'name',
    'value'       => '',
    'placeholder' => '0',
    'required'    => false,
    'disabled'    => false,
])

@php
    // Mostrar el valor formateado con separador de miles (punto)
    $display = $value !== '' && $value !== null
        ? number_format((float) $value, 0, ',', '.')
        : '';
@endphp

<div {{ $attributes->class(['money-input-wrap']) }}>
    <span class="money-prefix" aria-hidden="true">$</span>
    <input
        type="text"
        @if($id) id="{{ $id }}" @endif
        name="{{ $name }}"
        value="{{ $display }}"
        placeholder="{{ $placeholder }}"
        inputmode="numeric"
        autocomplete="off"
        data-money="true"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        class="w-full py-2.5 text-sm wings-input money-input"
    >
</div>
