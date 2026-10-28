@extends('layouts.ds-app')

@section('title', $pagina['titulo'] . ' – Ayuda Wings')
@section('module-title', 'Ayuda: ' . $pagina['titulo'])

@section('content')

    {{-- Barra de acciones --}}
    <div class="stats-bar mb-4 ayuda-acciones-barra" style="display:flex; justify-content:space-between; align-items:center;">
        <div class="stats-info">
            <span style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:700; color:var(--color-text-muted);">
                {{ $pagina['categoria'] }} &bull; Rol: {{ ucfirst($pagina['rol']) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <x-ds.button variant="secondary" href="{{ route('web.ayuda.index') }}">
                Volver
            </x-ds.button>
            <x-ds.button variant="secondary" type="button" data-accion="imprimir">
                Imprimir
            </x-ds.button>
        </div>
    </div>

    {{-- Contenido del manual --}}
    <article class="alumno-card ayuda-documento" style="padding: 1.5rem 2rem;">
        @if($pagina['resumen'])
            <div style="background: var(--color-surface-alt); border-left: 4px solid var(--color-brand); padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                <p style="margin: 0; font-size: 0.9rem; color: var(--color-text); font-weight: 500;">
                    {{ $pagina['resumen'] }}
                </p>
            </div>
        @endif

        <div class="ayuda-markdown-cuerpo" style="line-height: 1.6; color: var(--color-text);">
            {!! $pagina['contenido_html'] !!}
        </div>
    </article>

    <style>
        .ayuda-markdown-cuerpo h1 { display: none; }
        .ayuda-markdown-cuerpo h2 { font-size: 1.25rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.25rem; }
        .ayuda-markdown-cuerpo h3 { font-size: 1.05rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .ayuda-markdown-cuerpo p { margin-bottom: 0.85rem; font-size: 0.9rem; }
        .ayuda-markdown-cuerpo ol, .ayuda-markdown-cuerpo ul { margin-left: 1.25rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .ayuda-markdown-cuerpo li { margin-bottom: 0.35rem; }
        .ayuda-markdown-cuerpo hr { border: 0; border-top: 1px solid var(--color-border); margin: 1.5rem 0; }
        .ayuda-markdown-cuerpo img { max-width: 100%; height: auto; border: 1px solid var(--color-border); border-radius: 6px; margin: 0.75rem 0 1.25rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .ayuda-markdown-cuerpo blockquote { border-left: 4px solid var(--color-brand); padding: 0.5rem 1rem; background: var(--color-surface-alt); border-radius: 4px; margin: 1rem 0; }

        @media print {
            .ds-sidebar,
            .ds-sidebar-overlay,
            .ds-topbar,
            .ds-module-header,
            .ayuda-acciones-barra,
            .ds-btn {
                display: none !important;
            }

            body, .ds-layout, .ds-main, .ds-content {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            .ayuda-documento {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                background: transparent !important;
            }

            .ayuda-markdown-cuerpo h1 {
                display: block !important;
                font-size: 1.75rem !important;
                margin-bottom: 1rem !important;
            }
        }
    </style>

@endsection
