@extends('layouts.app')

@section('title', 'Sin permiso – Wings')
@section('module-title', 'Sin permiso')

@section('content')
@php
    $inicio = match (auth()->user()?->rol) {
        'ADMIN' => route('admin.dashboard'),
        'OPERATIVO' => route('web.operativo.dashboard'),
        'PROFESOR' => route('web.clases.index'),
        default => route('login'),
    };
@endphp
<article class="alumno-card" aria-labelledby="sin-permiso">
    <div class="alumno-card-header">
        <h2 id="sin-permiso" class="alumno-nombre">No podés entrar a esta sección</h2>
    </div>
    <p class="text-wings-muted mb-3">No tenés permiso para abrir esta sección con tu usuario. Podés volver a tu inicio y seguir trabajando.</p>
    <div class="alumno-actions">
        <x-ds.button variant="secondary" href="{{ $inicio }}">Volver</x-ds.button>
    </div>
</article>
@endsection
