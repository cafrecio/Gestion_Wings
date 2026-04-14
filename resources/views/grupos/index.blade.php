@extends('layouts.app')

@section('title', 'Grupos – Wings')
@section('module-title', 'Grupos')

@section('content')

    {{-- Filtros --}}
    <form method="GET" action="{{ route('web.grupos.index') }}">
        <div class="filtros-card">
            <div class="filtros-row">

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
                    <x-ds.button variant="secondary" href="{{ route('web.grupos.index') }}">Limpiar</x-ds.button>
                </div>
            </div>
        </div>
    </form>

    {{-- Stats bar --}}
    <div class="stats-bar mb-3">
        <div class="stats-info">
            @if($grupos->total() > 0)
                Mostrando <strong>{{ $grupos->firstItem() }}</strong>
                a <strong>{{ $grupos->lastItem() }}</strong>
                de <strong>{{ $grupos->total() }}</strong> grupos
            @else
                <strong>0</strong> grupos encontrados
            @endif
        </div>
        @if(Auth::user()->rol === 'ADMIN')
            <x-ds.button variant="primary" href="{{ route('web.grupos.create') }}">
                Nuevo
            </x-ds.button>
        @endif
    </div>

    {{-- Listado --}}
    @forelse($grupos as $grupo)

        @php
            $dep  = mb_strtolower($grupo->deporte->nombre ?? '');
            $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail = str_contains($dep, 'pat') ? 'patin'
                  : (str_contains($dep, 'fut') ? 'futbol' : 'otro');

            $alumnosActivos = $grupo->alumnos->where('activo', true)->count();

            $planesLabel = $grupo->planesActivos
                ->sortBy('clases_por_semana')
                ->map(fn($p) => $p->clases_por_semana . 'x/sem — $' . number_format($p->precio_mensual, 0, ',', '.'))
                ->join(' · ');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}">

            <div class="alumno-card-header">
                <span class="alumno-dot alumno-dot--neutral" title="Estado"></span>
                <h3 class="alumno-nombre">{{ $grupo->nombre }}</h3>
            </div>

            <div class="alumno-info">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="info-label">Deporte:</span>
                    <span class="info-value ds-truncate">{{ $grupo->deporte->nombre ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Alumnos activos:</span>
                    <span class="info-value">{{ $alumnosActivos }}</span>
                </div>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Planes:</span>
                    <span class="info-value ds-truncate">{{ $planesLabel ?: '–' }}</span>
                </div>
            </div>

            <div class="alumno-actions">
                <x-ds.button variant="primary"
                             href="{{ route('web.grupos.show', $grupo->id) }}">
                    Ver
                </x-ds.button>

                @if(auth()->user()->rol === 'ADMIN')
                    <x-ds.button variant="secondary"
                                 href="{{ route('web.grupos.edit', $grupo->id) }}">
                        Editar
                    </x-ds.button>

                    <form class="toggle-activo-form"
                          method="POST"
                          action="{{ route('web.grupos.toggle-activo', $grupo->id) }}">
                        @csrf @method('PATCH')
                        <x-ds.toggle
                            labelOn="Activo"
                            labelOff="Inactivo"
                            :checked="(bool) $grupo->activo"
                        />
                    </form>
                @endif
            </div>

        </div>

    @empty

        <div class="empty-state">
            <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3>No se encontraron grupos</h3>
            <p>Intentá con otros criterios de búsqueda</p>
        </div>

    @endforelse

    {{-- Paginación --}}
    @if($grupos->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $grupos->links() }}
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
