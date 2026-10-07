@props(['id'])
<div id="{{ $id }}" class="ds-flash ds-flash--error mb-3 mobile-error-summary mobile-error-summary--only" tabindex="-1" role="alert" @if(!$errors->any()) hidden @endif>
    <strong>No se guardó.</strong>
    <div>Revisá estos datos; lo que cargaste se conserva.</div>
    <ul>
        @foreach($errors->messages() as $campo => $mensajes)
            @foreach($mensajes as $mensaje)
                <li data-error-field="{{ $campo }}"><a href="#{{ $campo }}">{{ $mensaje }}</a></li>
            @endforeach
        @endforeach
    </ul>
</div>
