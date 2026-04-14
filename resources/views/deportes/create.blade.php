@extends('layouts.app')

@section('title', 'Nuevo Deporte – Wings')
@section('module-title', 'Nuevo Deporte')

@section('content')

@php
$iconAttr = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

<div class="filtros-card">
    <form method="POST" action="{{ route('web.deportes.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Nombre --}}
            <div>
                <label for="nombre" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Nombre <span class="form-required">*</span>
                </label>
                <input type="text"
                       id="nombre"
                       name="nombre"
                       value="{{ old('nombre') }}"
                       required
                       autofocus
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Ej: Fútbol, Patín...">
                @error('nombre')
                    <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo Liquidación --}}
            <div>
                <label for="tipo_liquidacion" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Tipo de Liquidación <span class="form-required">*</span>
                </label>
                <select id="tipo_liquidacion"
                        name="tipo_liquidacion"
                        required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    <option value="HORA"     {{ old('tipo_liquidacion') === 'HORA'     ? 'selected' : '' }}>Por hora</option>
                    <option value="COMISION" {{ old('tipo_liquidacion') === 'COMISION' ? 'selected' : '' }}>Por comisión</option>
                </select>
                @error('tipo_liquidacion')
                    <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.deportes.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>

    </form>
</div>

@endsection
