/* ── grupos.js ─────────────────────────────────────────────────────────────
   Lógica de cliente para el formulario de grupos (grupos/_form.blade.php):
   - Verificación de disponibilidad de combinación deporte + nivel.
   - Carga dinámica de precios por frecuencia (+ Frecuencia / eliminar fila).
   - Formateo de importes monetarios vía window.initMoneyInput.
───────────────────────────────────────────────────────────────────────── */
(function () {
    function initGrupos() {
        /* ── Disponibilidad de deporte + nivel ───────────────────────── */
        const selectDeporte = document.getElementById('deporte_id');
        const selectNivel   = document.getElementById('nivel_id');
        const errorDiv      = document.getElementById('error-deporte-nivel');
        const btnSubmit     = document.querySelector('[type="submit"]');
        const grupoIdEl     = document.getElementById('grupo-id-actual');
        const grupoId       = grupoIdEl ? grupoIdEl.value : '';

        if (selectDeporte && selectNivel) {
            async function verificarDisponible() {
                const deporteId = selectDeporte.value;
                const nivelId   = selectNivel.value;
                if (!deporteId || !nivelId) {
                    if (errorDiv) errorDiv.style.display = 'none';
                    if (btnSubmit) btnSubmit.disabled = false;
                    return;
                }
                let url = '/grupos/check-disponible?deporte_id=' + encodeURIComponent(deporteId) + '&nivel_id=' + encodeURIComponent(nivelId);
                if (grupoId) url += '&grupo_id=' + encodeURIComponent(grupoId);
                try {
                    const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (!data.disponible) {
                        if (errorDiv) errorDiv.style.display = 'block';
                        if (btnSubmit) btnSubmit.disabled = true;
                    } else {
                        if (errorDiv) errorDiv.style.display = 'none';
                        if (btnSubmit) btnSubmit.disabled = false;
                    }
                } catch(e) {
                    if (errorDiv) errorDiv.style.display = 'none';
                    if (btnSubmit) btnSubmit.disabled = false;
                }
            }

            selectDeporte.addEventListener('change', verificarDisponible);
            selectNivel.addEventListener('change', verificarDisponible);
        }

        /* ── Precios por frecuencia ──────────────────────────────────── */
        const container = document.getElementById('planes-container');
        const template  = document.getElementById('plan-row-template');
        const emptyMsg  = document.getElementById('planes-empty-msg');
        const btnAdd    = document.getElementById('btn-add-plan');

        if (!container || !template) return;

        let idx = parseInt(container.dataset.initialIdx || container.querySelectorAll('.plan-row').length, 10);

        function updateEmptyMsg() {
            if (emptyMsg) {
                emptyMsg.style.display = container.children.length === 0 ? '' : 'none';
            }
        }

        function setupRemoveButtons() {
            container.querySelectorAll('.btn-remove-plan').forEach(function (btn) {
                btn.onclick = null;
                btn.onclick = function () {
                    btn.closest('.plan-row').remove();
                    updateEmptyMsg();
                };
            });
        }

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                const html = template.innerHTML.replaceAll('__IDX__', idx++);
                const div  = document.createElement('div');
                div.innerHTML = html;
                const row = div.firstElementChild;
                row.querySelectorAll('[data-money="true"]').forEach(function (inp) {
                    if (typeof window.initMoneyInput === 'function') {
                        window.initMoneyInput(inp);
                    }
                });
                container.appendChild(row);
                setupRemoveButtons();
                updateEmptyMsg();
                const sel = row.querySelector('select');
                if (sel) sel.focus();
            });
        }

        setupRemoveButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGrupos);
    } else {
        initGrupos();
    }
})();