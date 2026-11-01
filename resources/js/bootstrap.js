// Interceptar respuestas 419 (sesión vencida / CSRF mismatch) o redirecciones a login en fetch
const fetchOriginal = window.fetch;
window.fetch = async function (...args) {
    const respuesta = await fetchOriginal.apply(this, args);
    if (respuesta.status === 419) {
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
        window.location.assign(respuesta.url);
        return new Promise(() => {});
    }
    return respuesta;
};
