{{-- Macro para no repetir el SVG wrapper --}}
@php
$iconAttr = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Nombre --}}
    <div>
        <label for="nombre" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Nombre <span class="form-required">*</span>
        </label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $alumno->nombre ?? '') }}" required autofocus
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Nombre">
        @error('nombre') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Apellido --}}
    <div>
        <label for="apellido" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Apellido <span class="form-required">*</span>
        </label>
        <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $alumno->apellido ?? '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Apellido">
        @error('apellido') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- DNI --}}
    <div>
        <label for="dni" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
            DNI <span class="form-required">*</span>
        </label>
        <input type="text" id="dni" name="dni" value="{{ old('dni', $alumno->dni ?? '') }}" required
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="12345678">
        @error('dni') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Fecha Nacimiento --}}
    <div>
        <label for="fecha_nacimiento" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Fecha de nacimiento <span class="form-required">*</span>
        </label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
               value="{{ old('fecha_nacimiento', isset($alumno) && $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->format('Y-m-d') : '') }}" required
               max="{{ date('Y-m-d') }}"
               class="w-full px-4 py-2.5 text-sm wings-input">
        <p id="fecha-nacimiento-error" class="text-xs mt-1" style="color: var(--color-danger); display:none;"></p>
        @error('fecha_nacimiento') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Celular --}}
    <div>
        <label for="celular" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            Celular
        </label>
        <input type="text" id="celular" name="celular" value="{{ old('celular', $alumno->celular ?? '') }}"
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="11-1234-5678">
        <label class="flex items-center gap-1.5 mt-1.5 cursor-pointer" style="font-size: 0.7rem; color: var(--color-text-muted);">
            <input type="checkbox" id="celular-mismo-tutor" class="cursor-pointer">
            Mismo que el teléfono del tutor
        </label>
        @error('celular') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Email
        </label>
        <input type="text" id="email" name="email" value="{{ old('email', $alumno->email ?? '') }}"
               class="w-full px-4 py-2.5 text-sm wings-input" placeholder="email@ejemplo.com">
        <p id="email-error" class="text-xs mt-1" style="color: var(--color-danger); display:none;"></p>
        @error('email') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Deporte: fijo en edición (DNI:Deporte es 1:1), seleccionable en create --}}
    <div>
        <label for="deporte_id" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Deporte @unless(isset($alumno))<span class="form-required">*</span>@endunless
        </label>
        @if(isset($alumno))
            <p class="w-full px-4 py-2.5 text-sm wings-input" style="opacity: 0.6; cursor: not-allowed;">{{ $alumno->deporte->nombre ?? '–' }}</p>
            <input type="hidden" name="deporte_id" value="{{ $alumno->deporte_id }}">
        @else
            <select id="deporte_id" name="deporte_id" required class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
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

    {{-- Grupo --}}
    <div>
        <label for="grupo_id" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Grupo <span class="form-required">*</span>
        </label>
        <select id="grupo_id" name="grupo_id" required class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
            <option value="">Seleccionar...</option>
            @foreach($grupos as $grupo)
                <option value="{{ $grupo->id }}" data-deporte="{{ $grupo->deporte_id }}" {{ old('grupo_id', $alumno->grupo_id ?? '') == $grupo->id ? 'selected' : '' }}>
                    {{ $grupo->nombre_completo }}
                </option>
            @endforeach
        </select>
        @error('grupo_id') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Frecuencia semanal --}}
@php $currentPlanId = old('plan_id', isset($alumno) ? ($alumno->planActivo?->plan_id ?? '') : ''); @endphp
<div id="plan-section" class="mt-4" style="{{ isset($alumno) ? '' : 'display:none' }}">
    <label for="plan_id" class="{{ $labelClass }}">
        <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Frecuencia semanal @unless(isset($alumno))<span class="form-required">*</span>@endunless
        <span class="text-wings-muted font-normal">(define el precio mensual)</span>
    </label>
    <select id="plan_id" name="plan_id" class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
        <option value="">Seleccionar frecuencia...</option>
    </select>
    @error('plan_id') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
</div>

{{-- Tutor --}}
<div class="mt-4 p-4" style="background: var(--color-surface-alt); border: 1px solid var(--color-border); border-radius: var(--radius-card);">
    <p class="text-xs font-medium text-wings-muted mb-3">Datos del tutor — <span class="font-normal">obligatorio para menores de edad</span></p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="nombre_tutor" class="{{ $labelClass }}">
                <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Nombre del tutor
            </label>
            <input type="text" id="nombre_tutor" name="nombre_tutor" value="{{ old('nombre_tutor', $alumno->nombre_tutor ?? '') }}"
                   class="w-full px-4 py-2.5 text-sm wings-input" placeholder="Nombre y Apellido">
            @error('nombre_tutor') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="telefono_tutor" class="{{ $labelClass }}">
                <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                Teléfono del tutor
            </label>
            <input type="text" id="telefono_tutor" name="telefono_tutor" value="{{ old('telefono_tutor', $alumno->telefono_tutor ?? '') }}"
                   class="w-full px-4 py-2.5 text-sm wings-input" placeholder="11-1234-5678">
            @error('telefono_tutor') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

