@extends('layouts.ds-app')

@section('title', 'Términos y privacidad – Wings')
@section('module-title', 'Términos y privacidad')

@section('content')

    <div class="stats-bar mb-4" style="display:flex; justify-content:space-between; align-items:center;">
        <div class="stats-info">
            @if($aceptacion)
                <span style="font-size:0.85rem; color:var(--color-text-muted);">Aceptado por {{ $aceptacion->nombre }} el {{ $aceptacion->aceptado_en->format('d/m/Y H:i') }}.</span>
            @endif
        </div>
        <x-ds.button variant="secondary" href="{{ route('web.ayuda.index') }}">Volver</x-ds.button>
    </div>

    @include('legal._documentos')

    @if($aviso)
        <article class="alumno-card" style="padding: 1.5rem 2rem; margin-top: 1rem;">
            <div class="legal-texto">{!! $aviso !!}</div>
        </article>
    @endif

@endsection
