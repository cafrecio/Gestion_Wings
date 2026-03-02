@props([
    'href'  => null,
    'rail'  => null,   // patin | futbol | otro
    'dot'   => null,   // success | warning | danger | neutral | active
    'cols'  => 4,      // 3 | 4 | 5
    'lines' => 1,      // 1 | 2
])

<article {{ $attributes->class(['ds-card', 'ds-surface']) }}>

    {{-- Overlay link (cubre header + info; actions quedan encima vía z-index) --}}
    @if($href)
        <a class="ds-card__link"
           href="{{ $href }}"
           aria-label="Ver: {{ strip_tags((string) $title) }}"
           tabindex="0"></a>
    @endif

    {{-- Rail izquierdo por deporte --}}
    @if($rail)
        <div class="ds-card__rail ds-rail ds-rail--{{ $rail }}"></div>
    @endif

    <div class="ds-card__content">

        {{-- Header: dot opcional + título --}}
        <div class="ds-card__header">
            @if($dot)
                <span class="ds-dot ds-dot--{{ $dot }}"
                      role="img"
                      aria-label="{{ $dot }}"></span>
            @endif
            {{ $title }}
        </div>

        {{-- Grid de info --}}
        @if(isset($info) && $info->isNotEmpty())
            <div class="ds-card__info" data-cols="{{ $cols }}" data-lines="{{ $lines }}">
                {{ $info }}
            </div>
        @endif

        {{-- Acciones: posicionadas encima del overlay --}}
        @if(isset($actions) && $actions->isNotEmpty())
            <div class="ds-card__actions">
                {{ $actions }}
            </div>
        @endif

    </div>
</article>
