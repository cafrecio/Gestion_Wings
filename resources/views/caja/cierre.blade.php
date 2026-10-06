@extends('layouts.ds-app')
@section('title', 'Cierre de caja – Wings')
@section('module-title', 'Cierre de caja')
@php($title = 'Cierre de caja')

@section('content')
@if($errors->any())
<div class="filtros-card mb-4" role="alert" style="border-left:4px solid var(--color-danger);">
    <p class="text-sm font-semibold" style="color:var(--color-danger)">No se cerró la caja</p>
    @foreach($errors->all() as $error)<p class="text-sm mt-1">{{ $error }}</p>@endforeach
</div>
@endif
<div class="filtros-card">
    <p class="text-sm text-wings-muted mb-4">Caja #{{ $caja->id }} · {{ $caja->usuarioOperativo->name }}. Contá solo el efectivo del mostrador, no las transferencias ni lo que cobró ADMIN aparte.</p>
    @if($caja->efectivo_contado !== null)
    <p class="text-sm mb-4" style="color:var(--color-warning)">Corrección de un cierre rechazado: se conserva lo que se contó y entregó. Solo se recalculan el esperado y la diferencia con los movimientos corregidos.</p>
    @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div><span class="info-label">Efectivo inicial</span><p class="info-value">{{ $caja->efectivo_inicial === null ? 'Sin declaración inicial' : '$'.number_format($caja->efectivo_inicial, 2, ',', '.') }}</p></div>
        <div><span class="info-label">Efectivo esperado</span><p class="info-value">{{ $arqueo['efectivo_esperado'] === null ? 'No calculable: caja anterior sin declaración' : '$'.number_format($arqueo['efectivo_esperado'], 2, ',', '.') }}</p></div>
    </div>
    <form method="POST" action="{{ route('web.caja.cerrar', $caja->id) }}" data-caja-arqueo data-esperado-centavos="{{ $arqueo['efectivo_esperado'] === null ? '' : (int) round($arqueo['efectivo_esperado'] * 100) }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(['efectivo_contado' => 'Efectivo contado', 'cambio_retenido' => 'Cambio que queda'] as $campo => $rotulo)
            <div>
                <label for="{{ $campo }}" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2 0-3 1-3 2s1 2 3 2 3 1 3 2-1 2-3 2m0-10v12"/></svg>
                    {{ $rotulo }} <span class="form-required">*</span>
                </label>
                @if($caja->efectivo_contado !== null)
                <p class="text-sm font-semibold">${{ number_format($caja->$campo, 2, ',', '.') }}</p>
                <input type="hidden" id="{{ $campo }}" name="{{ $campo }}" value="{{ $caja->$campo }}" data-importe-canonico>
                @else
                <x-ds.money-input :id="$campo" :name="$campo" :value="old($campo, '')" required />
                @endif
            </div>
            @endforeach
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4" aria-live="polite">
            <div><span class="info-label">Diferencia</span><p class="info-value" data-diferencia>Ingresá el efectivo contado</p></div>
            <div><span class="info-label">Retiro / entrega</span><p class="info-value" data-retiro>Contado menos cambio que queda</p></div>
        </div>
        <p class="text-xs text-wings-muted mt-4">Podés cerrar con faltante o sobrante: ADMIN revisará la diferencia. El retiro no se registra como gasto.</p>
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.resumen', $caja->id) }}">Volver</x-ds.button>
            <x-ds.button variant="primary" type="submit">Cerrar</x-ds.button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
@vite('resources/js/caja-arqueo.js')
@endpush
