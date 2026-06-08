@extends('layouts.ds-app')

@section('title', 'Caja – Wings')
@section('module-title', 'Caja')

@section('content')

@php
$btnB     = 'display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap; text-decoration:none; border:none; font-family:inherit;';
$btnBSec  = $btnB . ' background:var(--color-btn-secondary); color:var(--color-surface);';
$btnBPrim = $btnB . ' background:var(--color-btn-primary); color:#fff;';

$meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
@endphp

{{-- ── Filtros admin ────────────────────────────────────────────────────── --}}
@if(Auth::user()->isAdmin())
<form method="GET" action="{{ route('web.caja.index') }}">
    <div class="filtros-card mb-4">
        <div class="filtros-row">
            <select name="operativo_id" class="filtros-control filtros-select" style="width:auto;">
                <option value="">Todos los operativos</option>
                @foreach($operativos as $op)
                    <option value="{{ $op->id }}" {{ request('operativo_id') == $op->id ? 'selected' : '' }}>
                        {{ $op->name }}
                    </option>
                @endforeach
            </select>
            <input type="month" name="mes" value="{{ $mes }}"
                   class="filtros-control" style="width:auto;">
            <div class="filtros-actions">
                <x-ds.button variant="primary" type="submit">Filtrar</x-ds.button>
                <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Limpiar</x-ds.button>
            </div>
        </div>
    </div>
</form>

@else
    {{-- ── Banner: caja vieja bloqueante ─── --}}
    @if($cajaVieja)
    <div class="filtros-card mb-4" style="border-left: 4px solid var(--color-danger);">
        <p style="font-size:0.75rem; color:var(--color-danger); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
            Caja pendiente de cierre
        </p>
        <p style="font-size:0.9rem; color:var(--color-text);">
            Tenés una caja abierta de un día anterior. Hacé clic en "Detalle" para cerrarla antes de operar.
        </p>
    </div>

    {{-- ── Banner: sin caja hoy ─── --}}
    @elseif($sinCajaHoy)
    <div class="filtros-card mb-4">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
            <div>
                <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:4px;">
                    No tenés caja abierta hoy.
                </p>
                <p style="font-size:0.82rem; color:var(--color-text-muted);">
                    Se abrirá automáticamente al registrar el primer movimiento.
                </p>
            </div>
            <div style="display:flex; gap:8px; flex-shrink:0;">
                <a href="{{ route('web.caja.movimiento') }}"
                   class="ds-btn" style="background:var(--color-btn-primary); color:#fff;">Nuevo</a>
                <a href="{{ route('web.caja.cobrar-cuota') }}"
                   class="ds-btn" style="background:var(--color-btn-secondary); color:var(--color-surface);">Cobrar</a>
            </div>
        </div>
    </div>
    @endif
@endif

{{-- ── Stats bar ─────────────────────────────────────────────────────────── --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $cajas->count() }}</strong>
        {{ $cajas->count() === 1 ? 'caja' : 'cajas' }}
    </div>
</div>

{{-- ── Cards ─────────────────────────────────────────────────────────────── --}}
@forelse($cajas as $caja)
@php
    $dotStyle = match($caja->estado) {
        'ABIERTA'   => 'background:var(--color-warning);',
        'CERRADA'   => 'background:var(--color-text-muted);',
        'VALIDADA'  => 'background:var(--color-success);',
        'RECHAZADA' => 'background:var(--color-danger);',
        default     => 'background:var(--color-text-muted);',
    };
    $estadoColor = match($caja->estado) {
        'ABIERTA'   => 'var(--color-warning)',
        'CERRADA'   => 'var(--color-text-muted)',
        'VALIDADA'  => 'var(--color-success)',
        'RECHAZADA' => 'var(--color-danger)',
        default     => 'var(--color-text-muted)',
    };
    $fechaLabel = ucfirst($dias[$caja->apertura_at->dayOfWeek]) . ' ' . $caja->apertura_at->day . ' de ' . $meses[$caja->apertura_at->month];
    $canEdit = in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])
        && (Auth::user()->isAdmin() || Auth::id() === $caja->usuario_operativo_id);
@endphp

<div class="alumno-card">

    <div class="alumno-card-header">
        <span class="alumno-dot" style="{{ $dotStyle }}"></span>
        <h3 class="alumno-nombre">{{ $fechaLabel }}</h3>
    </div>

    @if(Auth::user()->isAdmin())
    <p style="font-size:0.8rem; color:var(--color-text-muted); padding-left:1.5rem; margin-top:2px; margin-bottom:4px;">
        {{ $caja->usuarioOperativo->name ?? '–' }}
    </p>
    @endif

    <div class="alumno-info" style="grid-template-columns: repeat(3, 1fr);">
        <div class="info-item">
            <span class="info-label">Estado</span>
            <span class="info-value" style="color:{{ $estadoColor }}; font-weight:700;">{{ $caja->estado }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Movimientos</span>
            <span class="info-value">{{ $caja->movimientos->count() }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Total</span>
            <span class="info-value">${{ number_format($caja->movimientos->sum('monto'), 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="alumno-actions">
        @if($canEdit)
            <a href="{{ route('web.caja.editar', $caja->id) }}" style="{{ $btnBSec }}">Editar</a>
        @endif
        <a href="{{ route('web.caja.resumen', $caja->id) }}" style="{{ $btnBPrim }}">Resumen</a>
        <a href="{{ route('web.caja.detalle', $caja->id) }}" style="{{ $btnBSec }}">Detalle</a>
    </div>

</div>
@empty
<div class="empty-state">
    <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <h3>Sin cajas en este período</h3>
    <p>No hay cajas registradas para el período seleccionado.</p>
</div>
@endforelse

@endsection
