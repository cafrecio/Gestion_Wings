<section class="alumno-card mt-4" id="cashflow-historial-correcciones">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Historial de correcciones</h3></div>
    <div class="card-body-alineado" style="padding:0 24px 16px; font-size:0.85rem;">
    @forelse($historial as $cambio)
        <div style="margin-top:12px; border-top:1px solid var(--color-border); padding-top:12px;">
            <p><strong>{{ $cambio->accion === 'EDITAR' ? 'Edición' : 'Anulación' }}</strong> · {{ $cambio->created_at->format('d/m/Y H:i') }} · {{ $cambio->admin?->name }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                @foreach(['antes' => 'Antes', 'despues' => 'Después'] as $lado => $titulo)
                @php($foto = $cambio->$lado)
                <div>
                    <p class="info-label">{{ $titulo }}</p>
                    <p><strong>{{ \App\Support\FormatoImporte::pesos($foto['monto']) }}</strong> · {{ \Carbon\Carbon::parse($foto['fecha'])->format('d/m/Y') }}</p>
                    <p>{{ $foto['subrubro'] }} · {{ $foto['medio'] }}</p>
                    <p>{{ $foto['observaciones'] }}</p>
                    @if($foto['anulado'])<p style="color:var(--color-danger);">Original conservado · asiento en contra registrado</p>@endif
                </div>
                @endforeach
            </div>
            @if($cambio->motivo !== null && $cambio->motivo !== '')<p class="mt-2">Motivo: {{ $cambio->motivo }}</p>@endif
        </div>
    @empty
        <p>Este movimiento todavía no tiene correcciones.</p>
    @endforelse
    </div>
</section>
