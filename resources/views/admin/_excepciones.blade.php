<section id="excepciones" class="mt-4" aria-labelledby="titulo-excepciones">
    <div class="rv-section"><h2 id="titulo-excepciones">Excepciones justificadas</h2></div>
    <p class="text-sm text-wings-muted mb-3">Decisiones registradas por el equipo. Se incluyen juntas en el resumen diario, sin avisos individuales.</p>
    @forelse($excepciones as $excepcion)
        <article class="alumno-card mb-3">
            <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">{{ $excepcion->alumno?->apellido }}, {{ $excepcion->alumno?->nombre }}</h3></div>
            <div class="alumno-info" style="display:block;">
                <p class="text-sm font-semibold">{{ $excepcion->etiqueta() }}</p>
                <p class="text-sm">{{ $excepcion->motivo }}</p>
                <p class="text-xs text-wings-muted">{{ $excepcion->autor?->name }} · {{ $excepcion->ocurrida_at->format('d/m/Y H:i') }} · {{ $excepcion->registro_tipo === 'PAGO' ? 'Pago' : 'Asistencia' }} #{{ $excepcion->registro_id }}</p>
            </div>
            <div class="alumno-actions">
                <x-ds.button variant="secondary" href="{{ $excepcion->registro_tipo === 'ASISTENCIA' ? route('web.clases.show', $excepcion->contexto['clase_id']) : route('web.alumnos.show', $excepcion->alumno_id) }}">Ver</x-ds.button>
            </div>
        </article>
    @empty
        <p class="filtros-card text-sm text-wings-muted">Sin excepciones registradas.</p>
    @endforelse
    {{ $excepciones->fragment('excepciones')->links() }}
</section>
