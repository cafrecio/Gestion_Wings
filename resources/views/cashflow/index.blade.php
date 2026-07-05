@extends('layouts.ds-app')

@section('title', 'Cashflow histórico – Wings')
@section('module-title', 'Cashflow')

@section('content')

@php
$mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$balance = (float)$totalIngresos - (float)$totalEgresos;
$balanceColor = $balance >= 0 ? 'var(--color-success)' : 'var(--color-danger)';
@endphp

{{-- Filtros --}}
<form method="GET" action="{{ route('web.cashflow.index') }}" id="filtros-form">
<div class="filtros-card mb-4">
    <div style="display:grid; grid-template-columns: 100px 1fr 1fr 1fr; gap:12px; align-items:end;">

        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Año</label>
            <select name="anio" class="w-full px-3 py-2 text-sm wings-input" onchange="this.form.submit()">
                @foreach($aniosDisponibles as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Mes</label>
            <select name="mes" class="w-full px-3 py-2 text-sm wings-input" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach($mesesNombres as $num => $nombre)
                    @if($num > 0)
                    <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Tipo de caja</label>
            <select name="tipo_caja_id" class="w-full px-3 py-2 text-sm wings-input" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach($tiposCaja as $tc)
                    <option value="{{ $tc->id }}" {{ $tipoCajaId == $tc->id ? 'selected' : '' }}>{{ $tc->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Tipo</label>
            <select name="tipo" class="w-full px-3 py-2 text-sm wings-input" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="INGRESO" {{ $tipo === 'INGRESO' ? 'selected' : '' }}>Ingresos</option>
                <option value="EGRESO"  {{ $tipo === 'EGRESO'  ? 'selected' : '' }}>Egresos</option>
            </select>
        </div>

    </div>
</div>
</form>

{{-- Stats --}}
<div class="stats-bar mb-3">
    <div class="stats-info" style="gap:20px; display:flex; align-items:center;">
        <span>
            <strong style="color:var(--color-success);">${{ number_format($totalIngresos, 0, ',', '.') }}</strong>
            <span style="font-size:0.72rem; color:var(--color-text-muted); margin-left:4px;">ingresos</span>
        </span>
        <span>
            <strong style="color:var(--color-danger);">${{ number_format($totalEgresos, 0, ',', '.') }}</strong>
            <span style="font-size:0.72rem; color:var(--color-text-muted); margin-left:4px;">egresos</span>
        </span>
        <span>
            <strong style="color:{{ $balanceColor }};">${{ number_format($balance, 0, ',', '.') }}</strong>
            <span style="font-size:0.72rem; color:var(--color-text-muted); margin-left:4px;">balance</span>
        </span>
        <span style="font-size:0.78rem; color:var(--color-text-muted);">
            {{ $movimientos->total() }} movimiento{{ $movimientos->total() !== 1 ? 's' : '' }}
        </span>
    </div>
    <div style="display:flex; gap:8px;">
        @if($mes || $tipoCajaId || $tipo)
            <a href="{{ route('web.cashflow.index', ['anio' => $anio]) }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 14px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Limpiar
            </a>
        @endif
        <a href="{{ route('web.cashflow.movimiento') }}"
           style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 14px;
                  font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                  text-decoration:none; background:var(--color-btn-primary); color:#fff;">
            Nuevo
        </a>
    </div>
</div>

{{-- Tabla --}}
@if($movimientos->isEmpty())
<div class="empty-state" style="padding:2rem 0;">
    <p style="color:var(--color-text-muted); font-size:0.9rem;">Sin movimientos para los filtros seleccionados.</p>
</div>
@else
<div class="alumno-card" style="padding:0; overflow:hidden; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed; min-width:700px;">
        <colgroup>
            <col style="width:90px">
            <col style="width:30px">
            <col style="width:110px">
            <col>
            <col style="width:20%">
            <col style="width:100px">
            <col style="width:110px">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Fecha</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">I/E</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Tipo caja</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Rubro — Subrubro</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Observaciones</th>
                <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Monto</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Registrado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            @php
                $tipoRubro = $mov->subrubro?->rubro?->tipo;
                $ieLabel   = $tipoRubro === 'INGRESO' ? 'I' : ($tipoRubro === 'EGRESO' ? 'E' : '–');
                $ieColor   = $tipoRubro === 'EGRESO' ? 'var(--color-danger)' : 'var(--color-text)';
                $rubroNom  = $mov->subrubro?->rubro?->nombre ?? '';
                $subNom    = $mov->subrubro?->nombre ?? '–';
                $montoColor = $tipoRubro === 'EGRESO' ? 'var(--color-danger)' : 'var(--color-success)';
            @endphp
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                    {{ $mov->fecha?->format('d/m/Y') ?? '–' }}
                </td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:{{ $ieColor }};">
                    {{ $ieLabel }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">
                    {{ $mov->tipoCaja?->abreviatura ?? $mov->tipoCaja?->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    @if($rubroNom)
                        <span style="color:var(--color-text-muted);">{{ $rubroNom }}</span> — {{ $subNom }}
                    @else
                        {{ $subNom }}
                    @endif
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $mov->observaciones ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; text-align:right; color:{{ $montoColor }};">
                    {{ $tipoRubro === 'EGRESO' ? '−' : '' }}${{ number_format(abs((float)$mov->monto), 0, ',', '.') }}
                </td>
                <td style="padding:8px 12px; font-size:0.75rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $mov->usuarioAdmin?->name ?? '–' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Paginación --}}
@if($movimientos->hasPages())
<div style="margin-top:1rem;">
    {{ $movimientos->links() }}
</div>
@endif
@endif

@endsection
