@extends('layouts.app')

@section('title', 'Clases – Wings')
@section('module-title', 'Clases')

@section('content')

{{-- Filtros --}}
<form method="GET" action="{{ route('web.clases.index') }}" id="filter-form">
    <div class="filtros-card">
        <div class="filtros-row" style="flex-wrap:wrap; gap:8px;">

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

            {{-- Grupo --}}
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

            {{-- Fecha desde --}}
            <input type="date" name="fecha_desde"
                   value="{{ $fechaDesde }}"
                   class="filtros-control"
                   style="width:auto;">

            {{-- Fecha hasta --}}
            <input type="date" name="fecha_hasta"
                   value="{{ $fechaHasta }}"
                   class="filtros-control"
                   style="width:auto;">

            {{-- Cancelada --}}
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
            Mostrando <strong>{{ $clases->firstItem() }}</strong>
            a <strong>{{ $clases->lastItem() }}</strong>
            de <strong>{{ $clases->total() }}</strong> clase(s)
        @else
            <strong>0</strong> clases encontradas
        @endif
    </div>
    <x-ds.button variant="primary" href="{{ route('web.clases.create') }}">
        Nueva Clase
    </x-ds.button>
</div>

{{-- Tabla --}}
@if($clases->count() > 0)
<div class="filtros-card" style="padding:0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
        <thead>
            <tr style="border-bottom:1px solid var(--color-border); background:var(--color-surface-alt);">
                <th style="padding:10px 14px; text-align:left; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); white-space:nowrap;">Fecha</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Día</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Hora</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Grupo</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Profesores</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Estado</th>
                <th style="padding:10px 14px; text-align:right; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @php
                $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            @endphp
            @foreach($clases as $clase)
                @php
                    $dep  = mb_strtolower($clase->grupo->deporte->nombre ?? '');
                    $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
                    $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
                    $rowOpacity = $clase->cancelada ? 'opacity:0.55;' : '';
                @endphp
                <tr style="border-bottom:1px solid var(--color-border); {{ $rowOpacity }} transition:background .12s;"
                    onmouseover="this.style.background='var(--color-surface-alt)'"
                    onmouseout="this.style.background=''">

                    {{-- Fecha --}}
                    <td style="padding:10px 14px; white-space:nowrap; font-weight:500; color:var(--color-text);">
                        {{ $clase->fecha->format('d/m/Y') }}
                    </td>

                    {{-- Día --}}
                    <td style="padding:10px 14px; color:var(--color-text-muted); white-space:nowrap;">
                        {{ $diasSemana[$clase->fecha->dayOfWeek] }}
                    </td>

                    {{-- Hora --}}
                    <td style="padding:10px 14px; white-space:nowrap; color:var(--color-text);">
                        {{ $clase->hora_inicio->format('H:i') }} – {{ $clase->hora_fin->format('H:i') }}
                    </td>

                    {{-- Grupo + deporte badge --}}
                    <td style="padding:10px 14px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="
                                font-size:0.65rem; font-weight:700; padding:2px 7px; border-radius:999px;
                                background:color-mix(in srgb, var(--color-sport-{{ $rail }}) 18%, transparent);
                                color:var(--color-sport-{{ $rail }});
                                white-space:nowrap;
                            ">{{ $clase->grupo->deporte->nombre ?? '–' }}</span>
                            <span style="font-weight:500; color:var(--color-text);">{{ $clase->grupo->nombre }}</span>
                        </div>
                    </td>

                    {{-- Profesores --}}
                    <td style="padding:10px 14px; color:var(--color-text-muted);">
                        @if($clase->profesores->isNotEmpty())
                            {{ $clase->profesores->map(fn($p) => $p->apellido . ', ' . $p->nombre)->implode(' · ') }}
                        @else
                            <span style="opacity:0.45;">–</span>
                        @endif
                    </td>

                    {{-- Estado --}}
                    <td style="padding:10px 14px; white-space:nowrap;">
                        <div style="display:flex; gap:4px; flex-wrap:wrap;">
                            @if($clase->cancelada)
                                <span style="font-size:0.65rem; font-weight:600; padding:2px 7px; border-radius:999px; background:color-mix(in srgb, var(--color-danger) 15%, transparent); color:var(--color-danger);">Cancelada</span>
                            @else
                                <span style="font-size:0.65rem; font-weight:600; padding:2px 7px; border-radius:999px; background:color-mix(in srgb, var(--color-success) 15%, transparent); color:var(--color-success);">Activa</span>
                            @endif
                            @if($clase->validada_para_liquidacion)
                                <span style="font-size:0.65rem; font-weight:600; padding:2px 7px; border-radius:999px; background:color-mix(in srgb, var(--color-btn-primary) 15%, transparent); color:var(--color-btn-primary);">Validada</span>
                            @endif
                        </div>
                    </td>

                    {{-- Acciones --}}
                    <td style="padding:10px 14px; text-align:right; white-space:nowrap;">
                        <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center;">
                            <x-ds.button variant="secondary" href="{{ route('web.clases.show', $clase->id) }}" style="font-size:0.75rem; padding:4px 10px;">Ver</x-ds.button>
                            <x-ds.button variant="secondary" href="{{ route('web.clases.edit', $clase->id) }}" style="font-size:0.75rem; padding:4px 10px;">Editar</x-ds.button>
                            <form method="POST" action="{{ route('web.clases.toggle-cancelada', $clase->id) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" style="
                                    font-size:0.75rem; padding:4px 10px; border-radius:var(--radius-btn);
                                    border:1px solid var(--color-border); background:var(--color-surface);
                                    color:var(--color-text-muted); cursor:pointer;
                                    font-family:inherit; transition:background .12s;
                                ">{{ $clase->cancelada ? 'Activar' : 'Cancelar' }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h3>No se encontraron clases</h3>
        <p>Intentá con otros filtros o creá una nueva clase</p>
    </div>
@endif

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
        const options = grupoSelect.querySelectorAll('option[data-deporte]');
        options.forEach(opt => {
            opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
        });
        if (deporteId) {
            const currentOption = grupoSelect.querySelector('option[value="' + grupoSelect.value + '"]');
            if (currentOption && currentOption.dataset.deporte && currentOption.dataset.deporte !== deporteId) {
                grupoSelect.value = '';
            }
        }
    }

    if (deporteSelect) {
        deporteSelect.addEventListener('change', function () {
            filtrarGruposPorDeporte(this.value);
            filterForm.submit();
        });

        // Init
        if (deporteSelect.value) {
            filtrarGruposPorDeporte(deporteSelect.value);
        }
    }

    if (grupoSelect) {
        grupoSelect.addEventListener('change', () => filterForm.submit());
    }
})();
</script>
@endpush
