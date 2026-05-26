@extends('layouts.app')

@section('title', 'Tipos de Caja – Wings')
@section('module-title', 'Tipos de Caja')

@section('content')

@php
    $btnB = 'display:inline-flex; align-items:center; justify-content:center;'
          . ' width:96px; height:32px; font-size:0.82rem; font-weight:600;'
          . ' border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;'
          . ' text-decoration:none; border:none; font-family:inherit;';
    $btnBSec  = $btnB . ' background:var(--color-btn-secondary); color:var(--color-surface);';
    $btnBDang = $btnB . ' background:var(--color-btn-danger);    color:var(--color-surface);';
    $btnBPrim = $btnB . ' background:var(--color-btn-primary);   color:var(--color-surface);';
@endphp

{{-- Stats bar --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $tiposCaja->total() }}</strong> {{ $tiposCaja->total() === 1 ? 'tipo de caja' : 'tipos de caja' }}
    </div>
    <a href="{{ route('web.tipos-caja.create') }}" style="{{ $btnBPrim }}">Nuevo</a>
</div>

{{-- Listado --}}
@forelse($tiposCaja as $tipoCaja)

    <div class="alumno-card">

        <div class="alumno-card-header">
            <span class="alumno-dot alumno-dot--neutral"></span>
            <h3 class="alumno-nombre">{{ $tipoCaja->nombre }}</h3>
        </div>

        @if($tipoCaja->descripcion)
            <p style="font-size:0.8rem; color:var(--color-text-muted); padding-left:1.5rem; margin-top:2px; margin-bottom:4px;">
                {{ $tipoCaja->descripcion }}
            </p>
        @endif

        <div class="alumno-info">
            <div class="info-item">
                <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span class="info-label">Mov. operativos:</span>
                <span class="info-value">{{ $tipoCaja->movimientos_operativos_count }}</span>
            </div>
            <div class="info-item">
                <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="info-label">Mov. cashflow:</span>
                <span class="info-value">{{ $tipoCaja->cashflow_movimientos_count }}</span>
            </div>
            <div class="info-item">
                <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span class="info-label">Descubierto:</span>
                <span class="info-value" style="color:{{ $tipoCaja->permite_descubierto ? 'var(--color-warning)' : 'var(--color-text-muted)' }}; font-weight:600;">
                    {{ $tipoCaja->permite_descubierto ? 'Sí' : 'No' }}
                </span>
            </div>
        </div>

        <div class="alumno-actions">
            <a href="{{ route('web.tipos-caja.edit', $tipoCaja->id) }}" style="{{ $btnBSec }}">Editar</a>
            <form method="POST" action="{{ route('web.tipos-caja.destroy', $tipoCaja->id) }}"
                  onsubmit="return confirm('¿Eliminar el tipo de caja «{{ $tipoCaja->nombre }}»?')">
                @csrf @method('DELETE')
                <button type="submit" style="{{ $btnBDang }}">Eliminar</button>
            </form>
        </div>

    </div>

@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <h3>Sin tipos de caja</h3>
        <p>Creá el primer tipo de caja para clasificar los movimientos.</p>
    </div>
@endforelse

{{-- Paginación --}}
@if($tiposCaja->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $tiposCaja->links() }}
    </div>
@endif

@endsection
