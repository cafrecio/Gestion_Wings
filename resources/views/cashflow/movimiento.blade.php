@extends('layouts.ds-app')

@section('title', isset($movimiento) ? 'Editar movimiento – Cashflow – Wings' : 'Nuevo movimiento – Cashflow – Wings')
@section('module-title', isset($movimiento) ? 'Editar movimiento' : 'Nuevo movimiento')
@section('ayuda-slug', 'registrar-movimiento-cashflow')

@section('content')

@php
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
$movimiento = $movimiento ?? null;

$rubroActual    = old('rubro_id', $movimiento?->subrubro?->rubro_id ?? '');
$subrubroActual = old('subrubro_id', $movimiento?->subrubro_id ?? '');
$tipoCajaActual = old('tipo_caja_id', $movimiento?->tipo_caja_id ?? '');
$montoActual    = old('monto', $movimiento ? ltrim($movimiento->monto, '-') : '');
$fechaActual    = old('fecha', $movimiento?->fecha?->toDateString() ?? today()->format('Y-m-d'));
$obsActual      = old('observaciones', $movimiento?->observaciones ?? '');

$rubroTipoActual = '';
if ($rubroActual) {
    $rubroObj = $rubros->firstWhere('id', (int)$rubroActual);
    $rubroTipoActual = $rubroObj?->tipo ?? '';
}
@endphp

<form method="POST" action="{{ $movimiento ? route('web.cashflow.movimientos.update', $movimiento->id) : route('web.cashflow.movimiento.store') }}" id="mov-form" data-rubro-inicial="{{ $rubroActual }}" data-subrubro-inicial="{{ $subrubroActual }}">
    @csrf
    @if($movimiento)
        @method('PUT')
        <input type="hidden" name="clave" value="{{ old('clave', $clave) }}">
        <input type="hidden" name="version" value="{{ old('version', $movimiento->version_correccion) }}">
        <p class="text-sm mb-3" style="color:var(--color-text-muted);">La corrección actualiza el reporte del período del movimiento y conserva su historial.</p>
        @foreach(['version', 'clave'] as $campo)
            @error($campo)<p class="text-sm mb-3" style="color:var(--color-danger);">{{ $message }}</p>@enderror
        @endforeach
    @endif

    {{-- ── Tipo I/E ─────────────────────────────────────────────────────── --}}
    <div class="filtros-card mb-4" id="tipo-card" style="transition: border-left 0.2s;">
        <p class="{{ $labelClass }}" style="margin-bottom:0.75rem;">
            <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Tipo de movimiento <span class="form-required">*</span>
        </p>
        <div style="display:flex; gap:12px; margin-bottom:1.25rem;">
            <label style="flex:1; cursor:pointer;">
                <input type="radio" name="tipo_ie" value="INGRESO" id="tipo-ingreso"
                       {{ ($rubroTipoActual === 'INGRESO' || $rubroTipoActual === '') ? 'checked' : '' }}
                       style="display:none;">
                <div id="btn-ingreso"
                     style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid; border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700; transition:all 0.15s;">
                    INGRESO
                </div>
            </label>
            <label style="flex:1; cursor:pointer;">
                <input type="radio" name="tipo_ie" value="EGRESO" id="tipo-egreso"
                       {{ $rubroTipoActual === 'EGRESO' ? 'checked' : '' }}
                       style="display:none;">
                <div id="btn-egreso"
                     style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid; border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700; transition:all 0.15s;">
                    EGRESO
                </div>
            </label>
        </div>

        {{-- ── Campos del form ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Fecha --}}
            <div>
                <label for="fecha" class="{{ $labelClass }}">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Fecha <span class="form-required">*</span></label>
                <input type="date" id="fecha" name="fecha"
                       value="{{ $fechaActual }}" required
                       max="{{ today()->format('Y-m-d') }}"
                       class="w-full px-4 py-2.5 text-sm wings-input">
                @error('fecha') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Tipo de caja --}}
            <div>
                <label for="tipo_caja_id" class="{{ $labelClass }}">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Medio de pago <span class="form-required">*</span></label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($tiposCaja as $tipo)
                        <option value="{{ $tipo->id }}" {{ $tipoCajaActual == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->abreviatura ? $tipo->abreviatura . ' — ' : '' }}{{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_caja_id') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Rubro --}}
            <div>
                <label for="rubro_id" class="{{ $labelClass }}">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Rubro <span class="form-required">*</span></label>
                <select id="rubro_id" name="rubro_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($rubros as $rubro)
                        <option value="{{ $rubro->id }}"
                                data-tipo="{{ $rubro->tipo }}"
                                {{ $rubroActual == $rubro->id ? 'selected' : '' }}>
                            {{ $rubro->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Subrubro --}}
            <div>
                <label for="subrubro_id" class="{{ $labelClass }}">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Subrubro <span class="form-required">*</span></label>
                <select id="subrubro_id" name="subrubro_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar rubro primero...</option>
                    @foreach($rubros as $rubro)
                        @foreach($rubro->subrubros as $sub)
                            <option value="{{ $sub->id }}"
                                    data-rubro="{{ $rubro->id }}"
                                    {{ $subrubroActual == $sub->id ? 'selected' : '' }}>
                                {{ $sub->nombre }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('subrubro_id') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Monto --}}
            <div>
                <label for="monto" class="{{ $labelClass }}">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Monto <span class="form-required">*</span></label>
                <x-ds.money-input id="monto" name="monto" :value="$montoActual"
                                  min="0.01" :max="$movimiento ? '9999999999.99' : null" :prefix="false" required />
                @error('monto') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Observaciones --}}
            <div>
                <label for="observaciones" class="{{ $labelClass }}">
                    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Observaciones <span class="form-required">*</span></label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ $obsActual }}" required maxlength="500"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Descripción del movimiento">
                @error('observaciones') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            @if($movimiento)
            <div class="md:col-span-2">
                <label for="motivo" class="{{ $labelClass }}"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2z"/></svg>Motivo (opcional)</label>
                <input id="motivo" name="motivo" value="{{ old('motivo') }}" maxlength="500" class="w-full px-4 py-2.5 text-sm wings-input">
                @error('motivo')<p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>@enderror
            </div>
            @endif
        </div>
    </div>

    @if(session('aviso_fecha_vieja'))
        <input type="hidden" name="confirmar_fecha_vieja" value="1">
        <div class="mb-4 p-4" style="border: 1px solid var(--color-warning); background: color-mix(in srgb, var(--color-warning) 10%, transparent); border-radius: var(--radius-card);">
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

    <div class="filtros-actions" style="justify-content:flex-end;">
        <x-ds.button variant="secondary" href="{{ $movimiento ? route('web.cashflow.index') : route('web.caja.index') }}">Cancelar</x-ds.button>
        <x-ds.button variant="primary" type="submit">{{ $movimiento ? 'Guardar' : (session('aviso_fecha_vieja') ? 'Confirmar' : 'Registrar') }}</x-ds.button>
    </div>

</form>
@if($movimiento && $historial->isNotEmpty())
    @include('cashflow._historial')
@endif

@endsection

@push('scripts')
    @vite('resources/js/cashflow-movimiento.js')
@endpush
