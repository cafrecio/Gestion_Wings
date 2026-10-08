@extends('layouts.app')

@section('title', 'Usuarios – Wings')
@section('module-title', 'Usuarios')

@section('content')
<style>
 .usuarios-cuenta-propia { display:inline-flex; align-items:center; gap:10px; min-height:32px; }
 @media(max-width:639px) {
  .alumno-actions.usuarios-actions { display:grid; grid-template-columns:96px; justify-content:end; padding-right:calc(.5rem - 1px); }
  .usuarios-actions > .ds-toggle, .usuarios-actions > .usuarios-cuenta-propia { justify-self:end; }
 }
</style>

@php
    $btnB = 'display:inline-flex; align-items:center; justify-content:center;'
          . ' width:96px; height:32px; font-size:0.82rem; font-weight:600;'
          . ' border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;'
          . ' text-decoration:none; border:none; font-family:inherit;';
    $btnBSec = $btnB . ' background:var(--color-btn-secondary); color:var(--color-surface);';

    $rolColors = [
        'ADMIN'     => 'var(--color-btn-primary)',
        'OPERATIVO' => 'var(--color-success)',
        'PROFESOR'  => 'var(--color-warning)',
    ];
    $rolLabels = \App\Models\User::getRoles();
@endphp

{{-- Stats bar --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $usuarios->total() }}</strong> {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}
    </div>
    <x-ds.button variant="primary" href="{{ route('web.usuarios.create') }}">Nuevo</x-ds.button>
</div>

{{-- Listado --}}
@forelse($usuarios as $usuario)
@php
    $esSelf  = auth()->id() === $usuario->id;
    $activo  = (bool) $usuario->activo;
    $color   = $rolColors[$usuario->rol] ?? 'var(--color-text-muted)';
    $label   = $rolLabels[$usuario->rol] ?? $usuario->rol;
    $dotClass = $activo ? 'alumno-dot--success' : 'alumno-dot--danger';
@endphp

<div class="alumno-card" style="{{ !$activo ? 'opacity:0.6;' : '' }}">

    <div class="alumno-card-header">
        <span class="alumno-dot {{ $dotClass }}"></span>
        <h3 class="alumno-nombre">
            {{ $usuario->name }}
            @if($esSelf)
                <span style="font-size:0.7rem; font-weight:500; color:var(--color-text-muted); margin-left:6px;">(vos)</span>
            @endif
        </h3>
    </div>

    <div class="alumno-info">
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $usuario->email }}</span>
        </div>
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            <span class="info-label">Rol:</span>
            <span class="info-value"
                  style="color:{{ $color }}; font-weight:700;
                         background:color-mix(in srgb, {{ $color }} 12%, transparent);
                         padding:1px 8px; border-radius:999px; font-size:0.72rem;">
                {{ $label }}
            </span>
        </div>
        @if($usuario->rol === 'PROFESOR' && $usuario->profesor_id && $usuario->profesor)
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="info-label">Profesor:</span>
            <span class="info-value">{{ $usuario->profesor->apellido }}, {{ $usuario->profesor->nombre }}</span>
        </div>
        @endif
    </div>

    <div class="alumno-actions usuarios-actions">
        <a href="{{ route('web.usuarios.edit', $usuario->id) }}" style="{{ $btnBSec }}">Editar</a>

        @if($esSelf)
            <span class="usuarios-cuenta-propia" title="No podés desactivar tu propia cuenta.">
                <span style="display:flex; flex-direction:column; align-items:flex-end; line-height:1.2;">
                    <strong style="color:var(--color-text); font-size:0.875rem;">{{ $activo ? 'Activo' : 'Inactivo' }}</strong>
                    <span style="color:var(--color-text-muted); font-size:0.7rem;">Tu cuenta</span>
                </span>
                <svg aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4M6 11h12a1 1 0 011 1v8a1 1 0 01-1 1H6a1 1 0 01-1-1v-8a1 1 0 011-1z" />
                </svg>
            </span>
        @else
        <x-ds.toggle
            labelOn="Activo"
            labelOff="Inactivo"
            :checked="$activo"
            :disabled="$esSelf"
            data-url="{{ route('web.usuarios.toggle-activo', $usuario->id) }}"
        />
        @endif
    </div>

</div>

@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h3>Sin usuarios</h3>
        <p>No hay usuarios registrados.</p>
    </div>
@endforelse

{{-- Paginación --}}
@if($usuarios->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $usuarios->links() }}
    </div>
@endif

@endsection
