@php
    $estaAbierta = $liquidacion->estaAbierta();
    $esPagada = $liquidacion->estaPagada();
    $atrasadasPendientes = $liquidacion->detalles->filter(fn ($d) => $d->origen_cuota === 'atrasado'
        && (!$d->decision_atrasada || $d->decision_atrasada === 'PENDIENTE'))->count();
@endphp
            @if($estaAbierta && ($atrasadasPendientes || !$liquidacion->puedeCerrarse()))
                <p style="font-size:0.78rem; font-weight:600; color:var(--color-text-muted);">
                    @if($atrasadasPendientes)
                        Revisá las cuotas atrasadas para cerrar.
                    @else
                        Se puede cerrar desde el {{ $liquidacion->fechaHabilitacionCierre()->format('d/m/Y') }}
                    @endif
                </p>
            @endif

            <div class="acciones-celular-derecha liquidacion-acciones-movil" style="display:flex; justify-content:flex-end; align-items:center; gap:8px; flex-wrap:wrap; border-top:1px solid var(--color-border); margin-top:0.75rem; padding-top:0.75rem;">

                @if($estaAbierta)
                    @if(!$atrasadasPendientes && $liquidacion->puedeCerrarse())
                        <form method="POST" action="{{ route('web.liquidaciones.cerrar', $liquidacion->id) }}"
                              data-confirmar-envio="¿Cerrar esta liquidación? No podrá ser modificada después.">
                            @csrf
                            <button type="submit"
                                    style="display:inline-flex; align-items:center; justify-content:center;
                                           width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                           border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                           border:none; font-family:inherit;
                                           background:var(--color-btn-primary); color:var(--color-surface);">
                                Cerrar
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('web.liquidaciones.recalcular', $liquidacion->id) }}"
                          data-confirmar-envio="¿Recalcular? Los detalles actuales serán reemplazados.">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:var(--color-btn-secondary); color:var(--color-surface);">
                            Recalcular
                        </button>
                    </form>

                    <form method="POST" action="{{ route('web.liquidaciones.eliminar', $liquidacion->id) }}"
                          data-confirmar-envio="¿Eliminar esta liquidación permanentemente?">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:var(--color-btn-danger); color:var(--color-surface);">
                            Eliminar
                        </button>
                    </form>
                @endif

                @if($esPagada)
                <a href="{{ route('web.recibos.liquidacion', $liquidacion->id) }}?inline=1"
                   target="_blank"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          width:96px; height:32px; font-size:0.82rem; font-weight:600;
                          border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                          text-decoration:none; border:none; font-family:inherit;
                          background:var(--color-btn-secondary); color:var(--color-surface);">
                    Recibo
                </a>
                @endif

                <a href="{{ route('web.liquidaciones.index') }}"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          width:96px; height:32px; font-size:0.82rem; font-weight:600;
                          border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                          text-decoration:none; border:none; font-family:inherit;
                          background:var(--color-btn-secondary); color:var(--color-surface);">
                    Volver
                </a>
            </div>
