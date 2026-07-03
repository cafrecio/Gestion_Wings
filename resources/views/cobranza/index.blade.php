@extends('layouts.ds-app')

@section('title', 'Cobranza – Wings')
@section('module-title', 'Cobranza')

@section('content')

@php
$totalActivos = $resumen['total_alumnos_activos'];
$cAlDia  = $resumen['por_estado']['AL_DIA']  ?? 0;
$cMoroso = $resumen['por_estado']['MOROSO'] ?? 0;
$cDeudor = $resumen['por_estado']['DEUDOR'] ?? 0;
@endphp

{{-- Cards resumen --}}
<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; margin-bottom:1rem;">
    <div class="filtros-card" style="text-align:center; padding:1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Total activos</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-text);">{{ $totalActivos }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-success);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-success); margin-bottom:4px;">Al día</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-success);">{{ $cAlDia }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-warning);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-warning); margin-bottom:4px;">Morosos</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-warning);">{{ $cMoroso }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem; border-top:3px solid var(--color-danger);">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-danger); margin-bottom:4px;">Deudores</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-danger);">{{ $cDeudor }}</p>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('web.cobranza.index') }}">
<div class="filtros-card mb-3">
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr auto; gap:12px; align-items:end;">
        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Estado</label>
            <select name="estado" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                <option value="AL_DIA"  {{ $estadoFiltro === 'AL_DIA'  ? 'selected' : '' }}>Al día</option>
                <option value="MOROSO"  {{ $estadoFiltro === 'MOROSO'  ? 'selected' : '' }}>Moroso</option>
                <option value="DEUDOR"  {{ $estadoFiltro === 'DEUDOR'  ? 'selected' : '' }}>Deudor</option>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Deporte</label>
            <select name="deporte_id" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                @foreach($deportes as $dep)
                    <option value="{{ $dep->id }}" {{ $deporteId == $dep->id ? 'selected' : '' }}>{{ $dep->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Grupo</label>
            <select name="grupo_id" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                @foreach($grupos as $grp)
                    <option value="{{ $grp->id }}" {{ $grupoId == $grp->id ? 'selected' : '' }}>{{ $grp->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit"
                    style="display:inline-flex; align-items:center; justify-content:center; height:38px; padding:0 16px;
                           font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                           border:none; font-family:inherit; background:var(--color-btn-primary); color:#fff;">
                Filtrar
            </button>
            @if($estadoFiltro || $deporteId || $grupoId)
            <a href="{{ route('web.cobranza.index') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:38px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Limpiar
            </a>
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
        <strong>{{ $alumnos->count() }}</strong> alumno{{ $alumnos->count() !== 1 ? 's' : '' }}
    </div>
</div>

<div class="alumno-card" style="padding:0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <colgroup>
            <col>
            <col style="width:110px">
            <col style="width:110px">
            <col style="width:90px">
            <col style="width:80px">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Alumno</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Deporte</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Grupo</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Estado</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumnos as $alumno)
            @php
                $ec = $alumno->estado_cobranza ?? 'AL_DIA';
                $ecColor = match($ec) {
                    'AL_DIA' => 'var(--color-success)',
                    'MOROSO' => 'var(--color-warning)',
                    'DEUDOR' => 'var(--color-danger)',
                    default  => 'var(--color-text-muted)',
                };
                $ecLabel = match($ec) {
                    'AL_DIA' => 'Al día',
                    'MOROSO' => 'Moroso',
                    'DEUDOR' => 'Deudor',
                    default  => $ec,
                };
            @endphp
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:600; color:var(--color-text);">
                    {{ $alumno->apellido }}, {{ $alumno->nombre }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                    {{ $alumno->deporte->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                    {{ $alumno->grupo->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; text-align:center;">
                    <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:999px;
                                 background:color-mix(in srgb, {{ $ecColor }} 15%, transparent);
                                 color:{{ $ecColor }};">{{ $ecLabel }}</span>
                </td>
                <td style="padding:8px 12px; text-align:center;">
                    <a href="{{ route('web.alumnos.show', $alumno->id) }}"
                       class="ds-btn-row ds-btn-row--sec">Ver</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
