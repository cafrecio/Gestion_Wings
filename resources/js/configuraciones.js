// A11: comportamiento exclusivo de Configuración.
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const resumen = document.getElementById('configuracion-error-resumen');
const listaErrores = document.getElementById('configuracion-error-lista');
const pendientes = new Map();
const errores = new Map();

function actualizarResumen(enfocar = false) {
    listaErrores.replaceChildren();
    errores.forEach(({ titulo, mensaje, destino }) => {
        const li = document.createElement('li');
        const link = document.createElement('a');
        link.href = '#' + destino;
        link.textContent = titulo + ': ' + mensaje;
        li.append(link);
        listaErrores.append(li);
    });
    resumen.querySelector('strong').textContent = [...errores.values()].some(error => error.incierto)
        ? 'No se pudo confirmar el guardado.' : 'No se guardó.';
    resumen.hidden = errores.size === 0;
    resumen.querySelector(':scope > a').href = '#' + ([...errores.values()][0]?.destino ?? 'cfg-inscripcion_importe');
    if (enfocar && errores.size) {
        resumen.focus();
        resumen.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

async function solicitar(url, method, datos) {
    let response, data;
    try {
        response = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'Content-Type': 'application/json' },
            ...(datos === undefined ? {} : { body: JSON.stringify(datos) }),
        });
        data = await response.json();
    } catch {
        throw { mensaje: 'No se pudo confirmar el guardado. Revisá la conexión y volvé a intentar.', incierto: true };
    }
    if (!response.ok) {
        throw {
            mensaje: Object.values(data.errors ?? {}).flat().join(' ') || data.error || (response.status === 419
                ? 'La sesión venció. Volvé a entrar antes de guardar.' : 'No se pudo guardar. Volvé a intentar.'),
            campos: data.errors ?? {}, incierto: response.status >= 500,
        };
    }
    return data;
}

document.querySelectorAll('.configuracion-form').forEach(form => {
    const clave = form.dataset.clave;
    const campo = form.elements.valor;
    const errorCampo = document.getElementById('error-' + clave);
    const guardado = document.getElementById('guardado-' + clave);
    const boton = form.querySelector('button[type="submit"]');
    pendientes.set(clave, { campo, original: form.dataset.valorGuardado });
    if (!errorCampo.hidden) errores.set(clave, { titulo: form.dataset.titulo, mensaje: errorCampo.textContent, destino: campo.id });
    campo.addEventListener('input', () => { guardado.hidden = true; });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (boton.disabled) return;
        boton.disabled = campo.disabled = true;
        guardado.hidden = true;
        try {
            const data = await solicitar(form.action, 'PATCH', { valor: campo.value });
            if (data.ok !== true || typeof data.valor !== 'string') throw { mensaje: 'No se pudo confirmar el guardado. Volvé a intentar.', incierto: true };
            campo.value = data.valor;
            pendientes.get(clave).original = data.valor;
            errorCampo.hidden = true;
            errorCampo.querySelector('a').textContent = '';
            campo.setAttribute('aria-invalid', 'false');
            errores.delete(clave);
            actualizarResumen();
            guardado.hidden = false;
        } catch (error) {
            errorCampo.querySelector('a').textContent = error.mensaje;
            errorCampo.hidden = false;
            campo.setAttribute('aria-invalid', 'true');
            errores.set(clave, { titulo: form.dataset.titulo, mensaje: error.mensaje, destino: campo.id, incierto: error.incierto });
            actualizarResumen(true);
        } finally {
            boton.disabled = campo.disabled = false;
        }
    });
});

window.addEventListener('beforeunload', event => {
    if ([...pendientes.values()].some(({ campo, original }) => campo.value !== original)) {
        event.preventDefault();
        event.returnValue = '';
    }
});

