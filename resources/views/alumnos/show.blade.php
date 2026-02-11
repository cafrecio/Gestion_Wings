@extends('layouts.panel')

@section('title', '{{ $alumno->apellido }}, {{ $alumno->nombre }} – Wings')

@section('panel-content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('web.alumnos.index') }}" class="p-2 glass-card-sm text-wings-muted hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl font-semibold text-wings">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h1>
            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full {{ $alumno->activo ? 'text-emerald-400' : 'text-red-400' }}"
                  style="background: {{ $alumno->activo ? 'rgba(52,211,153,0.12)' : 'rgba(248,113,113,0.12)' }};">
                {{ $alumno->activo ? 'Activo' : 'Inactivo' }}
            </span>
        </div>
        @if(Auth::user()->rol === 'ADMIN')
            <a href="{{ route('web.alumnos.edit', $alumno->id) }}" class="px-4 py-2 text-sm glass-card-sm text-wings-muted hover:text-white transition-colors inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
        @endif
    </div>

    <div class="glass-card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            {{-- Datos personales --}}
            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">DNI</p>
                <p class="text-sm text-wings">{{ $alumno->dni ?: '–' }}</p>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Fecha de nacimiento</p>
                <p class="text-sm text-wings">{{ $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->format('d/m/Y') : '–' }}</p>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Celular</p>
                <p class="text-sm text-wings">{{ $alumno->celular ?: '–' }}</p>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Email</p>
                <p class="text-sm text-wings">{{ $alumno->email ?: '–' }}</p>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Deporte</p>
                <p class="text-sm text-wings">{{ $alumno->deporte->nombre ?? '–' }}</p>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Grupo</p>
                <p class="text-sm text-wings">{{ $alumno->grupo->nombre ?? '–' }}</p>
            </div>

            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Fecha de alta</p>
                <p class="text-sm text-wings">{{ $alumno->fecha_alta ? $alumno->fecha_alta->format('d/m/Y') : '–' }}</p>
            </div>

            @if($alumno->planActivo)
            <div>
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Plan activo</p>
                <p class="text-sm text-wings">{{ $alumno->planActivo->clases_por_semana }} clase(s)/semana – ${{ number_format($alumno->planActivo->monto, 0, ',', '.') }}</p>
            </div>
            @endif
        </div>

        {{-- Tutor --}}
        @if($alumno->nombre_tutor)
            <div class="mt-6 pt-4" style="border-top: 1px solid rgba(255,255,255,0.06);">
                <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-3">Datos del tutor</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Nombre</p>
                        <p class="text-sm text-wings">{{ $alumno->nombre_tutor }}</p>
                    </div>
                    @if($alumno->telefono_tutor)
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-wings-muted mb-1">Teléfono</p>
                        <p class="text-sm text-wings">{{ $alumno->telefono_tutor }}</p>
                    </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
