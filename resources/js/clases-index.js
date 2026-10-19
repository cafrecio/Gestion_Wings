// Listado de clases: ventana de hoy y filtros (T73, antes incrustado en clases/index).
(function () {
    // 1. Auto-scroll de la ventana de hoy a la clase actual o próxima
    (function () {
        const container = document.getElementById('clases-hoy-container');
        if (!container) return;

        const prioridad = ['en_curso', 'por_comenzar', 'finalizada'];
        let target = null;

        for (const estado of prioridad) {
            target = container.querySelector('[data-estado="' + estado + '"]');
            if (target) break;
        }

        if (target) {
            container.scrollTop = target.offsetTop - container.offsetTop;
        }
    })();

    // 3. Filtro de grupo por deporte
    (function () {
        const filterForm    = document.getElementById('filter-form');
        const deporteSelect = document.getElementById('filter-deporte');
        const grupoSelect   = document.getElementById('filter-grupo');

        function filtrarGrupos(deporteId) {
            grupoSelect.querySelectorAll('option[data-deporte]').forEach(function (opt) {
                opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
            });
            if (deporteId) {
                const sel = grupoSelect.querySelector('option[value="' + grupoSelect.value + '"]');
                if (sel && sel.dataset.deporte && sel.dataset.deporte !== deporteId) {
                    grupoSelect.value = '';
                }
            }
        }

        if (deporteSelect) {
            deporteSelect.addEventListener('change', function () {
                filtrarGrupos(this.value);
                filterForm.submit();
            });
            if (deporteSelect.value) filtrarGrupos(deporteSelect.value);
        }

        if (grupoSelect) {
            grupoSelect.addEventListener('change', function () { filterForm.submit(); });
        }

        const ordenSelect = document.getElementById('filter-orden');
        if (ordenSelect) {
            ordenSelect.addEventListener('change', function () { filterForm.submit(); });
        }
    })();
})();
