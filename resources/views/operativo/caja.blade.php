@extends('layouts.app')

@section('title', 'Caja Operativa – Wings')
@section('module-title', 'Caja')

@section('content')

    {{-- Búsqueda --}}
    <form method="GET" action="{{ route('operativo.caja') }}">
        <div class="filtros-card">
            <div class="filtros-row">
                <div class="search-input-group" style="position:relative; flex:1;">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nombre, apellido o DNI..."
                           class="filtros-control"
                           autocomplete="off">
                </div>
                <div class="filtros-actions">
                    <x-ds.button variant="primary" type="submit">Buscar</x-ds.button>
                    <x-ds.button variant="secondary" href="{{ route('operativo.caja') }}">Limpiar</x-ds.button>
                </div>
            </div>
        </div>
    </form>

    {{-- Stats bar --}}
    <div class="stats-bar mb-3">
        <div class="stats-info">
            @if($alumnos->total() > 0)
                <strong>{{ $alumnos->total() }}</strong> {{ $alumnos->total() === 1 ? 'alumno con deuda pendiente' : 'alumnos con deuda pendiente' }}
            @else
                Sin deudas pendientes
            @endif
        </div>
    </div>

    {{-- Listado --}}
    @forelse($alumnos as $alumno)

        @php
            $dep   = mb_strtolower($alumno->deporte->nombre ?? '');
            $dep   = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail  = str_contains($dep, 'pat') ? 'patin'
                   : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
            $saldo  = $alumno->deudaCuotas->sum('saldo_pendiente');
            $cuotas = $alumno->deudaCuotas->count();
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}">

            <div class="alumno-card-header">
                <span class="alumno-dot alumno-dot--danger" title="Con deuda pendiente"></span>
                <h3 class="alumno-nombre">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h3>
            </div>

            <div class="alumno-info">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                    </svg>
                    <span class="info-label">DNI:</span>
                    <span class="info-value">{{ $alumno->dni ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Grupo:</span>
                    <span class="info-value">{{ $alumno->grupo->nombre ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Saldo:</span>
                    <span class="info-value" style="font-weight: 700; color: var(--color-danger);">
                        ${{ number_format($saldo, 0, ',', '.') }}
                    </span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="info-label">Cuotas:</span>
                    <span class="info-value">{{ $cuotas }} pendiente{{ $cuotas !== 1 ? 's' : '' }}</span>
                </div>
            </div>

            <div class="alumno-actions">
                <x-ds.button variant="primary"
                             href="{{ route('operativo.caja.cobrar', $alumno->id) }}">
                    Cobrar
                </x-ds.button>
                <x-ds.button variant="secondary"
                             href="{{ route('web.alumnos.show', $alumno->id) }}">
                    Ver
                </x-ds.button>
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

    {{-- Paginación --}}
    @if($alumnos->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $alumnos->links() }}
        </div>
    @endif

@endsection
