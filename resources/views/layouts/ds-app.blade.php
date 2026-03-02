<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Wings')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="ds-layout">

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <aside class="ds-sidebar">

        {{-- Logo --}}
        <div class="pb-4 mb-4 border-b border-white/20">
            <a href="/">
                <img src="{{ asset('img/logo-wings.png') }}" alt="Wings">
            </a>
        </div>

        {{-- Nav: mismos links y roles que antes, sin estilos oscuros --}}
        <nav class="flex flex-col gap-1">
            @auth
                @if(Auth::user()->rol === 'ADMIN')
                    <a href="{{ route('admin.dashboard') }}"
                       class="ds-nav-link {{ request()->is('admin/dashboard') ? 'ds-nav-link--active' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/>
                        </svg>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('operativo.caja') }}"
                       class="ds-nav-link {{ request()->is('caja') ? 'ds-nav-link--active' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Caja
                    </a>
                @endif

                <a href="{{ route('web.alumnos.index') }}"
                   class="ds-nav-link {{ request()->is('alumnos*') ? 'ds-nav-link--active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                    Alumnos
                </a>
            @endauth
        </nav>

    </aside>

    {{-- ── Columna principal ────────────────────────────────────────────── --}}
    <div class="ds-main">

        {{-- Topbar --}}
        <div class="ds-topbar">
            @auth
                <span class="text-sm text-[var(--color-text-muted)]">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ds.button variant="ghost" type="submit">Salir</x-ds.button>
                </form>
            @endauth
        </div>

        {{-- Module Header: título del módulo actual (sin botones) --}}
        @isset($title)
            <x-ds.module-header :title="$title" />
        @endisset

        {{-- Contenido del módulo --}}
        <main class="ds-content">

            @if(session('success'))
                <div class="ds-flash ds-flash--success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="ds-flash ds-flash--error">{{ session('error') }}</div>
            @endif

            @yield('content')

        </main>

        {{-- Footer --}}
        <footer class="ds-footer">
            <div class="ds-footer__inner">Wings</div>
        </footer>

    </div>{{-- /.ds-main --}}

</div>{{-- /.ds-layout --}}

</body>
</html>
