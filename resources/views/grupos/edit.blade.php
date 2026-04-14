@extends('layouts.app')

@section('title', 'Editar – ' . $grupo->nombre . ' – Wings')
@section('module-title', 'Editar: ' . $grupo->nombre)

@section('content')
<div class="filtros-card">
    <form method="POST" action="{{ route('web.grupos.update', $grupo->id) }}">
        @csrf
        @method('PUT')
        @include('grupos._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.grupos.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
