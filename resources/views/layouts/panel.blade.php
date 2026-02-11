@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col">
    {{-- Topbar --}}
    <header class="wings-header fixed top-0 left-0 right-0 z-40 flex items-center justify-between px-4 py-3">
        <div class="flex items-center gap-3">
            {{-- Hamburger --}}
            <button id="sidebar-toggle" class="p-2 rounded-lg text-wings-muted hover:text-white transition-colors cursor-pointer" style="background: rgba(255,255,255,0.04);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('img/logo-wings.png') }}" alt="Wings" class="h-8 w-auto">
            </a>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-wings-muted hidden sm:inline">{{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 text-xs cursor-pointer glass-card-sm text-wings-muted hover:text-white transition-colors">
                    Salir
                </button>
            </form>
        </div>
    </header>

    <div class="flex flex-1 pt-14">
        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed top-14 left-0 bottom-0 z-30 w-56 transition-transform duration-200 -translate-x-full lg:translate-x-0" style="background: rgba(7, 6, 10, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-right: 1px solid rgba(255,255,255,0.06);">
            <nav class="flex flex-col gap-1 p-3 mt-2">
                @if(Auth::user()->rol === 'ADMIN')
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ request()->is('admin/dashboard') ? 'text-white' : 'text-wings-muted hover:text-white' }}"
                       style="{{ request()->is('admin/dashboard') ? 'background: rgba(230,37,47,0.15); border: 1px solid rgba(230,37,47,0.2);' : 'background: transparent;' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/>
                        </svg>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('operativo.caja') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ request()->is('caja') ? 'text-white' : 'text-wings-muted hover:text-white' }}"
                       style="{{ request()->is('caja') ? 'background: rgba(230,37,47,0.15); border: 1px solid rgba(230,37,47,0.2);' : 'background: transparent;' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Caja
                    </a>
                @endif

                <a href="{{ route('web.alumnos.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ request()->is('alumnos*') ? 'text-white' : 'text-wings-muted hover:text-white' }}"
                   style="{{ request()->is('alumnos*') ? 'background: rgba(230,37,47,0.15); border: 1px solid rgba(230,37,47,0.2);' : 'background: transparent;' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                    Alumnos
                </a>
            </nav>
        </aside>

        {{-- Overlay mobile --}}
        <div id="sidebar-overlay" class="fixed inset-0 z-20 bg-black/50 hidden lg:hidden" style="top: 56px;"></div>

        {{-- Content --}}
        <main class="flex-1 lg:ml-56 p-4 sm:p-6">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="glass-card-sm mb-4 px-4 py-3 text-sm text-wings-soft" style="border-color: rgba(34, 197, 94, 0.3);">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="glass-card-sm mb-4 px-4 py-3 text-sm text-wings-soft" style="border-color: rgba(230, 37, 47, 0.3);">
                    {{ session('error') }}
                </div>
            @endif

            @yield('panel-content')
        </main>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('sidebar-toggle');

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });
</script>
@endsection
