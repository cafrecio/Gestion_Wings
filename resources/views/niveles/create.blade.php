@extends('layouts.app')

@section('title', 'Nuevo Nivel – Wings')
@section('module-title', 'Nuevo Nivel')

@section('content')
<div class="filtros-card">
    @if($nivelesExistentes->isNotEmpty())
        <p style="font-size:0.72rem; color:var(--color-text-muted); margin-bottom:6px;">
            Niveles existentes: {{ $nivelesExistentes->pluck('nombre')->join(', ') }}
        </p>
    @endif

    <form method="POST" action="{{ route('web.niveles.store') }}">
        @csrf
        @include('niveles._form')

        <div class="filtros-actions mt-6 pt-4" style="border-top: 1px solid var(--color-border); justify-content: flex-end;">
            <x-ds.button variant="secondary" href="{{ route('web.niveles.index') }}">Cancelar</x-ds.button>
            <x-ds.button variant="primary" type="submit">Guardar</x-ds.button>
        </div>
    </form>
</div>
@endsection
