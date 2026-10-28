// Estado de profesora: decisión y aviso en su tarjeta, sin navegación.
document.querySelectorAll('[data-profesor-estado-url]').forEach(label => {
    const input = label.querySelector('input');
    const card = label.closest('[data-profesor-card]');
    const aviso = card.querySelector('[data-profesor-estado-aviso]');
    let guardando = false;
    label.addEventListener('click', evento => { if (guardando) evento.preventDefault(); });
    input.addEventListener('change', async () => {
        if (guardando) return;
        guardando = true;
        input.disabled = true;
        const anterior = !input.checked;
        try {
            const respuesta = await fetch(label.dataset.profesorEstadoUrl, {
                method: 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                body: JSON.stringify({ activo: input.checked }),
            });
            const datos = await respuesta.json();
            if (!respuesta.ok || typeof datos.activo !== 'boolean') throw new Error(datos.error || Object.values(datos.errors || {}).flat()[0] || 'No se pudo guardar el estado.');
            input.checked = datos.activo;
            const badge = card.querySelector('[data-profesor-badge]');
            badge.textContent = datos.activo ? 'Activo' : 'Inactivo';
            badge.style.color = datos.activo ? 'var(--color-success)' : 'var(--color-danger)';
            badge.style.background = datos.activo ? 'color-mix(in srgb, var(--color-success) 15%, transparent)' : 'color-mix(in srgb, var(--color-danger) 15%, transparent)';
            const dot = card.querySelector('[data-profesor-dot]');
            dot.classList.toggle('alumno-dot--active', datos.activo);
            dot.classList.toggle('alumno-dot--neutral', !datos.activo);
            dot.setAttribute('aria-label', badge.textContent); dot.title = badge.textContent;
            aviso.textContent = datos.mensaje;
            aviso.style.color = 'var(--color-text-muted)';
        } catch (error) {
            input.checked = anterior;
            aviso.textContent = error.message || 'No se pudo comunicar con el servidor.';
            aviso.style.color = 'var(--color-danger)';
        } finally {
            input.setAttribute('aria-checked', String(input.checked));
            aviso.hidden = false;
            input.disabled = false;
            guardando = false;
        }
    });
});
