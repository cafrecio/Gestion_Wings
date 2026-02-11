@extends('layouts.app')

@section('title', 'Acceso denegado – Wings')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm text-center glass-card p-8">
        <div class="flex justify-center mb-6">
            <img src="{{ asset('img/logo-wings.png') }}" alt="Wings" class="h-16 w-auto opacity-40">
        </div>

        <div class="mb-2 text-4xl font-semibold" style="color: rgba(230, 37, 47, 0.8);">403</div>
        <h1 class="text-base font-medium mb-2 text-wings">Acceso denegado</h1>
        <p class="text-sm mb-6 text-wings-muted">No tenés permisos para acceder a esta página.</p>

        <a href="/login" class="inline-block px-6 py-3 text-sm font-semibold text-white wings-btn">
            Volver al inicio
        </a>
    </div>
</div>
@endsection
