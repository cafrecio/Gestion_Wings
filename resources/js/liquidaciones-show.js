// Guardar una decisión sin volver a consultar ni dibujar la pantalla completa.
// Sin JavaScript, los formularios conservan el POST normal.
let guardando = false;

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-decision-atrasada]');
    if (!form) return;
    event.preventDefault(); // El anti doble-submit global respeta defaultPrevented.
    if (guardando) return;
    const card = form.closest('[data-liquidacion-detalle]');
    if (!card) return;
    const top = card.getBoundingClientRect().top;
    const anclaje = document.documentElement.style.overflowAnchor;
    document.documentElement.style.overflowAnchor = 'none';
    const data = new FormData(form);
    const controles = [...document.querySelectorAll('form[data-decision-atrasada] button, [data-liquidacion-acciones] button')]
        .map(button => ({ button, disabled: button.disabled }));
    guardando = true;
    let confirmada = false;
    card.setAttribute('aria-busy', 'true');
    card.querySelector('[data-decision-error]')?.remove();
    controles.forEach(({ button }) => { button.disabled = true; });
    try {
        const response = await fetch(form.action, {
            method: 'POST', body: data, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (response.redirected || response.status === 419 || response.status === 401) {
            throw new Error('La sesión venció. Volvé a ingresar antes de seguir.');
        }
        const resultado = await response.json();
        if (!response.ok) {
            throw new Error(Object.values(resultado.errors || {}).flat()[0]
                || 'No se pudo confirmar la decisión. Recargá para comprobar su estado antes de reintentar.');
        }
        if (String(resultado.detalle_id) !== card.dataset.liquidacionDetalle) {
            throw new Error('La respuesta no corresponde a esta cuota. Recargá para comprobar su estado.');
        }
        card.querySelector('[data-decision-estado]').textContent = resultado.estado;
        card.querySelector('[data-decision-autor]').textContent = resultado.autor;
        card.querySelectorAll('form[data-decision-atrasada]').forEach(actual => {
            actual.elements.decision_esperada.value = resultado.decision;
            const boton = actual.querySelector('button');
            boton.disabled = actual.elements.decision.value === resultado.decision;
            boton.setAttribute('aria-disabled', String(boton.disabled));
        });
        for (const [nombre, valor] of Object.entries(resultado.importes)) {
            document.querySelectorAll(`[data-liquidacion-importe="${nombre}"]`)
                .forEach(elemento => { elemento.textContent = valor; });
        }
        // Solo fragmentos del servidor: conservan las reglas de cierre y las confirmaciones.
        document.querySelector('[data-liquidacion-acciones]').innerHTML = resultado.acciones;
        document.querySelector('[data-liquidacion-revision]').innerHTML = resultado.revision;
        const ajuste = document.querySelector('#editar-liquidacion form');
        if (ajuste && [...ajuste.querySelectorAll('input:not([type="hidden"])')]
            .every(input => input.value === input.defaultValue)) {
            ajuste.elements.monto_final.value = resultado.monto_mostrado;
            ajuste.elements.monto_final.defaultValue = resultado.monto_mostrado;
            ajuste.elements.monto_anterior.value = resultado.monto_final;
        }
        // Si hay algo escrito, conservarlo: el servidor comprueba monto_anterior al guardar.
        confirmada = true;
        card.tabIndex = -1;
        card.focus({ preventScroll: true });
    } catch (error) {
        const aviso = document.createElement('p');
        aviso.dataset.decisionError = '';
        aviso.className = 'text-sm mt-2';
        aviso.style.color = 'var(--color-danger)';
        aviso.setAttribute('role', 'alert');
        aviso.textContent = error.message;
        card.append(aviso);
    } finally {
        controles.forEach(({ button, disabled }) => {
            if (button.isConnected && !(confirmada && card.contains(button))) button.disabled = disabled;
        });
        card.removeAttribute('aria-busy');
        requestAnimationFrame(() => {
            window.scrollBy({ top: card.getBoundingClientRect().top - top, behavior: 'instant' });
            requestAnimationFrame(() => { document.documentElement.style.overflowAnchor = anclaje; });
            guardando = false;
        });
    }
}, true);
