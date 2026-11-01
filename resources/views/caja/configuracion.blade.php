@extends('layouts.ds-app')
@section('title', 'Saldo inicial de Efectivo – Wings')
@section('module-title', 'Saldo inicial de Efectivo')
@section('ayuda-slug', 'configurar-mostrador')
@php($title = 'Saldo inicial de Efectivo')
@php($etiqueta = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted')

@section('content')
<div class="filtros-card">
    @if($saldoEditable)
    <p class="text-sm text-wings-muted mb-4">Indicá cuánto efectivo tiene el club hoy. Una vez guardado no se puede cambiar: cualquier ajuste posterior se hace con un movimiento.</p>
    @else
    <p class="text-sm text-wings-muted mb-4">Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar. Cualquier ajuste se hace con un movimiento.</p>
    @endif
    <form method="POST" action="{{ route('web.caja.configuracion.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="cuenta" class="{{ $etiqueta }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18v12H3V7zm3-3h12M12 11v4"/></svg>
                    Cuenta
                </label>
                <input type="text" id="cuenta" value="{{ \App\Models\TipoCaja::NOMBRE_EFECTIVO }}" readonly class="w-full px-4 py-2.5 text-sm wings-input">
            </div>
            <div>
                <label for="saldo_inicial" class="{{ $etiqueta }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                    Saldo inicial @if($saldoEditable)<span class="form-required">*</span>@endif
                </label>
                @if($saldoEditable)
                <x-ds.money-input id="saldo_inicial" name="saldo_inicial" :value="old('saldo_inicial')" required />
                @error('saldo_inicial')<p class="text-xs mt-1" style="color:var(--color-danger)">{{ $message }}</p>@enderror
                @else
                <x-ds.money-input id="saldo_inicial" :value="$efectivo->saldo_inicial" readonly />
                @endif
            </div>
        </div>
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Volver</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
