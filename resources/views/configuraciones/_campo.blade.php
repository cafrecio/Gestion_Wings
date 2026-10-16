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
        <label class="form-label" for="cfg-{{ $clave }}">
            @if($clave === 'inscripcion_importe')
            <svg class="w-3.5 h-3.5 flex-shrink-0" aria-hidden="true" style="color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            @endif
            {{ $campo['etiqueta'] }}
        </label>
        @if($clave === 'inscripcion_importe')
        <x-ds.money-input id="cfg-{{ $clave }}" name="valor" :value="$valor"
                          min="0.01" max="99999999.99" :prefix="false" required class="p-3 mb-3"
                          aria-describedby="ayuda-{{ $clave }} error-{{ $clave }}"
                          aria-invalid="{{ $errorCampo ? 'true' : 'false' }}" />
        @else
        <input id="cfg-{{ $clave }}" name="valor" value="{{ $valor }}" type="{{ $campo['tipo'] }}"
               @if($clave === 'mora_porcentaje') inputmode="decimal" @endif
               class="wings-input w-full p-3 mb-3" maxlength="255"
               @if(isset($campo['min'])) min="{{ $campo['min'] }}" @endif
               @if(isset($campo['max'])) max="{{ $campo['max'] }}" @endif
               @if(isset($campo['step'])) step="{{ $campo['step'] }}" @endif
               @if(isset($campo['placeholder'])) placeholder="{{ $campo['placeholder'] }}" @endif
               @if($campo['obligatorio'] ?? false) required @endif
               aria-describedby="ayuda-{{ $clave }} error-{{ $clave }}"
               aria-invalid="{{ $errorCampo ? 'true' : 'false' }}">
        @endif
        <p id="ayuda-{{ $clave }}" class="text-wings-muted mb-3">{{ $campo['ayuda'] }}</p>
        <p id="error-{{ $clave }}" class="ds-flash ds-flash--error" @if(!$errorCampo) hidden @endif>
            <a href="#cfg-{{ $clave }}">{{ $errorCampo }}</a>
        </p>
        <div class="alumno-actions acciones-celular-derecha">
            <x-ds.button type="submit" variant="secondary">Guardar</x-ds.button>
            <span id="guardado-{{ $clave }}" class="text-wings-muted" role="status" hidden>Guardado</span>
        </div>
    </form>
</article>
