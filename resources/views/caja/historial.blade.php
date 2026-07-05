@extends('layouts.ds-app')

@section('title', 'Historial – Wings')
@section('module-title', 'Historial')

@section('content')

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('web.caja.historial') }}">
    <div class="filtros-card mb-3">
        <div class="filtros-row">

            <div class="search-input-group">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Alumno, subrubro u observación..."
                       class="filtros-control"
                       autocomplete="off">
            </div>

            <select name="subrubro_id" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Todos los subrubros</option>
                @foreach($subrubros as $sub)
                    <option value="{{ $sub->id }}" {{ request('subrubro_id') == $sub->id ? 'selected' : '' }}>
                        {{ $sub->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="tipo" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Ingresos y egresos</option>
                <option value="INGRESO" {{ request('tipo') === 'INGRESO' ? 'selected' : '' }}>Ingresos</option>
                <option value="EGRESO"  {{ request('tipo') === 'EGRESO'  ? 'selected' : '' }}>Egresos</option>
            </select>

            <input type="date" name="desde" value="{{ request('desde') }}"
                   min="{{ $desdeLimite->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="filtros-control" style="width:auto;" title="Desde">

            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   min="{{ $desdeLimite->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="filtros-control" style="width:auto;" title="Hasta">

            <div class="filtros-actions">
                <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>
                <x-ds.button variant="secondary" href="{{ route('web.caja.historial') }}">Limpiar</x-ds.button>
            </div>
        </div>
    </div>
</form>

{{-- ── Stats ───────────────────────────────────────────────────────────── --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $filas->total() }}</strong>
        {{ $filas->total() === 1 ? 'movimiento' : 'movimientos' }}
        · Ingresos <strong style="color:var(--color-success);">${{ number_format($totalIngresos, 0, ',', '.') }}</strong>
        · Egresos <strong style="color:var(--color-danger);">${{ number_format($totalEgresos, 0, ',', '.') }}</strong>
    </div>
    <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Volver</x-ds.button>
</div>

{{-- ── Tabla ───────────────────────────────────────────────────────────── --}}
@if($filas->isEmpty())
<div class="empty-state">
    <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <h3>Sin movimientos</h3>
    <p>No hay movimientos para los filtros seleccionados.</p>
</div>
@else
<div class="filtros-card" style="padding:0; overflow-x:auto;">
    <table style="width:100%; min-width:640px; border-collapse:collapse; table-layout:fixed;">
        <colgroup>
            <col style="width:96px;">
            <col>
            <col style="width:110px;">
            <col style="width:140px;">
            <col style="width:130px;">
        </colgroup>
        <thead>
            <tr style="border-bottom:1px solid var(--color-border);">
                <th style="padding:10px 14px; text-align:left; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Fecha</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Detalle</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Medio</th>
                <th style="padding:10px 14px; text-align:left; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Registró</th>
                <th style="padding:10px 14px; text-align:right; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $f)
            @php $esIngreso = $f->tipo === 'INGRESO'; @endphp
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:10px 14px; font-size:0.8rem; color:var(--color-text-muted); white-space:nowrap;">
                    {{ $f->fecha->format('d/m/Y') }}
                </td>
                <td style="padding:10px 14px; overflow:hidden;">
                    <p class="ds-truncate" style="font-size:0.85rem; font-weight:600; color:var(--color-text);">
                        {{ $f->subrubro }}
                    </p>
                    @if($f->alumno || $f->obs)
                    <p class="ds-truncate" style="font-size:0.75rem; color:var(--color-text-muted);">
                        {{ $f->alumno ?? $f->obs }}
                    </p>
                    @endif
                </td>
                <td style="padding:10px 14px; font-size:0.8rem; color:var(--color-text-muted);">
                    <span class="ds-truncate" style="display:block;">{{ $f->medio }}</span>
                </td>
                <td style="padding:10px 14px; font-size:0.8rem; color:var(--color-text-muted);">
                    <span class="ds-truncate" style="display:block;">{{ $f->usuario }}</span>
                </td>
                <td style="padding:10px 14px; text-align:right; font-size:0.85rem; font-weight:700; white-space:nowrap;
                           color:{{ $esIngreso ? 'var(--color-success)' : 'var(--color-danger)' }};">
                    {{ $esIngreso ? '+' : '−' }}${{ number_format($f->monto, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Paginación ──────────────────────────────────────────────────────── --}}
@if($filas->hasPages())
<div class="mt-6 flex justify-center">
    {{ $filas->links() }}
</div>
@endif

<p style="font-size:0.72rem; color:var(--color-text-muted); margin-top:12px;">
    Se muestran los movimientos de los últimos 90 días.
</p>

@endsection
