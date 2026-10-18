@extends('layouts.ds-app')
@section('title', 'Eliminar movimiento – Cashflow – Wings')
@section('module-title', 'Eliminar movimiento')
@section('content')
<form method="POST" action="{{ route('web.cashflow.movimientos.destroy', $movimiento->id) }}">
    @csrf
    @method('DELETE')
    <input type="hidden" name="clave" value="{{ old('clave', $clave) }}">
    <input type="hidden" name="version" value="{{ old('version', $movimiento->version_correccion) }}">
    <section class="alumno-card">
        <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">{{ $movimiento->subrubro?->nombre }}</h3></div>
        <div class="card-body-alineado" style="padding:0 24px 16px; font-size:0.85rem;">
            <p>{{ $movimiento->observaciones }}</p>
            <p class="mt-2"><strong>{{ \App\Support\FormatoImporte::pesos($movimiento->monto) }}</strong> · {{ $movimiento->fecha->format('d/m/Y') }} · {{ $movimiento->tipoCaja?->nombre }}</p>
            <p class="mt-3">Se conserva el original y se crea un asiento en contra. El reporte de {{ $movimiento->fecha->format('m/Y') }} se actualizará.</p>
            <label for="motivo" class="info-label mt-3"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2z"/></svg>Motivo (opcional)</label>
            <input id="motivo" name="motivo" maxlength="500" value="{{ old('motivo') }}" class="w-full px-4 py-2.5 text-sm wings-input">
            @foreach(['motivo', 'version', 'clave'] as $campo)
                @error($campo)<p class="text-sm mt-2" style="color:var(--color-danger);">{{ $message }}</p>@enderror
            @endforeach
        </div>
        <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end; border-top:1px solid var(--color-border); padding-top:12px;">
            <x-ds.button variant="secondary" style="width:96px; height:32px; font-size:0.82rem;" :href="route('web.cashflow.index')">Cancelar</x-ds.button>
            <x-ds.button variant="danger" type="submit" style="width:96px; height:32px; font-size:0.82rem; background:none; color:var(--color-btn-danger); border-color:var(--color-btn-danger);">Eliminar</x-ds.button>
        </div>
    </section>
</form>
@endsection
