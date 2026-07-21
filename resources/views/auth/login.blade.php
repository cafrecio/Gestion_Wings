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
                <div style="position:relative;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-3 text-sm wings-input"
                        placeholder="••••••••"
                        style="padding-right: 2.75rem;"
                    >
                    <button type="button" id="toggle-password" aria-label="Mostrar contraseña"
                            style="position:absolute; right:0.6rem; top:50%; transform:translateY(-50%);
                                   background:none; border:none; cursor:pointer; padding:6px;
                                   display:flex; color:var(--color-text-muted, #6b7280);">
                        <svg id="icon-eye" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg id="icon-eye-off" class="w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
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

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('toggle-password');
    var input = document.getElementById('password');
    var eye = document.getElementById('icon-eye');
    var eyeOff = document.getElementById('icon-eye-off');
    if (!btn || !input) return;

    btn.addEventListener('click', function () {
        var mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        eye.style.display = mostrar ? 'none' : 'block';
        eyeOff.style.display = mostrar ? 'block' : 'none';
        btn.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
})();
</script>
@endpush
@endsection
