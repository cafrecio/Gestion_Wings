// Listado de alumnos: filtros y búsqueda con sugerencias (T73, antes incrustado en alumnos/index).
(function () {
    const filterForm    = document.querySelector('form[method="GET"]');
    const searchInput   = document.getElementById('search-input');
    const dropdown      = document.getElementById('search-dropdown');
    const deporteSelect = document.getElementById('filter-deporte');
    const grupoSelect   = document.getElementById('filter-grupo');
    const autocompleteUrl = searchInput ? searchInput.dataset.autocompleteUrl : '';
    let debounceTimer, activeIndex = -1, lastResults = [];

    // Auto-submit selects
    [deporteSelect, grupoSelect].forEach(sel => {
        if (sel) sel.addEventListener('change', () => filterForm.submit());
    });

    if (!searchInput || !dropdown) return;

    function closeDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        activeIndex = -1;
        searchInput.setAttribute('aria-expanded', 'false');
    }

    function submitSearch() {
        clearTimeout(debounceTimer);
        closeDropdown();
        filterForm.submit();
    }

    function renderResults(results) {
        lastResults = results;
        activeIndex = -1;
        dropdown.innerHTML = '';

        if (!results.length) { closeDropdown(); return; }

        results.forEach((r, i) => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.dataset.index = i;
            li.style.cssText = 'padding:8px 12px; cursor:pointer; display:flex; flex-direction:column; gap:1px;';

            const label = document.createElement('span');
            label.style.cssText = 'font-size:0.82rem; font-weight:600; color:var(--color-text);';
            label.textContent = r.label;
            li.appendChild(label);

            const sub = document.createElement('span');
            sub.style.cssText = 'font-size:0.7rem; color:var(--color-text-muted);';
            sub.textContent = r.sub;
            li.appendChild(sub);
            li.addEventListener('mousedown', (e) => {
                e.preventDefault();
                window.location.href = r.url;
            });
            li.addEventListener('mouseenter', () => setActive(i));
            dropdown.appendChild(li);
        });

        // Separador + "Ver todos"
        const sep = document.createElement('li');
        sep.setAttribute('role', 'separator');
        sep.style.cssText = 'height:1px; background:var(--color-border); margin:4px 0;';
        dropdown.appendChild(sep);

        const verTodos = document.createElement('li');
        verTodos.setAttribute('role', 'option');
        verTodos.style.cssText = 'padding:8px 12px; cursor:pointer; display:flex; align-items:center; gap:6px;';
        verTodos.innerHTML = `
            <svg style="width:13px;height:13px;flex-shrink:0;color:var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <span style="font-size:0.78rem; color:var(--color-btn-primary); font-weight:500;">Ver todos los resultados</span>
        `;
        verTodos.addEventListener('mousedown', (e) => { e.preventDefault(); submitSearch(); });
        verTodos.addEventListener('mouseenter', () => setActive(lastResults.length));
        dropdown.appendChild(verTodos);

        dropdown.style.display = 'block';
        searchInput.setAttribute('aria-expanded', 'true');
    }

    // activeIndex: 0..lastResults.length-1 = resultados, lastResults.length = "Ver todos"
    function setActive(index) {
        // Limpiar todos
        dropdown.querySelectorAll('li[role="option"]').forEach(li => {
            li.style.background = '';
        });
        activeIndex = index;
        if (index < 0) return;

        const options = dropdown.querySelectorAll('li[role="option"]');
        // options[0..n-1] = resultados, options[n] = "Ver todos"
        if (options[index]) options[index].style.background = 'var(--color-surface-alt)';
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 1) { closeDropdown(); return; }

        debounceTimer = setTimeout(() => {
            fetch(`${autocompleteUrl}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    // Descartar respuestas si el texto del campo ya no coincide con lo pedido
                    if (searchInput.value.trim() !== q) {
                        return;
                    }
                    renderResults(data);
                })
                .catch(() => closeDropdown());
        }, 250);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            if (activeIndex === lastResults.length) {
                e.preventDefault();
                submitSearch();
            } else if (activeIndex >= 0 && lastResults[activeIndex]) {
                e.preventDefault();
                window.location.href = lastResults[activeIndex].url;
            } else {
                submitSearch();
            }
            return;
        }

        if (e.key === 'Escape') { closeDropdown(); return; }

        const items = dropdown.querySelectorAll('li[role="option"]');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIndex + 1, lastResults.length)); // lastResults.length = "Ver todos"
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
        }
    });

    // Cerrar al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });
})();
