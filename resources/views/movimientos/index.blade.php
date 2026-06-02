@extends('layouts.ds-app')

@section('title', 'Movimientos – Wings')
@section('module-title', 'Movimientos')

@section('content')

@php $esAdmin = Auth::user()->isAdmin(); @endphp

{{-- Filtros --}}
<form method="GET" action="{{ route('web.movimientos.index') }}">
    <div class="filtros-card mb-4">
        <div class="filtros-row" style="flex-wrap:wrap; gap:8px;">
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="filtros-control" style="width:auto;" title="Desde">
            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   class="filtros-control" style="width:auto;" title="Hasta">

            <select name="tipo" class="filtros-control filtros-select" style="width:auto;">
                <option value="">I/E</option>
                <option value="INGRESO" {{ request('tipo') === 'INGRESO' ? 'selected' : '' }}>Ingreso</option>
                <option value="EGRESO"  {{ request('tipo') === 'EGRESO'  ? 'selected' : '' }}>Egreso</option>
            </select>

            <select name="tipo_caja_id" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Todos los medios</option>
                @foreach($tiposCaja as $tipo)
                    <option value="{{ $tipo->id }}" {{ request('tipo_caja_id') == $tipo->id ? 'selected' : '' }}>
                        {{ $tipo->abreviatura ?: $tipo->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="rubro_id" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Todos los rubros</option>
                @foreach($rubros as $rubro)
                    <option value="{{ $rubro->id }}" {{ request('rubro_id') == $rubro->id ? 'selected' : '' }}>
                        {{ $rubro->nombre }}
                    </option>
                @endforeach
            </select>

            @if($esAdmin)
            <select name="usuario_id" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Todos los operativos</option>
                @foreach($operativos as $op)
                    <option value="{{ $op->id }}" {{ request('usuario_id') == $op->id ? 'selected' : '' }}>
                        {{ $op->name }}
                    </option>
                @endforeach
            </select>
            @endif

            <div class="filtros-actions">
                <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>
                <x-ds.button variant="secondary" href="{{ route('web.movimientos.index') }}">Limpiar</x-ds.button>
            </div>
        </div>
    </div>
</form>

<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $movimientos->total() }}</strong> movimiento{{ $movimientos->total() !== 1 ? 's' : '' }}
        @if($movimientos->total() > 0)
            &nbsp;·&nbsp; Total: <strong>${{ number_format($total, 0, ',', '.') }}</strong>
        @endif
    </div>
</div>

@if($movimientos->isNotEmpty())
    <div class="alumno-card" style="padding:0; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
            <colgroup>
                <col style="width:85px">
                <col style="width:32px">
                <col style="width:50px">
                <col>
                <col style="width:140px">
                <col style="width:90px">
                <col style="width:95px">
                @if($esAdmin) <col style="width:110px"> @endif
            </colgroup>
            <thead>
                <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                    <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Fecha</th>
                    <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">I/E</th>
                    <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Medio</th>
                    <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Rubro — Subrubro</th>
                    <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Alumno / Obs.</th>
                    <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Monto</th>
                    <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Caja</th>
                    @if($esAdmin)
                    <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Operativo</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $mov)
                @php
                    $tipoRubro    = $mov->subrubro?->rubro?->tipo;
                    $ieLabel      = $tipoRubro === 'INGRESO' ? 'I' : ($tipoRubro === 'EGRESO' ? 'E' : '–');
                    $ieColor      = $tipoRubro === 'EGRESO' ? 'var(--color-danger)' : 'var(--color-text)';
                    $rubroNombre  = $mov->subrubro?->rubro?->nombre ?? '';
                    $subNombre    = $mov->subrubro?->nombre ?? '–';
                    $alumnoObs    = $mov->alumno
                        ? $mov->alumno->apellido . ', ' . $mov->alumno->nombre
                        : (strlen($mov->observaciones ?? '') > 36
                            ? substr($mov->observaciones, 0, 36) . '…'
                            : ($mov->observaciones ?? '–'));
                    $caja         = $mov->cajaOperativa;
                @endphp
                <tr style="border-bottom:1px solid var(--color-border);">
                    <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                        {{ $mov->fecha?->format('d/m/Y') ?? $mov->created_at->format('d/m/Y') }}
                    </td>
                    <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:{{ $ieColor }};">
                        {{ $ieLabel }}
                    </td>
                    <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">
                        {{ $mov->tipoCaja?->abreviatura ?? $mov->tipoCaja?->nombre ?? '–' }}
                    </td>
                    <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        @if($rubroNombre)
                            <span style="color:var(--color-text-muted);">{{ $rubroNombre }}</span> — {{ $subNombre }}
                        @else
                            {{ $subNombre }}
                        @endif
                    </td>
                    <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $alumnoObs }}
                    </td>
                    <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; color:var(--color-text); text-align:right;">
                        ${{ number_format($mov->monto, 0, ',', '.') }}
                    </td>
                    <td style="padding:8px 12px; font-size:0.78rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        @if($caja)
                            <a href="{{ route('web.caja.detalle', $caja->id) }}"
                               style="color:var(--color-btn-primary); text-decoration:none;">
                                {{ $caja->apertura_at->format('d/m/Y') }}
                            </a>
                        @else
                            –
                        @endif
                    </td>
                    @if($esAdmin)
                    <td style="padding:8px 12px; font-size:0.78rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $caja?->usuarioOperativo?->name ?? '–' }}
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($movimientos->hasPages())
        <div class="mt-4 flex justify-center">{{ $movimientos->links() }}</div>
    @endif
@else
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h3>Sin movimientos</h3>
        <p>Intentá con otros filtros</p>
    </div>
@endif

@endsection
