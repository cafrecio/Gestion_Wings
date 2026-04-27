@php
$iconAttr = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
if (isset($grupo) && $grupo->relationLoaded('planes')) {
    $planesExistentes = $grupo->planes->sortBy('clases_por_semana')->values();
} else {
    // Repoblar desde old() cuando hay error de validación en create
    $planesExistentes = collect(old('planes', []))->values()->map(fn($p) => (object) $p);
}
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Deporte: fijo en edición, seleccionable en create --}}
    <div>
        <label for="deporte_id" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Deporte @unless(isset($grupo))<span class="form-required">*</span>@endunless
        </label>
        @if(isset($grupo))
            <p class="w-full px-4 py-2.5 text-sm wings-input" style="opacity: 0.6; cursor: not-allowed;">{{ $grupo->deporte->nombre ?? '–' }}</p>
            <input type="hidden" name="deporte_id" value="{{ $grupo->deporte_id }}">
        @else
            <select id="deporte_id" name="deporte_id" required
                    class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                <option value="">Seleccionar...</option>
                @foreach($deportes as $deporte)
                    <option value="{{ $deporte->id }}" {{ old('deporte_id') == $deporte->id ? 'selected' : '' }}>
                        {{ $deporte->nombre }}
                    </option>
                @endforeach
            </select>
            @error('deporte_id') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        @endif
    </div>

    {{-- Nivel: fijo en edición, seleccionable en create --}}
    <div>
        <label for="nivel_id" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
            Nivel <span class="form-required">*</span>
        </label>
        @if(isset($grupo))
            <p class="w-full px-4 py-2.5 text-sm wings-input" style="opacity: 0.6; cursor: not-allowed;">{{ $grupo->nivel->nombre ?? '–' }}</p>
            <input type="hidden" name="nivel_id" value="{{ $grupo->nivel_id }}">
        @else
            <select id="nivel_id" name="nivel_id" required
                    class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                <option value="">Seleccionar nivel...</option>
                @foreach($niveles as $nivel)
                    <option value="{{ $nivel->id }}" {{ old('nivel_id') == $nivel->id ? 'selected' : '' }}>
                        {{ $nivel->nombre }}
                        @if($nivel->descripcion) — {{ $nivel->descripcion }} @endif
                    </option>
                @endforeach
            </select>
            @error('nivel_id') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        @endif
    </div>
</div>

{{-- Campo oculto con el id del grupo actual (vacío en create) --}}
<input type="hidden" id="grupo-id-actual" value="{{ $grupo->id ?? '' }}">

{{-- Error en tiempo real para combinación deporte+nivel --}}
<div id="error-deporte-nivel"
     style="display:none; color:var(--color-danger); font-size:0.75rem; margin-top:4px;">
    Ya existe un grupo con ese deporte y nivel.
</div>

