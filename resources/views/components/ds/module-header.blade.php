@props(['title'])

<header class="ds-module-header">
    <div class="ds-module-header__inner">
        @if(isset($icon) && $icon->isNotEmpty())
            <span class="ds-module-header__icon" aria-hidden="true">{{ $icon }}</span>
        @endif
        <h1 class="ds-module-header__title">{{ $title }}</h1>
    </div>
</header>
