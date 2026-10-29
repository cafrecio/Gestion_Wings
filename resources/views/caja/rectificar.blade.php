@extends('layouts.ds-app')
@section('title', 'Rectificar apertura – Wings')
@section('module-title', 'Rectificar apertura')
@section('ayuda-slug', 'rectificar-caja')
@php($title = 'Rectificar apertura')

@section('content')
@if($errors->any())
<div class="filtros-card mb-4" role="alert" style="border-left:4px solid var(--color-danger);">
    <p class="text-sm font-semibold" style="color:var(--color-danger)">No se pudo rectificar la apertura</p>
    @foreach($errors->all() as $error)<p class="text-sm mt-1">{{ $error }}</p>@endforeach
</div>
@endif

<div class="filtros-card">
    <p class="text-sm text-wings-muted mb-4">Corregí el efectivo recibido declarado al abrir la caja si se ingresó un valor erróneo.</p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div><span class="info-label">Responsable</span><p class="info-value">{{ $caja->usuarioOperativo->name ?? '–' }}</p></div>
        <div><span class="info-label">Apertura</span><p class="info-value">{{ $caja->apertura_at->format('d/m/Y H:i') }}</p></div>
        <div><span class="info-label">Efectivo declarado actual</span><p class="info-value">{{ \App\Support\FormatoImporte::pesos($caja->efectivo_inicial) }}</p></div>
    </div>

    <form method="POST" action="{{ route('web.caja.rectificar.guardar', $caja->id) }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="efectivo_inicial" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2 0-3 1-3 2s1 2 3 2 3 1 3 2-1 2-3 2m0-10v12"/></svg>
                    Nuevo efectivo recibido <span class="form-required">*</span>
                </label>
                <x-ds.money-input id="efectivo_inicial" name="efectivo_inicial" :value="old('efectivo_inicial', $caja->efectivo_inicial)" max="9999999999.99" required />
            </div>
            <div>
                <label for="motivo_rectificacion" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5V4h14v12h-3l-4 4z"/></svg>
                    Motivo de la rectificación <span class="form-required">*</span>
                </label>
                <input id="motivo_rectificacion" name="motivo_rectificacion" value="{{ old('motivo_rectificacion') }}" required maxlength="500" placeholder="Ej: Se tipeó un cero de más" class="w-full px-4 py-2.5 text-sm wings-input">
            </div>
        </div>

        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.resumen', $caja->id) }}">Volver</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
