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
    $esCancelada = $liquidacion->estaCancelada();
    $estaAbierta = $liquidacion->estaAbierta();
    $totalSum    = $liquidacion->detalles->sum('monto');
    $totalDetalles = $liquidacion->detalles->count();
    $atrasadasPendientes = $liquidacion->detalles->filter(fn ($d) => $d->origen_cuota === 'atrasado'
        && (!$d->decision_atrasada || $d->decision_atrasada === 'PENDIENTE'))->count();
@endphp

@section('title', $periodo . ' — ' . $liquidacion->profesor->apellido . ' – Wings')
@section('module-title', 'Liquidación — ' . $liquidacion->profesor->apellido . ', ' . $liquidacion->profesor->nombre)

@section('content')

{{-- ── SECCIÓN A — Header ─────────────────────────────────────────────────── --}}
<div data-liquidacion-actualizable="cabecera" class="filtros-card mb-3 cabecera-rail-movil" style="padding:0; overflow:hidden;">
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
                            <span style="font-size:0.7rem; color:var(--color-text-muted); display:block;">Monto final</span>
                    <span style="font-size:1.4rem; font-weight:800; color:var(--color-text);" class="ds-money-value">
                                <span data-liquidacion-importe="final" class="ds-money ds-money--total">{{ \App\Support\FormatoImporte::pesos((float)$liquidacion->monto_a_pagar) }}</span>
                    </span>
                </div>
                <div>
                    <span style="font-size:0.7rem; color:var(--color-text-muted); display:block;">Estado</span>
                    @if($esPagada)
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                     background:color-mix(in srgb, var(--color-success) 15%, transparent);
                                     color:var(--color-success);">Pagada</span>
                    @elseif($esCancelada)
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                     background:color-mix(in srgb, var(--color-danger) 15%, transparent);
                                     color:var(--color-danger);">Cancelada</span>
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
                @if($esCancelada)
                    <div>
                        <span style="font-size:0.7rem; color:var(--color-danger); display:block;">Cancelada</span>
                        <span style="font-size:0.85rem; font-weight:600; color:var(--color-text);">
                            {{ $liquidacion->cancelada_at ? $liquidacion->cancelada_at->format('d/m/Y') : '—' }}
                            @if($liquidacion->usuarioCancelacion)
                                <span style="font-size:0.72rem; color:var(--color-text-muted);">
                                    — por {{ $liquidacion->usuarioCancelacion->name }}
                                </span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            @if($esCancelada && $liquidacion->motivo_cancelacion)
                <div class="mb-2" style="font-size:0.78rem; color:var(--color-text-muted); background:var(--color-bg); padding:6px 10px; border-radius:var(--radius-sm); border-left:3px solid var(--color-danger);">
                    <strong>Motivo de cancelación:</strong> {{ $liquidacion->motivo_cancelacion }}
                    @if($liquidacion->reemplazadaPor)
                        <span style="margin-left:10px;">
                            <strong>Reemplazada por:</strong>
                            <a href="{{ route('web.liquidaciones.show', $liquidacion->reemplazada_por_id) }}" style="color:var(--color-btn-primary); font-weight:600;">
                                Liquidación #{{ $liquidacion->reemplazada_por_id }}
                            </a>
                        </span>
                    @endif
                </div>
            @endif

            <div data-liquidacion-acciones>
                @include('liquidaciones.partials.acciones')
            </div>

        </div>
    </div>
</div>

{{-- ── SECCIÓN B — Stats + Detalles ────────────────────────────────────────── --}}

@error('decision')
    <div data-liquidacion-error class="filtros-card mb-3" style="color:var(--color-danger);">{{ $message }}</div>
@enderror
<div data-liquidacion-revision>
    @include('liquidaciones.partials.revision-atrasadas')
</div>

@if($liquidacion->tipo === 'HORA')

