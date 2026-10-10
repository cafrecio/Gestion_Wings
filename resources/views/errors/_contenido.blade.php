<article class="alumno-card" aria-labelledby="error-titulo">
    <div class="alumno-card-header">
        <h2 id="error-titulo" class="alumno-nombre">{{ $mensaje }}</h2>
    </div>
    <p class="text-wings-muted mb-3">{{ $ayuda }}</p>
    <div class="alumno-actions">
        <x-ds.button variant="secondary" href="{{ $inicio }}">Volver</x-ds.button>
    </div>
</article>
