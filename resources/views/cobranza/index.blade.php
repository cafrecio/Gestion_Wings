@extends('layouts.ds-app')

@section('title', 'Cobranza – Wings')
@section('module-title', 'Cobranza')

@section('content')

@php
$totalActivos = $resumen['total_alumnos_activos'];
$totalAdeudado = $resumen['total_adeudado'] ?? 0;
$cAlDia  = $resumen['por_estado']['AL_DIA']  ?? 0;
$cEnPlazo = $resumen['por_estado']['EN_PLAZO'] ?? 0;
$cMoroso = $resumen['por_estado']['MOROSO'] ?? 0;
$cDeudor = $resumen['por_estado']['DEUDOR'] ?? 0;
@endphp

{{-- Cards resumen --}}
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap:12px; margin-bottom:1rem;">
    <div class="filtros-card" style="text-align:center; padding:1rem 0.5rem; border-top:3px solid var(--color-danger);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:700; color:var(--color-danger); margin-bottom:4px;">Total adeudado</p>
        <p style="font-size:1.05rem; font-weight:800; color:var(--color-danger); white-space:nowrap;">$ {{ number_format($totalAdeudado, 2, ',', '.') }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-text-muted);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Total activos</p>
        <p style="font-size:1.6rem; font-weight:800; color:var(--color-text);">{{ $totalActivos }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-success);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-success); margin-bottom:4px;">Al día</p>
        <p style="font-size:1.6rem; font-weight:800; color:var(--color-success);">{{ $cAlDia }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-info);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-info); margin-bottom:4px;">En plazo</p>
        <p style="font-size:1.6rem; font-weight:800; color:var(--color-info);">{{ $cEnPlazo }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-warning);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-warning); margin-bottom:4px;">Morosos</p>
        <p style="font-size:1.6rem; font-weight:800; color:var(--color-warning);">{{ $cMoroso }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-danger);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-danger); margin-bottom:4px;">Deudores</p>
        <p style="font-size:1.6rem; font-weight:800; color:var(--color-danger);">{{ $cDeudor }}</p>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('web.cobranza.index') }}">
    <div class="filtros-card mb-3">
        <div class="filtros-row">
            <select name="estado" class="filtros-control filtros-select">
                <option value="DEUDORES" {{ ($estadoFiltro === 'DEUDORES' || !$estadoFiltro) ? 'selected' : '' }}>Con deuda (Deudores y morosos)</option>
                <option value="TODOS"    {{ $estadoFiltro === 'TODOS' ? 'selected' : '' }}>Todos los estados</option>
                <option value="AL_DIA"   {{ $estadoFiltro === 'AL_DIA'  ? 'selected' : '' }}>Al día</option>
                <option value="EN_PLAZO" {{ $estadoFiltro === 'EN_PLAZO' ? 'selected' : '' }}>En plazo</option>
                <option value="MOROSO"   {{ $estadoFiltro === 'MOROSO'  ? 'selected' : '' }}>Moroso</option>
                <option value="DEUDOR"   {{ $estadoFiltro === 'DEUDOR'  ? 'selected' : '' }}>Deudor</option>
            </select>

            <select name="deporte_id" class="filtros-control filtros-select">
                <option value="">Deporte</option>
                @foreach($deportes as $dep)
                    <option value="{{ $dep->id }}" {{ $deporteId == $dep->id ? 'selected' : '' }}>{{ $dep->nombre }}</option>
                @endforeach
            </select>

            <select name="grupo_id" class="filtros-control filtros-select">
                <option value="">Grupo</option>
                @foreach($grupos as $grp)
                    <option value="{{ $grp->id }}" {{ $grupoId == $grp->id ? 'selected' : '' }}>{{ $grp->nombre }}</option>
                @endforeach
            </select>

            <div class="filtros-actions">
                <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>
                @if($estadoFiltro !== 'DEUDORES' || $deporteId || $grupoId)
                    <x-ds.button variant="secondary" href="{{ route('web.cobranza.index') }}">Limpiar</x-ds.button>
                @endif
            </div>
        </div>
    </div>
</form>

{{-- Tabla alumnos --}}
@if($alumnos->isEmpty())
<div class="empty-state" style="padding:2rem 0;">
    <p style="color:var(--color-text-muted); font-size:0.9rem;">No hay alumnos para los filtros seleccionados.</p>
</div>
@else
<div class="stats-bar mb-2">
    <div class="stats-info">
        @if($estadoFiltro === 'DEUDORES' || !$estadoFiltro)
            Mostrando <strong>{{ $alumnos->count() }}</strong> {{ $alumnos->count() === 1 ? 'persona con deuda' : 'personas con deuda' }} de <strong>{{ $totalActivos }}</strong> alumnos activos (ordenado de deuda más antigua a más reciente)
        @else
            <strong>{{ $alumnos->count() }}</strong> {{ $alumnos->count() === 1 ? 'persona' : 'personas' }}
        @endif
    </div>
</div>

