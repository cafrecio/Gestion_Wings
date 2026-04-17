@extends('layouts.app')

@section('title', 'Editar Nivel – ' . $nivel->nombre)
@section('module-title', 'Editar Nivel: ' . $nivel->nombre)

@section('content')
<div class="filtros-card">
    <form method="POST" action="{{ route('web.niveles.update', $nivel->id) }}">
        @csrf
        @method('PUT')
        @include('niveles._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.niveles.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
