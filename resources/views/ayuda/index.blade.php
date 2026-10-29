@extends('layouts.ds-app')

@section('title', 'Ayuda y manual del usuario – Wings')
@section('module-title', 'Ayuda')

@section('content')

    <div class="mb-4">
        <p class="text-sm text-wings-muted">
            Guía de tareas paso a paso para el uso diario de Wings. Seleccioná la tarea que necesitás resolver para ver las instrucciones y capturas de pantalla.
        </p>
    </div>

    {{-- Buscador de ayuda --}}
    <form method="GET" action="{{ route('web.ayuda.index') }}" class="mb-4">
        <div class="filtros-card">
            <div class="filtros-row">
                <div class="search-input-group">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="{{ $busqueda ?? '' }}"
                           placeholder="Buscar por tarea, tema o palabra clave..."
                           class="filtros-control"
                           autocomplete="off">
                </div>
                <div class="filtros-actions">
                    <button type="submit" class="ds-btn ds-btn--primary">Buscar</button>
                    @if(!empty($busqueda))
                        <x-ds.button variant="secondary" href="{{ route('web.ayuda.index') }}">Limpiar</x-ds.button>
                    @endif
                </div>
            </div>
        </div>
    </form>

    @if(!empty($busqueda))
        <div class="stats-bar mb-3">
            <div class="stats-info">
                @if($total > 0)
                    Mostrando <strong>{{ $total }}</strong> {{ $total === 1 ? 'guía encontrada' : 'guías encontradas' }} para «{{ $busqueda }}»
                @else
                    <strong>0</strong> guías encontradas para «{{ $busqueda }}»
                @endif
            </div>
        </div>
    @endif

    @if(empty($agrupadas))
        <div class="empty-state" style="padding: 2.5rem 0; text-align: center;">
            <p style="color: var(--color-text-muted); font-size: 0.95rem;">
                @if(!empty($busqueda))
                    No se encontraron guías que coincidan con «{{ $busqueda }}». Probá con otra palabra o limpiá la búsqueda.
                @else
                    No hay guías disponibles para tu rol actualmente.
                @endif
            </p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @foreach($agrupadas as $categoria => $paginasCategoria)
                <section class="alumno-card" aria-label="{{ $categoria }}">
                    <div class="alumno-card-header" style="border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                        <span class="alumno-dot alumno-dot--info"></span>
                        <h2 class="alumno-nombre" style="font-size: 1.1rem; font-weight: 600;">{{ $categoria }}</h2>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach($paginasCategoria as $pag)
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px dashed var(--color-border);">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="{{ route('web.ayuda.show', $pag['slug']) }}"
                                           style="font-size: 0.95rem; font-weight: 600; color: var(--color-brand); text-decoration: none;">
                                            {{ $pag['titulo'] }}
                                        </a>
                                        @if(Auth::user()->isAdmin())
                                            <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: var(--color-surface-alt); color: var(--color-text-muted); border: 1px solid var(--color-border);">
                                                {{ $pag['rol'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($pag['resumen'])
                                        <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0.25rem 0 0 0; line-height: 1.35;">
                                            {{ $pag['resumen'] }}
                                        </p>
                                    @endif
                                </div>
                                <div style="flex-shrink: 0;">
                                    <x-ds.button variant="secondary" href="{{ route('web.ayuda.show', $pag['slug']) }}">
                                        Ver
                                    </x-ds.button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

@endsection
