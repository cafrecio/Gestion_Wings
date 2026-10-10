@extends('layouts.ds-app')

@section('title', 'Cobrar – ' . $alumno->apellido . ', ' . $alumno->nombre)
@section('module-title', 'Cobrar: ' . $alumno->apellido . ', ' . $alumno->nombre)

@section('content')
@php
    $cargoInscripcion = app(\App\Services\InscripcionService::class)->cargo($alumno->dni);
    $saldoInscripcion = $cargoInscripcion?->saldo_pendiente ?? 0;
@endphp


@php
    $dep   = mb_strtolower($alumno->deporte->nombre ?? '');
    $dep   = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $sport = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
    $iconAttr = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
    $labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

{{-- Info del alumno --}}
<div class="filtros-card mb-4" style="border-left: 4px solid var(--color-sport-{{ $sport }});">
    {{-- En el celular el DNI no se muestra: no aporta al cobrar y saca lugar (Carlos, 10/10/2026). --}}
    <div class="grid grid-cols-3 sm:grid-cols-4 gap-4">
        <div class="hidden sm:block">
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">DNI</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumno->dni ?: '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Deporte</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumno->deporte->nombre ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Grupo</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumno->grupo->nombre_completo ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Total pendiente</p>
            <p style="font-size:0.85rem; font-weight:700; color:var(--color-danger);">
                {{-- Un mes que todavía no empezó no es deuda (T18). --}}
                ${{ number_format($alumno->deudaCuotas->where('periodo', '<=', now()->format('Y-m'))->sum('saldo_pendiente') + $saldoInscripcion, 2, ',', '.') }}
            </p>
        </div>
    </div>
</div>

{{-- El formulario abre acá, antes del selector de plan, a propósito: un form solo
     envía los campos que tiene adentro y `nuevo_plan_id` quedaba afuera. La etiqueta
     no dibuja nada, así que la pantalla se ve igual. --}}
<form method="POST" action="{{ route('web.caja.pagar', $alumno->id) }}" id="cobrar-form" data-periodo-descuento="{{ $periodoConDescuento }}" data-periodo-actual="{{ now()->format('Y-m') }}" data-inscripcion="{{ $saldoInscripcion }}">
@csrf

{{-- Selector de plan (solo si el grupo tiene más de uno) --}}
@if($planesDisponibles->count() > 1)
<div class="filtros-card mb-4">
    <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:0.75rem;">
        Plan — frecuencia semanal
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" id="plan-options">
        @foreach($planesDisponibles as $plan)
        @php $veces = $plan->clases_por_semana === 1 ? '1 vez/semana' : $plan->clases_por_semana . ' veces/semana'; @endphp
        <label style="
            display:flex; align-items:center; gap:8px; padding:8px 14px;
            border:1px solid var(--color-border); border-radius:8px;
            background:var(--color-surface); cursor:pointer;
            transition:border-color 0.15s, background 0.15s;
        " class="plan-label w-full justify-between {{ $alumno->planActivo?->plan_id == $plan->id ? 'plan-label--active' : '' }}">
            <div style="display:flex; align-items:center; gap:8px;">
                <input type="radio"
                       name="nuevo_plan_id"
                       value="{{ $plan->id }}"
                       data-precio="{{ (int) $plan->precio_mensual }}"
                       data-precio-mes="{{ $plan->precio_mes }}"
                       {{ $alumno->planActivo?->plan_id == $plan->id ? 'checked' : '' }}
                       style="accent-color:var(--color-btn-primary); width:15px; height:15px; flex-shrink:0;">
                <span style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $veces }}</span>
            </div>
            <span style="font-size:0.78rem; color:var(--color-text-muted);">${{ number_format($plan->precio_mensual, 0, ',', '.') }}/mes</span>
        </label>
        @endforeach
    </div>
    <p style="font-size:0.72rem; color:var(--color-text-muted); margin-top:8px;">
        Las subidas aplican al mes en curso. Las bajas aplican al mes siguiente si ya hubo asistencia este mes.
    </p>
</div>
@endif

