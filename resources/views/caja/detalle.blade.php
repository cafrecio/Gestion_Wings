@extends('layouts.ds-app')

@section('title', 'Detalle caja ' . $caja->apertura_at->format('d/m/Y') . ' – Wings')
@section('module-title', 'Detalle de caja')

@section('content')

@php
$user      = Auth::user();
$esAdmin   = $user->isAdmin();
$esProp    = $user->id === $caja->usuario_operativo_id;

$hasEditar    = in_array($caja->estado, ['ABIERTA']) && $esProp;
$hasCheckbox  = $esAdmin && $caja->estado === 'CERRADA';

$estadoColor = match($caja->estado) {
    'ABIERTA'   => 'var(--color-warning)',
    'CERRADA'   => 'var(--color-text-muted)',
    'VALIDADA'  => 'var(--color-success)',
    'RECHAZADA' => 'var(--color-danger)',
    default     => 'var(--color-text-muted)',
};

$btnB     = 'display:inline-flex; align-items:center; justify-content:center; height:32px; font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap; text-decoration:none; border:none; font-family:inherit; padding:0 14px;';
$btnBSec  = $btnB . ' background:var(--color-btn-secondary); color:var(--color-surface);';
$btnBPrim = $btnB . ' background:var(--color-btn-primary); color:#fff;';
$btnBDang = $btnB . ' background:var(--color-btn-danger); color:#fff;';

$movimientosActivos = $caja->movimientos->where('estado', 'ACTIVO');
$ingresos  = $movimientosActivos->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'INGRESO')->sum('monto');
$egresos   = $movimientosActivos->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'EGRESO')->sum('monto');
$totalMovs = $caja->movimientos->count();
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
</div>

