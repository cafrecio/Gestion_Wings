@extends('layouts.ds-app')

@php
    $editando  = isset($movimiento);
    $pageTitle = $editando ? 'Editar movimiento' : 'Nuevo movimiento';
@endphp

@section('title', $pageTitle . ' – Wings')
@section('module-title', $pageTitle)

@section('content')

@php
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';

$rubroActual    = old('rubro_id', $editando ? ($movimiento->subrubro?->rubro_id ?? '') : '');
$subrubroActual = old('subrubro_id', $editando ? ($movimiento->subrubro_id ?? '') : '');
$tipoCajaActual = old('tipo_caja_id', $editando ? ($movimiento->tipo_caja_id ?? '') : '');
$montoActual    = old('monto', $editando ? $movimiento->monto : '');
$fechaActual    = old('fecha', $editando ? ($movimiento->fecha?->format('Y-m-d') ?? today()->format('Y-m-d')) : today()->format('Y-m-d'));
$obsActual      = old('observaciones', $editando ? ($movimiento->observaciones ?? '') : '');

$rubroTipoActual = '';
if ($rubroActual) {
    $rubroObj = $rubros->firstWhere('id', (int)$rubroActual);
    $rubroTipoActual = $rubroObj?->tipo ?? '';
}

$formAction = $editando
    ? route('web.caja.movimientos.update', [$caja->id, $movimiento->id])
    : route('web.caja.editar.store', $caja->id);
$formMethod = $editando ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $formAction }}" id="mov-form">
    @csrf
    @if($editando) @method('PUT') @endif

    {{-- ── Tipo I/E ─────────────────────────────────────────────────────── --}}
    <div class="filtros-card mb-4" id="tipo-card" style="transition: border-left 0.2s;">
        <p class="{{ $labelClass }}" style="margin-bottom:0.75rem;">
            Tipo de movimiento <span class="form-required">*</span>
        </p>
        <div style="display:flex; gap:12px; margin-bottom:1.25rem;">
            <label style="flex:1; cursor:pointer;">
                <input type="radio" name="tipo_ie" value="INGRESO" id="tipo-ingreso"
                       {{ ($rubroTipoActual === 'INGRESO' || $rubroTipoActual === '') ? 'checked' : '' }}
                       style="display:none;">
                <div id="btn-ingreso"
                     style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid; border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700; transition:all 0.15s;">
                    INGRESO
                </div>
            </label>
            <label style="flex:1; cursor:pointer;">
                <input type="radio" name="tipo_ie" value="EGRESO" id="tipo-egreso"
                       {{ $rubroTipoActual === 'EGRESO' ? 'checked' : '' }}
                       style="display:none;">
                <div id="btn-egreso"
                     style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid; border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700; transition:all 0.15s;">
                    EGRESO
                </div>
            </label>
        </div>

        {{-- ── Campos del form ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Fecha --}}
            <div>
                <label for="fecha" class="{{ $labelClass }}">Fecha <span class="form-required">*</span></label>
                <input type="date" id="fecha" name="fecha"
                       value="{{ $fechaActual }}" required
                       max="{{ today()->format('Y-m-d') }}"
                       class="w-full px-4 py-2.5 text-sm wings-input">
                @error('fecha') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Tipo de caja --}}
            <div>
                <label for="tipo_caja_id" class="{{ $labelClass }}">Medio de pago <span class="form-required">*</span></label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($tiposCaja as $tipo)
                        <option value="{{ $tipo->id }}" {{ $tipoCajaActual == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->abreviatura ? $tipo->abreviatura . ' — ' : '' }}{{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_caja_id') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Rubro --}}
            <div>
                <label for="rubro_id" class="{{ $labelClass }}">Rubro <span class="form-required">*</span></label>
                <select id="rubro_id" name="rubro_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($rubros as $rubro)
                        <option value="{{ $rubro->id }}"
                                data-tipo="{{ $rubro->tipo }}"
                                {{ $rubroActual == $rubro->id ? 'selected' : '' }}>
                            {{ $rubro->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Subrubro --}}
            <div>
                <label for="subrubro_id" class="{{ $labelClass }}">Subrubro <span class="form-required">*</span></label>
                <select id="subrubro_id" name="subrubro_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar rubro primero...</option>
                    @foreach($rubros as $rubro)
                        @foreach($rubro->subrubros as $sub)
                            <option value="{{ $sub->id }}"
                                    data-rubro="{{ $rubro->id }}"
                                    {{ $subrubroActual == $sub->id ? 'selected' : '' }}>
                                {{ $sub->nombre }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('subrubro_id') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Monto --}}
            <div>
                <label for="monto" class="{{ $labelClass }}">Monto <span class="form-required">*</span></label>
                <input type="text" id="monto" name="monto"
                       value="{{ old('monto', $editando && $montoActual ? number_format((float)$montoActual, 0, ',', '.') : '') }}"
                       required data-money="true"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="0">
                @error('monto') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

            {{-- Observaciones --}}
            <div>
                <label for="observaciones" class="{{ $labelClass }}">Observaciones <span class="form-required">*</span></label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ $obsActual }}" required maxlength="500"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Descripción del movimiento">
                @error('observaciones') <p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p> @enderror
            </div>

        </div>
    </div>

    <div class="filtros-actions" style="justify-content:flex-end;">
        <x-ds.button variant="secondary" href="{{ route('web.caja.resumen', $caja->id) }}">Cancelar</x-ds.button>
        <x-ds.button variant="primary" type="submit">{{ $editando ? 'Guardar' : 'Registrar' }}</x-ds.button>
    </div>

