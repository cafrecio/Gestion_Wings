<div id="alumno-error-resumen" class="{{ $errors->any() ? 'ds-flash ds-flash--error ' : '' }}mb-3" tabindex="-1" role="alert" @if(!$errors->any()) hidden @endif>
    <strong>No se guardó.</strong>
    <div>Revisá estos datos; lo que cargaste se conserva.</div>
    <ul>
        @foreach($errors->messages() as $campo => $mensajes)
            @foreach($mensajes as $mensaje)
                <li><a href="#{{ $campo === 'generar_cuota_actual' ? 'cuota-alta-aviso' : $campo }}">{{ $mensaje }}</a></li>
            @endforeach
        @endforeach
    </ul>
</div>
