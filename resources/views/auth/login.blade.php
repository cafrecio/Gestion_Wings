@extends('layouts.app')

@section('title', 'Ingresar – Wings')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm glass-card p-8">

        {{-- Logo --}}
        <div class="flex justify-center mb-8">
            <img src="{{ asset('img/logo-wings.png') }}" alt="Wings" class="h-20 w-auto">
        </div>

        {{-- Título --}}
        <h1 class="text-center text-lg font-semibold mb-6 text-wings">Iniciar sesión</h1>

        {{-- Error --}}
        @if ($errors->any())
            <div class="glass-card-sm mb-5 px-4 py-3 text-sm" style="border-color: rgba(230, 37, 47, 0.3);">
                <p class="text-wings-soft">{{ $errors->first() }}</p>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ url('/login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-xs font-medium mb-1.5 text-wings-muted">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    autofocus
                    class="w-full px-4 py-3 text-sm wings-input"
                    placeholder="tu@email.com"
                >
            </div>

            <div class="mb-5">
                <label for="password" class="block text-xs font-medium mb-1.5 text-wings-muted">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full px-4 py-3 text-sm wings-input"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center mb-6">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    class="mr-2 rounded"
                    style="accent-color: #E6252F;"
                >
                <label for="remember" class="text-xs cursor-pointer text-wings-muted">Recordarme</label>
            </div>

            <button
                type="submit"
                class="w-full py-3 text-sm font-semibold text-white cursor-pointer wings-btn"
            >
                Ingresar
            </button>
        </form>
    </div>
</div>
@endsection
