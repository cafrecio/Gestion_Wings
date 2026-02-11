@extends('layouts.panel')

@section('title', 'Editar Alumno – Wings')

@section('panel-content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('web.alumnos.show', $alumno->id) }}" class="p-2 glass-card-sm text-wings-muted hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-xl font-semibold text-wings">Editar Alumno</h1>
    </div>

    <div class="glass-card p-6">
        <form method="POST" action="{{ route('web.alumnos.update', $alumno->id) }}">
            @csrf
            @method('PUT')
            @include('alumnos._form')

            <div class="flex items-center gap-3 mt-6 pt-4" style="border-top: 1px solid rgba(255,255,255,0.06);">
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white wings-btn cursor-pointer">
                    Guardar cambios
                </button>
                <a href="{{ route('web.alumnos.show', $alumno->id) }}" class="px-6 py-2.5 text-sm glass-card-sm text-wings-muted hover:text-white transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
