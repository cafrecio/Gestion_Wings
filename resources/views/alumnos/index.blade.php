@extends('layouts.app')

@section('title', 'Alumnos – Wings')
@php $title = 'Alumnos'; @endphp

@section('content')

    {{-- Filtros --}}
    <form method="GET" action="{{ route('web.alumnos.index') }}">
        <div class="filtros-card">
            <div class="filtros-row">

                {{-- Search --}}
                <div class="search-input-group">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nombre, apellido o DNI..."
                           class="filtros-control">
                </div>

                {{-- Deporte --}}
                <select name="deporte_id" class="filtros-control filtros-select">
                    <option value="">Todos los deportes</option>
                    @foreach($deportes as $deporte)
                        <option value="{{ $deporte->id }}"
                                {{ request('deporte_id') == $deporte->id ? 'selected' : '' }}>
                            {{ $deporte->nombre }}
                        </option>
                    @endforeach
                </select>

                {{-- Grupo --}}
                <select name="grupo_id" class="filtros-control filtros-select">
                    <option value="">Todos los grupos</option>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}"
                                {{ request('grupo_id') == $grupo->id ? 'selected' : '' }}>
                            {{ $grupo->nombre }}
                        </option>
                    @endforeach
                </select>

                {{-- Acciones --}}
                <div class="filtros-actions">

                    <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>

                    <x-ds.button variant="ghost"
                                 href="{{ route('web.alumnos.index') }}"
                                 :iconOnly="true">
                        <x-slot:icon>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </x-slot:icon>
                        Limpiar
                    </x-ds.button>

                    @if(Auth::user()->rol === 'ADMIN')
                        <x-ds.button variant="primary" href="{{ route('web.alumnos.create') }}">
                            Nuevo
                        </x-ds.button>
                    @endif

                </div>
            </div>
        </div>
    </form>

    {{-- Stats bar --}}
    <div class="stats-bar mb-3">
        <div class="stats-info">
            @if($alumnos->total() > 0)
                Mostrando <strong>{{ $alumnos->firstItem() }}</strong>
                a <strong>{{ $alumnos->lastItem() }}</strong>
                de <strong>{{ $alumnos->total() }}</strong> alumnos
            @else
                <strong>0</strong> alumnos encontrados
            @endif
        </div>
    </div>

    {{-- Listado --}}
    @forelse($alumnos as $alumno)

        @php
            $dep  = mb_strtolower($alumno->deporte->nombre ?? '');
            $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail = str_contains($dep, 'pat') ? 'patin'
                  : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}">

            <div class="alumno-card-header">
                <span class="alumno-dot alumno-dot--neutral" title="Estado (pendiente)"></span>
                <h3 class="alumno-nombre">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h3>
            </div>

            <div class="alumno-info">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                    </svg>
                    <span class="info-label">DNI:</span>
                    <span class="info-value ds-truncate">{{ $alumno->dni ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="info-label">Deporte:</span>
                    <span class="info-value ds-truncate">{{ $alumno->deporte->nombre ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Grupo:</span>
                    <span class="info-value ds-truncate">{{ $alumno->grupo->nombre ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="info-label">Tutor:</span>
                    <span class="info-value ds-truncate">{{ $alumno->nombre_tutor ?? '–' }}</span>
                </div>
            </div>

            <div class="alumno-actions">
                <x-ds.button variant="secondary" :disabled="true">Cobrar</x-ds.button>

                <x-ds.button variant="primary"
                             href="{{ route('web.alumnos.show', $alumno->id) }}">
                    Ver
                </x-ds.button>

                @if(Auth::user()->rol === 'ADMIN')
                    <x-ds.button variant="secondary"
                                 href="{{ route('web.alumnos.edit', $alumno->id) }}">
                        Editar
                    </x-ds.button>
                @endif

                <x-ds.toggle
                    labelOn="Activo"
                    labelOff="Inactivo"
                    :checked="(bool) $alumno->activo"
                    :disabled="true"
                />
            </div>

        </div>

    @empty

        <div class="empty-state">
            <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h3>No se encontraron alumnos</h3>
            <p>Intentá con otros criterios de búsqueda</p>
        </div>

    @endforelse

    {{-- Paginación --}}
    @if($alumnos->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $alumnos->links() }}
        </div>
    @endif

@endsection
