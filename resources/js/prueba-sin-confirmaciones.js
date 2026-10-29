// Solo en el sitio de prueba (PRUEBA_SIN_CONFIRMACIONES; nunca en producción, ver
// App\Support\PruebaSinConfirmaciones): las confirmaciones nativas traban al navegador
// automático de la prueba por días, así que se aceptan solas. Va primero en app.js.
if (document.documentElement.hasAttribute('data-sin-confirmaciones')) {
    window.confirm = function () { return true; };
}
