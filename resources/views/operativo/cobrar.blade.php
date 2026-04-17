@extends('layouts.app')

@section('title', 'Cobrar – ' . $alumno->apellido . ', ' . $alumno->nombre)
@section('module-title', 'Cobrar: ' . $alumno->apellido . ', ' . $alumno->nombre)

@section('content')

@php
    $dep   = mb_strtolower($alumno->deporte->nombre ?? '');
    $dep   = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $sport = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
    $sportColor = "var(--color-sport-{$sport})";
@endphp

<div class="filtros-card mb-4" style="border-left: 4px solid {{ $sportColor }};">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">DNI</p>
            <p class="text-sm font-medium text-wings">{{ $alumno->dni ?: '–' }}</p>
        </div>
        <div>
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">Deporte</p>
            <p class="text-sm font-medium text-wings">{{ $alumno->deporte->nombre ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">Grupo</p>
            <p class="text-sm font-medium text-wings">{{ $alumno->grupo->nombre_completo ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">Total pendiente</p>
            <p class="text-sm font-bold" style="color: var(--color-danger);">
                ${{ number_format($alumno->deudaCuotas->sum('saldo_pendiente'), 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('operativo.caja.pagar', $alumno->id) }}" id="cobrar-form">
    @csrf

    {{-- Deudas pendientes --}}
    <div class="filtros-card mb-4">
        <p class="text-xs font-medium text-wings-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.06em;">
            Cuotas pendientes — seleccioná las que querés cobrar (FIFO)
        </p>

        @if($alumno->deudaCuotas->isEmpty())
            <p class="text-sm text-wings-muted">Sin deudas pendientes.</p>
        @else
            <div class="flex flex-col gap-2">
                @foreach($alumno->deudaCuotas as $deuda)
                    @php
                        [$year, $month] = explode('-', $deuda->periodo);
                        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        $periodoLabel = $meses[(int)$month] . ' ' . $year;
                    @endphp
                    <label class="cuota-row" style="
                        display: flex; align-items: center; gap: 12px;
                        padding: 10px 14px; cursor: pointer;
                        border: 1px solid var(--color-border);
                        border-radius: 8px; background: var(--color-surface);
                        transition: background 0.15s;
                    ">
                        <input type="checkbox"
                               name="periodos[]"
                               value="{{ $deuda->periodo }}"
                               class="cuota-check"
                               style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--color-btn-primary);">
                        <div style="flex: 1;">
                            <span class="text-sm font-medium text-wings">{{ $periodoLabel }}</span>
                        </div>
                        <div style="text-align: right;">
                            <span class="text-sm font-bold" style="color: var(--color-danger);">
                                ${{ number_format($deuda->saldo_pendiente, 0, ',', '.') }}
                            </span>
                            @if((float)$deuda->monto_pagado > 0)
                                <br><span style="font-size: 0.65rem; color: var(--color-text-muted);">
                                    Orig: ${{ number_format($deuda->monto_original, 0, ',', '.') }} · Pagado: ${{ number_format($deuda->monto_pagado, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            @error('periodos')
                <p class="text-xs mt-2" style="color: var(--color-danger);">{{ $message }}</p>
            @enderror
        @endif
    </div>

    {{-- Tipo de pago + observaciones --}}
    <div class="filtros-card mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="tipo_caja_id"
                       class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Tipo de pago <span class="form-required">*</span>
                </label>
                <select id="tipo_caja_id" name="tipo_caja_id" required
                        class="w-full px-4 py-2.5 text-sm wings-input cursor-pointer">
                    <option value="">Seleccionar...</option>
                    @foreach($tiposCaja as $tipo)
                        <option value="{{ $tipo->id }}" {{ old('tipo_caja_id') == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_caja_id')
                    <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="observaciones"
                       class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Observaciones
                </label>
                <input type="text" id="observaciones" name="observaciones"
                       value="{{ old('observaciones') }}"
                       class="w-full px-4 py-2.5 text-sm wings-input"
                       placeholder="Opcional">
            </div>
        </div>

        {{-- Resumen total --}}
        <div id="resumen-total" class="mt-4 pt-4" style="border-top: 1px solid var(--color-border); display: none;">
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px;">
                <span class="text-sm text-wings-muted">Total a cobrar:</span>
                <span id="total-label" class="text-lg font-bold" style="color: var(--color-btn-primary);">$0</span>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="filtros-actions" style="justify-content: flex-end;">
        <x-ds.button variant="secondary" href="{{ route('operativo.caja') }}">Cancelar</x-ds.button>
        <x-ds.button variant="primary" type="submit" id="btn-cobrar" :disabled="true">
            Cobrar
        </x-ds.button>
    </div>

</form>

@endsection

@push('scripts')
<script>
(function () {
    const montos = @json($alumno->deudaCuotas->pluck('saldo_pendiente', 'periodo'));
    const checks  = document.querySelectorAll('.cuota-check');
    const btnCobrar = document.getElementById('btn-cobrar');
    const resumen   = document.getElementById('resumen-total');
    const totalLabel = document.getElementById('total-label');

    function actualizarTotal() {
        let total = 0;
        let alguno = false;
        checks.forEach(chk => {
            if (chk.checked) {
                alguno = true;
                total += parseFloat(montos[chk.value] || 0);
            }
        });

        if (alguno) {
            resumen.style.display = '';
            totalLabel.textContent = '$' + total.toLocaleString('es-AR', { maximumFractionDigits: 0 });
            btnCobrar.removeAttribute('disabled');
        } else {
            resumen.style.display = 'none';
            btnCobrar.setAttribute('disabled', 'disabled');
        }
    }

    checks.forEach(chk => chk.addEventListener('change', actualizarTotal));

    // Hover de cuota-row
    document.querySelectorAll('.cuota-row').forEach(row => {
        row.addEventListener('mouseenter', () => row.style.background = 'var(--color-surface-alt)');
        row.addEventListener('mouseleave', () => row.style.background = 'var(--color-surface)');
    });
})();
</script>
@endpush
