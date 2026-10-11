@props([
    'id'          => null,
    'name'        => '',
    'value'       => '',
    'placeholder' => '0',
    'required'    => false,
    'disabled'    => false,
    'readonly'    => false,
    'min'         => '0',
    'max'         => null,
    'prefix'      => true,
    'wrapperClass'=> '',
])

@php
    $display = \App\Rules\ImportePesos::mostrar($value);
@endphp

<div class="money-input-wrap {{ $wrapperClass }}">
    @if($prefix)
    <span class="money-prefix" aria-hidden="true">$</span>
    @endif
    <input
        type="text"
        @if($id) id="{{ $id }}" @endif
        @if($name !== '') name="{{ $name }}" @endif
        value="{{ $display }}"
        placeholder="{{ $placeholder }}"
        inputmode="decimal"
        autocomplete="off"
        data-money="true"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        min="{{ $min }}"
        @if($max !== null) max="{{ $max }}" @endif
        {{ $attributes->class(['w-full py-2.5 text-sm wings-input', 'px-4' => !$prefix, 'money-input' => $prefix]) }}
    >
</div>
