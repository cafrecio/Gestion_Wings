@extends('layouts.ds-app')
@section('title', 'Movimiento – Cashflow – Wings')
@section('module-title', 'Movimiento')
@section('content')
<section class="alumno-card">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">{{ $movimiento->subrubro?->nombre }}</h3></div>
    <div class="alumno-info" style="grid-template-columns:repeat(3,minmax(0,1fr));">
        <div><span class="info-label">Fecha</span><p class="info-value">{{ $movimiento->fecha->format('d/m/Y') }}</p></div>
        <div><span class="info-label">Medio</span><p class="info-value">{{ $movimiento->tipoCaja?->nombre }}</p></div>
        <div><span class="info-label">Importe</span><p class="info-value">{{ \App\Support\FormatoImporte::pesos($movimiento->monto) }}</p></div>
    </div>
    <p style="padding:0 24px 16px; font-size:0.85rem;">{{ $movimiento->observaciones }}</p>
    @if($movimiento->anulado_at)<p style="padding:0 24px 16px; color:var(--color-danger);">Anulado · el original y el asiento en contra se conservan.</p>@endif
    <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end; border-top:1px solid var(--color-border); padding-top:12px;">
        <x-ds.button variant="secondary" style="width:96px; height:32px; font-size:0.82rem;" :href="route('web.cashflow.index')">Volver</x-ds.button>
        @if($movimiento->puedeCorregirse())
        <x-ds.button variant="secondary" style="width:96px; height:32px; font-size:0.82rem;" :href="route('web.cashflow.movimientos.editar', $movimiento->id)">Editar</x-ds.button>
        @endif
    </div>
</section>
@include('cashflow._historial')
@endsection
