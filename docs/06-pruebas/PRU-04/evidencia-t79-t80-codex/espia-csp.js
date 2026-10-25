// Diagnostico externo: recoge eventos reales antes de los scripts de Wings.
(() => {
    const datos = { bloqueos: [], errores: [] };
    const guardar = () => { document.documentElement.dataset.verificacionT80 = JSON.stringify(datos); };
    document.addEventListener('securitypolicyviolation', e => {
        datos.bloqueos.push({ directiva: e.violatedDirective, bloqueado: e.blockedURI, linea: e.lineNumber, disposicion: e.disposition }); guardar();
    });
    window.addEventListener('error', e => { datos.errores.push({ mensaje: e.message, archivo: e.filename, linea: e.lineno }); guardar(); });
    window.addEventListener('unhandledrejection', e => { datos.errores.push({ promesa: String(e.reason) }); guardar(); });
    const original = console.error.bind(console);
    console.error = (...valores) => { datos.errores.push({ consola: valores.map(String).join(' ') }); guardar(); original(...valores); };
    guardar();
})();
