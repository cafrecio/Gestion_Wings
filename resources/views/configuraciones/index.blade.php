@extends('layouts.app')

@section('title', 'Configuración – Wings')
@section('module-title', 'Configuración')

@section('content')

{{-- ── Parámetros generales ───────────────────────────────────────── --}}
@foreach($configuraciones as $config)

<div class="alumno-card" style="margin-bottom:12px;">

    <div class="alumno-card-header">
        <span class="alumno-dot alumno-dot--neutral"></span>
        <h3 class="alumno-nombre" style="font-size:0.85rem;">{{ $config->clave }}</h3>
    </div>

    @if($config->descripcion)
    <p style="font-size:0.78rem; color:var(--color-text-muted); padding-left:1.5rem; margin-bottom:12px;">
        {{ $config->descripcion }}
    </p>
    @endif

    <div style="padding:0 0 4px 1.5rem; display:flex; align-items:center; gap:12px;">

        @if($config->tipo === 'boolean')
            <x-ds.toggle
                labelOn="Sí"
                labelOff="No"
                :checked="filter_var($config->valor, FILTER_VALIDATE_BOOLEAN)"
                data-clave="{{ $config->clave }}"
            />
        @elseif($config->tipo === 'integer')
            <input type="number"
                   id="cfg-{{ $config->clave }}"
                   value="{{ $config->valor }}"
                   min="1" max="31"
                   data-clave="{{ $config->clave }}"
                   class="wings-input cfg-field"
                   style="width:80px; padding:6px 10px; text-align:center; font-size:0.9rem; font-weight:600;">
        @else
            <input type="text"
                   id="cfg-{{ $config->clave }}"
                   value="{{ $config->valor }}"
                   maxlength="255"
                   data-clave="{{ $config->clave }}"
                   class="wings-input cfg-field"
                   style="width:240px; padding:6px 10px; font-size:0.9rem;">
        @endif

        <span id="flash-{{ $config->clave }}"
              style="display:none; font-size:0.75rem; font-weight:600; color:var(--color-success);">
            Guardado
        </span>

    </div>

</div>

@endforeach

{{-- ── Sección: Reglas de primer cobro ───────────────────────────── --}}
<div style="margin-top:28px; padding-top:8px; border-top:1px solid var(--color-border); margin-bottom:8px;">
    <h4 style="font-size:0.78rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase;
               color:var(--color-text-muted); margin:0 0 4px;">
        Reglas de primer cobro
    </h4>
    <p style="font-size:0.78rem; color:var(--color-text-muted); margin:0;">
        Define qué porcentaje se cobra según el día de inscripción del alumno.
    </p>
</div>

<div class="stats-bar mb-3">
    <div class="stats-info">
        <span id="reglas-count"><strong>{{ $reglasPrimerPago->count() }}</strong> {{ $reglasPrimerPago->count() === 1 ? 'regla configurada' : 'reglas configuradas' }}</span>
    </div>
    <x-ds.button variant="primary" id="btn-agregar-regla">Agregar</x-ds.button>
</div>

<div id="reglas-list">
@foreach($reglasPrimerPago as $regla)

