@extends('layouts.ds-app')

@section('title', 'Resumen – Caja del ' . $caja->apertura_at->format('d/m/Y') . ' – Wings')
@section('module-title', 'Resumen de caja')

@section('content')

@php
$btnB     = 'display:inline-flex; align-items:center; justify-content:center; height:32px; font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap; text-decoration:none; border:none; font-family:inherit; padding:0 14px;';
$btnBSec  = $btnB . ' background:var(--color-btn-secondary); color:var(--color-surface);';
$btnBPrim = $btnB . ' background:var(--color-btn-primary); color:#fff;';
$btnBDang = $btnB . ' background:var(--color-btn-danger); color:#fff;';

$estadoColor = match($caja->estado) {
    'ABIERTA'   => 'var(--color-warning)',
    'CERRADA'   => 'var(--color-text-muted)',
    'VALIDADA'  => 'var(--color-success)',
    'RECHAZADA' => 'var(--color-danger)',
    default     => 'var(--color-text-muted)',
};

$esPropietario = Auth::id() === $caja->usuario_operativo_id;
@endphp

{{-- ── Info de la caja ─────────────────────────────────────────────────── --}}
<div class="filtros-card mb-4">
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1rem;">
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Operativo</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $caja->usuarioOperativo->name ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Apertura</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $caja->apertura_at->format('d/m/Y H:i') }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Cierre</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $caja->cierre_at?->format('d/m/Y H:i') ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Estado</p>
            <p style="font-size:0.85rem; font-weight:700; color:{{ $estadoColor }};">{{ $caja->estado }}</p>
        </div>
    </div>
    @if($caja->motivo_rechazo)
    <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--color-border);">
        <p style="font-size:0.72rem; font-weight:600; color:var(--color-danger); text-transform:uppercase; letter-spacing:0.05em;">Motivo de rechazo</p>
        <p style="font-size:0.85rem; color:var(--color-text); margin-top:2px;">{{ $caja->motivo_rechazo }}</p>
    </div>
    @endif
</div>

{{-- ── Stats + acciones ─────────────────────────────────────────────────── --}}
<div class="stats-bar mb-4">
    <div class="stats-info">
        <strong>{{ $caja->movimientos->count() }}</strong>
        movimiento{{ $caja->movimientos->count() !== 1 ? 's' : '' }}
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
        @if($caja->estado === 'ABIERTA' && $esPropietario)
            <a href="{{ route('web.caja.cobrar-cuota') }}" style="{{ $btnBSec }}">Cobrar cuota</a>
            <a href="{{ route('web.caja.editar', $caja->id) }}" style="{{ $btnBSec }}">Registrar movimiento</a>
            <form method="POST" action="{{ route('web.caja.cerrar', $caja->id) }}"
                  onsubmit="return confirm('¿Cerrar la caja?')">
                @csrf
                <button type="submit" style="{{ $btnBDang }}">Cerrar</button>
            </form>
        @endif
        @if(Auth::user()->isAdmin() && $caja->estado === 'CERRADA')
            <button type="button" onclick="abrirRechazar()" style="{{ $btnBDang }}">Rechazar</button>
            <form method="POST" action="{{ route('web.cajas.validar', $caja->id) }}">
                @csrf
                <button type="submit" style="{{ $btnBPrim }}">Validar</button>
            </form>
        @endif
        <a href="{{ route('web.caja.detalle', $caja->id) }}" style="{{ $btnBSec }}">Ver detalle</a>
        <a href="{{ route('web.caja.index') }}" style="{{ $btnBSec }}">Volver</a>
    </div>
</div>

@if($caja->movimientos->isEmpty())
<div class="empty-state" style="padding:2rem 0;">
    <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <h3>Sin movimientos</h3>
    <p>Esta caja no tiene movimientos registrados.</p>
</div>
@else

{{-- ── Sección A: Totales por medio de pago ────────────────────────────── --}}
<p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:0.5rem;">
    Por medio de pago
</p>
<div class="alumno-card mb-4" style="padding:0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <colgroup>
            <col style="width:56px">
            <col>
            <col style="width:120px">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Abr.</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Medio</th>
                <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($porTipo as $fila)
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.82rem; font-weight:700; color:var(--color-text);">
                    {{ $fila['tipo']?->abreviatura ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.82rem; color:var(--color-text);">
                    {{ $fila['tipo']?->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; color:var(--color-success); text-align:right;">
                    ${{ number_format($fila['total'], 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ── Sección B: Totales por rubro ────────────────────────────────────── --}}
<p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:0.5rem;">
    Por rubro
</p>
<div class="alumno-card mb-4" style="padding:0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <colgroup>
            <col>
            <col style="width:90px">
            <col style="width:120px">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Rubro</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Tipo</th>
                <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($porRubro as $fila)
            @php
                $esIngreso = $fila['rubro']?->tipo === 'INGRESO';
                $totalColor = $esIngreso ? 'var(--color-success)' : 'var(--color-danger)';
            @endphp
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.82rem; color:var(--color-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $fila['rubro']?->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.72rem; font-weight:600; color:{{ $totalColor }};">
                    {{ $fila['rubro']?->tipo ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; color:{{ $totalColor }}; text-align:right;">
                    ${{ number_format($fila['total'], 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ── Totales generales ────────────────────────────────────────────────── --}}
<div class="filtros-card">
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem;">
        <div style="text-align:center;">
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Ingresos</p>
            <p style="font-size:1.1rem; font-weight:700; color:var(--color-success);">${{ number_format($ingresos, 0, ',', '.') }}</p>
        </div>
        <div style="text-align:center;">
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Egresos</p>
            <p style="font-size:1.1rem; font-weight:700; color:var(--color-danger);">${{ number_format($egresos, 0, ',', '.') }}</p>
        </div>
        <div style="text-align:center;">
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Neto</p>
            <p style="font-size:1.1rem; font-weight:700; color:{{ $neto >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                {{ $neto < 0 ? '–' : '' }}${{ number_format(abs($neto), 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>

@endif

{{-- ── Modal rechazo (solo admin + CERRADA) ────────────────────────────── --}}
@if(Auth::user()->isAdmin() && $caja->estado === 'CERRADA')
<div id="modal-rechazar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--color-surface); border-radius:var(--radius-card); padding:1.5rem; max-width:440px; width:100%; margin:1rem;">
        <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:1rem;">Motivo del rechazo <span style="font-size:0.75rem; color:var(--color-text-muted); font-weight:400;">(opcional)</span></p>
        <form method="POST" action="{{ route('web.cajas.rechazar', $caja->id) }}">
            @csrf
            <input type="text" name="motivo" maxlength="500"
                   placeholder="Describí el motivo o dejá en blanco..."
                   class="w-full px-4 py-2.5 text-sm wings-input"
                   style="display:block; width:100%; margin-bottom:1rem;">
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-rechazar').style.display='none'"
                        style="{{ $btnBSec }}">Cancelar</button>
                <button type="submit" style="{{ $btnBDang }}">Rechazar</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if(Auth::user()->isAdmin() && $caja->estado === 'CERRADA')
<script>
function abrirRechazar() {
    var m = document.getElementById('modal-rechazar');
    if (m) { m.style.display = 'flex'; }
}
document.getElementById('modal-rechazar')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endif
@endpush