{{-- Stats HORA --}}
<div class="filtros-card mb-3 liquidacion-importes resumen-cifras" style="padding:0; overflow:hidden;">
    <div style="display:grid; grid-template-columns:1fr 1px 1fr 1px 1fr;">
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Clases liquidadas
            </span>
            <span class="ds-count-value" style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                {{ $totalDetalles }}
            </span>
        </div>
        <div style="background:var(--color-border);"></div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Valor por hora
            </span>
            <span class="ds-money-summary ds-money-value" style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                <span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos((float)($liquidacion->profesor->valor_hora ?? 0)) }}</span>
            </span>
        </div>
        <div style="background:var(--color-border);"></div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Total
            </span>
            <span class="ds-money-summary ds-money-value" style="font-size:1.6rem; font-weight:800; color:var(--color-btn-primary);">
                <span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos((float)$totalSum) }}</span>
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
        : (\App\Support\FormatoImporte::textoHistorico($detalle->descripcion ?? ''));

    $grupoNombre = $clase?->grupo?->nombre ?? '—';
    $duracion    = \App\Services\ReciboService::formatearDuracion($detalle->minutos);
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
            <span class="info-value ds-money-value" style="font-weight:700;">
                <span class="ds-money ds-money--row">{{ \App\Support\FormatoImporte::pesos((float)$detalle->monto) }}</span>
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
<div class="filtros-card mb-3">
    <div data-liquidacion-actualizable="importes" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><span class="info-label"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Cálculo original</span><p class="info-value ds-money-value"><span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos((float)$liquidacion->total_calculado) }}</span></p></div>
        <div><span class="info-label"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M4 7h.01M16 17H4m16 0h.01M7 4v6m10 4v6"/></svg>Ajuste del admin</span><p class="info-value ds-money-value"><span data-liquidacion-importe="ajuste" class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos((float)$liquidacion->monto_a_pagar - (float)$liquidacion->total_confirmado) }}</span></p></div>
        <div><span class="info-label"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Monto final</span><p class="info-value ds-money-value"><span data-liquidacion-importe="final" class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos((float)$liquidacion->monto_a_pagar) }}</span></p></div>
    </div>
    @if(!$esPagada && !$esCancelada && \App\Models\Liquidacion::ajustesDisponibles())
    <details data-liquidacion-actualizable="ajuste" class="mt-3" id="editar-liquidacion" {{ request()->boolean('editar') ? 'open' : '' }}>
        <summary class="info-label" style="cursor:pointer;">Editar monto final</summary>
        <form method="POST" action="{{ route('web.liquidaciones.ajustar', $liquidacion->id) }}" class="mt-3">
            @csrf
            <input type="hidden" name="monto_anterior" value="{{ $liquidacion->monto_a_pagar }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label for="monto_final" class="info-label"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Monto ajustado</label>
                    <x-ds.money-input id="monto_final" name="monto_final" aria-describedby="monto_final_ayuda"
                                      min="0" max="9999999999.99" :prefix="false" required
                                      :value="old('monto_final', $liquidacion->monto_a_pagar)" />
                    <p id="monto_final_ayuda" class="text-xs mt-1" style="color:var(--color-text-muted);">Total a pagar, desde <span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos(0) }}</span>. La diferencia se calcula automáticamente.</p>
                    @error('monto_final')<p style="color:var(--color-danger);">{{ $message }}</p>@enderror
                </div>
                <div><label for="motivo_ajuste" class="info-label"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Motivo del ajuste</label>
                    <input id="motivo_ajuste" name="motivo" required minlength="5" maxlength="255" value="{{ old('motivo') }}" class="w-full px-3 py-2 text-sm wings-input">
                    @error('motivo')<p style="color:var(--color-danger);">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="alumno-actions acciones-celular-derecha mt-3"><x-ds.button variant="primary" type="submit">Guardar</x-ds.button></div>
        </form>
    </details>
    @endif
</div>
<div class="filtros-card mb-3 resumen-cifras" style="padding:0; overflow:hidden;">
    <div class="grid grid-cols-1 sm:grid-cols-3">
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Alumnos con pagos registrados
            </span>
            <span class="ds-count-value" style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                {{ $liquidacion->detalles->pluck('referencia_id')->unique()->count() }}
            </span>
        </div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                % Comisión
            </span>
            <span class="ds-count-value" style="font-size:1.6rem; font-weight:800; color:var(--color-text);">
                {{ number_format((float)($liquidacion->porcentaje_comision_aplicado ?? $liquidacion->profesor->porcentaje_comision ?? 0), 1) }}%
            </span>
        </div>
        <div style="padding:1rem 1.5rem; text-align:center;">
            <span style="font-size:0.68rem; font-weight:600; color:var(--color-text-muted);
                         display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                Cálculo original
            </span>
            <span style="font-size:1.6rem; font-weight:800; color:var(--color-btn-primary);" class="ds-money-value">
                <span class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos((float)$totalSum) }}</span>
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
        : \App\Support\FormatoImporte::textoHistorico($detalle->descripcion ?? '');
    $grupoNom   = $alumno?->grupo?->nombre ?? '—';

    // Parsear monto pagado del campo descripcion: "... - Pago $X.XXX,XX (...)"
    $montoPagado = '—';
    if (preg_match('/Pago \$([\d.,]+)/', $detalle->descripcion, $m)) {
        $montoPagado = \App\Support\FormatoImporte::desdeArgentino($m[1]);
    }
    if ($detalle->base_cuota !== null) $montoPagado = \App\Support\FormatoImporte::pesos((float)$detalle->base_cuota);
