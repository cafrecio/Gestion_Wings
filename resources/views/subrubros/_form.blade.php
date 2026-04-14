@php
$iconAttr   = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

{{-- Rubro al que pertenece (read-only) --}}
<div class="mb-4 p-3" style="background: var(--color-surface-alt); border: 1px solid var(--color-border); border-radius: var(--radius-card);">
    <p class="text-xs font-medium text-wings-muted mb-0.5">Rubro</p>
    <p style="font-size:0.88rem; font-weight:600; color:var(--color-text); margin:0;">
        {{ $rubro->nombre }}
        <span style="font-size:0.7rem; font-weight:600; padding:0.15rem 0.5rem; border-radius:999px; margin-left:0.4rem;
            background: color-mix(in srgb, {{ $rubro->tipo === 'INGRESO' ? 'var(--color-success)' : 'var(--color-danger)' }} 15%, transparent);
            color: {{ $rubro->tipo === 'INGRESO' ? 'var(--color-success)' : 'var(--color-danger)' }};">
            {{ $rubro->tipo }}
        </span>
    </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Nombre --}}
    <div>
        <label for="nombre" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Nombre <span class="form-required">*</span>
        </label>
        <input type="text" id="nombre" name="nombre"
               value="{{ old('nombre', $subrubro->nombre ?? '') }}"
               required autofocus
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Nombre del subrubro">
        @error('nombre') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Permitido para --}}
    <div>
        <label for="permitido_para" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Permitido para
        </label>
        <select id="permitido_para" name="permitido_para"
                class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
            @php $permActual = old('permitido_para', $subrubro->permitido_para ?? ''); @endphp
            <option value="" {{ $permActual === '' ? 'selected' : '' }}>Todos los roles</option>
            <option value="ADMIN"     {{ $permActual === 'ADMIN'     ? 'selected' : '' }}>Solo Admin</option>
            <option value="OPERATIVO" {{ $permActual === 'OPERATIVO' ? 'selected' : '' }}>Solo Operativo</option>
        </select>
        @error('permitido_para') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

</div>

{{-- Afecta caja --}}
<div class="mt-4">
    <input type="hidden" name="afecta_caja" value="0">
    <x-ds.toggle
        name="afecta_caja"
        id="afecta_caja"
        :checked="(bool) old('afecta_caja', $subrubro->afecta_caja ?? true)"
        labelOn="Afecta caja"
        labelOff="No afecta caja"
    />
    @error('afecta_caja') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
</div>