{{-- ── Stats + acciones ─────────────────────────────────────────────────── --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        @if($hasCheckbox)
            <span id="verificados-counter">0 de {{ $totalMovs }} verificados</span>
        @else
            <strong>{{ $totalMovs }}</strong> movimiento{{ $totalMovs !== 1 ? 's' : '' }}
        @endif
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
        @if($hasCheckbox)
            <form method="POST" action="{{ route('web.cajas.validar', $caja->id) }}">
                @csrf
                <button type="submit" id="btn-validar" style="{{ $btnBPrim }}">Validar</button>
            </form>
            <button type="button" onclick="abrirRechazar()" style="{{ $btnBDang }}">Rechazar</button>
        @endif
        <a href="{{ route('web.caja.resumen', $caja->id) }}" style="{{ $btnBSec }}">Resumen</a>
        <a href="{{ route('web.caja.index') }}" style="{{ $btnBSec }}">Volver</a>
    </div>
</div>

{{-- ── Tabla de movimientos ─────────────────────────────────────────────── --}}
@if($caja->movimientos->isNotEmpty())
<div class="alumno-card" style="padding:0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <colgroup>
            <col style="width:90px">
            <col style="width:30px">
            <col style="width:52px">
            <col>
            <col style="width:22%">
            <col style="width:100px">
            @if($hasEditar)
                <col style="width:148px">
            @elseif($hasCheckbox)
                <col style="width:40px">
            @endif
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Fecha</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">I/E</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Medio</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Rubro — Subrubro</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Alumno / Obs.</th>
                <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Monto</th>
                @if($hasEditar || $hasCheckbox)
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">
                    @if($hasCheckbox) Ver @endif
                </th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($caja->movimientos as $mov)
            @php
                $tipoRubro      = $mov->subrubro?->rubro?->tipo;
                $ieLabel        = $tipoRubro === 'INGRESO' ? 'I' : ($tipoRubro === 'EGRESO' ? 'E' : '–');
                $ieColor        = $tipoRubro === 'EGRESO' ? 'var(--color-danger)' : 'var(--color-text)';
                $rubroNombre    = $mov->subrubro?->rubro?->nombre ?? '';
                $subrubroNombre = $mov->subrubro?->nombre ?? '–';
                if ($mov->alumno) {
                    $depAbrev  = strtoupper(substr($mov->alumno->deporte->nombre ?? '', 0, 3));
                    $periodos  = '';
                    if ($mov->pago && $mov->pago->deudasCuota->isNotEmpty()) {
                        $periodos = ' · ' . $mov->pago->deudasCuota
                            ->sortBy('periodo')
                            ->map(fn($dc) => substr($dc->periodo, 5, 2) . '/' . substr($dc->periodo, 2, 2))
                            ->join(', ');
                    }
                    $alumnoObs = $mov->alumno->apellido . ', ' . $mov->alumno->nombre
                        . ($depAbrev ? ' · ' . $depAbrev : '')
                        . $periodos;
                } else {
                    $alumnoObs = $mov->observaciones ?? '–';
                }
                $esCuota     = !is_null($mov->alumno_id);
                $esCancelado = $mov->estado === 'CANCELADO';
                $trStyle     = 'border-bottom:1px solid var(--color-border);'
                    . ($esCancelado ? ' opacity:0.45;' : '');
                $textStyle   = $esCancelado ? 'text-decoration:line-through;' : '';
            @endphp
            <tr style="{{ $trStyle }}">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted); {{ $textStyle }}">
                    {{ $mov->fecha?->format('d/m/Y') ?? '–' }}
                </td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:{{ $ieColor }}; {{ $textStyle }}">
                    {{ $ieLabel }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text); {{ $textStyle }}">
                    {{ $mov->tipoCaja?->abreviatura ?? $mov->tipoCaja?->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; {{ $textStyle }}">
                    @if($rubroNombre)
                        <span style="color:var(--color-text-muted);">{{ $rubroNombre }}</span> — {{ $subrubroNombre }}
                    @else
                        {{ $subrubroNombre }}
                    @endif
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; {{ $textStyle }}">
                    {{ $alumnoObs }}
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; color:var(--color-text); text-align:right; {{ $textStyle }}">
                    ${{ number_format($mov->monto, 0, ',', '.') }}
                </td>
                @if($hasEditar)
                <td style="padding:8px 12px; text-align:center;">
                    @if($esCancelado)
                        <span style="font-size:0.72rem; font-weight:600; color:var(--color-danger);">Cancelado</span>
                    @elseif($esCuota)
                        <button type="button"
                                class="ds-btn-row ds-btn-row--dang"
                                onclick="abrirCancelar({{ $mov->id }}, '{{ route('web.caja.movimientos.cancelar.store', [$caja->id, $mov->id]) }}')">
                            Cancelar
                        </button>
                    @else
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="{{ route('web.caja.movimientos.editar', [$caja->id, $mov->id]) }}"
                               class="ds-btn-row ds-btn-row--sec">Editar</a>
                            <form method="POST"
                                  action="{{ route('web.caja.movimientos.destroy', [$caja->id, $mov->id]) }}"
                                  onsubmit="return confirm('¿Eliminar este movimiento?')"
                                  style="display:contents;">
                                @csrf @method('DELETE')
                                <button type="submit" class="ds-btn-row ds-btn-row--dang">Eliminar</button>
                            </form>
                        </div>
                    @endif
                </td>
                @elseif($hasCheckbox)
                <td style="padding:8px 12px; text-align:center;">
                    <input type="checkbox" class="mov-check"
                           style="width:15px; height:15px; cursor:pointer; accent-color:var(--color-btn-primary);">
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:var(--color-surface-alt); border-top:2px solid var(--color-border);">
                <td colspan="5" style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                    Ingresos: <strong style="color:var(--color-success);">${{ number_format($ingresos, 0, ',', '.') }}</strong>
                    &nbsp;·&nbsp;
                    Egresos: <strong style="color:var(--color-danger);">${{ number_format($egresos, 0, ',', '.') }}</strong>
                </td>
                <td style="padding:8px 12px; font-size:0.9rem; font-weight:700; color:var(--color-text); text-align:right;">
                    ${{ number_format($movimientosActivos->sum('monto'), 0, ',', '.') }}
                </td>
                @if($hasEditar || $hasCheckbox)
                <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>
@else
<div class="empty-state" style="padding:2rem 0;">
    <p style="color:var(--color-text-muted); font-size:0.9rem;">Esta caja no tiene movimientos.</p>
</div>
@endif

{{-- ── Modal cancelar cobro (solo $hasEditar) ─────────────────────────── --}}
@if($hasEditar)
<div id="modal-cancelar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--color-surface); border-radius:var(--radius-card); padding:1.5rem; max-width:440px; width:100%; margin:1rem;">
        <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:0.25rem;">Cancelar cobro de cuota</p>
        <p style="font-size:0.78rem; color:var(--color-text-muted); margin-bottom:1rem;">La deuda del alumno volverá a estado pendiente.</p>
        <form id="form-cancelar" method="POST" action="">
            @csrf
            <textarea name="motivo" required maxlength="500" rows="3"
                      placeholder="Motivo de la cancelación..."
                      class="w-full px-4 py-2.5 text-sm wings-input"
                      style="display:block; width:100%; margin-bottom:1rem; resize:vertical;"></textarea>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="cerrarCancelar()"
                        class="ds-btn" style="background:var(--color-btn-secondary); color:var(--color-surface);">Cerrar</button>
                <button type="submit"
                        class="ds-btn" style="background:var(--color-danger); color:#fff;">Confirmar</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ── Modal rechazo ───────────────────────────────────────────────────── --}}
@if($hasCheckbox)
<div id="modal-rechazar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--color-surface); border-radius:var(--radius-card); padding:1.5rem; max-width:440px; width:100%; margin:1rem;">
        <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:1rem;">Motivo del rechazo <span style="font-size:0.75rem; color:var(--color-text-muted); font-weight:400;">(opcional)</span></p>
        <form method="POST" action="{{ route('web.cajas.rechazar', $caja->id) }}">
            @csrf
            <input type="text" name="motivo" maxlength="500"
                   placeholder="Describí el motivo..."
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
@if($hasEditar)
<script>
function abrirCancelar(movId, actionUrl) {
    var form = document.getElementById('form-cancelar');
    if (form) form.action = actionUrl;
    var m = document.getElementById('modal-cancelar');
    if (m) m.style.display = 'flex';
}
function cerrarCancelar() {
    var m = document.getElementById('modal-cancelar');
    if (m) m.style.display = 'none';
}
document.getElementById('modal-cancelar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarCancelar();
});
</script>
@endif
@if($hasCheckbox)
<script>
(function () {
    var checks  = document.querySelectorAll('.mov-check');
    var counter = document.getElementById('verificados-counter');
    var btnVal  = document.getElementById('btn-validar');
    var total   = checks.length;

    function update() {
        var checked = document.querySelectorAll('.mov-check:checked').length;
        if (counter) counter.textContent = checked + ' de ' + total + ' verificados';
        if (btnVal) {
            btnVal.style.background = (checked === total && total > 0)
                ? 'var(--color-success)'
                : 'var(--color-btn-primary)';
        }
    }

    checks.forEach(function (c) { c.addEventListener('change', update); });
    update();

    function abrirRechazar() {
        var m = document.getElementById('modal-rechazar');
        if (m) m.style.display = 'flex';
    }
    window.abrirRechazar = abrirRechazar;

    document.getElementById('modal-rechazar')?.addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
})();
</script>
@endif
@endpush