@endphp
<div class="alumno-card" data-liquidacion-detalle="{{ $detalle->id }}">
    <div class="alumno-card-header">
        <span class="alumno-dot" style="background:var(--color-success);"></span>
        <h3 class="alumno-nombre">{{ $nombreHdr }}</h3>
    </div>
    <div class="alumno-info">
        @if($detalle->periodo_cuota)
        <div class="info-item"><span class="info-label">Cuota:</span><span class="info-value">{{ substr($detalle->periodo_cuota, 5, 2) }}/{{ substr($detalle->periodo_cuota, 0, 4) }}</span></div>
        <div class="info-item"><span class="info-label">Origen:</span><span class="info-value">{{ match($detalle->origen_cuota) { 'atrasado' => 'Atrasada', 'adelantado' => 'Adelantada', default => 'Del mes' } }}</span></div>
        <div class="info-item"><span class="info-label">Cobrado:</span><span class="info-value">{{ $detalle->fecha_cobro?->format('d/m/Y') }}</span></div>
        @endif
        <div class="info-item">
            <span class="info-label">Grupo:</span>
            <span class="info-value">{{ $grupoNom }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Monto pagado:</span>
            <span class="info-value ds-money-value"><span class="ds-money ds-money--summary">{{ $montoPagado }}</span></span>
        </div>
        <div class="info-item" style="margin-left:auto;">
            <span class="info-label">Comisión:</span>
            <span class="info-value ds-money-value" style="font-weight:700; color:var(--color-success);">
                <span class="ds-money ds-money--row">{{ \App\Support\FormatoImporte::pesos((float)$detalle->monto) }}</span>
            </span>
        </div>
    </div>
    @if($detalle->origen_cuota === 'atrasado')
        <p class="text-xs mb-2" style="color:var(--color-text-muted);">
            Decisión: <strong data-decision-estado>{{ match($detalle->decision_atrasada) { 'APROBADA' => 'Validada', 'RECHAZADA' => 'Rechazada', default => 'Pendiente' } }}</strong>
            <span data-decision-autor>@if($detalle->decision_at) · {{ $detalle->decisionAdmin?->name ?? 'Administración' }} · {{ $detalle->decision_at->format('d/m/Y H:i') }} @endif</span>
        </p>
        @if($estaAbierta && !$esPagada)
        <div class="alumno-actions acciones-celular-derecha" style="border-top:1px solid var(--color-border); padding-top:0.6rem; justify-content:flex-end; gap:0.5rem;">
            @foreach(['RECHAZADA' => 'Rechazar', 'APROBADA' => 'Validar'] as $decision => $verbo)
                <form data-decision-atrasada method="POST" action="{{ route('web.liquidaciones.decidir-atrasada', [$liquidacion->id, $detalle->id]) }}" style="order:2;">
                    @csrf
                    <input type="hidden" name="decision" value="{{ $decision }}">
                    <input type="hidden" name="decision_esperada" value="{{ $detalle->decision_atrasada ?? 'PENDIENTE' }}">
                    <x-ds.button type="submit" :variant="$decision === 'APROBADA' ? 'primary' : 'danger'" :disabled="$detalle->decision_atrasada === $decision">{{ $verbo }}</x-ds.button>
                </form>
            @endforeach
        </div>
        @endif
    @endif
</div>
@empty
    <div class="empty-state">
        <p style="font-size:0.82rem; color:var(--color-text-muted);">Sin detalles registrados.</p>
    </div>
@endforelse

@endif

{{-- Total al pie --}}
@if($liquidacion->detalles->isNotEmpty())
<div class="liquidacion-total" style="display:flex; justify-content:flex-end; align-items:center; gap:8px;
            padding:14px 20px; margin-bottom:1rem;
            border-top:2px solid var(--color-border);
            background:color-mix(in srgb, var(--color-btn-primary) 4%, var(--color-surface));
            border-radius:var(--radius-card);">
    <span style="font-size:0.82rem; font-weight:600; color:var(--color-text-muted); text-transform:uppercase;
                 letter-spacing:0.05em;">
        {{ $liquidacion->tipo === 'COMISION' ? 'Total confirmado' : 'Cálculo de los detalles' }}
    </span>
    <span style="font-size:1.5rem; font-weight:800; color:var(--color-btn-primary);" class="ds-money-value">
        <span data-liquidacion-importe="confirmado" class="ds-money ds-money--total">{{ \App\Support\FormatoImporte::pesos((float)($liquidacion->tipo === 'COMISION' ? $liquidacion->total_confirmado : $totalSum)) }}</span>
    </span>
</div>
@endif

{{-- ── SECCIÓN C — Pagar (solo si cerrada y no pagada) ───────────────────── --}}
@if($esCerrada && !$esPagada)
<div class="filtros-card">
    <p style="font-size:0.82rem; font-weight:700; color:var(--color-text); margin:0 0 12px;">
        Registrar pago de liquidación
    </p>

    {{-- B10: a dónde transferirle, a la vista en el momento de pagar. --}}
    <p style="font-size:0.82rem; color:var(--color-text-muted); margin:0 0 12px;">
        @if($liquidacion->profesor?->cbu_alias)
            CBU o alias de {{ $liquidacion->profesor->nombre }}:
            <strong style="color:var(--color-text); overflow-wrap:anywhere;">{{ $liquidacion->profesor->cbu_alias }}</strong>
        @else
            {{ $liquidacion->profesor?->nombre }} no tiene CBU ni alias cargado.
            <a href="{{ route('web.profesores.edit', $liquidacion->profesor_id) }}" style="color:var(--color-brand); font-weight:600;">Editar</a>
        @endif
    </p>

    <form method="POST" action="{{ route('web.liquidaciones.pagar', $liquidacion->id) }}">
        @csrf
        <input type="hidden" name="monto_esperado" value="{{ $liquidacion->monto_a_pagar }}">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <label for="tipo_caja_id"
                       style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Tipo de caja <span class="form-required">*</span>
                </label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-3 py-2.5 text-sm wings-input">
                    <option value="">— Seleccioná —</option>
                    @foreach($tiposCaja as $tc)
                        @php
                            $saldo    = (float) ($saldosPorTipoCaja[$tc->id] ?? 0);
                            $saldoFmt = \App\Support\FormatoImporte::pesos($saldo);
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
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Fecha de pago <span class="form-required">*</span>
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
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Observaciones
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

        @if(session('aviso_fecha_vieja'))
            <input type="hidden" name="confirmar_fecha_vieja" value="1">
            <div class="mt-4 p-4" style="border: 1px solid var(--color-warning); background: color-mix(in srgb, var(--color-warning) 10%, transparent); border-radius: var(--radius-card);">
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <svg style="width:20px; height:20px; color:var(--color-warning); flex-shrink:0; margin-top:2px;" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p style="font-size:0.875rem; font-weight:600; color:var(--color-text); margin-bottom:4px;">
                            Atención: fecha de un mes anterior
                        </p>
                        <p style="font-size:0.8125rem; color:var(--color-text-muted); line-height:1.4;">
                            {{ session('aviso_fecha_vieja') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:16px; padding-top:12px;
                    border-top:1px solid var(--color-border);">
            <x-ds.button variant="primary" type="submit">{{ session('aviso_fecha_vieja') ? 'Confirmar' : 'Pagar' }}</x-ds.button>
        </div>

    </form>
</div>
@endif

{{-- ── SECCIÓN D — Cancelar liquidación (solo cerrada no pagada) ────────── --}}
@if($esCerrada && !$esPagada && !$esCancelada)
<div class="filtros-card mt-3">
    <p style="font-size:0.82rem; font-weight:700; color:var(--color-danger); margin:0 0 8px;">
        Cancelar liquidación
    </p>
    <p style="font-size:0.75rem; color:var(--color-text-muted); margin:0 0 12px;">
        Permite volver a revisar las asistencias de las clases y generar una nueva liquidación para este período. Esta liquidación quedará registrada como cancelada.
    </p>
    <form method="POST" action="{{ route('web.liquidaciones.cancelar', $liquidacion->id) }}">
        @csrf
        <div style="margin-bottom:12px;">
            <label for="motivo_cancelacion" style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">
                <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Motivo de cancelación <span class="form-required">*</span>
            </label>
            <input type="text" id="motivo_cancelacion" name="motivo" required minlength="5" maxlength="255"
                   placeholder="Ej: Corrección de asistencias clase 15/08"
                   class="w-full px-3 py-2 text-sm wings-input">
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <x-ds.button variant="danger" type="submit"
                         data-confirmar="¿Cancelar esta liquidación cerrada para corregir asistencias?">
                Cancelar
            </x-ds.button>
        </div>
    </form>
</div>
@endif

@push('scripts')
    @vite('resources/js/liquidaciones-show.js')
@endpush

@endsection
