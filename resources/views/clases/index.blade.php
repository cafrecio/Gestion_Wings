@extends('layouts.app')

@section('title', 'Clases – Wings')
@section('module-title', 'Clases')

@section('content')

{{-- Filtros --}}
<form method="GET" action="{{ route('web.clases.index') }}" id="filter-form">
    <div class="filtros-card">
        <div class="filtros-row" style="flex-wrap:wrap; gap:8px;">

            <select id="filter-deporte" name="deporte_id" class="filtros-control filtros-select">
                <option value="">Todos los deportes</option>
                @foreach($deportes as $deporte)
                    <option value="{{ $deporte->id }}" {{ request('deporte_id') == $deporte->id ? 'selected' : '' }}>
                        {{ $deporte->nombre }}
                    </option>
                @endforeach
            </select>

            <select id="filter-grupo" name="grupo_id" class="filtros-control filtros-select">
                <option value="">Todos los grupos</option>
                @foreach($grupos as $grupo)
                    <option value="{{ $grupo->id }}"
                            data-deporte="{{ $grupo->deporte_id }}"
                            {{ request('grupo_id') == $grupo->id ? 'selected' : '' }}>
                        {{ $grupo->nombre }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="fecha_desde" value="{{ $fechaDesde }}"
                   class="filtros-control" style="width:auto;">

            <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}"
                   class="filtros-control" style="width:auto;">

            <select name="cancelada" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Todas</option>
                <option value="0" {{ request('cancelada') === '0' ? 'selected' : '' }}>Activas</option>
                <option value="1" {{ request('cancelada') === '1' ? 'selected' : '' }}>Canceladas</option>
            </select>

            <div class="filtros-actions" style="margin-left:auto;">
                <x-ds.button variant="secondary" href="{{ route('web.clases.index') }}">Limpiar</x-ds.button>
                <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>
            </div>
        </div>
    </div>
</form>

{{-- Stats bar --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        @if($clases->total() > 0)
            <strong>{{ $clases->total() }}</strong> clase(s) encontradas
        @else
            <strong>0</strong> clases encontradas
        @endif
    </div>
    @if($esAdmin)
        <x-ds.button variant="primary" href="{{ route('web.clases.create') }}">Nueva Clase</x-ds.button>
    @endif
</div>

{{-- Cards --}}
@php
    $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
@endphp

@forelse($clases as $clase)
@php
    $dep  = mb_strtolower($clase->grupo->deporte->nombre ?? '');
    $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');

    $esFutura      = $clase->fecha->isFuture();
    $tieneAsistencia = $clase->asistencias->where('presente', true)->count() > 0;

    if ($clase->cancelada)    $dot = 'danger';
    elseif ($esFutura)        $dot = 'neutral';
    elseif ($tieneAsistencia) $dot = 'success';
    else                      $dot = 'warning';
@endphp

<div class="alumno-card alumno-card--{{ $rail }}">

    <div class="alumno-card-header">
        <span class="alumno-dot alumno-dot--{{ $dot }}"></span>
        <h3 class="alumno-nombre">
            {{ $diasSemana[$clase->fecha->dayOfWeek] }}
            {{ $clase->fecha->format('d/m/Y') }}
            — {{ $clase->hora_inicio->format('H:i') }} a {{ $clase->hora_fin->format('H:i') }}
        </h3>
    </div>

    <div class="alumno-info">

        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="info-label">Grupo:</span>
            <span class="info-value ds-truncate">{{ $clase->grupo->nombre_completo }}</span>
        </div>

        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="info-label">Deporte:</span>
            <span class="info-value">{{ $clase->grupo->deporte->nombre ?? '–' }}</span>
        </div>

        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="info-label">Profesores:</span>
            <span class="info-value ds-truncate">
                @if($clase->profesores->isNotEmpty())
                    {{ $clase->profesores->map(fn($p) => $p->apellido . ', ' . $p->nombre)->implode(' · ') }}
                @else
                    <span style="opacity:0.45;">Sin asignar</span>
                @endif
            </span>
        </div>

        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="info-label">Estado:</span>
            <span class="info-value">
                @if($clase->cancelada)
                    <span style="color:var(--color-danger); font-weight:600;">Cancelada</span>
                    @if($clase->motivo_cancelacion)
                        <span style="color:var(--color-text-muted); font-weight:400;"> — {{ $clase->motivo_cancelacion }}</span>
                    @endif
                @elseif($esFutura)
                    <span style="color:var(--color-text-muted);">Programada</span>
                @elseif($tieneAsistencia)
                    <span style="color:var(--color-success); font-weight:600;">Realizada</span>
                @else
                    <span style="color:var(--color-warning); font-weight:600;">Sin asistencia</span>
                @endif
                @if($clase->validada_para_liquidacion)
                    <span style="font-size:0.65rem; font-weight:700; padding:2px 7px; border-radius:999px; margin-left:4px;
                                 background:color-mix(in srgb, var(--color-btn-primary) 15%, transparent);
                                 color:var(--color-btn-primary);">Validada</span>
                @endif
            </span>
        </div>

    </div>

    <div class="alumno-actions">
        <x-ds.button variant="primary" href="{{ route('web.clases.show', $clase->id) }}">Ver</x-ds.button>
        @if($esAdmin)
            <x-ds.button variant="secondary" href="{{ route('web.clases.edit', $clase->id) }}">Editar</x-ds.button>
        @endif
    </div>

</div>
@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h3>No se encontraron clases</h3>
        <p>Intentá con otros filtros o creá una nueva clase</p>
    </div>
@endforelse

{{-- Paginación --}}
@if($clases->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $clases->links() }}
    </div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const filterForm    = document.getElementById('filter-form');
    const deporteSelect = document.getElementById('filter-deporte');
    const grupoSelect   = document.getElementById('filter-grupo');

    function filtrarGruposPorDeporte(deporteId) {
        grupoSelect.querySelectorAll('option[data-deporte]').forEach(function (opt) {
            opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
        });
        if (deporteId) {
            const current = grupoSelect.querySelector('option[value="' + grupoSelect.value + '"]');
            if (current && current.dataset.deporte && current.dataset.deporte !== deporteId) {
                grupoSelect.value = '';
            }
        }
    }

    if (deporteSelect) {
        deporteSelect.addEventListener('change', function () {
            filtrarGruposPorDeporte(this.value);
            filterForm.submit();
        });
        if (deporteSelect.value) filtrarGruposPorDeporte(deporteSelect.value);
    }
    if (grupoSelect) {
        grupoSelect.addEventListener('change', function () { filterForm.submit(); });
    }
})();
</script>
@endpush