{{-- JS: filtrar grupos por deporte + cargar planes por grupo --}}
<script>
(function () {
    const grupoPlanes = @json($grupoPlanesJson ?? []);

    function actualizarPlanes(grupoId) {
        const section  = document.getElementById('plan-section');
        if (!section) return;

        const select = document.getElementById('plan_id');
        const planes = grupoPlanes[grupoId] || [];

        select.innerHTML = '<option value="">Seleccionar frecuencia...</option>';

        if (planes.length > 0) {
            planes.forEach(p => {
                const opt     = document.createElement('option');
                opt.value     = p.id;
                const veces   = p.clases === 1 ? '1 vez/semana' : p.clases + ' veces/semana';
                const precio  = parseFloat(p.precio).toLocaleString('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
                opt.textContent = veces + ' — ' + precio;
                if (String({{ $currentPlanId ?: 'null' }}) === String(p.id)) opt.selected = true;
                select.appendChild(opt);
            });
            section.style.display = '';
        } else {
            section.style.display = 'none';
        }
    }

    function filtrarGruposPorDeporte(deporteId) {
        const grupoSelect = document.getElementById('grupo_id');
        const options     = grupoSelect.querySelectorAll('option[data-deporte]');

        grupoSelect.value = '';
        options.forEach(opt => {
            opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
        });

        const section = document.getElementById('plan-section');
        if (section) section.style.display = 'none';
    }

    // Celular: "mismo que tutor"
    const chkMismo = document.getElementById('celular-mismo-tutor');
    if (chkMismo) {
        const celularInput   = document.getElementById('celular');
        const tutorTelInput  = document.getElementById('telefono_tutor');

        function sincronizarCelular() {
            if (chkMismo.checked) {
                celularInput.value    = tutorTelInput.value;
                celularInput.readOnly = true;
                celularInput.style.opacity = '0.6';
            } else {
                celularInput.readOnly = false;
                celularInput.style.opacity = '';
            }
        }

        chkMismo.addEventListener('change', sincronizarCelular);
        tutorTelInput.addEventListener('input', () => {
            if (chkMismo.checked) celularInput.value = tutorTelInput.value;
        });
    }

    const deporteSelect = document.getElementById('deporte_id');
    if (deporteSelect && deporteSelect.tagName === 'SELECT') {
        deporteSelect.addEventListener('change', function () {
            filtrarGruposPorDeporte(this.value);
        });
    }

    document.getElementById('grupo_id').addEventListener('change', function () {
        actualizarPlanes(this.value);
    });

    document.addEventListener('DOMContentLoaded', () => {
        const deporteEl = document.getElementById('deporte_id');
        const deporteId = deporteEl ? deporteEl.value : '';
        if (deporteId) {
            document.getElementById('grupo_id').querySelectorAll('option[data-deporte]').forEach(opt => {
                opt.style.display = opt.dataset.deporte === deporteId ? '' : 'none';
            });
        }
        const grupoId = document.getElementById('grupo_id').value;
        if (grupoId) actualizarPlanes(grupoId);
    });

    // Validación fecha de nacimiento en tiempo real
    const fechaInput = document.getElementById('fecha_nacimiento');
    const fechaError = document.getElementById('fecha-nacimiento-error');
    if (fechaInput && fechaError) {
        fechaInput.addEventListener('input', function () {
            const val = this.value;
            if (!val) { fechaError.style.display = 'none'; fechaError.textContent = ''; return; }
            const year = parseInt(val.split('-')[0], 10);
            const today = new Date().toISOString().split('T')[0];
            if (year < 1900 || year > new Date().getFullYear()) {
                fechaError.textContent = 'El año ingresado no es válido.';
                fechaError.style.display = '';
                this.setCustomValidity('Año inválido');
            } else if (val > today) {
                fechaError.textContent = 'La fecha debe ser anterior a hoy.';
                fechaError.style.display = '';
                this.setCustomValidity('Fecha futura');
            } else {
                fechaError.style.display = 'none';
                fechaError.textContent = '';
                this.setCustomValidity('');
            }
        });
    }

    // Validación email en tiempo real
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    if (emailInput && emailError) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        function validarEmail() {
            if (emailInput.value && !emailRegex.test(emailInput.value)) {
                emailError.textContent = 'El email no tiene un formato válido.';
                emailError.style.display = '';
            } else {
                emailError.style.display = 'none';
                emailError.textContent = '';
            }
        }
        emailInput.addEventListener('blur', validarEmail);
        emailInput.addEventListener('input', function () {
            if (!this.value || emailRegex.test(this.value)) {
                emailError.style.display = 'none';
                emailError.textContent = '';
            }
        });
    }
})();
</script>
