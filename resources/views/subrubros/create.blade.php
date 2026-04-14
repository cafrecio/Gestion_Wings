@extends('layouts.app')

@section('title', 'Nuevo Subrubro – Wings')
@section('module-title', 'Nuevo Subrubro en: ' . $rubro->nombre)

@section('content')
<div class="filtros-card">
    <form method="POST" action="{{ route('web.subrubros.store', $rubro->id) }}">
        @csrf
        @include('subrubros._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.rubros.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
