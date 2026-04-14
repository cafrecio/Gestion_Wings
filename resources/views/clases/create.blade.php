@extends('layouts.app')

@section('title', 'Nueva Clase – Wings')
@section('module-title', 'Nueva Clase')

@section('content')

@php
$iconAttr  = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
@endphp

<form method="POST" action="{{ route('web.clases.store') }}">
    @csrf

    {{-- Selector de modo --}}
    <div class="filtros-card mb-4">
        <div class="filtros-row" style="gap:8px;">
            <label style="cursor:pointer; display:flex; align-items:center; gap:6px; font-size:0.82rem; padding:6px 12px; border-radius:var(--radius-card); border:1px solid var(--color-border);" id="lbl-unica">
                <input type="radio" name="tipo_creacion" value="unica" checked> Clase única
            </label>
            <label style="cursor:pointer; display:flex; align-items:center; gap:6px; font-size:0.82rem; padding:6px 12px; border-radius:var(--radius-card); border:1px solid var(--color-border);" id="lbl-recurrente">
                <input type="radio" name="tipo_creacion" value="recurrente"> Clases recurrentes
            </label>
        </div>
    </div>

    <div class="filtros-card">

        {{-- Sección: Clase única --}}
        <div id="seccion-unica">
            <div class="mb-4">
                <label for="fecha" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Fecha <span class="form-required">*</span>
                </label>
                <input type="date" id="fecha" name="fecha"
                       value="{{ old('fecha') }}"
                       min="{{ date('Y-m-d') }}"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       style="max-width:220px;">
                @error('fecha') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Sección: Recurrente --}}
        <div id="seccion-recurrente" style="display:none;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="fecha_desde" class="{{ $labelClass }}">
                        <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Fecha desde <span class="form-required">*</span>
                    </label>
                    <input type="date" id="fecha_desde" name="fecha_desde"
                           value="{{ old('fecha_desde') }}"
                           class="w-full px-4 py-2.5 text-sm wings-input">
                    @error('fecha_desde') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="fecha_hasta" class="{{ $labelClass }}">
                        <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Fecha hasta <span class="form-required">*</span>
                    </label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta"
                           value="{{ old('fecha_hasta') }}"
                           class="w-full px-4 py-2.5 text-sm wings-input">
                    @error('fecha_hasta') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h8"/></svg>
                    Días de la semana <span class="form-required">*</span>
                </label>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $idx => $dia)
                        @php
                            // Lun=1, Mar=2, Mié=3, Jue=4, Vie=5, Sáb=6, Dom=0
                            $val = ($idx === 6) ? 0 : $idx + 1;
                            $preCheck = $idx <= 4; // Lun-Vie pre-checked
                        @endphp
                        <label style="cursor:pointer; display:flex; align-items:center; gap:4px; font-size:0.78rem; padding:4px 10px; border-radius:999px; border:1px solid var(--color-border); background:var(--color-surface-alt);">
                            <input type="checkbox" name="dias_semana[]" value="{{ $val }}"
                                {{ $preCheck ? 'checked' : '' }}>
                            {{ $dia }}
                        </label>
                    @endforeach
                </div>
                @error('dias_semana') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Separador --}}
        <div style="height:1px; background:var(--color-border); margin:16px 0;"></div>

        {{-- Campos comunes --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

            {{-- Grupo --}}
            <div>
                <label for="grupo_id" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Grupo <span class="form-required">*</span>
                </label>
                <select id="grupo_id" name="grupo_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar grupo...</option>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}"
                                data-deporte="{{ $grupo->deporte->nombre ?? '' }}"
                                {{ old('grupo_id') == $grupo->id ? 'selected' : '' }}>
                            {{ $grupo->nombre }}
                            @if($grupo->deporte) ({{ $grupo->deporte->nombre }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('grupo_id') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
                <p id="deporte-info" class="text-xs mt-1 text-wings-muted" style="display:none;"></p>
            </div>

            <div>{{-- placeholder for grid --}}</div>

            {{-- Hora inicio --}}
            <div>
                <label for="hora_inicio" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Hora inicio <span class="form-required">*</span>
                </label>
                <input type="time" id="hora_inicio" name="hora_inicio"
                       value="{{ old('hora_inicio') }}"
                       required
                       class="w-full px-4 py-2.5 text-sm wings-input">
                @error('hora_inicio') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Hora fin --}}
            <div>
                <label for="hora_fin" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Hora fin <span class="form-required">*</span>
                </label>
                <input type="time" id="hora_fin" name="hora_fin"
                       value="{{ old('hora_fin') }}"
                       required
                       class="w-full px-4 py-2.5 text-sm wings-input">
                <p id="hora-fin-error" class="text-xs mt-1" style="color:var(--color-danger); display:none;">La hora de fin debe ser posterior a la hora de inicio.</p>
                @error('hora_fin') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Profesores --}}
        <div class="mt-2">
            <label class="{{ $labelClass }}">
                <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profesores
                <span class="font-normal text-wings-muted">(opcional)</span>
            </label>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:4px;">
                @forelse($profesores as $profesor)
                    @php
                        $depP  = mb_strtolower($profesor->deporte->nombre ?? '');
                        $depP  = strtr($depP, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
                        $railP = str_contains($depP, 'pat') ? 'patin' : (str_contains($depP, 'fut') ? 'futbol' : 'otro');
                    @endphp
                    <label style="cursor:pointer; display:flex; align-items:center; gap:6px; font-size:0.8rem; padding:6px 12px; border-radius:var(--radius-card); border:1px solid var(--color-border); background:var(--color-surface-alt);">
                        <input type="checkbox" name="profesores[]" value="{{ $profesor->id }}"
                            {{ in_array($profesor->id, old('profesores', [])) ? 'checked' : '' }}>
                        {{ $profesor->apellido }}, {{ $profesor->nombre }}
                        @if($profesor->deporte)
                            <span style="font-size:0.62rem; font-weight:700; padding:1px 6px; border-radius:999px; background:color-mix(in srgb, var(--color-sport-{{ $railP }}) 18%, transparent); color:var(--color-sport-{{ $railP }});">
                                {{ $profesor->deporte->nombre }}
                            </span>
                        @endif
                    </label>
                @empty
                    <p class="text-xs text-wings-muted">No hay profesores activos registrados.</p>
                @endforelse
            </div>
            @error('profesores') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
        </div>

        {{-- Acciones --}}
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.clases.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    const radios        = document.querySelectorAll('input[name="tipo_creacion"]');
    const secUnica      = document.getElementById('seccion-unica');
    const secRecurrente = document.getElementById('seccion-recurrente');
    const lblUnica      = document.getElementById('lbl-unica');
    const lblRec        = document.getElementById('lbl-recurrente');
    const grupoSelect   = document.getElementById('grupo_id');
    const deporteInfo   = document.getElementById('deporte-info');
    const horaInicio    = document.getElementById('hora_inicio');
    const horaFin       = document.getElementById('hora_fin');
    const horaFinError  = document.getElementById('hora-fin-error');

    function actualizarModo() {
        const tipo = document.querySelector('input[name="tipo_creacion"]:checked').value;
        if (tipo === 'unica') {
            secUnica.style.display = '';
            secRecurrente.style.display = 'none';
            lblUnica.style.background = 'color-mix(in srgb, var(--color-btn-primary) 12%, transparent)';
            lblUnica.style.borderColor = 'var(--color-btn-primary)';
            lblRec.style.background = '';
            lblRec.style.borderColor = 'var(--color-border)';
        } else {
            secUnica.style.display = 'none';
            secRecurrente.style.display = '';
            lblRec.style.background = 'color-mix(in srgb, var(--color-btn-primary) 12%, transparent)';
            lblRec.style.borderColor = 'var(--color-btn-primary)';
            lblUnica.style.background = '';
            lblUnica.style.borderColor = 'var(--color-border)';
        }
    }

    radios.forEach(r => r.addEventListener('change', actualizarModo));
    actualizarModo();

    // Deporte info dinámico al seleccionar grupo
    if (grupoSelect && deporteInfo) {
        grupoSelect.addEventListener('change', function () {
            const sel = this.options[this.selectedIndex];
            const dep = sel.dataset.deporte || '';
            if (dep) {
                deporteInfo.textContent = 'Deporte: ' + dep;
                deporteInfo.style.display = '';
            } else {
                deporteInfo.style.display = 'none';
            }
        });
        // Init
        const sel = grupoSelect.options[grupoSelect.selectedIndex];
        if (sel && sel.dataset.deporte) {
            deporteInfo.textContent = 'Deporte: ' + sel.dataset.deporte;
            deporteInfo.style.display = '';
        }
    }

    // Validación hora fin
    function validarHoras() {
        if (!horaInicio.value || !horaFin.value) return;
        if (horaFin.value <= horaInicio.value) {
            horaFinError.style.display = '';
            horaFin.setCustomValidity('La hora de fin debe ser posterior a la hora de inicio.');
        } else {
            horaFinError.style.display = 'none';
            horaFin.setCustomValidity('');
        }
    }

    if (horaInicio && horaFin) {
        horaInicio.addEventListener('change', validarHoras);
        horaFin.addEventListener('change', validarHoras);
    }
})();
</script>
@endpush
