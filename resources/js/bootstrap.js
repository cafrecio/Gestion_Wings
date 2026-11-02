// Interceptar respuestas 419 (sesión vencida / CSRF mismatch) o redirecciones a login en fetch
const fetchOriginal = window.fetch;
window.fetch = async function (...args) {
    const respuesta = await fetchOriginal.apply(this, args);
    if (respuesta.status === 419) {
        window.__ignorarAvisoSalida = true;
        window.onbeforeunload = null;
        let destino = '/login';
        try {
            const clon = respuesta.clone();
            const datos = await clon.json();
            if (datos && datos.redirect) {
                destino = datos.redirect;
            }
        } catch {
            // Si la respuesta no es JSON, destino por defecto /login
        }
        window.location.assign(destino);
        return new Promise(() => {});
    }
    if (respuesta.redirected && respuesta.url && respuesta.url.includes('/login')) {
        window.__ignorarAvisoSalida = true;
        window.onbeforeunload = null;
        window.location.assign(respuesta.url);
        return new Promise(() => {});
    }
    return respuesta;
};

// Evitar que avisos de salida (beforeunload) bloqueen la navegación forzada al login
window.addEventListener('beforeunload', event => {
    if (window.__ignorarAvisoSalida) {
        event.stopImmediatePropagation();
    }
}, true);
