@extends('layouts.ds-app')

@section('title', 'Nuevo movimiento – Wings')
@section('module-title', 'Nuevo movimiento')

@section('content')

@php
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';

$rubroActual    = old('rubro_id', '');
$subrubroActual = old('subrubro_id', '');
$tipoCajaActual = old('tipo_caja_id', '');

$rubroTipoActual = '';
if ($rubroActual) {
    $rubroObj = $rubros->firstWhere('id', (int)$rubroActual);
    $rubroTipoActual = $rubroObj?->tipo ?? '';
}
@endphp

<form method="POST" action="{{ route('web.caja.movimiento.store') }}" id="mov-form">
    @csrf

    {{-- ── Tipo I/E ─────────────────────────────────────────────────────── --}}
    <div class="filtros-card mb-4" id="tipo-card"
         data-rubro-actual="{{ $rubroActual }}"
         data-subrubro-actual="{{ $subrubroActual }}"
         style="transition: border-left 0.2s;">
        <p class="{{ $labelClass }}" style="margin-bottom:0.75rem;">
            <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            {{-- Tipo de caja --}}
            <div>
                <label for="tipo_caja_id" class="{{ $labelClass }}">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Medio de pago <span class="form-required">*</span>
                </label>
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
                    <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Rubro <span class="form-required">*</span>
                </label>
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
                    <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Subrubro <span class="form-required">*</span>
                </label>
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
                    <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Monto <span class="form-required">*</span>
                </label>
                <input type="text" id="monto" name="monto"
                       value="{{ old('monto') }}"
                       required data-money="true"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="0">
                @error('monto') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Observaciones --}}
            <div>
                <label for="observaciones" class="{{ $labelClass }}">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Observaciones <span class="text-xs font-normal text-wings-muted">(opcional)</span>
                </label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ old('observaciones') }}" maxlength="500"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Descripción del movimiento (opcional)">
                @error('observaciones') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

        </div>
    </div>

    <div class="filtros-actions flex items-center justify-end gap-2 w-full">
        <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Cancelar</x-ds.button>
        <x-ds.button variant="primary" type="submit">Registrar</x-ds.button>
    </div>

</form>

@endsection

@push('scripts')
@vite('resources/js/caja-movimiento.js')
@endpush
