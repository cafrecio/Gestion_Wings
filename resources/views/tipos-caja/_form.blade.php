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
               placeholder="Ej: Caja Chica">
        @error('nombre') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        <div id="error-nombre-tipo-caja"
             style="display:none; color:var(--color-danger); font-size:0.75rem; margin-top:4px;">
            Ya existe un tipo de caja con ese nombre.
        </div>
    </div>

    {{-- Descripción --}}
    <div>
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

<input type="hidden" id="tipo-caja-id-actual" value="{{ $tipoCaja->id ?? '' }}">

<script>
(function () {
    const input     = document.getElementById('nombre');
    const errorDiv  = document.getElementById('error-nombre-tipo-caja');
    const btnSubmit = document.querySelector('[type="submit"]');
    const tipoCajaId = document.getElementById('tipo-caja-id-actual').value;

    if (!input) return;

    async function verificar() {
        const nombre = input.value.trim();
        if (!nombre) {
            errorDiv.style.display = 'none';
            if (btnSubmit) btnSubmit.disabled = false;
            return;
        }
        let url = '/tipos-caja/check-disponible?nombre=' + encodeURIComponent(nombre);
        if (tipoCajaId) url += '&tipo_caja_id=' + tipoCajaId;
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!data.disponible) {
                errorDiv.style.display = 'block';
                if (btnSubmit) btnSubmit.disabled = true;
            } else {
                errorDiv.style.display = 'none';
                if (btnSubmit) btnSubmit.disabled = false;
            }
        } catch(e) {
            errorDiv.style.display = 'none';
            if (btnSubmit) btnSubmit.disabled = false;
        }
    }

    input.addEventListener('blur', verificar);
    input.addEventListener('input', function () {
        if (errorDiv.style.display !== 'none') verificar();
    });
})();
</script>
