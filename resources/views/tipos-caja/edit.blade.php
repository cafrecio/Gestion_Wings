@extends('layouts.app')

@section('title', 'Editar Tipo de Caja – ' . $tipoCaja->nombre)
@section('module-title', 'Editar Tipo de Caja: ' . $tipoCaja->nombre)

@section('content')
<div class="filtros-card">
    <form method="POST" action="{{ route('web.tipos-caja.update', $tipoCaja->id) }}">
        @csrf
        @method('PUT')
        @include('tipos-caja._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.tipos-caja.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
