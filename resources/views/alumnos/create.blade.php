@extends('layouts.app')

@section('title', 'Nuevo Alumno – Wings')
@section('module-title', 'Nuevo Alumno')

@section('content')
@include('alumnos._errores')
<div class="filtros-card">
    <form method="POST" action="{{ route('web.alumnos.store') }}" data-alumno-form data-con-errores="{{ $errors->any() ? '1' : '0' }}">
        @csrf
        @include('alumnos._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.alumnos.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
