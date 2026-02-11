@extends('layouts.panel')

@section('title', 'Alumnos – Wings')

@section('panel-content')
<div class="max-w-6xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-wings">Alumnos</h1>
        @if(Auth::user()->rol === 'ADMIN')
            <a href="{{ route('web.alumnos.create') }}" class="px-4 py-2 text-sm font-medium text-white wings-btn inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo
            </a>
        @endif
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('web.alumnos.index') }}" class="glass-card-sm p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, apellido o DNI..."
                   class="w-full px-3 py-2 text-sm wings-input">

            <select name="deporte_id" class="w-full px-3 py-2 text-sm wings-input cursor-pointer">
                <option value="">Todos los deportes</option>
                @foreach($deportes as $deporte)
                    <option value="{{ $deporte->id }}" {{ request('deporte_id') == $deporte->id ? 'selected' : '' }}>{{ $deporte->nombre }}</option>
                @endforeach
            </select>

            <select name="grupo_id" class="w-full px-3 py-2 text-sm wings-input cursor-pointer">
                <option value="">Todos los grupos</option>
                @foreach($grupos as $grupo)
                    <option value="{{ $grupo->id }}" {{ request('grupo_id') == $grupo->id ? 'selected' : '' }}>{{ $grupo->nombre }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white wings-btn cursor-pointer">Filtrar</button>
                <a href="{{ route('web.alumnos.index') }}" class="px-3 py-2 text-sm glass-card-sm text-wings-muted hover:text-white transition-colors flex items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>
        </div>
    </form>

    {{-- Cards Grid --}}
    @if($alumnos->isEmpty())
        <div class="glass-card p-8 text-center">
            <p class="text-wings-muted text-sm">No se encontraron alumnos.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($alumnos as $alumno)
                <div class="glass-card p-5 flex flex-col justify-between">
                    <div>
                        {{-- Nombre + Badge --}}
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-sm font-semibold text-wings">{{ $alumno->apellido }}, {{ $alumno->nombre }}</h3>
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full {{ $alumno->activo ? 'text-emerald-400' : 'text-red-400' }}"
                                  style="background: {{ $alumno->activo ? 'rgba(52,211,153,0.12)' : 'rgba(248,113,113,0.12)' }};">
                                {{ $alumno->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        {{-- Info --}}
                        <div class="space-y-1.5 text-xs text-wings-muted">
                            @if($alumno->dni)
                                <div class="flex items-center gap-2">
                                    <span class="text-wings-soft w-12">DNI</span>
                                    <span class="text-wings-soft">{{ $alumno->dni }}</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <span class="text-wings-soft w-12">Dep.</span>
                                <span>{{ $alumno->deporte->nombre ?? '–' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-wings-soft w-12">Grupo</span>
                                <span>{{ $alumno->grupo->nombre ?? '–' }}</span>
                            </div>
                            @if($alumno->nombre_tutor)
                                <div class="flex items-center gap-2">
                                    <span class="text-wings-soft w-12">Tutor</span>
                                    <span>{{ $alumno->nombre_tutor }}</span>
                                </div>
                            @endif
                            @if($alumno->telefono_tutor)
                                <div class="flex items-center gap-2">
                                    <span class="text-wings-soft w-12">Tel.</span>
                                    <span>{{ $alumno->telefono_tutor }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center gap-2 mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.06);">
                        <a href="{{ route('web.alumnos.show', $alumno->id) }}" class="px-3 py-1.5 text-xs glass-card-sm text-wings-muted hover:text-white transition-colors">
                            Ver
                        </a>
                        @if(Auth::user()->rol === 'ADMIN')
                            <a href="{{ route('web.alumnos.edit', $alumno->id) }}" class="px-3 py-1.5 text-xs glass-card-sm text-wings-muted hover:text-white transition-colors">
                                Editar
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        <div class="mt-6">
            {{ $alumnos->links() }}
        </div>
    @endif
</div>
@endsection
