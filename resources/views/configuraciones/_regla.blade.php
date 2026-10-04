<div class="alumno-card" id="regla-card-{{ $id }}" style="margin-bottom:12px;">

    <div class="alumno-card-header">
        <span class="alumno-dot alumno-dot--neutral"></span>
        <h3 class="alumno-nombre" id="regla-nombre-{{ $id }}" style="font-size:0.85rem;">
            {{ $nombre }}
        </h3>
    </div>

    <div class="alumno-info">
        <div class="info-item">
            <span class="info-label">Días:</span>
            <span class="info-value" id="regla-dias-{{ $id }}">
                {{ $desde }} al {{ $hasta }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Porcentaje:</span>
            <span class="info-value" id="regla-pct-{{ $id }}"
                  style="font-weight:700; color:var(--color-btn-primary);">
                {{ number_format($porcentaje, 0) }}%
            </span>
        </div>
    </div>

    <div class="alumno-actions">
        <x-ds.button variant="secondary" class="btn-editar-regla" data-id="{{ $id }}">Editar</x-ds.button>
        <x-ds.button variant="danger"    class="btn-eliminar-regla" data-id="{{ $id }}">Eliminar</x-ds.button>
    </div>

    {{-- Panel edición inline --}}
    <div id="panel-edit-{{ $id }}"
         style="display:none; border-top:1px solid var(--color-border); padding:16px 0 8px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;
                    max-width:480px; padding:0 0 0 1.5rem;">
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Nombre</label>
                <input type="text" id="edit-nombre-{{ $id }}"
                       value="{{ $nombre }}" maxlength="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Porcentaje (%)</label>
                <input type="number" id="edit-porcentaje-{{ $id }}"
                       value="{{ number_format($porcentaje, 0) }}" min="1" max="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día desde</label>
                <input type="number" id="edit-dia-desde-{{ $id }}"
                       value="{{ $desde }}" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día hasta</label>
                <input type="number" id="edit-dia-hasta-{{ $id }}"
                       value="{{ $hasta }}" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
        </div>
        <div id="edit-error-{{ $id }}"
             style="display:none; color:var(--color-danger); font-size:0.75rem; padding:6px 0 0 1.5rem;"></div>
        <div style="display:flex; gap:8px; margin-top:12px; padding-left:1.5rem;">
            <x-ds.button variant="primary"   class="btn-guardar-regla"  data-id="{{ $id }}">Guardar</x-ds.button>
            <x-ds.button variant="secondary" class="btn-cancelar-edit"  data-id="{{ $id }}">Cancelar</x-ds.button>
        </div>
    </div>

</div>
