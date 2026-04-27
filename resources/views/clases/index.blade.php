@extends('layouts.app')

@section('title', 'Clases – Wings')
@section('module-title', 'Clases')

@section('content')

@php
function claseEstado($clase, $ahora): string {
    if ($clase->cancelada) return 'cancelada';
    $fecha  = $clase->fecha->format('Y-m-d');
    $inicio = \Carbon\Carbon::parse($fecha . ' ' . $clase->hora_inicio->format('H:i'));
    $fin    = \Carbon\Carbon::parse($fecha . ' ' . $clase->hora_fin->format('H:i'));
    if ($clase->fecha->isToday()) {
        if ($ahora->lt($inicio->copy()->subHour())) return 'programada';
        if ($ahora->lt($inicio))                    return 'por_comenzar';
        if ($ahora->lte($fin))                      return 'en_curso';
        return $clase->asistencias->where('presente', true)->count() > 0
               ? 'cerrada' : 'finalizada';
    }
    if ($clase->fecha->isFuture()) return 'programada';
    return $clase->asistencias->where('presente', true)->count() > 0
           ? 'cerrada' : 'finalizada';
}

function estadoDot(string $estado): string {
    return match($estado) {
        'en_curso'     => 'success',
        'por_comenzar' => 'warning',
        'finalizada'   => 'warning',
        'cancelada'    => 'danger',
        'cerrada'      => 'neutral',
        default        => 'neutral',
    };
}

function estadoLabel(string $estado): string {
    return match($estado) {
        'programada'   => 'Programada',
        'por_comenzar' => 'Por comenzar',
        'en_curso'     => 'En curso',
        'finalizada'   => 'Finalizada',
        'cerrada'      => 'Cerrada',
        'cancelada'    => 'Cancelada',
        default        => $estado,
    };
}

function estadoColor(string $estado): string {
    return match($estado) {
        'en_curso'     => 'var(--color-success)',
        'por_comenzar' => 'var(--color-warning)',
        'finalizada'   => 'var(--color-warning)',
        'cancelada'    => 'var(--color-danger)',
        'cerrada'      => 'var(--color-btn-primary)',
        default        => 'var(--color-text-muted)',
    };
}

$diasSemana = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
@endphp

