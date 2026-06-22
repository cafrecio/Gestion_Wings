@extends('layouts.ds-app')

@section('title', 'Cobrar cuota – Wings')
@section('module-title', 'Cobrar cuota')

@section('content')

{{-- Buscador --}}
<form method="GET" action="{{ route('web.caja.cobrar-cuota') }}">
    <div class="filtros-card">
        <div class="filtros-row">
            <div class="search-input-group" style="position:relative; flex:1;">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre, apellido o DNI..."
                       class="filtros-control" autocomplete="off">
            </div>
            <div class="filtros-actions">
                <x-ds.button variant="primary" type="submit">Buscar</x-ds.button>
                <x-ds.button variant="secondary" href="{{ route('web.caja.cobrar-cuota') }}">Limpiar</x-ds.button>
            </div>
        </div>
    </div>
</form>

<div class="stats-bar mb-3">
    <div class="stats-info">
        @if($alumnos->total() > 0)
            <strong>{{ $alumnos->total() }}</strong>
            {{ $alumnos->total() === 1 ? 'alumno con deuda pendiente' : 'alumnos con deuda pendiente' }}
        @else
            Sin deudas pendientes
        @endif
    </div>
    <a href="{{ route('web.caja.index') }}"
       class="ds-btn" style="background:var(--color-btn-secondary); color:var(--color-surface);">Volver</a>
</div>

@forelse($alumnos as $alumno)
    @php
        $dep  = mb_strtolower($alumno->deporte->nombre ?? '');
        $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        $saldo  = $alumno->deudaCuotas->sum('saldo_pendiente');
        $cuotas = $alumno->deudaCuotas->count();
    @endphp
    <div class="alumno-card alumno-card--{{ $rail }}">
        <div class="alumno-card-header">
            <span class="alumno-dot alumno-dot--danger"></span>
            <h3 class="alumno-nombre">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h3>
        </div>
        <div class="alumno-info" style="grid-template-columns: repeat(3, 1fr);">
            <div class="info-item">
                <span class="info-label">Saldo:</span>
                <span class="info-value" style="font-weight:700; color:var(--color-danger);">
                    ${{ number_format($saldo, 0, ',', '.') }}
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Cuotas:</span>
                <span class="info-value">{{ $cuotas }} pendiente{{ $cuotas !== 1 ? 's' : '' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Grupo:</span>
                <span class="info-value ds-truncate">{{ $alumno->grupo->nombre_completo ?? '–' }}</span>
            </div>
        </div>
        <div class="alumno-actions">
            <x-ds.button variant="primary" href="{{ route('web.caja.cobrar', $alumno->id) }}">Cobrar</x-ds.button>
            <x-ds.button variant="secondary" href="{{ route('web.alumnos.show', $alumno->id) }}">Ver</x-ds.button>
        </div>
    </div>
@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3>Sin deudas pendientes</h3>
        <p>
            @if(request('search'))
                No se encontraron alumnos con deuda para "{{ request('search') }}"
            @else
                Todos los alumnos están al día
            @endif
        </p>
    </div>
@endforelse

@if($alumnos->hasPages())
    <div class="mt-4 flex justify-center">{{ $alumnos->links() }}</div>
@endif

@endsection
