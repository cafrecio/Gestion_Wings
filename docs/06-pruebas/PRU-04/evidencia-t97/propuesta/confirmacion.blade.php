<form method="POST" action="#propuesta-sin-operacion">
    <section class="alumno-card">
        <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Transferencia #{{ $id }}</h3></div>
        <div class="card-body-alineado" style="padding:0 24px 16px; font-size:0.85rem;">
            <p>Depósito del efectivo en el banco</p>
            <p class="mt-2"><strong class="ds-money ds-money--summary">$300,000.00</strong> · 20/10/2026</p>
            <p class="mt-2">De <strong>Efectivo</strong> a <strong>Banco Nación</strong>.</p>
            <p class="mt-3">Se anula la transferencia completa y se conserva el original. Vuelven $300,000.00 a Efectivo y se descuentan $300,000.00 de Banco Nación, con fecha 20/10/2026. El reporte de octubre se actualizará.</p>
            <label for="motivo" class="info-label mt-3"><svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline-block; vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2z"/></svg>Motivo <span class="form-required">*</span></label>
            <input id="motivo" name="motivo" maxlength="500" value="{{ $motivo }}" required class="w-full px-4 py-2.5 text-sm wings-input">
        </div>
        <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end; border-top:1px solid var(--color-border); padding-top:12px;">
            <x-ds.button variant="secondary" style="width:96px; height:32px; font-size:0.82rem;" href="#volver">Volver</x-ds.button>
            <x-ds.button variant="danger" type="submit" style="width:96px; height:32px; font-size:0.82rem; background:none; color:var(--color-btn-danger); border-color:var(--color-btn-danger);">Anular</x-ds.button>
        </div>
    </section>
</form>
