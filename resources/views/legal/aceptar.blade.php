@extends('layouts.ds-app')

@section('title', 'Términos y privacidad – Wings')
@section('module-title', 'Términos y privacidad')

@section('content')

    @include('legal._documentos')

    <form method="POST" action="{{ route('web.legal.guardar') }}" class="alumno-card" style="padding: 1rem 2rem; margin-top: 1rem;">
        @csrf

        <label style="display:flex; gap:0.6rem; align-items:flex-start; font-size:0.9rem; cursor:pointer;">
            <input type="checkbox" name="acepto" value="1" required style="margin-top:0.25rem; accent-color: var(--color-brand);">
            <span>
                @if(auth()->user()->isAdmin())
                    Leí y acepto los términos y la política de privacidad, en nombre del club.
                @else
                    Leí y acepto los términos y la política de privacidad.
                @endif
            </span>
        </label>

        @error('acepto')
            <p style="color: var(--color-danger); font-size: 0.85rem; margin: 0.5rem 0 0;">{{ $message }}</p>
        @enderror

        <div class="alumno-actions" style="margin-top: 0.75rem;">
            <x-ds.button variant="primary" type="submit">Aceptar</x-ds.button>
        </div>
    </form>

@endsection