<div class="alumno-card" id="regla-card-{{ $regla->id }}" style="margin-bottom:12px;">

    <div class="alumno-card-header">
        <span class="alumno-dot alumno-dot--neutral"></span>
        <h3 class="alumno-nombre" id="regla-nombre-{{ $regla->id }}" style="font-size:0.85rem;">
            {{ $regla->nombre }}
        </h3>
    </div>

    <div class="alumno-info">
        <div class="info-item">
            <span class="info-label">Días:</span>
            <span class="info-value" id="regla-dias-{{ $regla->id }}">
                {{ $regla->dia_desde }} al {{ $regla->dia_hasta }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Porcentaje:</span>
            <span class="info-value" id="regla-pct-{{ $regla->id }}"
                  style="font-weight:700; color:var(--color-btn-primary);">
                {{ number_format($regla->porcentaje, 0) }}%
            </span>
        </div>
    </div>

    <div class="alumno-actions">
        <x-ds.button variant="secondary" class="btn-editar-regla" data-id="{{ $regla->id }}">Editar</x-ds.button>
        <x-ds.button variant="danger"    class="btn-eliminar-regla" data-id="{{ $regla->id }}">Eliminar</x-ds.button>
    </div>

    {{-- Panel edición inline --}}
    <div id="panel-edit-{{ $regla->id }}"
         style="display:none; border-top:1px solid var(--color-border); padding:16px 0 8px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;
                    max-width:480px; padding:0 0 0 1.5rem;">
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Nombre</label>
                <input type="text" id="edit-nombre-{{ $regla->id }}"
                       value="{{ $regla->nombre }}" maxlength="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Porcentaje (%)</label>
                <input type="number" id="edit-porcentaje-{{ $regla->id }}"
                       value="{{ number_format($regla->porcentaje, 0) }}" min="1" max="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día desde</label>
                <input type="number" id="edit-dia-desde-{{ $regla->id }}"
                       value="{{ $regla->dia_desde }}" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día hasta</label>
                <input type="number" id="edit-dia-hasta-{{ $regla->id }}"
                       value="{{ $regla->dia_hasta }}" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;">
            </div>
        </div>
        <div id="edit-error-{{ $regla->id }}"
             style="display:none; color:var(--color-danger); font-size:0.75rem; padding:6px 0 0 1.5rem;"></div>
        <div style="display:flex; gap:8px; margin-top:12px; padding-left:1.5rem;">
            <x-ds.button variant="primary"   class="btn-guardar-regla"  data-id="{{ $regla->id }}">Guardar</x-ds.button>
            <x-ds.button variant="secondary" class="btn-cancelar-edit"  data-id="{{ $regla->id }}">Cancelar</x-ds.button>
        </div>
    </div>

</div>

@endforeach
</div>{{-- /#reglas-list --}}

{{-- Panel agregar nueva regla --}}
<div id="panel-add-regla" style="display:none; margin-bottom:12px;">
    <div class="alumno-card">
        <div class="alumno-card-header">
            <span class="alumno-dot alumno-dot--neutral"></span>
            <h3 class="alumno-nombre" style="font-size:0.85rem;">Nueva regla</h3>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;
                    max-width:480px; padding:0 0 12px 1.5rem;">
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Nombre</label>
                <input type="text" id="add-nombre" maxlength="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="Ej: Primera quincena">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Porcentaje (%)</label>
                <input type="number" id="add-porcentaje" min="1" max="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="100">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día desde</label>
                <input type="number" id="add-dia-desde" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="1">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día hasta</label>
                <input type="number" id="add-dia-hasta" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="31">
            </div>
        </div>
        <div id="add-error"
             style="display:none; color:var(--color-danger); font-size:0.75rem; padding:0 0 8px 1.5rem;"></div>
        <div class="alumno-actions">
            <x-ds.button variant="primary"   id="btn-guardar-add">Guardar</x-ds.button>
            <x-ds.button variant="secondary" id="btn-cancelar-add">Cancelar</x-ds.button>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ── Configuraciones generales: blur/change ──────────────────── */
    function showFlash(clave) {
        var el = document.getElementById('flash-' + clave);
        if (!el) return;
        el.style.display = 'inline';
        el.style.opacity = '';
        el.style.transition = '';
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(function () { el.style.display = 'none'; el.style.opacity = ''; }, 400);
        }, 2000);
    }

    function saveConfig(clave, valor) {
        fetch('/configuraciones/' + encodeURIComponent(clave), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json', 'Content-Type': 'application/json',
            },
            body: JSON.stringify({ valor: valor }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { if (data.ok) showFlash(clave); });
    }

    document.querySelectorAll('.cfg-field').forEach(function (input) {
        var original = input.value;
        input.addEventListener('blur', function () {
            if (this.value !== original) { saveConfig(this.dataset.clave, this.value); original = this.value; }
        });
    });

    document.querySelectorAll('.ds-toggle[data-clave]').forEach(function (label) {
        var input = label.querySelector('.ds-toggle__input');
        if (!input) return;
        input.addEventListener('change', function () { saveConfig(label.dataset.clave, input.checked ? '1' : '0'); });
    });

    /* ── Reglas de primer cobro ──────────────────────────────────── */
    function toggleEditPanel(id) {
        var panel = document.getElementById('panel-edit-' + id);
        if (!panel) return;
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function guardarEditRegla(id) {
        var nombre     = (document.getElementById('edit-nombre-'     + id) || {}).value || '';
        var diaDesde   = (document.getElementById('edit-dia-desde-'  + id) || {}).value || '';
        var diaHasta   = (document.getElementById('edit-dia-hasta-'  + id) || {}).value || '';
        var porcentaje = (document.getElementById('edit-porcentaje-' + id) || {}).value || '';
        var errorEl    = document.getElementById('edit-error-' + id);

        fetch('/configuraciones/primer-pago/' + id, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json', 'Content-Type': 'application/json',
            },
            body: JSON.stringify({ nombre: nombre.trim(), dia_desde: diaDesde, dia_hasta: diaHasta, porcentaje: porcentaje }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.errors || data.error) {
                var msg = data.message || data.error || 'Error al guardar.';
                if (errorEl) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
                return;
            }
            if (errorEl) errorEl.style.display = 'none';
            var nombreEl = document.getElementById('regla-nombre-' + id);
            var diasEl   = document.getElementById('regla-dias-'   + id);
            var pctEl    = document.getElementById('regla-pct-'    + id);
            if (nombreEl) nombreEl.textContent = data.nombre;
            if (diasEl)   diasEl.textContent   = data.dia_desde + ' al ' + data.dia_hasta;
            if (pctEl)    pctEl.textContent     = Math.round(data.porcentaje) + '%';
            toggleEditPanel(id);
        })
        .catch(function () {
            if (errorEl) { errorEl.textContent = 'Error de conexión.'; errorEl.style.display = 'block'; }
        });
    }

    function eliminarRegla(id) {
        if (!confirm('¿Eliminar esta regla?')) return;
        fetch('/configuraciones/primer-pago/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) { alert(data.error); return; }
            var card = document.getElementById('regla-card-' + id);
            if (card) card.remove();
            actualizarContador(-1);
        });
    }

    function actualizarContador(delta) {
        var el = document.getElementById('reglas-count');
        if (!el) return;
        var n = (document.getElementById('reglas-list').querySelectorAll('.alumno-card').length);
        el.innerHTML = '<strong>' + n + '</strong> ' + (n === 1 ? 'regla configurada' : 'reglas configuradas');
    }

    /* Event delegation sobre #reglas-list */
    var list = document.getElementById('reglas-list');
    if (list) {
        list.addEventListener('click', function (e) {
            var editBtn   = e.target.closest('.btn-editar-regla');
            var saveBtn   = e.target.closest('.btn-guardar-regla');
            var cancelBtn = e.target.closest('.btn-cancelar-edit');
            var elimBtn   = e.target.closest('.btn-eliminar-regla');
            if (editBtn)   toggleEditPanel(editBtn.dataset.id);
            if (saveBtn)   guardarEditRegla(saveBtn.dataset.id);
            if (cancelBtn) toggleEditPanel(cancelBtn.dataset.id);
            if (elimBtn)   eliminarRegla(elimBtn.dataset.id);
        });
    }

    /* ── Panel agregar ───────────────────────────────────────────── */
    var panelAdd = document.getElementById('panel-add-regla');

    document.getElementById('btn-agregar-regla').addEventListener('click', function () {
        if (panelAdd) panelAdd.style.display = panelAdd.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('btn-cancelar-add').addEventListener('click', function () {
        if (panelAdd) panelAdd.style.display = 'none';
    });

    document.getElementById('btn-guardar-add').addEventListener('click', function () {
        var nombre     = (document.getElementById('add-nombre')     || {}).value || '';
        var diaDesde   = (document.getElementById('add-dia-desde')  || {}).value || '';
        var diaHasta   = (document.getElementById('add-dia-hasta')  || {}).value || '';
        var porcentaje = (document.getElementById('add-porcentaje') || {}).value || '';
        var errorEl    = document.getElementById('add-error');

        fetch('/configuraciones/primer-pago', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json', 'Content-Type': 'application/json',
            },
            body: JSON.stringify({ nombre: nombre.trim(), dia_desde: diaDesde, dia_hasta: diaHasta, porcentaje: porcentaje }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.errors || data.error) {
                var msg = data.message || data.error || 'Error al guardar.';
                if (errorEl) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
                return;
            }
            if (errorEl) errorEl.style.display = 'none';

            var listEl = document.getElementById('reglas-list');
            if (listEl) listEl.insertAdjacentHTML('beforeend', buildReglaCard(data));
            actualizarContador(1);

            ['add-nombre','add-dia-desde','add-dia-hasta','add-porcentaje'].forEach(function (fid) {
                var el = document.getElementById(fid);
                if (el) el.value = '';
            });
            if (panelAdd) panelAdd.style.display = 'none';
        })
        .catch(function () {
            if (errorEl) { errorEl.textContent = 'Error de conexión.'; errorEl.style.display = 'block'; }
        });
    });

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildReglaCard(r) {
        var pct = Math.round(parseFloat(r.porcentaje));
        var n   = esc(r.nombre);
        var id  = r.id;
        return '<div class="alumno-card" id="regla-card-' + id + '" style="margin-bottom:12px;">'
            + '<div class="alumno-card-header"><span class="alumno-dot alumno-dot--neutral"></span>'
            + '<h3 class="alumno-nombre" id="regla-nombre-' + id + '" style="font-size:0.85rem;">' + n + '</h3></div>'
            + '<div class="alumno-info">'
            + '<div class="info-item"><span class="info-label">Días:</span><span class="info-value" id="regla-dias-' + id + '">' + r.dia_desde + ' al ' + r.dia_hasta + '</span></div>'
            + '<div class="info-item"><span class="info-label">Porcentaje:</span><span class="info-value" id="regla-pct-' + id + '" style="font-weight:700;color:var(--color-btn-primary);">' + pct + '%</span></div>'
            + '</div>'
            + '<div class="alumno-actions">'
            + '<button class="ds-btn ds-btn--secondary btn-editar-regla" type="button" data-id="' + id + '">Editar</button>'
            + '<button class="ds-btn ds-btn--danger btn-eliminar-regla" type="button" data-id="' + id + '">Eliminar</button>'
            + '</div>'
            + '<div id="panel-edit-' + id + '" style="display:none;border-top:1px solid var(--color-border);padding:16px 0 8px;">'
            + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:480px;padding:0 0 0 1.5rem;">'
            + '<div><label style="font-size:0.75rem;font-weight:600;display:block;margin-bottom:4px;color:var(--color-text-muted);">Nombre</label><input type="text" id="edit-nombre-' + id + '" value="' + n + '" maxlength="100" class="wings-input" style="width:100%;padding:6px 10px;font-size:0.85rem;"></div>'
            + '<div><label style="font-size:0.75rem;font-weight:600;display:block;margin-bottom:4px;color:var(--color-text-muted);">Porcentaje (%)</label><input type="number" id="edit-porcentaje-' + id + '" value="' + pct + '" min="1" max="100" class="wings-input" style="width:100%;padding:6px 10px;font-size:0.85rem;"></div>'
            + '<div><label style="font-size:0.75rem;font-weight:600;display:block;margin-bottom:4px;color:var(--color-text-muted);">Día desde</label><input type="number" id="edit-dia-desde-' + id + '" value="' + r.dia_desde + '" min="1" max="31" class="wings-input" style="width:100%;padding:6px 10px;font-size:0.85rem;"></div>'
            + '<div><label style="font-size:0.75rem;font-weight:600;display:block;margin-bottom:4px;color:var(--color-text-muted);">Día hasta</label><input type="number" id="edit-dia-hasta-' + id + '" value="' + r.dia_hasta + '" min="1" max="31" class="wings-input" style="width:100%;padding:6px 10px;font-size:0.85rem;"></div>'
            + '</div>'
            + '<div id="edit-error-' + id + '" style="display:none;color:var(--color-danger);font-size:0.75rem;padding:6px 0 0 1.5rem;"></div>'
            + '<div style="display:flex;gap:8px;margin-top:12px;padding-left:1.5rem;">'
            + '<button class="ds-btn ds-btn--primary btn-guardar-regla" type="button" data-id="' + id + '">Guardar</button>'
            + '<button class="ds-btn ds-btn--secondary btn-cancelar-edit" type="button" data-id="' + id + '">Cancelar</button>'
            + '</div></div></div>';
    }
})();
</script>
@endpush
