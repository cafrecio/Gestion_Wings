@extends('layouts.ds-app')

@section('title', 'Detalle caja ' . $caja->apertura_at->format('d/m/Y') . ' – Wings')
@section('module-title', 'Detalle de caja')

@section('content')

@error('motivo')
<div class="ds-flash ds-flash--error" role="alert">{{ $message }}</div>
@enderror

<div id="verificacion-error" class="ds-flash ds-flash--error" role="alert" @if(!$errors->has('movimientos')) hidden @endif>{{ $errors->first('movimientos') }}</div>

@php
$user      = Auth::user();
$esAdmin   = $user->isAdmin();
$esProp    = $user->id === $caja->usuario_operativo_id;

$hasEditar    = in_array($caja->estado, ['ABIERTA', 'RECHAZADA']) && $esProp;
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
$totalVerificados = $caja->movimientos->filter(fn ($movimiento) => $movimiento->estaVerificado())->count();
$hasRecibo = $movimientosActivos->whereNotNull('pago_id')->isNotEmpty();
@endphp

{{-- ── Info de la caja ─────────────────────────────────────────────────── --}}
<div class="filtros-card mb-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
<div class="stats-bar mb-3" style="flex-wrap:wrap;">
    <div class="stats-info" style="flex-shrink:0;">
        @if($hasCheckbox)
            <span id="verificados-counter">{{ $totalVerificados }} de {{ $totalMovs }} verificados</span>
        @else
            <strong>{{ $totalMovs }}</strong> movimiento{{ $totalMovs !== 1 ? 's' : '' }}
        @endif
    </div>
    <div class="grid w-full sm:w-auto sm:flex" style="grid-template-columns:repeat(2,max-content); justify-content:end; gap:8px; align-items:center;">
        @if($hasCheckbox)
            <form method="POST" action="{{ route('web.cajas.validar', $caja->id) }}">
                @csrf
                <button type="submit" id="btn-validar" class="w-full" style="{{ $btnBPrim }}">Validar</button>
            </form>
            <button type="button" data-abrir-rechazar style="{{ $btnBDang }}">Rechazar</button>
        @endif
        <a href="{{ route('web.caja.resumen', $caja->id) }}" style="{{ $btnBSec }}">Resumen</a>
        <a href="{{ route('web.caja.index') }}" style="{{ $btnBSec }}">Volver</a>
    </div>
</div>

