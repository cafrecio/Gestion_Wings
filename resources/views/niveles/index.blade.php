@extends('layouts.app')

@section('title', 'Niveles – Wings')
@section('module-title', 'Niveles')

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
        <strong>{{ $niveles->total() }}</strong> {{ $niveles->total() === 1 ? 'nivel' : 'niveles' }}
    </div>
    <a href="{{ route('web.niveles.create') }}" style="{{ $btnBPrim }}">Nuevo</a>
</div>

{{-- Listado --}}
@forelse($niveles as $nivel)

    <div class="alumno-card alumno-card--otro">

        <div class="alumno-card-header">
            <span class="alumno-dot alumno-dot--neutral"></span>
            <h3 class="alumno-nombre">{{ $nivel->nombre }}</h3>
        </div>

        @if($nivel->descripcion)
            <p style="font-size:0.8rem; color:var(--color-text-muted); padding-left:1.5rem; margin-top:2px; margin-bottom:4px;">
                {{ $nivel->descripcion }}
            </p>
        @endif

        <div class="alumno-info">
            <div class="info-item">
                <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="info-label">Grupos:</span>
                <span class="info-value">{{ $nivel->grupos_count }}</span>
            </div>
        </div>

        <div class="alumno-actions">
            <a href="{{ route('web.niveles.edit', $nivel->id) }}" style="{{ $btnBSec }}">Editar</a>
            <form method="POST" action="{{ route('web.niveles.destroy', $nivel->id) }}"
                  onsubmit="return confirm('¿Eliminar el nivel «{{ $nivel->nombre }}»?')">
                @csrf @method('DELETE')
                <button type="submit" style="{{ $btnBDang }}">Eliminar</button>
            </form>
        </div>

    </div>

@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
        </svg>
        <h3>Sin niveles creados</h3>
        <p>Creá el primer nivel para organizar los grupos.</p>
    </div>
@endforelse

{{-- Paginación --}}
@if($niveles->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $niveles->links() }}
    </div>
@endif

@endsection