{{-- Sección B — Ventana de hoy --}}
<div class="stats-bar mb-2">
    <div class="stats-info" style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;">
        Clases de hoy — {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd D [de] MMMM') }}
        · <strong>{{ $clasesHoy->count() }}</strong> clase(s)
    </div>
    @if($esAdmin)
        <x-ds.button variant="primary" href="{{ route('web.clases.create') }}">
            Nuevo
        </x-ds.button>
    @endif
</div>

<div id="clases-hoy-container"
     style="max-height:240px; overflow-y:auto; display:flex;
            flex-direction:column; gap:0; scroll-behavior:smooth;">

    @forelse($clasesHoy as $clase)
        @php
            $estado = claseEstado($clase, $ahora);
            $dot    = estadoDot($estado);
            $label  = estadoLabel($estado);
            $color  = estadoColor($estado);
            $dep    = mb_strtolower($clase->grupo->deporte->nombre ?? '');
            $dep    = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail   = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}"
             id="clase-hoy-{{ $clase->id }}"
             data-estado="{{ $estado }}"
             style="{{ $clase->cancelada ? 'opacity:0.6;' : '' }}">

            <div class="alumno-card-header">
                <span class="alumno-dot alumno-dot--{{ $dot }}"
                      style="color:{{ $color }};"></span>
                <h3 class="alumno-nombre">
                    {{ $clase->hora_inicio->format('H:i') }}
                    –
                    {{ $clase->hora_fin->format('H:i') }}
                    <span style="font-weight:400; color:var(--color-text-muted);
                                 font-size:0.82rem; margin-left:6px;">
                        {{ $label }}
                    </span>
                </h3>
            </div>

            <div class="alumno-info" style="grid-template-columns: repeat(3, 1fr);">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Grupo:</span>
                    <span class="info-value">{{ $clase->grupo->nombre_completo }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="info-label">Profesores:</span>
                    <span class="info-value">
                        {{ $clase->profesores->isNotEmpty()
                           ? $clase->profesores->map(fn($p) => $p->apellido)->implode(' · ')
                           : '–' }}
                    </span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Asistencia:</span>
                    <span class="info-value"
                          style="color:{{ $clase->asistencias->where('presente', true)->count() > 0 ? 'var(--color-success)' : 'var(--color-text-muted)' }};">
                        {{ $clase->asistencias->where('presente', true)->count() > 0 ? 'Cargada' : 'Pendiente' }}
                    </span>
                </div>
            </div>

            <div class="alumno-actions">
                <x-ds.button variant="primary"
                             href="{{ route('web.clases.show', $clase->id) }}">
                    Ver
                </x-ds.button>
                @if($esAdmin)
                    <x-ds.button variant="secondary"
                                 href="{{ route('web.clases.edit', $clase->id) }}">
                        Editar
                    </x-ds.button>
                @endif
            </div>

        </div>
    @empty
        <div style="padding:24px; text-align:center;
                    color:var(--color-text-muted); font-size:0.85rem;">
            No hay clases programadas para hoy.
        </div>
    @endforelse

</div>

{{-- Sección C — Separador --}}
<div style="height:1px; background:var(--color-border); margin:1rem 0;"></div>

{{-- Sección D — Filtros --}}
<form method="GET" action="{{ route('web.clases.index') }}" id="filter-form">
    <div class="filtros-card">
        <div class="filtros-row" style="flex-wrap:wrap; gap:8px;">

            <select id="filter-deporte" name="deporte_id"
                    class="filtros-control filtros-select">
                <option value="">Todos los deportes</option>
                @foreach($deportes as $deporte)
                    <option value="{{ $deporte->id }}"
                            {{ request('deporte_id') == $deporte->id ? 'selected' : '' }}>
                        {{ $deporte->nombre }}
                    </option>
                @endforeach
            </select>

            <select id="filter-grupo" name="grupo_id"
                    class="filtros-control filtros-select">
                <option value="">Todos los grupos</option>
                @foreach($grupos as $grupo)
                    <option value="{{ $grupo->id }}"
                            data-deporte="{{ $grupo->deporte_id }}"
                            {{ request('grupo_id') == $grupo->id ? 'selected' : '' }}>
                        {{ $grupo->nombre_completo }}
                    </option>
                @endforeach
            </select>

            <select name="profesor_id" class="filtros-control filtros-select">
                <option value="">Todos los profesores</option>
                @foreach($profesores as $profesor)
                    <option value="{{ $profesor->id }}"
                            {{ request('profesor_id') == $profesor->id ? 'selected' : '' }}>
                        {{ $profesor->apellido }}, {{ $profesor->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="estado" id="filter-estado"
                    class="filtros-control filtros-select">
                <option value="">Todos los estados</option>
                <option value="programada"   {{ request('estado') === 'programada'   ? 'selected' : '' }}>Programada</option>
                <option value="por_comenzar" {{ request('estado') === 'por_comenzar' ? 'selected' : '' }}>Por comenzar</option>
                <option value="en_curso"     {{ request('estado') === 'en_curso'     ? 'selected' : '' }}>En curso</option>
                <option value="finalizada"   {{ request('estado') === 'finalizada'   ? 'selected' : '' }}>Finalizada</option>
                <option value="cerrada"      {{ request('estado') === 'cerrada'      ? 'selected' : '' }}>Cerrada</option>
                <option value="cancelada"    {{ request('estado') === 'cancelada'    ? 'selected' : '' }}>Cancelada</option>
            </select>

            <input type="date" name="fecha"
                   value="{{ request('fecha') }}"
                   class="filtros-control"
                   style="width:auto;">

            <div class="filtros-actions" style="margin-left:auto;">
                <x-ds.button variant="secondary"
                             href="{{ route('web.clases.index') }}">
                    Limpiar
                </x-ds.button>
                <x-ds.button variant="primary" type="submit">
                    Filtrar
                </x-ds.button>
            </div>
        </div>
    </div>
</form>

{{-- Sección E — Stats y listado de clases no-hoy --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        @if($clasesFiltradas->total() > 0)
            Mostrando <strong>{{ $clasesFiltradas->firstItem() }}</strong>
            a <strong>{{ $clasesFiltradas->lastItem() }}</strong>
            de <strong>{{ $clasesFiltradas->total() }}</strong> clase(s)
        @else
            <strong>0</strong> clases encontradas
        @endif
    </div>
</div>

<div id="clases-listado">

    @forelse($clasesFiltradas as $clase)
        @php
            $estado = claseEstado($clase, $ahora);
            $dot    = estadoDot($estado);
            $label  = estadoLabel($estado);
            $color  = estadoColor($estado);
            $dep    = mb_strtolower($clase->grupo->deporte->nombre ?? '');
            $dep    = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
            $rail   = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
        @endphp

        <div class="alumno-card alumno-card--{{ $rail }}"
             data-estado="{{ $estado }}"
             style="{{ $clase->cancelada ? 'opacity:0.6;' : '' }}">

            <div class="alumno-card-header">
                <span class="alumno-dot alumno-dot--{{ $dot }}"
                      style="color:{{ $color }};"></span>
                <h3 class="alumno-nombre">
                    {{ $diasSemana[$clase->fecha->dayOfWeek] }}
                    {{ $clase->fecha->format('d/m/Y') }}
                    —
                    {{ $clase->hora_inicio->format('H:i') }}
                    a
                    {{ $clase->hora_fin->format('H:i') }}
                    <span style="font-weight:400; color:var(--color-text-muted);
                                 font-size:0.82rem; margin-left:6px;">
                        {{ $label }}
                    </span>
                </h3>
            </div>

            <div class="alumno-info" style="grid-template-columns: repeat(3, 1fr);">
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="info-label">Grupo:</span>
                    <span class="info-value">{{ $clase->grupo->nombre_completo }}</span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="info-label">Profesores:</span>
                    <span class="info-value">
                        {{ $clase->profesores->isNotEmpty()
                           ? $clase->profesores->map(fn($p) => $p->apellido)->implode(' · ')
                           : '–' }}
                    </span>
                </div>
                <div class="info-item">
                    <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="info-label">Asistencia:</span>
                    <span class="info-value"
                          style="color:{{ $clase->asistencias->where('presente', true)->count() > 0
                                         ? 'var(--color-success)' : 'var(--color-text-muted)' }};">
                        {{ $clase->asistencias->where('presente', true)->count() > 0
                           ? 'Cargada' : 'Pendiente' }}
                    </span>
                </div>
            </div>

            <div class="alumno-actions">
                <x-ds.button variant="primary"
                             href="{{ route('web.clases.show', $clase->id) }}">
                    Ver
                </x-ds.button>
                @if($esAdmin)
                    <x-ds.button variant="secondary"
                                 href="{{ route('web.clases.edit', $clase->id) }}">
                        Editar
                    </x-ds.button>
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
            <p>Intentá con otros filtros</p>
        </div>
    @endforelse

</div>

@if($clasesFiltradas->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $clasesFiltradas->links() }}
    </div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const filtroEstado = "{{ $filtroEstado }}";

    // 1. Filtro de estado por JS sobre los cards del listado inferior
    if (filtroEstado) {
        document.querySelectorAll('#clases-listado .alumno-card').forEach(function (card) {
            if (card.dataset.estado !== filtroEstado) {
                card.style.display = 'none';
            }
        });
    }

    // 2. Auto-scroll de la ventana de hoy a la clase actual o próxima
    (function () {
        const container = document.getElementById('clases-hoy-container');
        if (!container) return;

        const prioridad = ['en_curso', 'por_comenzar', 'finalizada'];
        let target = null;

        for (const estado of prioridad) {
            target = container.querySelector('[data-estado="' + estado + '"]');
            if (target) break;
        }

        if (target) {
            container.scrollTop = target.offsetTop - container.offsetTop;
        }
    })();

    // 3. Filtro de grupo por deporte
    (function () {
        const filterForm    = document.getElementById('filter-form');
        const deporteSelect = document.getElementById('filter-deporte');
        const grupoSelect   = document.getElementById('filter-grupo');

        function filtrarGrupos(deporteId) {
            grupoSelect.querySelectorAll('option[data-deporte]').forEach(function (opt) {
                opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
            });
            if (deporteId) {
                const sel = grupoSelect.querySelector('option[value="' + grupoSelect.value + '"]');
                if (sel && sel.dataset.deporte && sel.dataset.deporte !== deporteId) {
                    grupoSelect.value = '';
                }
            }
        }

        if (deporteSelect) {
            deporteSelect.addEventListener('change', function () {
                filtrarGrupos(this.value);
                filterForm.submit();
            });
            if (deporteSelect.value) filtrarGrupos(deporteSelect.value);
        }

        if (grupoSelect) {
            grupoSelect.addEventListener('change', function () { filterForm.submit(); });
        }
    })();
})();
</script>
@endpush
