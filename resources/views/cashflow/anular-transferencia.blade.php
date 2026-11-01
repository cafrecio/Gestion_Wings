@extends('layouts.ds-app')
@section('title', 'Anular transferencia – Cashflow – Wings')
@section('module-title', 'Anular transferencia')
@section('ayuda-slug', 'transferir-entre-cuentas')
@section('content')
<form method="POST" action="{{ route('web.cashflow.transferencia.anular.store', $movimientoId) }}">
    @csrf
    <section class="alumno-card">
        <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Transferencia #{{ $transferencia->id }}</h3></div>
        <div class="card-body-alineado" style="padding:0 24px 16px; font-size:0.85rem;">
            <p>{{ $transferencia->observaciones }}</p>
            <p class="mt-2"><strong class="ds-money ds-money--summary">{{ \App\Support\FormatoImporte::pesos($transferencia->monto) }}</strong> · {{ $fecha->format('d/m/Y') }}</p>
            <p class="mt-2">De <strong>{{ $transferencia->salida_nombre }}</strong> a <strong>{{ $transferencia->destino_nombre }}</strong>.</p>
            <p class="mt-3">Se anula la transferencia completa y se conserva el original. Vuelven {{ \App\Support\FormatoImporte::pesos($transferencia->monto) }} a {{ $transferencia->salida_nombre }} y se descuentan {{ \App\Support\FormatoImporte::pesos($transferencia->monto) }} de {{ $transferencia->destino_nombre }}, con fecha {{ $fecha->format('d/m/Y') }}. El reporte de {{ $fecha->translatedFormat('F') }} se actualizará.</p>
            <label for="motivo" class="info-label mt-3"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 002 2z"/></svg>Motivo <span class="form-required">*</span></label>
            <input id="motivo" name="motivo" maxlength="500" value="{{ old('motivo') }}" required class="w-full px-4 py-2.5 text-sm wings-input">
            @error('motivo')<p class="text-sm mt-2" style="color:var(--color-danger);">{{ $message }}</p>@enderror
        </div>
        <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end; border-top:1px solid var(--color-border); padding-top:12px;">
            <x-ds.button variant="secondary" style="width:96px; height:32px; font-size:0.82rem;" :href="route('web.cashflow.index', ['periodo' => 'mes', 'fecha' => $transferencia->fecha])">Volver</x-ds.button>
            <x-ds.button variant="danger" type="submit" style="width:96px; height:32px; font-size:0.82rem; background:none; color:var(--color-btn-danger); border-color:var(--color-btn-danger);">Anular</x-ds.button>
        </div>
    </section>
</form>
@endsection
