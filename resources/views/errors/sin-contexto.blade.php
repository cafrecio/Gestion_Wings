<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Wings')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/css/sidebar.css', 'resources/js/app.js'])
</head>
<body>
<div class="ds-layout">
    <aside class="ds-sidebar">
        <div class="ds-brand">
            <a href="{{ route('login') }}" class="ds-brand__link">
                <img src="{{ asset('img/logo-wings.png') }}" alt="" class="ds-brand__logo">
                <span class="ds-brand__name">Wings</span>
            </a>
        </div>
    </aside>
    <div class="ds-main">
        <div class="ds-topbar"></div>
        <x-ds.module-header :title="$__env->yieldContent('module-title')" />
        <main class="ds-content">
            @yield('content')
        </main>
        <footer class="ds-footer">
            <div class="ds-footer__inner">Wings</div>
        </footer>
    </div>
</div>
</body>
</html>
