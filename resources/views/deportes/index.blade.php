@extends('layouts.app')

@section('title', 'Deportes – Wings')
@section('module-title', 'Deportes')

@section('content')

    {{-- Stats bar --}}
    <div class="stats-bar mb-3">
        <div class="stats-info">
            <strong>{{ $deportes->count() }}</strong> {{ $deportes->count() === 1 ? 'deporte registrado' : 'deportes registrados' }}
        </div>
        <x-ds.button variant="primary" href="{{ route('web.deportes.create') }}">
            Nuevo
        </x-ds.button>
    </div>

    @forelse($deportes as $deporte)

        @php
            $dep   = mb_strtolower($deporte->nombre);
            $dep   = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail  = str_contains($dep, 'pat') ? 'patin'
                   : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}">

            <div class="alumno-card-header">
                <span class="alumno-dot {{ $deporte->activo ? 'alumno-dot--success' : 'alumno-dot--neutral' }}"></span>
                <h3 class="alumno-nombre">{{ $deporte->nombre }}</h3>
            </div>

            <div class="alumno-info">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Liquidación:</span>
                    <span class="info-value">{{ $deporte->tipo_liquidacion === 'HORA' ? 'Por hora' : 'Por comisión' }}</span>
                </div>
            </div>

            <div class="alumno-actions">
                <x-ds.toggle
                    labelOn="Activo"
                    labelOff="Inactivo"
                    :checked="(bool) $deporte->activo"
                    data-url="{{ route('web.deportes.toggle-activo', $deporte->id) }}"
                />

                <x-ds.button variant="secondary"
                             href="{{ route('web.deportes.edit', $deporte->id) }}">
                    Editar
                </x-ds.button>
            </div>

        </div>

    @empty

        <div class="empty-state">
            <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <h3>No hay deportes registrados</h3>
            <p>Creá el primer deporte para comenzar.</p>
        </div>

    @endforelse

@endsection

