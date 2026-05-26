@extends('layouts.app')

@section('title', 'Editar Clase – Wings')
@section('module-title', 'Editar Clase')

@section('content')

@php
$iconAttr     = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass   = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
$profAsignados = $clase->profesores->pluck('id')->toArray();
@endphp

<form method="POST" action="{{ route('web.clases.update', $clase->id) }}">
    @csrf
    @method('PUT')

    <div class="filtros-card">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

            {{-- Grupo (solo lectura) --}}
            <div>
                <label class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Grupo
                </label>
                <div class="w-full px-4 py-2.5 text-sm wings-input"
                     style="background:var(--color-surface-alt); color:var(--color-text-muted); cursor:default;">
                    {{ $clase->grupo->nombre_completo }}
                </div>
            </div>

            {{-- Fecha --}}
            <div>
                <label for="fecha" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Fecha <span class="form-required">*</span>
                </label>
                <input type="date" id="fecha" name="fecha"
                       value="{{ old('fecha', $clase->fecha->format('Y-m-d')) }}"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       style="max-width:220px;">
                @error('fecha') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Hora inicio --}}
            <div>
                <label for="hora_inicio" class="{{ $labelClass }}">
                    <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Hora inicio <span class="form-required">*</span>
                </label>
                <input type="time" id="hora_inicio" name="hora_inicio"
                       value="{{ old('hora_inicio', $clase->hora_inicio->format('H:i')) }}"
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
                       value="{{ old('hora_fin', $clase->hora_fin->format('H:i')) }}"
                       required
                       class="w-full px-4 py-2.5 text-sm wings-input">
                <p id="hora-fin-error" class="text-xs mt-1" style="color:var(--color-danger); display:none;">La hora de fin debe ser posterior a la hora de inicio.</p>
                @error('hora_fin') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- Separador --}}
        <div style="height:1px; background:var(--color-border); margin:16px 0;"></div>

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
                            {{ in_array($profesor->id, old('profesores', $profAsignados)) ? 'checked' : '' }}>
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
            <x-ds.button variant="secondary" href="{{ route('web.clases.show', $clase->id) }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    const horaInicio   = document.getElementById('hora_inicio');
    const horaFin      = document.getElementById('hora_fin');
    const horaFinError = document.getElementById('hora-fin-error');

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