const list = document.getElementById('reglas-list');
const panelAdd = document.getElementById('panel-add-regla');
const nombresCampos = { nombre: 'nombre', dia_desde: 'dia-desde', dia_hasta: 'dia-hasta', porcentaje: 'porcentaje' };
function camposRegla(id) {
    return Object.fromEntries(Object.entries(nombresCampos).map(([nombre, fragmento]) => [
        nombre, document.getElementById(id === 'nueva' ? 'add-' + fragmento : 'edit-' + fragmento + '-' + id),
    ]));
}
function datosRegla(id) {
    return Object.fromEntries(Object.entries(camposRegla(id)).map(([nombre, campo]) => [nombre, campo.value.trim()]));
}
function mostrarErrorRegla(id, error) {
    const el = document.getElementById(id === 'nueva' ? 'add-error' : 'edit-error-' + id);
    el.textContent = error.mensaje;
    el.style.display = 'block';
    for (const [nombre, campo] of Object.entries(camposRegla(id))) {
        let mensaje = document.getElementById(campo.id + '-error');
        if (!mensaje) {
            mensaje = document.createElement('p');
            mensaje.id = campo.id + '-error';
            mensaje.className = 'ds-flash ds-flash--error';
            const enlace = document.createElement('a');
            enlace.href = '#' + campo.id;
            mensaje.append(enlace);
            campo.after(mensaje);
            campo.setAttribute('aria-describedby', mensaje.id);
        }
        mensaje.querySelector('a').textContent = (error.campos?.[nombre] ?? []).join(' ');
        mensaje.hidden = !mensaje.querySelector('a').textContent;
        campo.setAttribute('aria-invalid', mensaje.hidden ? 'false' : 'true');
    }
    errores.set('regla-' + id, { titulo: 'Importe de la primera cuota', mensaje: error.mensaje, destino: el.id, incierto: error.incierto });
    actualizarResumen(true);
}
function limpiarErrorRegla(id, panel) {
    document.getElementById(id === 'nueva' ? 'add-error' : 'edit-error-' + id).style.display = 'none';
    panel.querySelectorAll('[aria-invalid]').forEach(campo => campo.setAttribute('aria-invalid', 'false'));
    panel.querySelectorAll('p.ds-flash--error').forEach(nodo => { nodo.hidden = true; });
    errores.delete('regla-' + id);
    actualizarResumen();
}
function pintarRegla(card, regla) {
    card.querySelector('#regla-nombre-' + regla.id).textContent = regla.nombre;
    card.querySelector('#regla-dias-' + regla.id).textContent = regla.dia_desde + ' al ' + regla.dia_hasta;
    card.querySelector('#regla-pct-' + regla.id).textContent = Math.round(regla.porcentaje) + '%';
    Object.entries(nombresCampos).forEach(([nombre, fragmento]) => {
        card.querySelector('#edit-' + fragmento + '-' + regla.id).value = regla[nombre];
    });
}
function contadorReglas() {
    const n = list.querySelectorAll('.alumno-card').length;
    document.getElementById('reglas-count').textContent = n + (n === 1 ? ' regla configurada' : ' reglas configuradas');
}

list.addEventListener('click', async event => {
    const button = event.target.closest('button[data-id]');
    if (!button || button.disabled) return;
    const id = button.dataset.id;
    const panel = document.getElementById('panel-edit-' + id);
    if (button.classList.contains('btn-editar-regla')) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        return;
    }
    if (button.classList.contains('btn-cancelar-edit')) {
        panel.style.display = 'none';
        limpiarErrorRegla(id, panel);
        return;
    }
    const eliminar = button.classList.contains('btn-eliminar-regla');
    if (eliminar && !window.confirm('¿Eliminar esta regla?')) return;
    const controles = [...panel.querySelectorAll('input, button'), button];
    const datos = eliminar ? undefined : datosRegla(id);
    controles.forEach(control => { control.disabled = true; });
    try {
        const regla = await solicitar('/configuraciones/primer-pago/' + id, eliminar ? 'DELETE' : 'PUT', datos);
        limpiarErrorRegla(id, panel);
        if (eliminar) {
            document.getElementById('regla-card-' + id).remove();
            contadorReglas();
        } else {
            pintarRegla(document.getElementById('regla-card-' + id), regla);
            panel.style.display = 'none';
        }
    } catch (error) {
        panel.style.display = 'block';
        mostrarErrorRegla(id, error);
    } finally {
        controles.forEach(control => { control.disabled = false; });
    }
});

document.getElementById('btn-agregar-regla').addEventListener('click', () => {
    panelAdd.style.display = panelAdd.style.display === 'none' ? 'block' : 'none';
});
document.getElementById('btn-cancelar-add').addEventListener('click', () => {
    panelAdd.style.display = 'none';
    limpiarErrorRegla('nueva', panelAdd);
});
document.getElementById('btn-guardar-add').addEventListener('click', async event => {
    if (event.currentTarget.disabled) return;
    const controles = [...panelAdd.querySelectorAll('input, button')];
    const datos = datosRegla('nueva');
    controles.forEach(control => { control.disabled = true; });
    try {
        const regla = await solicitar('/configuraciones/primer-pago', 'POST', datos);
        const fragment = document.getElementById('regla-template').content.cloneNode(true);
        fragment.querySelectorAll('*').forEach(el => {
            [...el.attributes].forEach(attribute => {
                if (attribute.value.includes('__ID__')) el.setAttribute(attribute.name, attribute.value.replaceAll('__ID__', String(regla.id)));
            });
        });
        pintarRegla(fragment.querySelector('.alumno-card'), regla);
        list.append(fragment);
        contadorReglas();
        limpiarErrorRegla('nueva', panelAdd);
        panelAdd.querySelectorAll('input').forEach(campo => { campo.value = ''; });
        panelAdd.style.display = 'none';
    } catch (error) {
        mostrarErrorRegla('nueva', error);
    } finally {
        controles.forEach(control => { control.disabled = false; });
    }
});