{{-- Precios por frecuencia --}}
<div class="mt-5 pt-4" style="border-top: 1px solid var(--color-border);">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
        <span style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">
            Precios por frecuencia
        </span>
        <button type="button" id="btn-add-plan" class="ds-btn ds-btn--secondary">
            + Frecuencia
        </button>
    </div>

    <div id="planes-container">
        @foreach($planesExistentes as $i => $plan)
            <div class="plan-row" style="display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0; border-bottom:1px solid var(--color-border);">
                <input type="hidden" name="planes[{{ $i }}][id]" value="{{ $plan->id }}">
                <select name="planes[{{ $i }}][clases_por_semana]"
                        class="wings-input text-sm cursor-pointer" style="flex-shrink:0; width:160px; padding:0.55rem 0.75rem;">
                    @for($f = 1; $f <= 5; $f++)
                        <option value="{{ $f }}" {{ $plan->clases_por_semana == $f ? 'selected' : '' }}>
                            {{ $f === 1 ? '1 vez' : $f . ' veces' }}/semana
                        </option>
                    @endfor
                </select>
                <div class="money-input-wrap" style="flex:1;">
                    <span class="money-prefix" aria-hidden="true">$</span>
                    <input type="text"
                           name="planes[{{ $i }}][precio_mensual]"
                           value="{{ number_format((float)$plan->precio_mensual, 0, ',', '.') }}"
                           placeholder="0" inputmode="numeric" autocomplete="off"
                           data-money="true"
                           class="w-full py-2.5 text-sm wings-input money-input">
                </div>
                <button type="button" class="btn-remove-plan ds-btn ds-btn--secondary"
                        style="flex-shrink:0; min-width:0; padding:0.5rem 0.65rem; width:auto;">✕</button>
            </div>
        @endforeach
    </div>

    <p id="planes-empty-msg" class="text-xs" style="color:var(--color-text-muted); opacity:0.7; padding:0.25rem 0; {{ $planesExistentes->isNotEmpty() ? 'display:none;' : '' }}">
        Sin precios cargados aún. Usá "+ Frecuencia" para agregar.
    </p>

    @php $hayErroresPlanes = collect($errors->keys())->contains(fn($k) => str_starts_with($k, 'planes.')); @endphp
    @if($hayErroresPlanes)
        <p class="text-xs mt-2" style="color: var(--color-danger);">Revisá los precios por frecuencia: frecuencia y precio son obligatorios.</p>
    @endif

    {{-- Template para filas nuevas (JS) — usar <template> para que sus inputs NO se envíen con el form --}}
    <template id="plan-row-template">
        <div class="plan-row" style="display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0; border-bottom:1px solid var(--color-border);">
            <input type="hidden" name="planes[__IDX__][id]" value="">
            <select name="planes[__IDX__][clases_por_semana]"
                    class="wings-input text-sm cursor-pointer" style="flex-shrink:0; width:160px; padding:0.55rem 0.75rem;">
                <option value="1">1 vez/semana</option>
                <option value="2">2 veces/semana</option>
                <option value="3">3 veces/semana</option>
                <option value="4">4 veces/semana</option>
                <option value="5">5 veces/semana</option>
            </select>
            <div class="money-input-wrap" style="flex:1;">
                <span class="money-prefix" aria-hidden="true">$</span>
                <input type="text"
                       name="planes[__IDX__][precio_mensual]"
                       value="" placeholder="0" inputmode="numeric" autocomplete="off"
                       data-money="true"
                       class="w-full py-2.5 text-sm wings-input money-input">
            </div>
            <button type="button" class="btn-remove-plan ds-btn ds-btn--secondary"
                    style="flex-shrink:0; min-width:0; padding:0.5rem 0.65rem; width:auto;">✕</button>
        </div>
    </template>

</div>

<script>
(function () {
    const selectDeporte = document.getElementById('deporte_id');
    const selectNivel   = document.getElementById('nivel_id');
    const errorDiv      = document.getElementById('error-deporte-nivel');
    const btnSubmit     = document.querySelector('[type="submit"]');
    const grupoId       = document.getElementById('grupo-id-actual').value;

    if (selectDeporte && selectNivel) {
        async function verificarDisponible() {
            const deporteId = selectDeporte.value;
            const nivelId   = selectNivel.value;
            if (!deporteId || !nivelId) {
                errorDiv.style.display = 'none';
                if (btnSubmit) btnSubmit.disabled = false;
                return;
            }
            let url = '/grupos/check-disponible?deporte_id=' + deporteId + '&nivel_id=' + nivelId;
            if (grupoId) url += '&grupo_id=' + grupoId;
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

        selectDeporte.addEventListener('change', verificarDisponible);
        selectNivel.addEventListener('change', verificarDisponible);
    }
})();

(function () {
    let idx = {{ $planesExistentes->count() }};
    const container = document.getElementById('planes-container');
    const template  = document.getElementById('plan-row-template');
    const emptyMsg  = document.getElementById('planes-empty-msg');

    function updateEmptyMsg() {
        emptyMsg.style.display = container.children.length === 0 ? '' : 'none';
    }

    function setupRemoveButtons() {
        container.querySelectorAll('.btn-remove-plan').forEach(function (btn) {
            btn.onclick = null;
            btn.onclick = function () {
                btn.closest('.plan-row').remove();
                updateEmptyMsg();
            };
        });
    }

    document.getElementById('btn-add-plan').addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__IDX__', idx++);
        const div  = document.createElement('div');
        div.innerHTML = html;
        const row = div.firstElementChild;
        row.querySelectorAll('[data-money="true"]').forEach(function (inp) {
            if (window.initMoneyInput) window.initMoneyInput(inp);
        });
        container.appendChild(row);
        setupRemoveButtons();
        updateEmptyMsg();
        const sel = row.querySelector('select');
        if (sel) sel.focus();
    });

    setupRemoveButtons();
})();
</script>