</form>

@endsection

@push('scripts')
<script>
(function () {
    var subrubrosData  = @json($subrubrosMap);
    var rubrosData     = @json($rubros->map(fn($r) => ['id' => $r->id, 'tipo' => $r->tipo]));

    var tipoIngreso = document.getElementById('tipo-ingreso');
    var tipoEgreso  = document.getElementById('tipo-egreso');
    var btnIngreso  = document.getElementById('btn-ingreso');
    var btnEgreso   = document.getElementById('btn-egreso');
    var card        = document.getElementById('tipo-card');
    var selectRubro = document.getElementById('rubro_id');
    var selectSub   = document.getElementById('subrubro_id');

    var rubroActual    = '{{ $rubroActual }}';
    var subrubroActual = '{{ $subrubroActual }}';

    function actualizarBotonesTipo(tipo) {
        if (tipo === 'INGRESO') {
            btnIngreso.style.borderColor = 'var(--color-success)';
            btnIngreso.style.color       = 'var(--color-success)';
            btnIngreso.style.background  = 'color-mix(in srgb, var(--color-success) 8%, transparent)';
            btnEgreso.style.borderColor  = 'var(--color-border)';
            btnEgreso.style.color        = 'var(--color-text-muted)';
            btnEgreso.style.background   = 'transparent';
            card.style.borderLeft        = '4px solid var(--color-success)';
        } else {
            btnEgreso.style.borderColor  = 'var(--color-danger)';
            btnEgreso.style.color        = 'var(--color-danger)';
            btnEgreso.style.background   = 'color-mix(in srgb, var(--color-danger) 8%, transparent)';
            btnIngreso.style.borderColor = 'var(--color-border)';
            btnIngreso.style.color       = 'var(--color-text-muted)';
            btnIngreso.style.background  = 'transparent';
            card.style.borderLeft        = '4px solid var(--color-danger)';
        }
        filtrarRubros(tipo);
    }

    function filtrarRubros(tipo) {
        var opts = selectRubro.querySelectorAll('option[data-tipo]');
        opts.forEach(function (opt) {
            opt.style.display = (!tipo || opt.dataset.tipo === tipo) ? '' : 'none';
        });
        if (selectRubro.value) {
            var selected = selectRubro.querySelector('option[value="' + selectRubro.value + '"]');
            if (selected && selected.style.display === 'none') {
                selectRubro.value = '';
                filtrarSubrubros('');
            } else {
                filtrarSubrubros(selectRubro.value);
            }
        }
    }

    function filtrarSubrubros(rubroId) {
        var opts = selectSub.querySelectorAll('option[data-rubro]');
        opts.forEach(function (opt) {
            opt.style.display = (!rubroId || opt.dataset.rubro === String(rubroId)) ? '' : 'none';
        });
        if (selectSub.value) {
            var sel = selectSub.querySelector('option[value="' + selectSub.value + '"]:not([style*="display: none"])');
            if (!sel) selectSub.value = '';
        }
        if (!rubroId) {
            selectSub.querySelector('option[value=""]').textContent = 'Seleccionar rubro primero...';
        } else {
            selectSub.querySelector('option[value=""]').textContent = 'Seleccionar...';
        }
    }

    tipoIngreso.addEventListener('change', function () { if (this.checked) actualizarBotonesTipo('INGRESO'); });
    tipoEgreso.addEventListener('change',  function () { if (this.checked) actualizarBotonesTipo('EGRESO'); });

    selectRubro.addEventListener('change', function () { filtrarSubrubros(this.value); });

    // Init
    var tipoInicial = tipoIngreso.checked ? 'INGRESO' : 'EGRESO';
    actualizarBotonesTipo(tipoInicial);
    filtrarSubrubros(rubroActual);
    if (subrubroActual) selectSub.value = subrubroActual;
})();
</script>
@endpush
