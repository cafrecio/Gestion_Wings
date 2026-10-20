@php
    $estaAbierta = $liquidacion->estaAbierta();
    $esPagada = $liquidacion->estaPagada();
    $atrasadasPendientes = $liquidacion->detalles->filter(fn ($d) => $d->origen_cuota === 'atrasado'
        && (!$d->decision_atrasada || $d->decision_atrasada === 'PENDIENTE'))->count();
@endphp
@if($liquidacion->tipo === 'COMISION' && $liquidacion->detalles->contains('origen_cuota', 'atrasado'))
    <div data-liquidacion-actualizable="revision" class="filtros-card mb-3">
        <p class="text-sm font-semibold mb-2">Cuotas atrasadas</p>
        <p class="text-sm mb-2" style="color:var(--color-text-muted);">
            @if($atrasadasPendientes)
                Hay {{ $atrasadasPendientes }} {{ $atrasadasPendientes === 1 ? 'cuota para revisar' : 'cuotas para revisar' }}. Validá o rechazá cada una antes de cerrar. Solo las validadas se incluyen en el recibo.
            @else
                Revisión completa. El recibo incluye las cuotas atrasadas validadas.
            @endif
        </p>
        <p class="text-sm">No incluidas: <strong>{{ \App\Support\FormatoImporte::pesos((float)$liquidacion->total_atrasadas_excluidas) }}</strong>.
            Base confirmada: <strong>{{ \App\Support\FormatoImporte::pesos((float)$liquidacion->total_confirmado) }}</strong>.</p>
        @if($liquidacion->monto_final !== null)
            <p class="text-xs mt-2" style="color:var(--color-text-muted);">El monto final ajustado se conserva. La decisión cambia las cuotas incluidas en el recibo; el ajuste se muestra aparte.</p>
        @endif
    </div>
@endif