{{-- ── Tabla de movimientos ─────────────────────────────────────────────── --}}
@if($caja->movimientos->isNotEmpty())
<p class="text-xs text-wings-muted mb-2 sm:hidden">Deslizá la tabla para ver todas las columnas.</p>
<div class="alumno-card" style="padding:0; overflow:hidden;">
    <div style="overflow-x:auto;" tabindex="0" role="region" aria-label="Movimientos de la caja">
    <table class="tabla-operativa-movil" style="width:100%; min-width:800px; border-collapse:collapse; table-layout:fixed;">
        <colgroup>
            <col style="width:90px">
            <col style="width:30px">
            <col style="width:52px">
            <col>
            <col style="width:22%">
            <col class="col-importe" style="width:100px">
            @if($hasEditar)
                <col class="col-acciones-movil" style="width:148px">
            @elseif($hasCheckbox)
                <col class="col-revision-movil" style="width:{{ $hasRecibo ? '110px' : '40px' }}">
            @elseif($hasRecibo)
                <col class="col-recibo-movil" style="width:72px">
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
                @if($hasEditar || $hasCheckbox || $hasRecibo)
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);"></th>
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
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; {{ $textStyle }}">
                        {{ $alumnoObs }}
                    </div>
                    @if($esCancelado)
                        <div style="font-size:0.72rem; font-weight:600; color:var(--color-danger); margin-top:2px; white-space:normal;">
                            Cancelado{{ $mov->motivo_cancelacion ? ': ' . $mov->motivo_cancelacion : '' }}
                        </div>
                    @endif
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; color:var(--color-text); text-align:right; {{ $textStyle }}">
                    <span class="ds-money ds-money--row">{{ \App\Support\FormatoImporte::pesos($mov->monto) }}</span>
                </td>
                @if($hasEditar)
                <td style="padding:8px 12px; text-align:center;">
                    @if($esCancelado)
                        <span style="font-size:0.72rem; font-weight:600; color:var(--color-danger);">Cancelado</span>
                    @elseif($esCuota)
                        <div style="display:flex; gap:6px; justify-content:center;">
                            @if($mov->pago_id)
                            <a href="{{ route('web.recibos.cuota', $mov->pago_id) }}?inline=1"
                               class="ds-btn-row ds-btn-row--sec" target="_blank">Recibo</a>
                            @endif
                            <button type="button"
                                    class="ds-btn-row ds-btn-row--dang"
                                    data-abrir-cancelar="{{ route('web.caja.movimientos.cancelar.store', [$caja->id, $mov->id]) }}"
                                    data-mov-id="{{ $mov->id }}">
                                Cancelar
                            </button>
                        </div>
                    @else
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="{{ route('web.caja.movimientos.editar', [$caja->id, $mov->id]) }}"
                               class="ds-btn-row ds-btn-row--sec">Editar</a>
                            <form method="POST"
                                  action="{{ route('web.caja.movimientos.destroy', [$caja->id, $mov->id]) }}"
                                  data-confirmar-eliminacion="¿Eliminar este movimiento?"
                                  style="display:contents;">
                                @csrf @method('DELETE')
                                <button type="submit" class="ds-btn-row ds-btn-row--dang">Eliminar</button>
                            </form>
                        </div>
                    @endif
                </td>
                @elseif($hasCheckbox)
                <td style="padding:8px 12px; text-align:center;">
                    @if($esCuota && $mov->pago_id && !$esCancelado)
                    <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                        <a href="{{ route('web.recibos.cuota', $mov->pago_id) }}?inline=1"
                           class="ds-btn-row ds-btn-row--sec" target="_blank">Recibo</a>
                        <input type="checkbox" class="mov-check"
                               data-verificar-url="{{ route('web.cajas.movimientos.verificar', [$caja->id, $mov->id]) }}"
                               data-huella="{{ $mov->huellaVerificacion() }}" @checked($mov->estaVerificado())
                               @disabled($caja->efectivo_contado === null) aria-label="Verificar movimiento {{ $mov->id }}"
                               style="width:15px; height:15px; cursor:pointer; accent-color:var(--color-btn-primary);">
                    </div>
                    @else
                    <input type="checkbox" class="mov-check"
                           data-verificar-url="{{ route('web.cajas.movimientos.verificar', [$caja->id, $mov->id]) }}"
                           data-huella="{{ $mov->huellaVerificacion() }}" @checked($mov->estaVerificado())
                           @disabled($caja->efectivo_contado === null) aria-label="Verificar movimiento {{ $mov->id }}"
                           style="width:15px; height:15px; cursor:pointer; accent-color:var(--color-btn-primary);">
                    @endif
                </td>
                @elseif($hasRecibo)
                <td style="padding:8px 12px; text-align:center;">
                    @if($esCuota && $mov->pago_id && !$esCancelado)
                    <a href="{{ route('web.recibos.cuota', $mov->pago_id) }}?inline=1"
                       class="ds-btn-row ds-btn-row--sec" target="_blank">Recibo</a>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:var(--color-surface-alt); border-top:2px solid var(--color-border);">
                <td colspan="5" style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                    Ingresos: <strong style="color:var(--color-success);"><span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos($ingresos) }}</span></strong>
                    &nbsp;·&nbsp;
                    Egresos: <strong style="color:var(--color-danger);"><span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos($egresos) }}</span></strong>
                </td>
                @php $netoDetalle = $ingresos - $egresos; @endphp
                <td style="padding:8px 12px; font-size:0.9rem; font-weight:700; text-align:right;
                           color:{{ $netoDetalle >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                    <span class="ds-money ds-money--summary">{{ $netoDetalle < 0 ? '−' : '' }}{{ \App\Support\FormatoImporte::pesos(abs($netoDetalle)) }}</span>
                </td>
                @if($hasEditar || $hasCheckbox || $hasRecibo)
                <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
    </div>
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
                <button type="button" data-cerrar-cancelar
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
        <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:1rem;">Motivo del rechazo</p>
        <form method="POST" action="{{ route('web.cajas.rechazar', $caja->id) }}">
            @csrf
            <input type="text" name="motivo" required maxlength="500" value="{{ is_string(old('motivo')) ? old('motivo') : '' }}"
                   placeholder="Describí el motivo..."
                   class="w-full px-4 py-2.5 text-sm wings-input"
                   style="display:block; width:100%; margin-bottom:1rem;">
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" data-cerrar-rechazar
                        style="{{ $btnBSec }}">Cancelar</button>
                <button type="submit" style="{{ $btnBDang }}">Rechazar</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
@vite('resources/js/caja-detalle.js')
@endpush