<div class="alumno-card" style="padding:0; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed; min-width:860px;">
        <colgroup>
            <col style="width:180px;">
            <col style="width:110px;">
            <col style="width:140px;">
            <col style="width:150px;">
            <col style="width:130px;">
            <col style="width:100px;">
            <col style="width:150px;">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:10px 14px; text-align:left; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Alumno</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Deporte</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Grupo</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Deuda desde</th>
                <th style="padding:10px 14px; text-align:right; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Total deuda</th>
                <th style="padding:10px 14px; text-align:center; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Estado</th>
                <th style="padding:10px 14px; text-align:center; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $alumno)
            @php
                $ec = $alumno->estado_cobranza ?? 'AL_DIA';
                $ecColor = match($ec) {
                    'AL_DIA' => 'var(--color-success)',
                    'EN_PLAZO' => 'var(--color-info)',
                    'MOROSO' => 'var(--color-warning)',
                    'DEUDOR' => 'var(--color-danger)',
                    default  => 'var(--color-text-muted)',
                };
                $ecLabel = match($ec) {
                    'AL_DIA' => 'Al día',
                    'EN_PLAZO' => 'En plazo',
                    'MOROSO' => 'Moroso',
                    'DEUDOR' => 'Deudor',
                    default  => $ec,
                };
                $dniNorm = \App\Services\InscripcionService::dni($alumno->dni);
                $inscPendiente = $inscripciones[$dniNorm] ?? ($alumno->inscripcion_pendiente ?? 0);
            @endphp
            <tr style="border-bottom:1px solid var(--color-border);">
                {{-- Alumno --}}
                <td style="padding:10px 14px; font-size:0.85rem; font-weight:600; color:var(--color-text);">
                    {{ $alumno->apellido }}, {{ $alumno->nombre }}
                    @if($alumno->dni)
                        <div style="font-size:0.72rem; font-weight:400; color:var(--color-text-muted); margin-top:2px;">
                            DNI: {{ number_format((int)$dniNorm, 0, '', '.') }}
                        </div>
                    @endif
                </td>

                {{-- Deporte --}}
                <td style="padding:10px 14px; font-size:0.8rem;">
                    @if(isset($alumno->actividades) && count($alumno->actividades) > 0)
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            @foreach($alumno->actividades as $act)
                                @php
                                    $depSlug = match(strtolower($act['deporte'])) {
                                        'patín', 'patin' => 'patin',
                                        'fútbol', 'futbol' => 'futbol',
                                        default => 'otro',
                                    };
                                @endphp
                                <div>
                                    <span style="font-size:0.7rem; font-weight:600; padding:2px 8px; border-radius:999px;
                                                 background:color-mix(in srgb, var(--color-sport-{{ $depSlug }}) 15%, transparent);
                                                 color:var(--color-sport-{{ $depSlug }});">
                                        {{ $act['deporte'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span style="color:var(--color-text-muted);">–</span>
                    @endif
                </td>

                {{-- Grupo --}}
                <td style="padding:10px 14px; font-size:0.8rem; color:var(--color-text);">
                    @if(isset($alumno->actividades) && count($alumno->actividades) > 0)
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            @foreach($alumno->actividades as $act)
                                <div>{{ $act['grupo'] }}</div>
                            @endforeach
                        </div>
                    @else
                        <span style="color:var(--color-text-muted);">–</span>
                    @endif
                </td>

                {{-- Deuda desde --}}
                <td style="padding:10px 14px;">
                    @if($alumno->deuda_mas_antigua && $alumno->deuda_mas_antigua !== '9999-99')
                        @php
                            $mesStr = \Carbon\Carbon::parse($alumno->deuda_mas_antigua . '-01')->translatedFormat('M Y');
                            $cant = $alumno->cantidad_cuotas_impagas ?? 1;
                        @endphp
                        <div>
                            <span style="font-size:0.8rem; font-weight:600; color:var(--color-danger);">{{ ucfirst($mesStr) }}</span>
                            <span style="font-size:0.72rem; color:var(--color-text-muted);">({{ $cant }} {{ $cant === 1 ? 'cuota' : 'cuotas' }})</span>
                        </div>
                        @if($inscPendiente > 0)
                            <div style="font-size:0.68rem; font-weight:600; color:var(--color-warning); margin-top:2px;">
                                + Inscripción
                            </div>
                        @endif
                    @elseif($inscPendiente > 0)
                        <span style="font-size:0.75rem; font-weight:600; color:var(--color-warning);">Inscripción</span>
                    @else
                        <span style="font-size:0.8rem; color:var(--color-text-muted);">–</span>
                    @endif
                </td>

                {{-- Total deuda --}}
                <td style="padding:10px 14px; text-align:right; font-size:0.85rem; font-weight:700; white-space:nowrap; color:{{ ($alumno->deuda_total ?? 0) > 0 ? 'var(--color-danger)' : 'var(--color-text-muted)' }};">
                    $ {{ number_format($alumno->deuda_total ?? 0, 2, ',', '.') }}
                </td>

                {{-- Estado --}}
                <td style="padding:10px 14px; text-align:center;">
                    <span style="font-size:0.7rem; font-weight:700; padding:3px 10px; border-radius:999px;
                                 background:color-mix(in srgb, {{ $ecColor }} 15%, transparent);
                                 color:{{ $ecColor }};">{{ $ecLabel }}</span>
                </td>

                {{-- Acciones --}}
                <td style="padding:10px 14px; text-align:center;">
                    <div style="display:inline-flex; gap:6px; justify-content:center;">
                        <a href="{{ route('web.caja.cobrar', $alumno->id) }}"
                           class="ds-btn-row"
                           style="width:64px; height:26px; font-size:0.72rem; background:var(--color-btn-primary); color:#fff; border:1px solid var(--color-btn-primary); text-decoration:none;">Cobrar</a>
                        <a href="{{ route('web.alumnos.show', $alumno->id) }}"
                           class="ds-btn-row ds-btn-row--sec"
                           style="width:64px; height:26px; font-size:0.72rem; text-decoration:none;">Ver</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection

