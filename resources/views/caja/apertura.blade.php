@extends('layouts.ds-app')
@section('title', 'Apertura de caja – Wings')
@section('module-title', 'Apertura de caja')
@php($title = 'Apertura de caja')

@section('content')
@if($errors->any())
<div class="filtros-card mb-4" role="alert" style="border-left:4px solid var(--color-danger);">
    <p class="text-sm font-semibold" style="color:var(--color-danger)">No se abrió la caja</p>
    @foreach($errors->all() as $error)<p class="text-sm mt-1">{{ $error }}</p>@endforeach
</div>
@endif
<div class="filtros-card">
    @if($turnoAbierto)
        <p class="text-sm">Hay un turno abierto de <strong>{{ $turnoAbierto->usuarioOperativo->name }}</strong>. Debe cerrarse antes de abrir el siguiente.</p>
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Volver</x-ds.button>
            @if(auth()->user()->isAdmin() || $turnoAbierto->usuario_operativo_id === auth()->id())
            <x-ds.button variant="primary" href="{{ route('web.caja.cierre', $turnoAbierto->id) }}">Cerrar</x-ds.button>
            @endif
        </div>
    @elseif(!$tipoEfectivo || !$tipoEfectivo->activo)
        <p class="text-sm">ADMIN debe configurar un medio de efectivo activo antes de abrir el cajón.</p>
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Volver</x-ds.button>
            @if(auth()->user()->isAdmin())<x-ds.button variant="primary" href="{{ route('web.caja.configuracion') }}">Configurar</x-ds.button>@endif
        </div>
    @else
    <p class="text-sm text-wings-muted mb-4">Contá el efectivo recibido en el cajón. No es un ingreso del club.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div><span class="info-label">Medio</span><p class="info-value">{{ $tipoEfectivo->nombre }}</p></div>
        <div><span class="info-label">Último cierre</span><p class="info-value">{{ $propuesta['caja_origen_id'] ? 'Caja #'.$propuesta['caja_origen_id'].' · $'.number_format($propuesta['efectivo_heredado'], 2, ',', '.') : 'Primera apertura: declarar el efectivo recibido' }}</p></div>
    </div>
    <form method="POST" action="{{ route('web.caja.abrir') }}">
        @csrf
        <input type="hidden" name="caja_origen_id" value="{{ $propuesta['caja_origen_id'] }}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(auth()->user()->isAdmin())
            <div>
                <label for="operativo_id" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0"/></svg>
                    Responsable del turno <span class="form-required">*</span>
                </label>
                <select id="operativo_id" name="operativo_id" required class="w-full px-4 py-2.5 text-sm wings-input">
                    <option value="">Seleccionar...</option>
                    @foreach($operativos as $operativo)<option value="{{ $operativo->id }}" @selected(old('operativo_id') == $operativo->id)>{{ $operativo->name }}</option>@endforeach
                </select>
            </div>
            @endif
            <div>
                <label for="efectivo_inicial" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2 0-3 1-3 2s1 2 3 2 3 1 3 2-1 2-3 2m0-10v12"/></svg>
                    Efectivo recibido <span class="form-required">*</span>
                </label>
                <x-ds.money-input id="efectivo_inicial" name="efectivo_inicial" :value="old('efectivo_inicial', $propuesta['efectivo_heredado'] ?? '')" required />
            </div>
            <div>
                <label for="motivo_apertura" class="flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-btn-primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5V4h14v12h-3l-4 4z"/></svg>
                    Motivo si recibiste otro importe
                </label>
                <input id="motivo_apertura" name="motivo_apertura" value="{{ old('motivo_apertura') }}" maxlength="500" class="w-full px-4 py-2.5 text-sm wings-input">
            </div>
        </div>
        <label class="flex items-start gap-2 text-sm mt-4">
            <input type="checkbox" name="confirmacion" value="1" required @checked(old('confirmacion')) class="mt-1">
            <span>Confirmo que conté y recibí este efectivo.</span>
        </label>
        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.caja.index') }}">Volver</x-ds.button>
            <x-ds.button variant="primary" type="submit">Abrir</x-ds.button>
        </div>
    </form>
    @endif
</div>
@endsection
