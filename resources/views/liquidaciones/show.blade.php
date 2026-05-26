@extends('layouts.app')

@php
    $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                     'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $diasSemana   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $mesesES      = ['','enero','febrero','marzo','abril','mayo','junio',
                     'julio','agosto','septiembre','octubre','noviembre','diciembre'];

    $dep  = mb_strtolower($liquidacion->profesor->deporte->nombre ?? '');
    $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');

    $periodo     = $mesesNombres[$liquidacion->mes] . ' ' . $liquidacion->anio;
    $tipoLabel   = $liquidacion->tipo === 'HORA' ? 'Por hora' : 'Por comisión';
    $esPagada    = $liquidacion->estaPagada();
    $esCerrada   = $liquidacion->estaCerrada();
    $estaAbierta = $liquidacion->estaAbierta();
    $totalSum    = $liquidacion->detalles->sum('monto');
    $totalDetalles = $liquidacion->detalles->count();
@endphp

@section('title', $periodo . ' — ' . $liquidacion->profesor->apellido . ' – Wings')
@section('module-title', 'Liquidación — ' . $liquidacion->profesor->apellido . ', ' . $liquidacion->profesor->nombre)

@section('content')

{{-- ── SECCIÓN A — Header ─────────────────────────────────────────────────── --}}
<div class="filtros-card mb-3" style="padding:0; overflow:hidden;">
    <div style="display:flex;">

        <div style="width:6px; flex-shrink:0; background:var(--color-sport-{{ $rail }});"></div>

        <div style="flex:1; padding:1.25rem 1.5rem;">

            <div style="display:flex; align-items:baseline; gap:1rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                <h2 style="font-size:1.1rem; font-weight:800; color:var(--color-text); margin:0;">
                    {{ $liquidacion->profesor->apellido }}, {{ $liquidacion->profesor->nombre }}
                </h2>
                <span style="font-size:0.85rem; color:var(--color-text-muted); font-weight:600;">
                    {{ $periodo }}
                </span>
                <span style="font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:999px;
                             background:color-mix(in srgb, var(--color-sport-{{ $rail }}) 15%, transparent);
                             color:var(--color-sport-{{ $rail }});">
                    {{ $liquidacion->profesor->deporte->nombre ?? '—' }}
                </span>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:1.5rem; margin-bottom:0.75rem; align-items:center;">
                <div>
                    <span style="font-size:0.7rem; color:var(--color-text-muted); display:block;">Tipo</span>
                    <span style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $tipoLabel }}</span>
                </div>
                <div>
                    <span style="font-size:0.7rem; color:var(--color-text-muted); display:block;">Total calculado</span>
                    <span style="font-size:1.4rem; font-weight:800; color:var(--color-text);">
                        ${{ number_format((float)$liquidacion->total_calculado, 0, ',', '.') }}
                    </span>
                </div>
                <div>
                    <span style="font-size:0.7rem; color:var(--color-text-muted); display:block;">Estado</span>
                    @if($esPagada)
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                     background:color-mix(in srgb, var(--color-success) 15%, transparent);
                                     color:var(--color-success);">Pagada</span>
                    @elseif($esCerrada)
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                     background:color-mix(in srgb, var(--color-text-muted) 15%, transparent);
                                     color:var(--color-text-muted);">Cerrada</span>
                    @else
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                     background:color-mix(in srgb, var(--color-warning) 15%, transparent);
                                     color:var(--color-warning);">Abierta</span>
                    @endif
                </div>
                @if($esPagada && $liquidacion->pagada_fecha)
                    <div>
                        <span style="font-size:0.7rem; color:var(--color-text-muted); display:block;">Fecha de pago</span>
                        <span style="font-size:0.85rem; font-weight:600; color:var(--color-text);">
                            {{ $liquidacion->pagada_fecha->format('d/m/Y') }}
                            @if($liquidacion->pagadaTipoCaja)
                                <span style="font-size:0.72rem; color:var(--color-text-muted);">
                                    — {{ $liquidacion->pagadaTipoCaja->nombre }}
                                </span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">

                @if($estaAbierta)
                    <form method="POST" action="{{ route('web.liquidaciones.cerrar', $liquidacion->id) }}"
                          onsubmit="return confirm('¿Cerrar esta liquidación? No podrá ser modificada después.')">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:var(--color-btn-primary); color:var(--color-surface);">
                            Cerrar
                        </button>
                    </form>

                    <form method="POST" action="{{ route('web.liquidaciones.recalcular', $liquidacion->id) }}"
                          onsubmit="return confirm('¿Recalcular? Los detalles actuales serán reemplazados.')">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:var(--color-btn-secondary); color:var(--color-surface);">
                            Recalcular
                        </button>
                    </form>

                    <form method="POST" action="{{ route('web.liquidaciones.eliminar', $liquidacion->id) }}"
                          onsubmit="return confirm('¿Eliminar esta liquidación permanentemente?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:var(--color-btn-danger); color:var(--color-surface);">
                            Eliminar
                        </button>
                    </form>
                @endif

                <a href="{{ route('web.liquidaciones.index') }}"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          width:96px; height:32px; font-size:0.82rem; font-weight:600;
                          border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                          text-decoration:none; border:none; font-family:inherit;
                          background:var(--color-btn-secondary); color:var(--color-surface);">
                    Volver
                </a>
            </div>

        </div>
    </div>
