@extends('layouts.app')

@section('title', 'Profesores – Wings')
@section('module-title', 'Profesores')

@section('content')

    {{-- Filtros --}}
    <form method="GET" action="{{ route('web.profesores.index') }}" autocomplete="off">
        <div class="filtros-card">
            <div class="filtros-row">

                {{-- Búsqueda --}}
                <div class="search-input-group" style="position:relative;">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nombre o apellido..."
                           class="filtros-control"
                           autocomplete="off">
                </div>

                {{-- Deporte --}}
                <select id="filter-deporte" name="deporte_id" class="filtros-control filtros-select">
                    <option value="">Todos los deportes</option>
                    @foreach($deportes as $deporte)
                        <option value="{{ $deporte->id }}"
                                {{ request('deporte_id') == $deporte->id ? 'selected' : '' }}>
                            {{ $deporte->nombre }}
                        </option>
                    @endforeach
                </select>

                {{-- Acciones filtro --}}
                <div class="filtros-actions">
                    <x-ds.button variant="secondary" href="{{ route('web.profesores.index') }}">Limpiar</x-ds.button>
                </div>
            </div>
        </div>
    </form>

    {{-- Stats bar --}}
    <div class="stats-bar mb-3">
        <div class="stats-info">
            @if($profesores->total() > 0)
                Mostrando <strong>{{ $profesores->firstItem() }}</strong>
                a <strong>{{ $profesores->lastItem() }}</strong>
                de <strong>{{ $profesores->total() }}</strong> profesores
            @else
                <strong>0</strong> profesores encontrados
            @endif
        </div>
        @if(Auth::user()->rol === 'ADMIN')
            <x-ds.button variant="primary" href="{{ route('web.profesores.create') }}">
                Nuevo
            </x-ds.button>
        @endif
    </div>

    {{-- Listado --}}
    @forelse($profesores as $profesor)

        @php
            $dep  = mb_strtolower($profesor->deporte->nombre ?? '');
            $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail = str_contains($dep, 'pat') ? 'patin'
                  : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}">

            <div class="alumno-card-header">
                <span class="alumno-dot {{ $profesor->activo ? 'alumno-dot--activo' : 'alumno-dot--inactivo' }}"
                      title="{{ $profesor->activo ? 'Activo' : 'Inactivo' }}"></span>
                <h3 class="alumno-nombre">{{ $profesor->apellido }}, {{ $profesor->nombre }}</h3>
                <span style="
                    margin-left: auto;
                    font-size: 0.65rem; font-weight: 600;
                    padding: 0.15rem 0.55rem; border-radius: 999px;
                    background: {{ $profesor->activo ? 'color-mix(in srgb, var(--color-success) 15%, transparent)' : 'color-mix(in srgb, var(--color-danger) 15%, transparent)' }};
                    color: {{ $profesor->activo ? 'var(--color-success)' : 'var(--color-danger)' }};
                ">{{ $profesor->activo ? 'Activo' : 'Inactivo' }}</span>
            </div>

            <div class="alumno-info">

                {{-- Deporte --}}
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="info-label">Deporte:</span>
                    <span class="info-value ds-truncate">{{ $profesor->deporte->nombre ?? '–' }}</span>
                </div>

                {{-- Tipo liquidación --}}
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Liquidación:</span>
                    <span class="info-value">
                        @if($profesor->liquidaPorHora())
                            Por hora
                        @elseif($profesor->liquidaPorComision())
                            Por comisión
                        @else
                            –
                        @endif
                    </span>
                </div>

                {{-- Valor (hora o comisión) --}}
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="info-label">
                        @if($profesor->liquidaPorHora()) Valor/hora: @else Comisión: @endif
                    </span>
                    <span class="info-value">
                        @if($profesor->liquidaPorHora())
                            {{ $profesor->valor_hora ? '$' . number_format($profesor->valor_hora, 0, ',', '.') : '–' }}
                        @elseif($profesor->liquidaPorComision())
                            {{ $profesor->porcentaje_comision ? $profesor->porcentaje_comision . '%' : '–' }}
                        @else
                            –
                        @endif
                    </span>
                </div>

                {{-- Teléfono --}}
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value ds-truncate">{{ $profesor->telefono ?? '–' }}</span>
                </div>

                {{-- Email --}}
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="info-label">Email:</span>
                    <span class="info-value ds-truncate">{{ $profesor->email ?? '–' }}</span>
                </div>

            </div>

            <div class="alumno-actions">
                <x-ds.button variant="primary"
                             href="{{ route('web.profesores.show', $profesor->id) }}">
                    Ver
                </x-ds.button>

                <x-ds.button variant="secondary"
                             href="{{ route('web.profesores.edit', $profesor->id) }}">
                    Editar
                </x-ds.button>

                <form class="toggle-activo-form"
                      method="POST"
                      action="{{ route('web.profesores.toggle-activo', $profesor->id) }}">
                    @csrf @method('PATCH')
                    <x-ds.toggle
                        labelOn="Activo"
                        labelOff="Inactivo"
                        :checked="(bool) $profesor->activo"
                    />
                </form>
            </div>

        </div>

    @empty

        <div class="empty-state">
            <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3>No se encontraron profesores</h3>
            <p>Intentá con otros criterios de búsqueda</p>
        </div>

    @endforelse

    {{-- Paginación --}}
    @if($profesores->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $profesores->links() }}
        </div>
    @endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('.toggle-activo-form').forEach(form => {
    form.querySelector('.ds-toggle__input').addEventListener('change', function () {
        form.submit();
    });
});

(function () {
    const filterForm    = document.querySelector('form[method="GET"]');
    const deporteSelect = document.getElementById('filter-deporte');

    if (deporteSelect) {
        deporteSelect.addEventListener('change', () => filterForm.submit());
    }
})();
</script>
@endpush
