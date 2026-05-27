@extends('layouts.app')

@section('title', 'Editar Usuario – Wings')
@section('module-title', 'Editar Usuario')

@section('content')

<div class="filtros-card">
    <form method="POST" action="{{ route('web.usuarios.update', $usuario->id) }}">
        @csrf
        @method('PUT')

        @include('usuarios._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top:1px solid var(--color-border); justify-content:flex-end;">
            <x-ds.button variant="secondary" tabindex="8" href="{{ route('web.usuarios.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" tabindex="9" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>

@endsection
