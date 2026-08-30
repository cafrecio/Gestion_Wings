/* Comportamientos globales del sistema.
 *
 * Vivian dentro de layouts/ds-app.blade.php, incrustados en la pagina.
 * Se movieron aca el 30/08/2026 para poder aplicar una politica de seguridad
 * que rechace el codigo incrustado en las paginas: eso cierra 55 de las 85
 * violaciones medidas, porque este layout esta en todas las pantallas.
 *
 * El contenido NO se modifico: es el mismo codigo, movido.
 */

/* ── toggle activo global: fetch PATCH sin recarga ─────────────────────
   Aplica a cualquier .ds-toggle[data-url] del sistema.
   Toggles de formulario (permite_descubierto, afecta_caja, etc.) no
   tienen data-url y no son interceptados.
──────────────────────────────────────────────────────────────────── */
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    document.querySelectorAll('.ds-toggle[data-url]').forEach(function (label) {
        var input = label.querySelector('.ds-toggle__input');
        if (!input || input.disabled) return;

        input.addEventListener('change', function () {
            var url  = label.dataset.url;
            var card = label.closest('.alumno-card');

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/json',
                },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.activo !== undefined && card) {
                    card.style.opacity = data.activo ? '' : '0.6';
                }
            })
            .catch(function () {
                // revertir estado visual si la petición falló
                input.checked = !input.checked;
            });
        });
    });
})();

/* ── anti doble-submit global: la segunda pulsación no dispara otro POST ── */
(function () {
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return; // un confirm() cancelado no cuenta
        var form = e.target;
        if (form.dataset.submitted === '1') { e.preventDefault(); return; }
        form.dataset.submitted = '1';
        setTimeout(function () {
            form.querySelectorAll('button[type=submit], input[type=submit]')
                .forEach(function (b) { b.disabled = true; });
        }, 0);
    });
    // volver con el botón atrás (bfcache) debe rehabilitar los formularios
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitted]').forEach(function (form) {
            delete form.dataset.submitted;
            form.querySelectorAll('button[type=submit], input[type=submit]')
                .forEach(function (b) { b.disabled = false; });
        });
    });
})();

/* ── menú móvil: sidebar colapsable con botón hamburguesa (UP1.0) ── */
(function () {
    var toggle  = document.getElementById('ds-menu-toggle');
    var sidebar = document.querySelector('.ds-sidebar');
    var overlay = document.getElementById('ds-sidebar-overlay');
    if (!toggle || !sidebar || !overlay) return;

    function abrir() {
        sidebar.classList.add('ds-sidebar--open');
        overlay.classList.add('ds-sidebar-overlay--visible');
    }
    function cerrar() {
        sidebar.classList.remove('ds-sidebar--open');
        overlay.classList.remove('ds-sidebar-overlay--visible');
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.contains('ds-sidebar--open') ? cerrar() : abrir();
    });
    overlay.addEventListener('click', cerrar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrar();
    });
    // Cerrar automáticamente al tocar un link del menú (si no, queda
    // tapando la pantalla después de navegar).
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', cerrar);
    });
})();

/* ── flash auto-dismiss (3s + fade 0.5s) ── */
(function () {
    setTimeout(function () {
        document.querySelectorAll('.ds-flash').forEach(function (el) {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        });
    }, 3000);
})();

/* ── money-input: formato numérico con separador de miles (punto) ── */
(function () {
    function toDisplay(raw) {
        // Eliminar todo excepto dígitos
        const digits = raw.replace(/\D/g, '');
        if (!digits) return '';
        return Number(digits).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    function toRaw(display) {
        return display.replace(/\./g, '').replace(/,/g, '');
    }

    function initMoneyInput(input) {
        // Formatear al mostrar la página
        if (input.value) {
            input.value = toDisplay(input.value);
        }

        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            const before = this.value.slice(0, pos).replace(/\./g, '').length;
            const raw = toRaw(this.value);
            this.value = raw ? toDisplay(raw) : '';
            // Reposicionar cursor aproximadamente
            let count = 0, newPos = 0;
            for (let i = 0; i < this.value.length; i++) {
                if (this.value[i] !== '.') count++;
                if (count === before) { newPos = i + 1; break; }
            }
            this.setSelectionRange(newPos, newPos);
        });
    }

    function stripMoneyInputs(form) {
        form.querySelectorAll('[data-money="true"]').forEach(function (input) {
            input.value = toRaw(input.value);
        });
    }

    function init() {
        document.querySelectorAll('[data-money="true"]').forEach(initMoneyInput);
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () { stripMoneyInputs(form); });
        });
    }

    // Exponer para uso externo (filas dinámicas)
    window.initMoneyInput = initMoneyInput;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
