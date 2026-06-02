@extends('layouts.ds-app')

@section('title', 'Cajas – Wings')
@section('module-title', 'Cajas')

@section('content')

@php
    $estadoLabel = [
        'ABIERTA'   => ['color' => 'var(--color-success)',   'text' => 'Abierta'],
        'CERRADA'   => ['color' => 'var(--color-warning)',   'text' => 'Pendiente'],
        'VALIDADA'  => ['color' => 'var(--color-btn-primary)', 'text' => 'Validada'],
        'RECHAZADA' => ['color' => 'var(--color-danger)',    'text' => 'Rechazada'],
    ];
@endphp

{{-- ── Pendientes de validación ────────────────────────────────────────── --}}

<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $pendientes->count() }}</strong> {{ $pendientes->count() === 1 ? 'caja pendiente' : 'cajas pendientes' }} de validación
    </div>
</div>

@if($pendientes->isNotEmpty())
    @foreach($pendientes as $caja)
        @php $info = $estadoLabel[$caja->estado]; @endphp
        <div class="alumno-card">
            <div class="alumno-card-header">
                <span class="alumno-dot" style="background:{{ $info['color'] }};"></span>
                <h3 class="alumno-nombre">{{ $caja->usuarioOperativo->name }}</h3>
            </div>
            <div class="alumno-info" style="grid-template-columns: repeat(4, 1fr);">
                <div class="info-item">
                    <span class="info-label">Apertura:</span>
                    <span class="info-value">{{ $caja->apertura_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cierre:</span>
                    <span class="info-value">{{ $caja->cierre_at?->format('d/m/Y H:i') ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total:</span>
                    <span class="info-value" style="font-weight:700;">
                        ${{ number_format($caja->movimientos->sum('monto'), 0, ',', '.') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-value" style="font-weight:600; color:{{ $info['color'] }};">{{ $info['text'] }}</span>
                </div>
            </div>
            <div class="alumno-actions">
                <a href="{{ route('web.cajas.show', $caja->id) }}"
                   style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:1px solid var(--color-btn-secondary); color:var(--color-btn-secondary); background:none; border-radius:var(--radius-btn); text-decoration:none;">
                    Ver
                </a>
                <form method="POST" action="{{ route('web.cajas.validar', $caja->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit"
                            style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:none; background:var(--color-btn-primary); color:#fff; border-radius:var(--radius-btn); cursor:pointer;">
                        Validar
                    </button>
                </form>
                <button type="button" data-caja-id="{{ $caja->id }}"
                        onclick="abrirRechazar(this.dataset.cajaId)"
                        style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:none; background:var(--color-btn-danger); color:#fff; border-radius:var(--radius-btn); cursor:pointer; margin-left:auto;">
                    Rechazar
                </button>
            </div>
        </div>
    @endforeach
@else
    <div class="empty-state" style="padding:2rem 0;">
        <p style="color:var(--color-text-muted); font-size:0.9rem;">Sin cajas pendientes de validación.</p>
    </div>
@endif

{{-- ── Cajas abiertas ───────────────────────────────────────────────────── --}}

<div class="stats-bar mb-3" style="margin-top:2rem;">
    <div class="stats-info">
        <strong>{{ $abiertas->count() }}</strong> {{ $abiertas->count() === 1 ? 'caja abierta' : 'cajas abiertas' }}
    </div>
</div>

@if($abiertas->isNotEmpty())
    @foreach($abiertas as $caja)
        <div class="alumno-card">
            <div class="alumno-card-header">
                <span class="alumno-dot" style="background:var(--color-success);"></span>
                <h3 class="alumno-nombre">{{ $caja->usuarioOperativo->name }}</h3>
            </div>
            <div class="alumno-info" style="grid-template-columns: repeat(3, 1fr);">
                <div class="info-item">
                    <span class="info-label">Apertura:</span>
                    <span class="info-value">{{ $caja->apertura_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-value" style="font-weight:600; color:var(--color-success);">Abierta</span>
                </div>
                <div class="info-item">
                    <span class="info-label">¿Día anterior?</span>
                    <span class="info-value" style="font-weight:600; {{ !$caja->apertura_at->isToday() ? 'color:var(--color-danger)' : '' }}">
                        {{ $caja->apertura_at->isToday() ? 'No' : 'SÍ' }}
                    </span>
                </div>
            </div>
            <div class="alumno-actions">
                <a href="{{ route('web.cajas.show', $caja->id) }}"
                   style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:1px solid var(--color-btn-secondary); color:var(--color-btn-secondary); background:none; border-radius:var(--radius-btn); text-decoration:none;">
                    Ver
                </a>
                <form method="POST" action="{{ route('web.caja.cerrar', $caja->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit"
                            style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:none; background:var(--color-btn-danger); color:#fff; border-radius:var(--radius-btn); cursor:pointer; margin-left:auto;">
                        Cerrar
                    </button>
                </form>
            </div>
        </div>
    @endforeach
@else
    <div class="empty-state" style="padding:2rem 0;">
        <p style="color:var(--color-text-muted); font-size:0.9rem;">Sin cajas abiertas actualmente.</p>
    </div>
@endif

{{-- ── Historial: validadas / rechazadas ───────────────────────────────── --}}

@if($validadas->isNotEmpty() || $rechazadas->isNotEmpty())
    <div class="stats-bar mb-3" style="margin-top:2rem;">
        <div class="stats-info">Historial reciente</div>
    </div>

    @foreach($validadas->merge($rechazadas)->sortByDesc('updated_at') as $caja)
        @php $info = $estadoLabel[$caja->estado]; @endphp
        <div class="alumno-card">
            <div class="alumno-card-header">
                <span class="alumno-dot" style="background:{{ $info['color'] }};"></span>
                <h3 class="alumno-nombre">{{ $caja->usuarioOperativo->name }}</h3>
            </div>
            <div class="alumno-info" style="grid-template-columns: repeat(4, 1fr);">
                <div class="info-item">
                    <span class="info-label">Apertura:</span>
                    <span class="info-value">{{ $caja->apertura_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cierre:</span>
                    <span class="info-value">{{ $caja->cierre_at?->format('d/m/Y') ?? '–' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-value" style="font-weight:600; color:{{ $info['color'] }};">{{ $info['text'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total:</span>
                    <span class="info-value" style="font-weight:700;">
                        ${{ number_format($caja->movimientos->sum('monto'), 0, ',', '.') }}
                    </span>
                </div>
            </div>
            <div class="alumno-actions">
                <a href="{{ route('web.cajas.show', $caja->id) }}"
                   style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:1px solid var(--color-btn-secondary); color:var(--color-btn-secondary); background:none; border-radius:var(--radius-btn); text-decoration:none;">
                    Ver
                </a>
            </div>
        </div>
    @endforeach
@endif

{{-- ── Modal rechazo ───────────────────────────────────────────────────── --}}
<div id="modal-rechazar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--color-surface); border-radius:var(--radius-card); padding:1.5rem; max-width:440px; width:100%; margin:1rem;">
        <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:1rem;">Motivo del rechazo</p>
        <form id="form-rechazar" method="POST">
            @csrf
            <input type="text" name="motivo" required maxlength="500"
                   placeholder="Describí el motivo..."
                   class="w-full px-4 py-2.5 text-sm wings-input mb-4"
                   style="display:block; width:100%; margin-bottom:1rem;">
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="cerrarModal()"
                        style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:1px solid var(--color-btn-secondary); color:var(--color-btn-secondary); background:none; border-radius:var(--radius-btn); cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="display:inline-flex; align-items:center; justify-content:center; width:96px; height:32px; font-size:0.82rem; font-weight:600; border:none; background:var(--color-btn-danger); color:#fff; border-radius:var(--radius-btn); cursor:pointer;">
                    Rechazar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function abrirRechazar(cajaId) {
    var modal = document.getElementById('modal-rechazar');
    var form  = document.getElementById('form-rechazar');
    form.action = '/cajas/' + cajaId + '/rechazar';
    modal.style.display = 'flex';
    modal.querySelector('input[name="motivo"]').focus();
}
function cerrarModal() {
    document.getElementById('modal-rechazar').style.display = 'none';
}
document.getElementById('modal-rechazar').addEventListener('click', function (e) {
    if (e.target === this) cerrarModal();
});
</script>
@endpush
