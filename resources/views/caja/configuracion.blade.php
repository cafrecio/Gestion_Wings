@extends('layouts.ds-app')
@section('title', 'Efectivo del mostrador – Wings')
@section('module-title', 'Efectivo del mostrador')
@php($title = 'Efectivo del mostrador')

@section('content')
<div class="filtros-card">
    <p class="text-sm text-wings-muted mb-4">Elegí una sola vez el medio de pago del cajón compartido. Las transferencias y los cobros directos de ADMIN no forman parte de este efectivo.</p>
    <form method="POST" action="{{ route('web.caja.configuracion.store') }}">
        @csrf
        <label for="tipo_caja_id" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18v12H3V7zm3-3h12M12 11v4"/></svg>
            Medio de efectivo <span class="form-required">*</span>
        </label>
        <select id="tipo_caja_id" name="tipo_caja_id" class="w-full px-4 py-2.5 text-sm wings-input" required @disabled($propuesta['tipo_caja_id'] !== null)>
            <option value="">Seleccionar...</option>
            @foreach($tiposCaja as $tipo)
            <option value="{{ $tipo->id }}" @selected(old('tipo_caja_id', $propuesta['tipo_caja_id']) == $tipo->id)>{{ $tipo->nombre }}</option>
            @endforeach
        </select>
        @error('tipo_caja_id')<p class="text-xs mt-1" style="color:var(--color-danger)">{{ $message }}</p>@enderror
        @if($propuesta['tipo_caja_id'] !== null)<p class="text-xs text-wings-muted mt-2">Ya configurado. No se cambia sobre turnos registrados.</p>@endif
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Volver</x-ds.button>
            @if($propuesta['tipo_caja_id'] === null)<x-ds.button variant="primary" type="submit">Guardar</x-ds.button>@endif
        </div>
    </form>
</div>
@endsection
