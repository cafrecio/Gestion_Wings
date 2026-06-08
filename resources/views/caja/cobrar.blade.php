@extends('layouts.ds-app')

@section('title', 'Cobrar – ' . $alumno->apellido . ', ' . $alumno->nombre)
@section('module-title', 'Cobrar: ' . $alumno->apellido . ', ' . $alumno->nombre)

@section('content')

@php
    $dep   = mb_strtolower($alumno->deporte->nombre ?? '');
    $dep   = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $sport = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
@endphp

{{-- Info del alumno --}}
<div class="filtros-card mb-4" style="border-left: 4px solid var(--color-sport-{{ $sport }});">
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1rem;">
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">DNI</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumno->dni ?: '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Deporte</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumno->deporte->nombre ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Grupo</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumno->grupo->nombre_completo ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Total pendiente</p>
            <p style="font-size:0.85rem; font-weight:700; color:var(--color-danger);">
                ${{ number_format($alumno->deudaCuotas->sum('saldo_pendiente'), 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('web.caja.pagar', $alumno->id) }}" id="cobrar-form">
    @csrf

    {{-- Cuotas pendientes --}}
    <div class="filtros-card mb-4">
        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:0.75rem;">
            Cuotas pendientes — seleccioná las que querés cobrar
        </p>

        @if($alumno->deudaCuotas->isEmpty())
            <p style="font-size:0.85rem; color:var(--color-text-muted);">Sin deudas pendientes.</p>
        @else
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($alumno->deudaCuotas as $deuda)
                    @php
                        [$year, $month] = explode('-', $deuda->periodo);
                        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        $periodoLabel = $meses[(int)$month] . ' ' . $year;
                    @endphp
                    <div class="cuota-row" style="
                        display:flex; align-items:center; gap:12px;
                        padding:10px 14px;
                        border:1px solid var(--color-border);
                        border-radius:8px; background:var(--color-surface);
                        transition:background 0.15s;
                    ">
                        <input type="checkbox"
                               name="periodos[]"
                               value="{{ $deuda->periodo }}"
                               class="cuota-check"
                               data-saldo="{{ $deuda->saldo_pendiente }}"
                               data-periodo="{{ $deuda->periodo }}"
                               style="width:16px; height:16px; cursor:pointer; flex-shrink:0; accent-color:var(--color-btn-primary);">
                        <div style="flex:1; cursor:default;">
                            <span style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $periodoLabel }}</span>
                            @if((float)$deuda->monto_pagado > 0)
                                <span style="font-size:0.72rem; color:var(--color-text-muted); margin-left:8px;">
                                    Orig: ${{ number_format($deuda->monto_original, 0, ',', '.') }} · Pagado: ${{ number_format($deuda->monto_pagado, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="text"
                                   name="montos_cuota[{{ $deuda->periodo }}]"
                                   class="monto-cuota wings-input"
                                   data-periodo="{{ $deuda->periodo }}"
                                   data-saldo="{{ $deuda->saldo_pendiente }}"
                                   value="{{ number_format((float)$deuda->saldo_pendiente, 0, ',', '.') }}"
                                   disabled
                                   data-money="true"
                                   style="width:110px; padding:4px 10px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-danger);">
                        </div>
                    </div>
                @endforeach
            </div>
            @error('periodos')
                <p style="font-size:0.75rem; color:var(--color-danger); margin-top:8px;">{{ $message }}</p>
            @enderror
        @endif
    </div>

    {{-- Tipo de pago + observaciones --}}
    <div class="filtros-card mb-4">
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem;">
            <div>
                <label for="tipo_caja_id"
                       style="display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:600; color:var(--color-text-muted); margin-bottom:6px;">
                    Tipo de pago <span class="form-required">*</span>
                </label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($tiposCaja as $tipo)
                        <option value="{{ $tipo->id }}" {{ old('tipo_caja_id') == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->abreviatura ? $tipo->abreviatura . ' — ' : '' }}{{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_caja_id')
                    <p style="font-size:0.75rem; color:var(--color-danger); margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="observaciones"
                       style="display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:600; color:var(--color-text-muted); margin-bottom:6px;">
                    Observaciones
                </label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ old('observaciones') }}"
                       maxlength="500"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Opcional">
            </div>
            <div>
                <label for="fecha_pago"
                       style="display:flex; align-items:center; gap:6px; font-size:0.75rem; font-weight:600; color:var(--color-text-muted); margin-bottom:6px;">
                    Fecha del pago
                </label>
                <input type="date" id="fecha_pago" name="fecha_pago"
                       value="{{ old('fecha_pago', now()->format('Y-m-d')) }}"
                       max="{{ now()->format('Y-m-d') }}"
                       class="w-full px-4 py-2.5 text-sm wings-input">
            </div>
        </div>

        <div id="resumen-total" class="mt-4 pt-4" style="border-top:1px solid var(--color-border); display:none;">
            <div style="display:flex; justify-content:flex-end; align-items:center; gap:12px;">
                <span style="font-size:0.85rem; color:var(--color-text-muted);">Total a cobrar:</span>
                <span id="total-label" style="font-size:1.1rem; font-weight:700; color:var(--color-btn-primary);">$0</span>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="filtros-actions" style="justify-content:flex-end;">
        <x-ds.button variant="secondary" href="{{ route('web.caja.cobrar-cuota') }}">Cancelar</x-ds.button>
        <button type="submit" id="btn-cobrar" disabled
                class="ds-btn"
                style="background:var(--color-btn-primary); color:#fff; opacity:0.4; cursor:not-allowed;">
            Cobrar
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
(function () {
    var checks      = document.querySelectorAll('.cuota-check');
    var montoInputs = document.querySelectorAll('.monto-cuota');
    var selectTipo  = document.getElementById('tipo_caja_id');
    var btnCobrar   = document.getElementById('btn-cobrar');
    var resumen     = document.getElementById('resumen-total');
    var totalLabel  = document.getElementById('total-label');

    function parseMonto(str) {
        // Remove thousand separators (dots) and replace comma decimal with dot
        return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
    }

    function calcularTotal() {
        var total = 0;
        checks.forEach(function (chk) {
            if (chk.checked) {
                var periodo = chk.dataset.periodo;
                var inp = document.querySelector('.monto-cuota[data-periodo="' + periodo + '"]');
                var val = inp ? parseMonto(inp.value) : 0;
                var saldo = parseFloat(chk.dataset.saldo) || 0;
                total += Math.min(Math.max(val, 0), saldo);
            }
        });
        return total;
    }

    function actualizar() {
        var algunaCuota = document.querySelectorAll('.cuota-check:checked').length > 0;
        var tipoCaja    = selectTipo.value !== '';
        var total       = calcularTotal();

        if (algunaCuota && tipoCaja) {
            resumen.style.display = '';
            totalLabel.textContent = '$' + total.toLocaleString('es-AR', { maximumFractionDigits: 0 });
            btnCobrar.disabled = false;
            btnCobrar.style.opacity = '1';
            btnCobrar.style.cursor = 'pointer';
        } else {
            resumen.style.display = 'none';
            btnCobrar.disabled = true;
            btnCobrar.style.opacity = '0.4';
            btnCobrar.style.cursor = 'not-allowed';
        }
    }

    checks.forEach(function (chk) {
        chk.addEventListener('change', function () {
            var periodo = this.dataset.periodo;
            var inp = document.querySelector('.monto-cuota[data-periodo="' + periodo + '"]');
            if (inp) {
                if (this.checked) {
                    inp.removeAttribute('disabled');
                } else {
                    inp.setAttribute('disabled', 'disabled');
                }
            }
            actualizar();
        });
    });

    montoInputs.forEach(function (inp) {
        inp.addEventListener('input', actualizar);
    });

    selectTipo.addEventListener('change', actualizar);

    document.querySelectorAll('.cuota-row').forEach(function (row) {
        row.addEventListener('mouseenter', function () { this.style.background = 'var(--color-surface-alt)'; });
        row.addEventListener('mouseleave', function () { this.style.background = 'var(--color-surface)'; });
    });
})();
</script>
@endpush
