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
            <a href="/" style="display:block; text-align:center; text-decoration:none;">
                <span style="font-size:1.5rem; font-weight:800; letter-spacing:0.08em; color:#fff;">WINGS</span>
            </a>
        </div>

        {{-- Nav --}}
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

                @if(Auth::user()->rol === 'ADMIN')
                <a href="{{ route('web.profesores.index') }}"
                   class="ds-nav-link {{ request()->is('profesores*') ? 'ds-nav-link--active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profesores
                </a>
                @endif

                {{-- Separador visual --}}
                <div style="height:1px; background:rgba(255,255,255,0.1); margin: 6px 0;"></div>

                <a href="{{ route('web.grupos.index') }}"
                   class="ds-nav-link {{ request()->is('grupos*') ? 'ds-nav-link--active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Grupos
                </a>

                @if(Auth::user()->rol === 'ADMIN')
                    <a href="{{ route('web.deportes.index') }}"
                       class="ds-nav-link {{ request()->is('deportes*') ? 'ds-nav-link--active' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Deportes
                    </a>

                    <a href="{{ route('web.rubros.index') }}"
                       class="ds-nav-link {{ request()->is('rubros*') ? 'ds-nav-link--active' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Rubros
                    </a>
                @endif
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

        {{-- Module Header: título del módulo actual (sin botones, siempre) --}}
        @hasSection('module-title')
            <x-ds.module-header :title="$__env->yieldContent('module-title')" />
        @endif

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

@stack('scripts')

<script>
/* ── money-input: formato numérico con separador de miles (punto) ── */
(function () {
    function toDisplay(raw) {
        // Eliminar todo excepto dígitos
        const digits = raw.replace(/\D/g, '');
        if (!digits) return '';
        return Number(digits).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    function toRaw(display) {
        return display.replace(/\./g, '').replace(/,/g, '');
    }

    function initMoneyInput(input) {
        // Formatear al mostrar la página
        if (input.value) {
            input.value = toDisplay(input.value);
        }

        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            const before = this.value.slice(0, pos).replace(/\./g, '').length;
            const raw = toRaw(this.value);
            this.value = raw ? toDisplay(raw) : '';
            // Reposicionar cursor aproximadamente
            let count = 0, newPos = 0;
            for (let i = 0; i < this.value.length; i++) {
                if (this.value[i] !== '.') count++;
                if (count === before) { newPos = i + 1; break; }
            }
            this.setSelectionRange(newPos, newPos);
        });
    }

    function stripMoneyInputs(form) {
        form.querySelectorAll('[data-money="true"]').forEach(function (input) {
            input.value = toRaw(input.value);
        });
    }

    function init() {
        document.querySelectorAll('[data-money="true"]').forEach(initMoneyInput);
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () { stripMoneyInputs(form); });
        });
    }

    // Exponer para uso externo (filas dinámicas)
    window.initMoneyInput = initMoneyInput;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
</body>
</html>