@if($reglaPrimerPago)
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-warning);">
    <p style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-warning); margin-bottom:4px;">
        {{ $motivoPrimerPago === 'reingreso' ? 'Reingreso' : 'Primer pago' }} — descuento automático
    </p>
    <p style="font-size:0.85rem; color:var(--color-text);">
        Se aplica <strong>{{ number_format($reglaPrimerPago->porcentaje, 0) }}%</strong>
        del valor de la cuota ({{ $reglaPrimerPago->nombre }}, días {{ $reglaPrimerPago->dia_desde }}–{{ $reglaPrimerPago->dia_hasta }}).
        El monto se ajusta automáticamente al confirmar.
    </p>
</div>
@endif

    {{-- Cuotas pendientes --}}
    <div class="filtros-card mb-4">
        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:0.75rem;">
            Cuotas pendientes — seleccioná las que querés cobrar
        </p>

        @if($alumno->deudaCuotas->isEmpty())
            <p style="font-size:0.85rem; color:var(--color-text-muted);">Sin cuotas pendientes.</p>
        @else
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($alumno->deudaCuotas as $deuda)
                    @php
                        [$year, $month] = explode('-', $deuda->periodo);
                        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        $periodoLabel = $meses[(int)$month] . ' ' . $year;
                        $esAdelantado = ($deuda->periodo > now()->format('Y-m'));
                    @endphp
                    <div class="cuota-row" style="
                        display:flex; align-items:center; gap:12px;
                        padding:10px 14px;
                        border:1px solid var(--color-border);
                        border-radius:8px; background:var(--color-surface);
                        transition:background 0.15s;
                    ">
                        <input type="checkbox"
                               name="periodos[]"
                               value="{{ $deuda->periodo }}"
                               class="cuota-check"
                               data-saldo="{{ $deuda->saldo_pendiente }}"
                               data-pagado="{{ (float) $deuda->monto_pagado }}"
                               data-periodo="{{ $deuda->periodo }}"
                               style="width:16px; height:16px; cursor:pointer; flex-shrink:0; accent-color:var(--color-btn-primary);">
                        <div style="flex:1; cursor:default;">
                            <span style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $periodoLabel }}</span>
                            @if($esAdelantado)
                                <span style="font-size:0.7rem; font-weight:600; padding:2px 8px; border-radius:999px; margin-left:8px;
                                             background:color-mix(in srgb, var(--color-info) 15%, transparent);
                                             color:var(--color-info);">Adelantado</span>
                            @endif
                            @if((float)$deuda->monto_pagado > 0)
                                <span style="font-size:0.72rem; color:var(--color-text-muted); margin-left:8px;">
                                    Orig: ${{ number_format($deuda->monto_original, 0, ',', '.') }} · Pagado: ${{ number_format($deuda->monto_pagado, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="text"
                                   name="montos_cuota[{{ $deuda->periodo }}]"
                                   class="monto-cuota wings-input"
                                   data-periodo="{{ $deuda->periodo }}"
                                   data-saldo="{{ $deuda->saldo_pendiente }}"
                                   value="{{ number_format((float)$deuda->saldo_pendiente, 0, ',', '.') }}"
                                   disabled
                                   data-money="true"
                                   style="width:110px; padding:4px 10px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-danger);">
                        </div>
                    </div>
                @endforeach
            </div>
            @error('periodos')
                <p style="font-size:0.75rem; color:var(--color-danger); margin-top:8px;">{{ $message }}</p>
            @enderror
        @endif
    </div>

    {{-- Cobro adelantado: aparte de la deuda, con mes e importe a elección (T18) --}}
    @if($mesesAdelantables->isNotEmpty())
    <div class="filtros-card mb-4" id="adelanto-bloque">
        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:0.75rem;">
            Cobro adelantado — un mes que todavía no empezó
        </p>
        <div id="adelantos-lista" style="display:flex; flex-direction:column; gap:8px;"></div>
        <div style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-top:8px;">
            <div style="flex:1; min-width:180px;">
                <label for="adelanto-periodo" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Mes
                </label>
                <select id="adelanto-periodo" class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($mesesAdelantables as $mes)
                        <option value="{{ $mes['periodo'] }}" data-precio="{{ $mes['precio'] }}">{{ $mes['etiqueta'] }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:150px;">
                <label for="adelanto-monto" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                    Importe
                </label>
                <input type="text" id="adelanto-monto" inputmode="numeric" class="w-full px-4 py-2.5 text-sm wings-input" style="text-align:right;" placeholder="0">
            </div>
            <button type="button" id="adelanto-agregar" class="ds-btn" disabled
                    style="background:var(--color-btn-secondary); color:var(--color-surface); opacity:0.4; cursor:not-allowed;">Agregar</button>
        </div>
        <p style="font-size:0.72rem; color:var(--color-text-muted); margin-top:8px;">
            El importe sugerido es el precio de hoy. Si ya se anunció un aumento, cambialo. Lo que se cobre queda fijo para ese mes.
        </p>
    </div>
    @endif

    {{-- Medio de pago + fecha + observaciones --}}
    <div class="filtros-card mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="tipo_caja_id" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Medio de pago <span class="form-required">*</span>
                </label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($tiposCaja as $tipo)
                        <option value="{{ $tipo->id }}" {{ old('tipo_caja_id') == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->abreviatura ? $tipo->abreviatura . ' — ' : '' }}{{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_caja_id')
                    <p style="font-size:0.75rem; color:var(--color-danger); margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="fecha_pago" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Fecha del pago
                </label>
                <input type="date" id="fecha_pago" name="fecha_pago"
                       value="{{ old('fecha_pago', now()->format('Y-m-d')) }}"
                       max="{{ now()->format('Y-m-d') }}"
                       class="w-full px-4 py-2.5 text-sm wings-input">
            </div>
            <div>
                <label for="observaciones" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    Observaciones
                </label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ old('observaciones') }}"
                       maxlength="500"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Opcional">
            </div>
        </div>

        <div id="resumen-total" class="mt-4 pt-4" style="border-top:1px solid var(--color-border); display:none;">
            <div style="display:flex; justify-content:flex-end; align-items:center; gap:12px;">
                <span style="font-size:0.85rem; color:var(--color-text-muted);">Total a cobrar:</span>
                <span id="total-label" style="font-size:1.1rem; font-weight:700; color:var(--color-btn-primary);">$0</span>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    @if($saldoInscripcion > 0)
    <div class="alumno-card mb-3">
        <div class="alumno-card-header"><h3 class="alumno-nombre">Inscripción al club</h3></div>
        <p>Pendiente por única vez: ${{ number_format($saldoInscripcion, 2, ',', '.') }}. Se cubre antes que la cuota, en cualquiera de los deportes.</p>
        <label for="monto-entregado">Importe que entrega (dejar vacío para cobrar el total)</label>
        <input type="number" id="monto-entregado" name="monto_entregado" min="0.01" step="0.01" class="wings-input">
        <p id="inscripcion-distribucion" aria-live="polite"></p>
    </div>
    @endif

    <div class="filtros-actions" style="justify-content:flex-end;">
        <x-ds.button variant="secondary" href="{{ route('web.caja.cobrar-cuota') }}">Cancelar</x-ds.button>
        <button type="submit" id="btn-cobrar" disabled
                class="ds-btn"
                style="background:var(--color-btn-primary); color:var(--color-surface); opacity:0.4; cursor:not-allowed;">
            Cobrar
        </button>
    </div>

</form>

{{-- Aviso por cobro con deudas anteriores pendientes --}}
<div id="modal-deuda-anterior" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--color-surface); border-radius:var(--radius-card); padding:1.5rem; max-width:440px; width:100%; margin:1rem;">
        <p style="font-size:0.9rem; font-weight:600; color:var(--color-text); margin-bottom:0.25rem;">Deudas anteriores pendientes</p>
        <p id="mensaje-deuda-anterior" style="font-size:0.78rem; color:var(--color-text-muted); margin-bottom:1rem;"></p>
        <form id="form-confirmar-deuda-anterior">
            <textarea name="motivo" required maxlength="500" rows="3"
                      placeholder="Motivo del cobro..."
                      class="w-full px-4 py-2.5 text-sm wings-input"
                      style="display:block; width:100%; margin-bottom:1rem; resize:vertical;"></textarea>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" id="cerrar-deuda-anterior"
                        class="ds-btn" style="background:var(--color-btn-secondary); color:var(--color-surface);">Cerrar</button>
                <button type="submit"
                        class="ds-btn" style="background:var(--color-btn-primary); color:var(--color-surface);">Cobrar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
@vite('resources/js/cobrar.js')
@endpush
