/* T24: único tratamiento de pesos, también para campos y filas agregados después de cargar. */
export function normalizar(valor) {
    let texto = String(valor ?? '');
    if (/^[0-9]{1,3}(?:,[0-9]{3})+\.[0-9]{1,2}$/.test(texto)) texto = texto.replaceAll(',', '');
    else texto = texto.replaceAll(',', '.');
    return /^[0-9]+(?:\.[0-9]{1,2})?$/.test(texto) ? texto : null;
}

export function mostrar(valor) {
    const crudo = normalizar(valor);
    if (crudo === null) return String(valor ?? '');
    const [entero, decimales = ''] = crudo.split('.');
    return (entero.replace(/^0+(?=[0-9])/, '').replace(/\B(?=(?:[0-9]{3})+(?![0-9]))/g, ','))
        + '.' + decimales.padEnd(2, '0');
}

export function numero(input) {
    const crudo = normalizar(input?.value);
    return crudo === null ? 0 : Number(crudo);
}

let aviso;
let temporizador;
function avisarDecimales() {
    if (!aviso) {
        aviso = document.createElement('div');
        aviso.className = 'ds-flash ds-flash--error money-notice';
        aviso.setAttribute('role', 'alert');
        document.body.append(aviso);
    }
    aviso.textContent = 'No se pueden colocar más de dos decimales.';
    aviso.hidden = false;
    clearTimeout(temporizador);
    temporizador = setTimeout(() => { aviso.hidden = true; }, 4000);
}

export function validar(input) {
    if (input.disabled || input.readOnly) return true;
    const valor = input.value;
    const crudo = normalizar(valor);
    let error = '';
    if (valor !== '' && crudo === null) error = 'Ingresá un importe válido, con hasta dos decimales.';
    else if (valor === '' && input.required) error = 'Completá este importe.';
    else if (crudo !== null) {
        const n = Number(crudo);
        const min = input.getAttribute('min');
        const max = input.getAttribute('max');
        if (!Number.isFinite(n)) error = 'Ingresá un importe válido.';
        else if (min !== null && n < Number(min)) error = Number(min) > 0
            ? 'El importe debe ser mayor que cero.' : 'El importe no puede ser negativo.';
        else if (max !== null && n > Number(max)) error = 'El importe supera el máximo permitido.';
        else if (input.dataset.moneyLte) {
            const otro = input.form?.elements.namedItem(input.dataset.moneyLte);
            if (otro && normalizar(otro.value) !== null && n > numero(otro)) error = 'El cambio no puede superar el efectivo contado.';
        }
    }
    // El borrador sin name se valida al Agregar; no se envía ni bloquea cobrar otras cuotas.
    input.setCustomValidity(input.name ? error : '');
    input.setAttribute('aria-invalid', error ? 'true' : 'false');
    input.classList.toggle('money-input--invalid', Boolean(error));
    return !error;
}

export function asignar(input, valor) {
    if (!input) return;
    const crudo = normalizar(valor);
    input.value = document.activeElement === input ? (crudo ?? String(valor ?? '')) : mostrar(valor);
    input.classList.remove('money-input--warning');
    validar(input);
}

export function inicializar(input) {
    if (!input || !input.matches('[data-money="true"]')) return;
    if (document.activeElement !== input && normalizar(input.value) !== null) input.value = mostrar(input.value);
    // Los vacíos todavía no se marcan rojos al abrir una pantalla.
    if (input.value !== '') validar(input);
}

export function comprobarFormulario(form) {
    const inputs = [...form.querySelectorAll('[data-money="true"]')].filter(input => input.name && !input.disabled && !input.readOnly);
    const invalidos = inputs.filter(input => !validar(input));
    if (invalidos.length) {
        invalidos[0].focus();
        invalidos[0].reportValidity();
        return false;
    }
    return true;
}

export function prepararDatos(form, datos) {
    form.querySelectorAll('[data-money="true"][name]').forEach(input => {
        if (input.disabled || input.readOnly || !input.name) return;
        const crudo = normalizar(input.value);
        datos.set(input.name, crudo ?? input.value);
    });
    return datos;
}

if (typeof document !== 'undefined') {
    window.WingsMoney = {normalizar, mostrar, numero, validar, asignar, comprobarFormulario, prepararDatos};
    window.initMoneyInput = inicializar;
    const inicio = () => document.querySelectorAll('[data-money="true"]').forEach(inicializar);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', inicio);
    else inicio();

    document.addEventListener('focusin', event => {
        const input = event.target;
        if (!input.matches?.('[data-money="true"]') || input.readOnly || input.disabled) return;
        const crudo = normalizar(input.value);
        if (crudo !== null) input.value = crudo;
    });
    // Captura: cálculos y resúmenes de cada pantalla ven primero el valor ya corregido.
    document.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches?.('[data-money="true"]') || input.readOnly || input.disabled) return;
        const cursor = input.selectionStart;
        let texto = input.value;
        if (/^[0-9]{1,3}(?:,[0-9]{3})+\.[0-9]+$/.test(texto)) texto = texto.replaceAll(',', '');
        else texto = texto.replaceAll(',', '.');
        const exceso = /^([0-9]+)\.([0-9]{3,})$/.exec(texto);
        if (exceso) {
            input.value = exceso[1] + '.' + exceso[2].slice(0, 2);
            input.classList.add('money-input--warning');
            avisarDecimales();
        } else {
            if (input.value !== texto) {
                input.value = texto;
                if (cursor !== null) input.setSelectionRange(Math.min(cursor, texto.length), Math.min(cursor, texto.length));
            }
            input.classList.remove('money-input--warning');
        }
        validar(input);
    }, true);
    document.addEventListener('focusout', event => {
        const input = event.target;
        if (!input.matches?.('[data-money="true"]')) return;
        if (validar(input) && input.value !== '') input.value = mostrar(input.value);
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.querySelector?.('[data-money="true"]')) return;
        if (!comprobarFormulario(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        form.querySelectorAll('[data-money="true"][name]').forEach(input => {
            if (!input.disabled && !input.readOnly) input.value = normalizar(input.value) ?? input.value;
        });
    }, true);
    document.addEventListener('formdata', event => prepararDatos(event.target, event.formData), true);
}
