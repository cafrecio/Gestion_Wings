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

    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem; padding-bottom:0.75rem; margin-bottom:0.5rem; border-bottom:1px solid var(--color-border);">
        <div style="flex:1 1 20rem; min-width:0;">
            <div style="margin-bottom:0.75rem;">
                <span style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">
                    Precios por frecuencia
                </span>
            </div>

            <p class="text-xs" style="color:var(--color-text-muted);">
                Al guardar un precio: altas nuevas desde {{ today()->format('d/m/Y') }};
                alumnos existentes desde {{ today()->addMonthNoOverflow()->startOfMonth()->format('d/m/Y') }}. Las cuotas generadas se conservan.
            </p>
        </div>

        <div class="filtros-actions" style="margin-left:auto; justify-content:flex-end;">
            <button type="button" id="btn-add-plan" class="ds-btn ds-btn--secondary">
                Nuevo
            </button>
        </div>
    </div>

    <div id="planes-container" data-initial-idx="{{ $planesExistentes->count() }}">
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
                <x-ds.money-input name="planes[{{ $i }}][precio_mensual]"
                                  :value="old('planes.'.$i.'.precio_mensual', $plan->precio_mensual)"
                                  min="0.01" wrapper-class="flex-1" required />
                <button type="button" class="btn-remove-plan ds-btn ds-btn--secondary"
                        style="flex-shrink:0; min-width:0; padding:0.5rem 0.65rem; width:auto;">✕</button>
            </div>
        @endforeach
    </div>

    <p id="planes-empty-msg" class="text-xs" style="color:var(--color-text-muted); opacity:0.7; padding:0.25rem 0; {{ $planesExistentes->isNotEmpty() ? 'display:none;' : '' }}">
        Sin precios cargados aún. Usá "Nuevo" para agregar.
    </p>

    @php $campoPlanConError = collect($errors->keys())->first(fn($k) => str_starts_with($k, 'planes.')); @endphp
    @if($campoPlanConError)
        <p id="planes-error" class="text-xs mt-2" style="color: var(--color-danger);">{{ $errors->first($campoPlanConError) }}</p>
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
            <x-ds.money-input name="planes[__IDX__][precio_mensual]"
                              min="0.01" wrapper-class="flex-1" required />
            <button type="button" class="btn-remove-plan ds-btn ds-btn--secondary"
                    style="flex-shrink:0; min-width:0; padding:0.5rem 0.65rem; width:auto;">✕</button>
        </div>
    </template>

</div>

@push('scripts')
    @vite('resources/js/grupos.js')
@endpush
