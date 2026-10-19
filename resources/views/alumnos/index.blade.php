@extends('layouts.app')

@section('title', 'Alumnos – Wings')
@section('module-title', 'Alumnos')

@section('content')

    {{-- Filtros --}}
    <form method="GET" action="{{ route('web.alumnos.index') }}">
        <div class="filtros-card">
            <div class="filtros-row">

                {{-- Search con autocomplete --}}
                <div class="search-input-group" style="position:relative;">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text"
                           id="search-input"
                           data-autocomplete-url="{{ route('web.alumnos.autocomplete') }}"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nombre, apellido o DNI..."
                           class="filtros-control"
                           autocomplete="off"
                           role="combobox"
                           aria-expanded="false"
                           aria-controls="search-dropdown"
                           aria-autocomplete="list">
                    <ul id="search-dropdown" role="listbox" style="
                        display:none; position:absolute; top:100%; left:0; right:0; z-index:100;
                        margin-top:4px; padding:4px 0; list-style:none;
                        background:var(--color-surface); border:1px solid var(--color-border);
                        border-radius:var(--radius-card); box-shadow:0 4px 16px rgba(0,0,0,.12);
                        max-height:320px; overflow-y:auto;
                    "></ul>
                </div>

                {{-- Deporte --}}
                <select id="filter-deporte" name="deporte_id" class="filtros-control filtros-select">
                    <option value="">Deporte</option>
                    @foreach($deportes as $deporte)
                        <option value="{{ $deporte->id }}"
                                {{ request('deporte_id') == $deporte->id ? 'selected' : '' }}>
                            {{ $deporte->nombre }}
                        </option>
                    @endforeach
                </select>

                {{-- Grupo --}}
                <select id="filter-grupo" name="grupo_id" class="filtros-control filtros-select">
                    <option value="">Grupo</option>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}"
                                {{ request('grupo_id') == $grupo->id ? 'selected' : '' }}>
                            {{ $grupo->nombre }}
                        </option>
                    @endforeach
                </select>

                {{-- Acciones filtro --}}
                <div class="filtros-actions">
                    <x-ds.button variant="secondary" href="{{ route('web.alumnos.index') }}">Limpiar</x-ds.button>
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
        @if(auth()->user()->isAdmin() || auth()->user()->isOperativo())
            <x-ds.button variant="primary" href="{{ route('web.alumnos.create') }}">
                Nuevo
            </x-ds.button>
        @endif
    </div>

    {{-- Listado: una tarjeta por fila, opción B aprobada por Carlos --}}
    @php
        $alumnos->getCollection()->loadMissing('planActivo.plan');
    @endphp
    <div class="alumnos-listado">
    @forelse($alumnos as $alumno)

        @php
            $dep  = mb_strtolower($alumno->deporte->nombre ?? '');
            $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail = str_contains($dep, 'pat') ? 'patin'
                  : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}">

            <div class="alumno-card-header">
                @php
                    $estadoCobranza = $alumno->activo
                        ? ($estadosCobranza[$alumno->id] ?? 'AL_DIA')
                        : null;
                    $dotClass = match($estadoCobranza) {
                        'AL_DIA' => 'success',
                        'EN_PLAZO' => 'info',
                        'MOROSO' => 'warning',
                        'DEUDOR' => 'danger',
                        default => 'neutral',
                    };
                    $estadoTitle = match($estadoCobranza) {
                        'AL_DIA' => 'Al día',
                        'EN_PLAZO' => 'En plazo',
                        'MOROSO' => 'Moroso',
                        'DEUDOR' => 'Deudor',
                        default => 'Inactivo',
                    };
                @endphp
                <span class="alumno-dot alumno-dot--{{ $dotClass }}"
                      title="{{ $estadoTitle }}"></span>
                <h3 class="alumno-nombre">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h3>
            </div>

            <div class="alumno-info">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Deporte:</span>
                    <span class="info-value">{{ $alumno->deporte->nombre ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Grupo:</span>
                    <span class="info-value">{{ $alumno->grupo->nombre_completo ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="info-label">Plan:</span>
                    <span class="info-value">{{ $alumno->planActivo?->plan ? $alumno->planActivo->plan->clases_por_semana . ($alumno->planActivo->plan->clases_por_semana == 1 ? ' clase/semana' : ' clases/semana') : 'Sin plan' }}</span>
                </div>
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
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="info-label">Celular:</span>
                    <span class="info-value">{{ $alumno->celular ?: '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="info-label">Edad:</span>
                    <span class="info-value">
                        @if($alumno->fecha_nacimiento)
                            {{ $alumno->fecha_nacimiento->age }} años
                            @if($alumno->fecha_nacimiento->month === now()->month) 🎂 @endif
                        @else –
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="info-label">Tutor:</span>
                    <span class="info-value">{{ $alumno->nombre_tutor ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="info-label">Tel. tutor:</span>
                    <span class="info-value">{{ $alumno->telefono_tutor ?? '–' }}</span>
                </div>
            </div>

            <div class="alumno-actions max-sm:justify-end">
                <x-ds.button variant="secondary"
                             href="{{ route('web.caja.cobrar', $alumno->id) }}">Cobrar</x-ds.button>

                <x-ds.button variant="primary"
                             href="{{ route('web.alumnos.show', $alumno->id) }}">
                    Ver
                </x-ds.button>

                <x-ds.button variant="secondary"
                             href="{{ route('web.alumnos.edit', $alumno->id) }}">
                    Editar
                </x-ds.button>

                <x-ds.toggle
                    labelOn="Activo"
                    labelOff="Inactivo"
                    :checked="(bool) $alumno->activo"
                    data-url="{{ route('web.alumnos.toggle-activo', $alumno->id) }}"
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
    </div>

    {{-- Paginación --}}
    @if($alumnos->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $alumnos->links() }}
        </div>
    @endif

@endsection

@push('scripts')
    @vite('resources/js/alumnos-index.js')
@endpush
