@extends('layouts.app')

@section('title', 'Nueva Liquidación – Wings')
@section('module-title', 'Nueva Liquidación')

@section('content')

<div class="filtros-card">

    @if(session('error'))
        <div class="ds-flash ds-flash--error mb-4">{{ session('error') }}</div>
    @endif

    {{-- Aviso último día del mes --}}
    @if($esUltimoDiaMes)
    <div id="aviso-hoy"
         style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:1.25rem;
                padding:12px 16px; border-radius:var(--radius-card);
                background:color-mix(in srgb, var(--color-warning) 12%, var(--color-surface));
                border:1px solid color-mix(in srgb, var(--color-warning) 35%, transparent);">
        <svg style="flex-shrink:0; color:var(--color-warning);" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span style="flex:1; font-size:0.82rem; color:var(--color-text); font-weight:500;">
            Hoy es el último día del mes. Las clases de hoy programadas no están incluidas aún.
            ¿Querés incluirlas?
        </span>
        <div style="display:flex; gap:6px; flex-shrink:0;">
            <button type="button" id="btn-incluir"
                    data-incluir-hoy="true"
                    style="height:30px; padding:0 14px; font-size:0.78rem; font-weight:600;
                           border-radius:var(--radius-btn); cursor:pointer; border:none; font-family:inherit;
                           background:var(--color-warning); color:#fff;">
                Incluir clases de hoy
            </button>
            <button type="button" id="btn-no-incluir"
                    data-incluir-hoy="false"
                    style="height:30px; padding:0 14px; font-size:0.78rem; font-weight:600;
                           border-radius:var(--radius-btn); cursor:pointer; font-family:inherit;
                           background:transparent; border:1px solid var(--color-warning);
                           color:var(--color-warning);">
                No incluir
            </button>
        </div>
        <input type="hidden" name="incluir_hoy_preview" id="incluir-hoy-label"
               style="display:none;" value="">
    </div>
    @endif

    <form method="POST" action="{{ route('web.liquidaciones.store') }}" id="form-liquidacion">
        @csrf
        <input type="hidden" name="incluir_hoy" id="incluir-hoy" value="0">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

            {{-- Profesores: lista radios --}}
            <div class="md:col-span-2">
                <label style="display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:600;
                              color:var(--color-text-muted); margin-bottom:8px;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color:var(--color-btn-primary)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profesor <span class="form-required">*</span>
                </label>

                @error('profesor_id')
                    <p class="text-xs mb-2" style="color:var(--color-danger);">{{ $message }}</p>
                @enderror

                <div style="max-height:320px; overflow-y:auto; border:1px solid var(--color-border);
                            border-radius:var(--radius-card); padding:4px;">
                    @forelse($profesores as $profesor)
                    @php
                        $dep             = $profesor->deporte->nombre ?? '—';
                        $tipoPeriodo     = $profesor->tipoLiquidacionParaPeriodo($mesActual, $anioActual);
                        $tipo            = $tipoPeriodo === 'HORA' ? 'Por hora' : 'Por comisión';
                        $count           = (int) $profesor->clases_count;
                        $sinAsistencia   = (int) $profesor->clases_sin_asistencia;
                        $tienePendientes = ($profesor->clases_count_total ?? $count) > 0;
                        $isSelected      = old('profesor_id') == $profesor->id;
                    @endphp
                    <label for="prof-{{ $profesor->id }}"
                           style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px;
                                  border-radius:calc(var(--radius-card) - 2px); cursor:{{ $tienePendientes ? 'pointer' : 'not-allowed' }};
                                  opacity:{{ $tienePendientes ? '1' : '0.5' }};
                                  transition:background 0.12s;"
                           class="profesor-radio-row {{ $isSelected ? 'selected' : '' }}">
                        <input type="radio"
                               id="prof-{{ $profesor->id }}"
                               name="profesor_id"
                               value="{{ $profesor->id }}"
                               {{ $isSelected ? 'checked' : '' }}
                               {{ !$tienePendientes ? 'disabled' : '' }}
                               style="margin-top:3px; flex-shrink:0; accent-color:var(--color-btn-primary);">
                        <div style="flex:1; min-width:0;">
                            <p class="profesor-sin-porcentaje" @if($tipoPeriodo !== 'COMISION' || $profesor->porcentaje_comision > 0) hidden @endif style="font-size:0.8rem; color:var(--color-danger);">Falta cargar el porcentaje de comisión. No se puede liquidar hasta completarlo.</p>
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
                                <span style="font-size:0.88rem; font-weight:700; color:var(--color-text);">
                                    {{ $profesor->apellido }}, {{ $profesor->nombre }}
                                    @if(!$profesor->activo) <small style="font-weight:500;">· Inactiva, con clases dictadas pendientes</small> @endif
                                </span>
                                <span style="font-size:0.65rem; font-weight:700; padding:1px 7px; border-radius:999px;
                                             background:color-mix(in srgb, var(--color-btn-primary) 12%, transparent);
                                             color:var(--color-btn-primary);">
                                    {{ $dep }}
                                </span>
                                <span class="profesor-tipo" style="font-size:0.65rem; color:var(--color-text-muted); font-weight:500;">
                                    {{ $tipo }}
                                </span>
                            </div>
                            @if($tienePendientes)
                                <span class="profesor-clases-count" style="font-size:0.72rem; font-weight:{{ $count > 0 ? '600' : '500' }}; color:{{ $count > 0 ? 'var(--color-success)' : 'var(--color-text-muted)' }}; display:block; margin-top:2px;">
                                    {{ $count }} {{ $count === 1 ? 'clase liquidable' : 'clases liquidables' }}
                                </span>
                            @else
                                <span class="profesor-clases-count" style="font-size:0.72rem; font-weight:500; color:var(--color-text-muted); display:block; margin-top:2px;">
                                    Sin clases pendientes
                                </span>
                            @endif
                            <span class="profesor-sin-asistencia-aviso" style="font-size:0.72rem; font-weight:600; color:var(--color-danger); display:{{ $sinAsistencia > 0 ? 'block' : 'none' }}; margin-top:1px;">
                                &#9888; {{ $sinAsistencia }} {{ $sinAsistencia === 1 ? 'clase sin asistencia' : 'clases sin asistencia' }} — no se liquidarán
                            </span>
                        </div>
                    </label>
                    @empty
                        <p style="font-size:0.82rem; color:var(--color-text-muted); padding:12px; margin:0;">
                            No hay profesores activos.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Panel advertencia clases sin asistencia --}}
            <div class="md:col-span-2">
                <div id="aviso-sin-asistencia"
                     style="display:none; flex-wrap:wrap; align-items:center; gap:10px;
                            padding:12px 16px; border-radius:var(--radius-card);
                            background:color-mix(in srgb, var(--color-warning) 12%, var(--color-surface));
                            border:1px solid color-mix(in srgb, var(--color-warning) 35%, transparent);">
                    <svg style="flex-shrink:0; color:var(--color-warning);" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span id="aviso-sin-asistencia-texto"
                          style="flex:1; font-size:0.82rem; color:var(--color-text); font-weight:500; min-width:180px;">
                    </span>
                    <a id="aviso-sin-asistencia-link" href="#"
                       style="flex-shrink:0; font-size:0.78rem; font-weight:600; color:var(--color-warning);
                              text-decoration:underline; white-space:nowrap;">
                        Ir a cargar asistencias →
                    </a>
                </div>
            </div>

            {{-- Mes --}}
            <div>
                <label for="mes"
                       style="display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:600;
                              color:var(--color-text-muted); margin-bottom:6px;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color:var(--color-btn-primary)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Mes <span class="form-required">*</span>
                </label>
                <select id="mes" name="mes" required class="w-full px-4 py-2.5 text-sm wings-input">
                    <option value="">— Seleccioná —</option>
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}"
                                {{ old('mes', $mesActual) == $num ? 'selected' : '' }}>
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>
                @error('mes')
                    <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

            {{-- Año --}}
            <div>
                <label for="anio"
                       style="display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:600;
                              color:var(--color-text-muted); margin-bottom:6px;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color:var(--color-btn-primary)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Año <span class="form-required">*</span>
                </label>
                <input type="number" id="anio" name="anio" required
                       min="2020" max="{{ $anioActual + 1 }}"
                       value="{{ old('anio', $anioActual) }}"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       style="max-width:140px;">
                @error('anio')
                    <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- Nota informativa --}}
        <p style="font-size:0.78rem; color:var(--color-text-muted); padding:10px 14px;
                  background:color-mix(in srgb, var(--color-btn-primary) 6%, var(--color-surface));
                  border:1px solid color-mix(in srgb, var(--color-btn-primary) 20%, transparent);
                  border-radius:var(--radius-card); margin-bottom:0;">
            Se generará la liquidación con todas las clases liquidables del período seleccionado.
        </p>

        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.liquidaciones.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Generar</x-ds.button>
        </div>

    </form>
</div>

<style>
.profesor-radio-row:hover {
    background: color-mix(in srgb, var(--color-btn-primary) 6%, transparent);
}
.profesor-radio-row.selected,
.profesor-radio-row:has(input:checked) {
    background: color-mix(in srgb, var(--color-btn-primary) 10%, transparent);
}
</style>

{{-- Datos para resources/js/liquidaciones-create.js (T73: sin JavaScript en la vista) --}}
<div id="liquidacion-create-datos" hidden data-profesores="{{ json_encode($profesoresJson) }}"></div>

@push('scripts')
    @vite('resources/js/liquidaciones-create.js')
@endpush

@endsection
