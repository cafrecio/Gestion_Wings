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
    // "Cerrada" = resuelta para liquidación: tiene presentes o el admin la
    // validó a mano. "Finalizada" = pasó y sigue trabando el pago.
    $resuelta = $clase->validada_para_liquidacion
                || $clase->asistencias->where('presente', true)->count() > 0;
    if ($clase->fecha->isToday()) {
        if ($ahora->lt($inicio->copy()->subHour())) return 'programada';
        if ($ahora->lt($inicio))                    return 'por_comenzar';
        if ($ahora->lte($fin))                      return 'en_curso';
        return $resuelta ? 'cerrada' : 'finalizada';
    }
    if ($clase->fecha->isFuture()) return 'programada';
    return $resuelta ? 'cerrada' : 'finalizada';
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
@php $tituloHoy = $esProfesor ? 'Tus clases de hoy' : 'Clases de hoy'; @endphp
<div class="stats-bar mb-2">
    <div class="stats-info" style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;">
        {{ $tituloHoy }} — {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd D [de] MMMM') }}
        · <strong>{{ $misClasesHoy->count() }}</strong> clase(s)
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

    @forelse($misClasesHoy as $clase)
        @include('clases._card', ['clase' => $clase, 'modo' => 'hoy'])
    @empty
        <div style="padding:24px; text-align:center;
                    color:var(--color-text-muted); font-size:0.85rem;">
            @if($esProfesor)
                No tenés clases programadas para hoy.
            @else
                No hay clases programadas para hoy.
            @endif
        </div>
    @endforelse

</div>

{{-- UP2.0: para el profesor, las clases de HOY de los demás quedan
     disponibles pero colapsadas — no le estorban, no se le ocultan. --}}
@if($esProfesor && $otrasClasesHoy->isNotEmpty())
    <details style="margin-top:0.5rem;">
        <summary style="cursor:pointer; font-size:0.75rem; font-weight:600;
                         color:var(--color-text-muted); padding:6px 2px;">
            Otras clases de hoy ({{ $otrasClasesHoy->count() }})
        </summary>
        <div style="display:flex; flex-direction:column; gap:0; margin-top:6px;">
            @foreach($otrasClasesHoy as $clase)
                @include('clases._card', ['clase' => $clase, 'modo' => 'hoy'])
            @endforeach
        </div>
    </details>
@endif

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
                <option value="programada" {{ request('estado') === 'programada' ? 'selected' : '' }}>Programada</option>
                <option value="finalizada" {{ request('estado') === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                <option value="cerrada"    {{ request('estado') === 'cerrada'    ? 'selected' : '' }}>Cerrada</option>
                <option value="cancelada"  {{ request('estado') === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
            </select>

            <input type="date" name="fecha"
                   value="{{ request('fecha') }}"
                   class="filtros-control">

            <select name="orden" id="filter-orden"
                    class="filtros-control filtros-select">
                <option value="">Orden por defecto</option>
                <option value="fecha_desc" {{ request('orden') === 'fecha_desc' ? 'selected' : '' }}>Fecha ↓ más recientes</option>
                <option value="fecha_asc"  {{ request('orden') === 'fecha_asc'  ? 'selected' : '' }}>Fecha ↑ más antiguas</option>
                <option value="grupo"      {{ request('orden') === 'grupo'      ? 'selected' : '' }}>Grupo (A–Z)</option>
            </select>

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
        @include('clases._card', ['clase' => $clase, 'modo' => 'listado'])
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
    // 1. Auto-scroll de la ventana de hoy a la clase actual o próxima
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

        const ordenSelect = document.getElementById('filter-orden');
        if (ordenSelect) {
            ordenSelect.addEventListener('change', function () { filterForm.submit(); });
        }
    })();
})();
</script>
@endpush
