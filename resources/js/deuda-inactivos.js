import { pesos } from './formato-importe';

// La confirmación ya la resuelve ds-app.js en captura. Guardar una fila no recarga.
document.querySelectorAll('form[data-perder-deuda]').forEach(form => {
    form.addEventListener('submit', async event => {
        if (event.defaultPrevented) return;
        event.preventDefault();
        if (form.dataset.guardando === '1') return;
        form.dataset.guardando = '1';
        const boton = form.querySelector('button[type="submit"]');
        const fila = form.closest('[data-inactivo-fila]');
        const seccion = form.closest('[data-deuda-inactivos]');
        const error = fila.querySelector('[data-inactivo-error]');
        boton.disabled = true;
        error.hidden = true;
        try {
            const respuesta = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const datos = await respuesta.json();
            if (!respuesta.ok) throw new Error(Object.values(datos.errors ?? {}).flat()[0] ?? datos.message ?? 'No se pudo guardar.');
            fila.remove();
            const filas = [...seccion.querySelectorAll('[data-inactivo-fila]')];
            const centavos = filas.reduce((total, f) => total + Number(f.dataset.centavos), 0);
            seccion.querySelector('[data-inactivos-total]').textContent = pesos((centavos / 100).toFixed(2));
            seccion.querySelector('[data-inactivos-cantidad]').textContent = String(filas.length);
            seccion.querySelector('[data-inactivos-vacio]').hidden = filas.length > 0;
            seccion.querySelector('[data-inactivos-aviso]').textContent = datos.mensaje;
        } catch (fallo) {
            error.textContent = fallo.message || 'No se pudo guardar. Volvé a intentar.';
            error.hidden = false;
        } finally {
            form.dataset.guardando = '0';
            boton.disabled = false;
        }
    });
});
