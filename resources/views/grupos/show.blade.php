@extends('layouts.app')

@section('title', $grupo->nombre . ' – Wings')
@section('module-title', $grupo->nombre)

@section('content')

@php
    $dep = mb_strtolower($grupo->deporte->nombre ?? '');
    $dep = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $sport = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
    $sportColor = "var(--color-sport-{$sport})";
    $alumnosActivos = $grupo->alumnos->where('activo', true);
    $isAdmin = auth()->user()->rol === 'ADMIN';
@endphp

{{-- Barra de acciones --}}
<div class="filtros-actions mb-4" style="justify-content: flex-end;">
    <span style="
        font-size: 0.7rem; font-weight: 600;
        padding: 0.2rem 0.65rem; border-radius: 999px;
        background: {{ $grupo->activo ? 'color-mix(in srgb, var(--color-success) 15%, transparent)' : 'color-mix(in srgb, var(--color-danger) 15%, transparent)' }};
        color: {{ $grupo->activo ? 'var(--color-success)' : 'var(--color-danger)' }};
    ">{{ $grupo->activo ? 'Activo' : 'Inactivo' }}</span>
    @if($isAdmin)
        <x-ds.button variant="secondary" href="{{ route('web.grupos.edit', $grupo->id) }}">Editar</x-ds.button>
    @endif
</div>

{{-- Grid plano: cada fila alinea alturas automáticamente --}}
<div class="grid grid-cols-1 md:grid-cols-5 gap-4">

    {{-- Datos del grupo --}}
    <div class="md:col-span-3 filtros-card">
        <div class="grid grid-cols-2 gap-x-6 gap-y-4">

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Nombre
                </p>
                <p class="text-sm font-medium text-wings">{{ $grupo->nombre }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Deporte
                </p>
                <p class="text-sm font-medium text-wings">{{ $grupo->deporte->nombre ?? '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    Alumnos
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumnosActivos->count() }} activos / {{ $grupo->alumnos->count() }} total</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Creación
                </p>
                <p class="text-sm font-medium text-wings">{{ $grupo->created_at->format('d/m/Y') }}</p>
            </div>

        </div>
    </div>

    {{-- Alumnos en este grupo --}}
    <div class="md:col-span-2 filtros-card">
        <p class="flex items-center gap-1.5 mb-3" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
            <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            Alumnos en este grupo
        </p>

        @if($alumnosActivos->isEmpty())
            <p class="text-xs text-wings-muted" style="opacity: 0.7;">Sin alumnos activos en este grupo.</p>
        @else
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.35rem;">
                @foreach($alumnosActivos->sortBy('apellido')->take(5) as $alumno)
                    <li>
                        <a href="{{ route('web.alumnos.show', $alumno->id) }}"
                           style="font-size: 0.82rem; color: var(--color-text); text-decoration: none; display: block; padding: 0.3rem 0.4rem; border-radius: var(--radius-sm);"
                           onmouseover="this.style.background='var(--color-surface-alt)'"
                           onmouseout="this.style.background=''">
                            {{ $alumno->apellido }}, {{ $alumno->nombre }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if($alumnosActivos->count() > 5)
                <div class="mt-2">
                    <a href="{{ route('web.alumnos.index', ['grupo_id' => $grupo->id]) }}"
                       style="font-size: 0.75rem; color: var(--color-btn-primary); text-decoration: none; font-weight: 500;">
                        Ver todos ({{ $alumnosActivos->count() }})
                    </a>
                </div>
            @endif
        @endif
    </div>

    {{-- Precios por frecuencia --}}
    <div class="md:col-span-3 filtros-card">
        <p class="flex items-center gap-1.5 mb-3" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
            <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Precios por frecuencia
        </p>

        @if($grupo->planes->count() > 0)
            <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <th style="text-align: left; padding: 0.4rem 0.5rem; font-weight: 600; color: var(--color-text-muted); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Frecuencia</th>
                        <th style="text-align: left; padding: 0.4rem 0.5rem; font-weight: 600; color: var(--color-text-muted); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Precio mensual</th>
                        @if($isAdmin)<th style="padding: 0.4rem 0.5rem;"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo->planes as $plan)
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 0.5rem 0.5rem; font-weight: 500; color: var(--color-text);">
                                {{ $plan->clases_por_semana }}x / semana
                            </td>
                            <td style="padding: 0.5rem 0.5rem; color: var(--color-text);">
                                ${{ number_format($plan->precio_mensual, 0, ',', '.') }}
                            </td>
                            @if($isAdmin)
                                <td style="padding: 0.5rem 0.5rem; text-align: right;">
                                    <form method="POST" action="{{ route('web.grupos.plans.destroy', $plan->id) }}"
                                          onsubmit="return confirm('¿Eliminar este plan?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="
                                            font-size: 0.72rem; font-weight: 500;
                                            color: var(--color-danger);
                                            background: none; border: none; cursor: pointer;
                                            padding: 0.2rem 0.4rem; border-radius: var(--radius-sm);
                                        " onmouseover="this.style.background='color-mix(in srgb, var(--color-danger) 10%, transparent)'"
                                           onmouseout="this.style.background='none'">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-xs text-wings-muted" style="opacity: 0.7;">Sin precios cargados todavía.</p>
        @endif
    </div>

    {{-- Cronograma semanal --}}
    <div class="md:col-span-2 filtros-card" style="border: 1px dashed var(--color-border);">
        <p class="flex items-center gap-1.5 mb-2" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
            <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Cronograma semanal
        </p>
        <p class="text-xs text-wings-muted" style="opacity: 0.6;">Próximamente: horario de clases del grupo.</p>
    </div>

</div>

{{-- Volver --}}
<div class="filtros-actions mt-4" style="justify-content: flex-end;">
    <x-ds.button variant="secondary" href="{{ route('web.grupos.index') }}">Volver</x-ds.button>
</div>

@endsection
