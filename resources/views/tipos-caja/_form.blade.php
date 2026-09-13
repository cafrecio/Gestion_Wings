@php
$iconAttr   = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Nombre --}}
    <div>
        <label for="nombre" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h11"/></svg>
            Nombre <span class="form-required">*</span>
        </label>
        <input type="text" id="nombre" name="nombre"
               value="{{ old('nombre', $tipoCaja->nombre ?? '') }}"
               required autofocus maxlength="100"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Ej: Caja Chica"
               data-verificar-disponible="/tipos-caja/check-disponible"
               data-verificar-param="tipo_caja_id"
               data-verificar-valor="{{ $tipoCaja->id ?? '' }}"
               data-verificar-error="error-nombre-tipo-caja"
               data-verificar-error-sv="error-nombre-tipo-caja-sv">
        @error('nombre') <p id="error-nombre-tipo-caja-sv" class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        <div id="error-nombre-tipo-caja"
             style="display:none; color:var(--color-danger); font-size:0.75rem; margin-top:4px;">
            Ya existe un tipo de caja con ese nombre.
        </div>
    </div>

    {{-- Abreviatura --}}
    <div>
        <label for="abreviatura" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4"/></svg>
            Abreviatura <span class="form-required">*</span>
        </label>
        <input type="text" id="abreviatura" name="abreviatura"
               value="{{ old('abreviatura', $tipoCaja->abreviatura ?? '') }}"
               required maxlength="5"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Ej: EFT">
        @error('abreviatura') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Descripción --}}
    <div class="md:col-span-2">
        <label for="descripcion" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            Descripción
        </label>
        <input type="text" id="descripcion" name="descripcion"
               value="{{ old('descripcion', $tipoCaja->descripcion ?? '') }}"
               maxlength="255"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Ej: Efectivo para gastos menores del día">
        @error('descripcion') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Saldo inicial --}}
    <div class="md:col-span-2">
        <label for="saldo_inicial" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            Saldo inicial @if($saldoInicialEditable ?? true)<span class="form-required">*</span>@endif
        </label>
        @if($saldoInicialEditable ?? true)
        <x-ds.money-input
            id="saldo_inicial"
            name="saldo_inicial"
            :value="old('saldo_inicial', $tipoCaja->saldo_inicial ?? 0)"
            required
        />
        @error('saldo_inicial') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        @else
        <div class="money-input-wrap">
            <span class="money-prefix" aria-hidden="true">$</span>
            <input type="text" id="saldo_inicial" readonly
                   value="{{ number_format((float) $tipoCaja->saldo_inicial, 2, ',', '.') }}"
                   aria-describedby="saldo-inicial-ayuda"
                   class="w-full py-2.5 text-sm wings-input money-input">
        </div>
        <p id="saldo-inicial-ayuda" class="text-xs mt-1 text-wings-muted">Se ajusta con un movimiento, no editando el saldo inicial.</p>
        @endif
    </div>

    {{-- Permite descubierto --}}
    <div class="md:col-span-2" style="display:flex; align-items:center; gap:12px; padding-top:4px;">
        <x-ds.toggle
            id="permite_descubierto"
            name="permite_descubierto"
            :checked="old('permite_descubierto', $tipoCaja->permite_descubierto ?? false)"
            labelOn="Descubierto permitido"
            labelOff="Sin descubierto"
        />
        <span style="font-size:0.75rem; color:var(--color-text-muted);">
            Si está activado, el saldo puede quedar negativo al registrar el pago.
        </span>
    </div>

</div>


