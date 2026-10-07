@extends('layouts.app')

@section('title', 'Editar – ' . $profesor->apellido . ', ' . $profesor->nombre)
@section('module-title', 'Editar: ' . $profesor->apellido . ', ' . $profesor->nombre)

@section('content')
<x-ds.mobile-errors id="profesor-error-resumen" />
<div class="filtros-card">
    <form class="mobile-form" method="POST" action="{{ route('web.profesores.update', $profesor->id) }}" data-error-summary="profesor-error-resumen">
        @csrf
        @method('PUT')
        @include('profesores._form')

        <div class="filtros-actions form-actions-divider mobile-form-actions mt-6 pt-4">
            <x-ds.button variant="secondary" href="{{ route('web.profesores.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
