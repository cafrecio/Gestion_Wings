{{-- Datos del deporte como JSON para el JS --}}
<script>
const deportesData = @json($deportes->keyBy('id'));
</script>

@php
$iconAttr   = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Nombre --}}
    <div>
        <label for="nombre" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Nombre <span class="form-required">*</span>
        </label>
        <input type="text" id="nombre" name="nombre"
               value="{{ old('nombre', $profesor->nombre ?? '') }}"
               required autofocus
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Nombre">
        @error('nombre') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Apellido --}}
    <div>
        <label for="apellido" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Apellido <span class="form-required">*</span>
        </label>
        <input type="text" id="apellido" name="apellido"
               value="{{ old('apellido', $profesor->apellido ?? '') }}"
               required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Apellido">
        @error('apellido') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- DNI --}}
    <div>
        <label for="dni" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
            DNI <span class="form-required">*</span>
        </label>
        <input type="text" id="dni" name="dni"
               value="{{ old('dni', $profesor->dni ?? '') }}"
               required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="12345678">
        @error('dni') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Fecha de nacimiento --}}
    <div>
        <label for="fecha_nacimiento" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Fecha de nacimiento <span class="form-required">*</span>
        </label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
               value="{{ old('fecha_nacimiento', isset($profesor->fecha_nacimiento) ? $profesor->fecha_nacimiento->format('Y-m-d') : '') }}"
               required
               class="w-full px-4 py-2.5 text-sm wings-input">
        @error('fecha_nacimiento') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Dirección --}}
    <div>
        <label for="direccion" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Dirección <span class="form-required">*</span>
        </label>
        <input type="text" id="direccion" name="direccion"
               value="{{ old('direccion', $profesor->direccion ?? '') }}"
               required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Calle 123">
        @error('direccion') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Localidad --}}
    <div>
        <label for="localidad" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Localidad <span class="form-required">*</span>
        </label>
        <input type="text" id="localidad" name="localidad"
               value="{{ old('localidad', $profesor->localidad ?? '') }}"
               required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Buenos Aires">
        @error('localidad') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Teléfono --}}
    <div>
        <label for="telefono" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            Teléfono <span class="form-required">*</span>
        </label>
        <input type="text" id="telefono" name="telefono"
               value="{{ old('telefono', $profesor->telefono ?? '') }}"
               required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="11-1234-5678">
        @error('telefono') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Email
        </label>
        <input type="email" id="email" name="email"
               value="{{ old('email', $profesor->email ?? '') }}"
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="email@ejemplo.com">
        @error('email') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Deporte --}}
    <div>
        <label for="deporte_id" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Deporte <span class="form-required">*</span>
        </label>
        <select id="deporte_id" name="deporte_id" required
                class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
            <option value="">Seleccionar...</option>
            @foreach($deportes as $deporte)
                <option value="{{ $deporte->id }}"
                        data-tipo="{{ $deporte->tipo_liquidacion }}"
                        {{ old('deporte_id', $profesor->deporte_id ?? '') == $deporte->id ? 'selected' : '' }}>
                    {{ $deporte->nombre }}
                </option>
            @endforeach
        </select>
        @error('deporte_id') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Valor por hora (se muestra si deporte liquida por HORA) --}}
    <div id="field-valor-hora" style="display:none;">
        <label for="valor_hora" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Valor por hora <span class="form-required">*</span>
        </label>
        <x-ds.money-input id="valor_hora" name="valor_hora"
            :value="old('valor_hora', $profesor->valor_hora ?? '')" />
        @error('valor_hora') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Porcentaje comisión (se muestra si deporte liquida por COMISION) --}}
    <div id="field-porcentaje-comision" style="display:none;">
        <label for="porcentaje_comision" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Porcentaje de comisión (%) <span class="form-required">*</span>
        </label>
        <input type="number" id="porcentaje_comision" name="porcentaje_comision"
               value="{{ old('porcentaje_comision', $profesor->porcentaje_comision ?? '') }}"
               min="0" max="100" step="0.01"
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="0 – 100">
        @error('porcentaje_comision') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

</div>

<script>
(function () {
    const deporteSelect      = document.getElementById('deporte_id');
    const fieldValorHora     = document.getElementById('field-valor-hora');
    const fieldComision      = document.getElementById('field-porcentaje-comision');
    const inputValorHora     = document.getElementById('valor_hora');
    const inputComision      = document.getElementById('porcentaje_comision');

    function actualizarCamposLiquidacion(deporteId) {
        if (!deporteId) {
            fieldValorHora.style.display = 'none';
            fieldComision.style.display  = 'none';
            return;
        }

        const deporte = deportesData[deporteId];
        if (!deporte) {
            fieldValorHora.style.display = 'none';
            fieldComision.style.display  = 'none';
            return;
        }

        if (deporte.tipo_liquidacion === 'HORA') {
            fieldValorHora.style.display = '';
            fieldComision.style.display  = 'none';
            inputComision.value          = '';
        } else if (deporte.tipo_liquidacion === 'COMISION') {
            fieldValorHora.style.display = 'none';
            fieldComision.style.display  = '';
            inputValorHora.value         = '';
        } else {
            fieldValorHora.style.display = 'none';
            fieldComision.style.display  = 'none';
        }
    }

    deporteSelect.addEventListener('change', function () {
        actualizarCamposLiquidacion(this.value);
    });

    // Inicializar al cargar la página
    actualizarCamposLiquidacion(deporteSelect.value);
})();
</script>
