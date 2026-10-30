@extends('layouts.ds-app')

@section('title', 'Transferir – Cashflow – Wings')
@section('module-title', 'Transferir')
@section('ayuda-slug', 'transferir-entre-cuentas')

@section('content')
<form method="POST" action="{{ route('web.cashflow.transferencia.store') }}">
    @csrf
    <input type="hidden" name="clave" value="{{ old('clave', $clave) }}">
    <div class="filtros-card mb-4">
        <p class="text-sm mb-4" style="color:var(--color-text-muted);">Mové plata entre cuentas del club. La salida y la entrada se guardan juntas, por el mismo importe.</p>
        @error('clave')<p class="text-sm mb-3" style="color:var(--color-danger);">{{ $message }}</p>@enderror
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(['salida_id' => 'Cuenta de salida', 'destino_id' => 'Cuenta de destino'] as $campo => $etiqueta)
            <div>
                <label for="{{ $campo }}" class="block text-xs font-medium mb-1.5 text-wings-muted">{{ $etiqueta }} <span class="form-required">*</span></label>
                <select id="{{ $campo }}" name="{{ $campo }}" required class="w-full px-4 py-2.5 text-sm wings-input">
                    <option value="">Seleccionar...</option>
                    @foreach($tiposCaja as $cuenta)
                    <option value="{{ $cuenta->id }}" @selected(old($campo) == $cuenta->id)>{{ $cuenta->abreviatura ? $cuenta->abreviatura.' — ' : '' }}{{ $cuenta->nombre }}</option>
                    @endforeach
                </select>
                @error($campo)<p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>@enderror
            </div>
            @endforeach
            <div>
                <label for="monto" class="block text-xs font-medium mb-1.5 text-wings-muted">Importe <span class="form-required">*</span></label>
                <x-ds.money-input id="monto" name="monto" :value="old('monto', '')" min="0.01" max="9999999999.99" :prefix="false" required />
                @error('monto')<p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="fecha" class="block text-xs font-medium mb-1.5 text-wings-muted">Fecha <span class="form-required">*</span></label>
                <input id="fecha" name="fecha" type="date" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="w-full px-4 py-2.5 text-sm wings-input">
                @error('fecha')<p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="observaciones" class="block text-xs font-medium mb-1.5 text-wings-muted">Observación</label>
                <textarea id="observaciones" name="observaciones" maxlength="500" rows="2" class="w-full px-4 py-2.5 text-sm wings-input">{{ old('observaciones') }}</textarea>
                @error('observaciones')<p class="text-xs mt-1" style="color:var(--color-danger);">{{ $message }}</p>@enderror
            </div>
        </div>
        @if(session('aviso_fecha_vieja'))
            <div class="mt-4 p-4" style="border:1px solid var(--color-warning); border-radius:var(--radius-btn);">
                <p class="text-sm font-semibold mb-2">Atención: fecha de un mes anterior</p>
                <p class="text-sm" style="color:var(--color-text-muted);">{{ session('aviso_fecha_vieja') }}</p>
                <input type="hidden" name="confirmar_fecha_vieja" value="1">
            </div>
        @endif
        <div class="flex justify-end gap-2 mt-4 acciones-celular-derecha">
            <x-ds.button variant="secondary" href="{{ route('web.cashflow.index') }}">Volver</x-ds.button>
            <x-ds.button type="submit" variant="primary">{{ session('aviso_fecha_vieja') ? 'Confirmar' : 'Transferir' }}</x-ds.button>
        </div>
    </div>
</form>
@endsection
