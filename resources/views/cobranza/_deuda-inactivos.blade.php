<section data-deuda-inactivos style="margin-top:24px;">
    <h2 class="text-lg font-semibold mb-2">Deuda de alumnos inactivos</h2>
    <p class="text-sm mb-3" style="color:var(--color-text-muted);">Actual al {{ \Carbon\Carbon::parse($deudaInactivos['fecha'])->format('d/m/Y') }}. Separada del por cobrar y de los totales de Inicio.</p>
    <div class="stats-bar mb-3">
        <div class="stats-info"><strong data-inactivos-cantidad>{{ count($deudaInactivos['filas']) }}</strong> alumnos</div>
        <span class="ds-money ds-money--total" data-inactivos-total>{{ \App\Support\FormatoImporte::pesos($deudaInactivos['total_centavos'] / 100) }}</span>
    </div>
    <div data-inactivos-lista>
    @foreach($deudaInactivos['filas'] as $fila)
        <article class="alumno-card mb-3" data-inactivo-fila data-centavos="{{ $fila['centavos'] }}">
            <div class="alumno-card-header"><span class="alumno-dot alumno-dot--neutral"></span><h3 class="alumno-nombre">{{ $fila['nombre'] }}</h3></div>
            <div class="alumno-info" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                <div><span class="text-xs" style="color:var(--color-text-muted);">Inactivo desde</span><p class="text-sm">{{ $fila['inactivo_desde'] ? \Carbon\Carbon::parse($fila['inactivo_desde'])->format('d/m/Y') : 'Sin fecha registrada' }}</p></div>
                <div style="text-align:right;"><span class="text-xs" style="color:var(--color-text-muted);">Deuda</span><p class="ds-money ds-money--row">{{ \App\Support\FormatoImporte::pesos($fila['centavos'] / 100) }}</p></div>
            </div>
            <p data-inactivo-error role="alert" hidden style="color:var(--color-danger);"></p>
            <div class="alumno-actions" style="border-top:1px solid var(--color-border);justify-content:flex-end;">
                <a class="ds-btn ds-btn--secondary" href="{{ route('web.alumnos.show',$fila['id']) }}">Ver</a>
                @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('web.inactivos.perder',$fila['id']) }}" data-perder-deuda data-confirmar-envio="¿Dar por perdida la deuda de {{ $fila['nombre'] }} por {{ \App\Support\FormatoImporte::pesos($fila['centavos'] / 100) }}? No vuelve a cobrarse al reactivar.">
                    @csrf
                    <input type="hidden" name="centavos" value="{{ $fila['centavos'] }}">
                    <button type="submit" class="ds-btn ds-btn--danger" title="Dar por perdida esta deuda">Perder</button>
                </form>
                @endif
            </div>
        </article>
    @endforeach
    </div>
    <p data-inactivos-vacio @if(count($deudaInactivos['filas'])) hidden @endif class="text-sm" style="color:var(--color-text-muted);">Sin deuda de alumnos inactivos.</p>
    <p data-inactivos-aviso role="status" class="text-sm" style="color:var(--color-text-muted);"></p>
</section>
@once
@push('scripts')
@vite('resources/js/deuda-inactivos.js')
@endpush
@endonce
