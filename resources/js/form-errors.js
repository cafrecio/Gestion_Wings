// Resúmenes opt-in. La validación definitiva sigue siendo la del servidor.
document.querySelectorAll('form[data-error-summary]').forEach(form => {
    const summary = document.getElementById(form.dataset.errorSummary);
    if (!summary) return;
    const list = summary.querySelector('ul');
    const fields = () => [...form.elements].filter(field => field.name);
    const valueFor = name => JSON.stringify(fields().filter(field => field.name === name)
        .map(field => [field.value, field.checked, field.disabled]));
    const serverErrors = [...list.querySelectorAll('[data-error-field]')].map(item => ({
        name: item.dataset.errorField,
        message: item.textContent.trim(),
        href: item.querySelector('a').getAttribute('href'),
        original: valueFor(item.dataset.errorField),
        nativeMissing: fields().some(field => field.name === item.dataset.errorField && field.willValidate && field.validity.valueMissing),
        inlineMessages: [...form.querySelectorAll('p')].filter(message => message.textContent.trim() === item.textContent.trim()),
    }));
    let attempted = !summary.hidden;
    let scheduled = false;

    const labelFor = field => field.labels?.[0]?.textContent.trim().replace(/\s+/g, ' ').replace(/\s*\*$/, '') || field.name;

    function refresh() {
        if (!attempted) return;
        const invalid = fields().filter(field => field.willValidate && !field.validity.valid);
        const entries = new Map();
        invalid.forEach(field => {
            if (!entries.has(field.name)) entries.set(field.name, {
                message: labelFor(field) + ': ' + field.validationMessage,
                href: '#' + (field.id || field.closest('[id]')?.id || ''),
                pending: false,
            });
        });
        serverErrors.forEach(error => {
            const changed = valueFor(error.name) !== error.original;
            const field = fields().find(field => field.name === error.name);
            error.inlineMessages.forEach(message => { message.hidden = changed; });
            if (entries.has(error.name)) return;
            // Un obligatorio vacío ya no está vacío: quitar ese error, sin certificar otras reglas.
            if (changed && error.nativeMissing) return;
            // No afirmar que un DNI ya es único ni que el plan es válido por cambiar el texto.
            entries.set(error.name, {
                message: changed ? (field ? labelFor(field) : error.name) + ': dato modificado; se comprueba al guardar.' : error.message,
                href: error.href,
                pending: changed,
            });
        });
        list.replaceChildren();
        entries.forEach(entry => {
            const item = document.createElement('li');
            const link = document.createElement('a');
            link.href = entry.href;
            link.textContent = entry.message;
            item.append(link);
            list.append(item);
        });
        summary.hidden = entries.size === 0;
        if (!summary.hidden) {
            summary.classList.add('ds-flash', 'ds-flash--error');
            const pendingOnly = [...entries.values()].every(entry => entry.pending);
            summary.querySelector('strong').textContent = pendingOnly ? 'Datos modificados.' : 'No se guardó.';
            summary.querySelector('div').textContent = pendingOnly
                ? 'Se comprobarán al guardar. Lo que cargaste se conserva.'
                : 'Revisá estos datos; lo que cargaste se conserva.';
        }
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    form.addEventListener('invalid', event => {
        // El resumen móvil reemplaza la burbuja; escritorio conserva la validación nativa.
        if (summary.classList.contains('mobile-error-summary--only') && !matchMedia('(max-width: 768px)').matches) return;
        event.preventDefault();
        attempted = true;
        if (scheduled) return;
        scheduled = true;
        queueMicrotask(() => {
            scheduled = false;
            refresh();
            summary.focus({preventScroll: true});
            summary.scrollIntoView({block: 'nearest'});
        });
    }, true);
    summary.addEventListener('click', event => {
        const link = event.target.closest('a[href^="#"]');
        if (!link) return;
        event.preventDefault();
        const field = document.getElementById(link.hash.slice(1));
        if (field) {
            field.focus({preventScroll: true});
            field.scrollIntoView({block: 'center'});
        }
    });
});
