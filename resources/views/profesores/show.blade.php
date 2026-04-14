@extends('layouts.app')

@section('title', $profesor->apellido . ', ' . $profesor->nombre . ' – Wings')
@section('module-title', $profesor->apellido . ', ' . $profesor->nombre)

@section('content')

@php
    $dep = mb_strtolower($profesor->deporte->nombre ?? '');
    $dep = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $sport = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
    $sportColor = "var(--color-sport-{$sport})";
@endphp

{{-- Barra de acciones --}}
<div class="filtros-actions mb-4" style="justify-content: flex-end;">
    <span style="
        font-size: 0.7rem; font-weight: 600;
        padding: 0.2rem 0.65rem; border-radius: 999px;
        background: {{ $profesor->activo ? 'color-mix(in srgb, var(--color-success) 15%, transparent)' : 'color-mix(in srgb, var(--color-danger) 15%, transparent)' }};
        color: {{ $profesor->activo ? 'var(--color-success)' : 'var(--color-danger)' }};
    ">{{ $profesor->activo ? 'Activo' : 'Inactivo' }}</span>
    <x-ds.button variant="secondary" href="{{ route('web.profesores.edit', $profesor->id) }}">Editar</x-ds.button>
</div>

{{-- Layout 2 columnas --}}
<div class="grid grid-cols-1 md:grid-cols-5 gap-4">

    {{-- Columna izquierda: información (3/5) --}}
    <div class="md:col-span-3 filtros-card">

        <div class="grid grid-cols-2 gap-x-6 gap-y-4 mb-4">

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Deporte
                </p>
                <p class="text-sm font-medium text-wings">{{ $profesor->deporte->nombre ?? '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Tipo de liquidación
                </p>
                <p class="text-sm font-medium text-wings">
                    @if($profesor->liquidaPorHora())
                        Por hora
                    @elseif($profesor->liquidaPorComision())
                        Por comisión
                    @else
                        –
                    @endif
                </p>
            </div>

            @if($profesor->liquidaPorHora())
            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Valor por hora
                </p>
                <p class="text-sm font-medium text-wings">
                    {{ $profesor->valor_hora ? '$' . number_format($profesor->valor_hora, 2, ',', '.') : '–' }}
                </p>
            </div>
            @elseif($profesor->liquidaPorComision())
            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Porcentaje de comisión
                </p>
                <p class="text-sm font-medium text-wings">
                    {{ $profesor->porcentaje_comision ? $profesor->porcentaje_comision . '%' : '–' }}
                </p>
            </div>
            @endif

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Teléfono
                </p>
                <p class="text-sm font-medium text-wings">{{ $profesor->telefono ?: '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email
                </p>
                <p class="text-sm font-medium text-wings">{{ $profesor->email ?: '–' }}</p>
            </div>

        </div>

    </div>

    {{-- Columna derecha: widgets placeholder (2/5) --}}
    <div class="md:col-span-2 flex flex-col gap-4">

        {{-- Historial de liquidaciones --}}
        <div class="filtros-card flex-1" style="border: 1px dashed var(--color-border);">
            <p class="flex items-center gap-1.5 mb-2" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historial de liquidaciones
            </p>
            <p class="text-xs text-wings-muted" style="opacity: 0.6;">Próximamente.</p>
        </div>

        {{-- Clases asignadas --}}
        <div class="filtros-card flex-1" style="border: 1px dashed var(--color-border);">
            <p class="flex items-center gap-1.5 mb-2" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Clases asignadas
            </p>
            <p class="text-xs text-wings-muted" style="opacity: 0.6;">Próximamente.</p>
        </div>

    </div>

</div>

{{-- Volver --}}
<div class="filtros-actions mt-4" style="justify-content: flex-end;">
    <x-ds.button variant="secondary" href="{{ route('web.profesores.index') }}">Volver</x-ds.button>
</div>

@endsection
