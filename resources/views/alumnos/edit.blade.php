@extends('layouts.app')

@section('title', 'Editar – ' . $alumno->apellido . ', ' . $alumno->nombre)
@section('module-title', 'Editar: ' . $alumno->apellido . ', ' . $alumno->nombre)

@section('content')

@php $tienePlanValido = $alumno->planActivo && $alumno->planActivo->plan; @endphp

@if(!$tienePlanValido)
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-danger);">
    <p style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-danger); margin-bottom:4px;">
        Sin plan activo
    </p>
    <p style="font-size:0.85rem; color:var(--color-text);">
        Este alumno no tiene un plan vigente. Asigná una frecuencia semanal para poder cobrarle.
    </p>
</div>
@endif

<div class="filtros-card">
    <form method="POST" action="{{ route('web.alumnos.update', $alumno->id) }}">
        @csrf
        @method('PUT')
        @include('alumnos._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.alumnos.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
