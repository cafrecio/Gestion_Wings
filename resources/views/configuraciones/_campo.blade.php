@php
    $campo = $campos[$clave];
    $config = $configuraciones->get($clave);
    $errorCampo = $claveError === $clave ? $errors->first('valor') : '';
    $valor = $claveError === $clave ? old('valor') : ($config?->valor ?? '');
@endphp
<article class="alumno-card">
    <div class="alumno-card-header"><h3 class="alumno-nombre">{{ $campo['titulo'] }}</h3></div>
    <p class="text-wings-muted mb-3">{{ $campo['descripcion'] }}</p>
    <form method="POST" action="{{ route('web.configuraciones.update', $clave) }}"
          class="configuracion-form" data-clave="{{ $clave }}" data-titulo="{{ $campo['titulo'] }}"
          data-valor-guardado="{{ $config?->valor ?? '' }}" novalidate>
        @csrf
        @method('PATCH')
        <label class="form-label" for="cfg-{{ $clave }}">{{ $campo['etiqueta'] }}</label>
        <input id="cfg-{{ $clave }}" name="valor" value="{{ $valor }}" type="{{ $campo['tipo'] }}"
               class="wings-input w-full p-3 mb-3" maxlength="255"
               @if(isset($campo['min'])) min="{{ $campo['min'] }}" @endif
               @if(isset($campo['max'])) max="{{ $campo['max'] }}" @endif
               @if(isset($campo['step'])) step="{{ $campo['step'] }}" @endif
               @if(isset($campo['placeholder'])) placeholder="{{ $campo['placeholder'] }}" @endif
               @if($campo['obligatorio'] ?? false) required @endif
               aria-describedby="ayuda-{{ $clave }} error-{{ $clave }}"
               aria-invalid="{{ $errorCampo ? 'true' : 'false' }}">
        <p id="ayuda-{{ $clave }}" class="text-wings-muted mb-3">{{ $campo['ayuda'] }}</p>
        <p id="error-{{ $clave }}" class="ds-flash ds-flash--error" @if(!$errorCampo) hidden @endif>
            <a href="#cfg-{{ $clave }}">{{ $errorCampo }}</a>
        </p>
        <div class="alumno-actions">
            <x-ds.button type="submit" variant="secondary">Guardar</x-ds.button>
            <span id="guardado-{{ $clave }}" class="text-wings-muted" role="status" hidden>Guardado</span>
        </div>
    </form>
</article>
