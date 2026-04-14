@php
$iconAttr   = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Nombre --}}
    <div>
        <label for="nombre" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Nombre <span class="form-required">*</span>
        </label>
        <input type="text" id="nombre" name="nombre"
               value="{{ old('nombre', $rubro->nombre ?? '') }}"
               required autofocus
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Nombre del rubro">
        @error('nombre') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Tipo --}}
    <div>
        <label for="tipo" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
            Tipo <span class="form-required">*</span>
        </label>
        <select id="tipo" name="tipo" required class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
            <option value="">Seleccionar...</option>
            <option value="INGRESO" {{ old('tipo', $rubro->tipo ?? '') === 'INGRESO' ? 'selected' : '' }}>INGRESO</option>
            <option value="EGRESO"  {{ old('tipo', $rubro->tipo ?? '') === 'EGRESO'  ? 'selected' : '' }}>EGRESO</option>
        </select>
        @error('tipo') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

</div>

{{-- Observación --}}
<div class="mt-4">
    <label for="observacion" class="{{ $labelClass }}">
        <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
        Observación
    </label>
    <textarea id="observacion" name="observacion" rows="3"
              class="w-full px-4 py-2.5 text-sm wings-input"
              placeholder="Observación opcional...">{{ old('observacion', $rubro->observacion ?? '') }}</textarea>
    @error('observacion') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
</div>