</div>

{{-- ── SECCIÓN B — Stats + Detalles ────────────────────────────────────────── --}}

@if($liquidacion->tipo === 'HORA')

{{-- Stats HORA --}}
<div class="filtros-card mb-3" style="padding:0; overflow:hidden;">
    <div style="display:grid; grid-template-columns:1fr 1px 1fr 1px 1fr;">
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Clases liquidadas
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                {{ $totalDetalles }}
            </span>
        </div>
        <div style="background:var(--color-border);"></div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Valor por clase
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                ${{ number_format((float)($liquidacion->profesor->valor_hora ?? 0), 0, ',', '.') }}
            </span>
        </div>
        <div style="background:var(--color-border);"></div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Total
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-btn-primary);">
                ${{ number_format((float)$totalSum, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>

{{-- Detalles HORA --}}
@forelse($liquidacion->detalles as $detalle)
@php
    $clase     = $detalle->referencia;
    $validada  = $clase?->validada_para_liquidacion ?? false;
    $estadoLbl = $validada ? 'Validada' : 'Con asistencia';
    $dotColor  = $validada ? 'var(--color-btn-primary)' : 'var(--color-success)';

    $fecha = $clase?->fecha;
    $fechaLabel = $fecha
        ? $diasSemana[$fecha->dayOfWeek] . ' ' . $fecha->day . ' de ' . $mesesES[$fecha->month]
        : ($detalle->descripcion);

    $grupoNombre = $clase?->grupo?->nombre ?? '—';

    $horaI = $clase?->hora_inicio;
    $horaF = $clase?->hora_fin;
    $duracion = ($horaI && $horaF)
        ? (abs((int)$horaF->diffInMinutes($horaI)) . ' min')
        : '—';
@endphp
<div class="alumno-card">
    <div class="alumno-card-header">
        <span class="alumno-dot" style="background:{{ $dotColor }};"></span>
        <h3 class="alumno-nombre">{{ $fechaLabel }}</h3>
        <span style="margin-left:auto; font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:999px;
                     white-space:nowrap;
                     background:color-mix(in srgb, {{ $dotColor }} 12%, transparent);
                     color:{{ $dotColor }};">
            {{ $estadoLbl }}
        </span>
    </div>
    <div class="alumno-info">
        <div class="info-item">
            <span class="info-label">Grupo:</span>
            <span class="info-value">{{ $grupoNombre }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Duración:</span>
            <span class="info-value">{{ $duracion }}</span>
        </div>
        <div class="info-item" style="margin-left:auto;">
            <span class="info-label">Subtotal:</span>
            <span class="info-value" style="font-weight:700;">
                ${{ number_format((float)$detalle->monto, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
@empty
    <div class="empty-state">
        <p style="font-size:0.82rem; color:var(--color-text-muted);">Sin detalles registrados.</p>
    </div>
@endforelse

@else

{{-- Stats COMISION --}}
<div class="filtros-card mb-3" style="padding:0; overflow:hidden;">
    <div style="display:grid; grid-template-columns:1fr 1px 1fr 1px 1fr;">
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Alumnos liquidados
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                {{ $totalDetalles }}
            </span>
        </div>
        <div style="background:var(--color-border);"></div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                % Comisión
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                {{ number_format((float)($liquidacion->profesor->porcentaje_comision ?? 0), 1) }}%
            </span>
        </div>
        <div style="background:var(--color-border);"></div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Total
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-btn-primary);">
                ${{ number_format((float)$totalSum, 2, ',', '.') }}
            </span>
        </div>
    </div>
</div>

{{-- Detalles COMISION --}}
@forelse($liquidacion->detalles as $detalle)
@php
    $alumno     = $detalle->referencia;
    $nombreHdr  = $alumno
        ? ($alumno->apellido . ', ' . $alumno->nombre)
        : $detalle->descripcion;
    $grupoNom   = $alumno?->grupo?->nombre ?? '—';

    // Parsear monto pagado del campo descripcion: "... - Pago $X.XXX,XX (...)"
    $montoPagado = '—';
    if (preg_match('/Pago \$([\d.,]+)/', $detalle->descripcion, $m)) {
        $montoPagado = '$' . $m[1];
    }
@endphp
<div class="alumno-card">
    <div class="alumno-card-header">
        <span class="alumno-dot" style="background:var(--color-success);"></span>
        <h3 class="alumno-nombre">{{ $nombreHdr }}</h3>
    </div>
    <div class="alumno-info">
        <div class="info-item">
            <span class="info-label">Grupo:</span>
            <span class="info-value">{{ $grupoNom }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Monto pagado:</span>
            <span class="info-value">{{ $montoPagado }}</span>
        </div>
        <div class="info-item" style="margin-left:auto;">
            <span class="info-label">Comisión:</span>
            <span class="info-value" style="font-weight:700; color:var(--color-success);">
                ${{ number_format((float)$detalle->monto, 2, ',', '.') }}
            </span>
        </div>
    </div>
</div>
@empty
    <div class="empty-state">
        <p style="font-size:0.82rem; color:var(--color-text-muted);">Sin detalles registrados.</p>
    </div>
@endforelse

@endif

{{-- Total al pie --}}
@if($liquidacion->detalles->isNotEmpty())
<div style="display:flex; justify-content:flex-end; align-items:center; gap:8px;
            padding:14px 20px; margin-bottom:1rem;
            border-top:2px solid var(--color-border);
            background:color-mix(in srgb, var(--color-btn-primary) 4%, var(--color-surface));
            border-radius:var(--radius-card);">
    <span style="font-size:0.82rem; font-weight:600; color:var(--color-text-muted); text-transform:uppercase;
                 letter-spacing:0.05em;">
        Total liquidado
    </span>
    <span style="font-size:1.5rem; font-weight:800; color:var(--color-btn-primary);">
        ${{ $liquidacion->tipo === 'HORA'
            ? number_format((float)$totalSum, 0, ',', '.')
            : number_format((float)$totalSum, 2, ',', '.') }}
    </span>
</div>
@endif

{{-- ── SECCIÓN C — Pagar (solo si cerrada y no pagada) ───────────────────── --}}
@if($esCerrada && !$esPagada)
<div class="filtros-card">
    <p style="font-size:0.82rem; font-weight:700; color:var(--color-text); margin:0 0 12px;">
        Registrar pago de liquidación
    </p>

    <form method="POST" action="{{ route('web.liquidaciones.pagar', $liquidacion->id) }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <label for="tipo_caja_id"
                       style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">
                    Tipo de caja <span class="form-required">*</span>
                </label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-3 py-2.5 text-sm wings-input">
                    <option value="">— Seleccioná —</option>
                    @foreach($tiposCaja as $tc)
                        @php
                            $saldo    = (float) ($saldosPorTipoCaja[$tc->id] ?? 0);
                            $saldoFmt = '$' . number_format($saldo, 0, ',', '.');
                            $descLabel = $tc->permite_descubierto ? ' (descubierto permitido)' : '';
                            $optLabel  = $tc->nombre . ' — Saldo: ' . $saldoFmt . $descLabel;
                        @endphp
                        <option value="{{ $tc->id }}" {{ old('tipo_caja_id') == $tc->id ? 'selected' : '' }}>
                            {{ $optLabel }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_caja_id')
                    <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_pago"
                       style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">
                    Fecha de pago <span class="form-required">*</span>
                </label>
                <input type="date" id="fecha_pago" name="fecha_pago" required
                       value="{{ old('fecha_pago', now()->format('Y-m-d')) }}"
                       class="w-full px-3 py-2.5 text-sm wings-input">
                @error('fecha_pago')
                    <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="observaciones"
                       style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">
                    Observaciones
                </label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ old('observaciones') }}" maxlength="500"
                       placeholder="Opcional"
                       class="w-full px-3 py-2.5 text-sm wings-input">
                @error('observaciones')
                    <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:16px; padding-top:12px;
                    border-top:1px solid var(--color-border);">
            <x-ds.button variant="primary" type="submit">Pagar</x-ds.button>
        </div>

    </form>
</div>
@endif

@endsection
