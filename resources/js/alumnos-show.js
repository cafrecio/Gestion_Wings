// El mismo modal pide motivo para perdonar y para reactivar, sin borrar historial.
window.abrirCondonar = function (actionUrl, reactivar = false) {
    const form = document.getElementById('form-condonar');
    if (!form) return;
    form.action = actionUrl;
    form.reset();
    document.getElementById('perdon-titulo').textContent = reactivar ? 'Reactivar deuda' : 'Condonar deuda';
    document.getElementById('perdon-aviso').textContent = reactivar
        ? 'La deuda vuelve a estar pendiente. Se conservan los pagos y el historial. No mueve plata.'
        : 'Se perdona el saldo pendiente. No mueve plata. Podés reactivarlo con motivo desde esta ficha.';
    document.getElementById('perdon-confirmar').textContent = reactivar ? 'Reactivar' : 'Condonar';
    form.elements.motivo.placeholder = reactivar ? 'Motivo de la reactivación...' : 'Motivo de la condonación...';
    document.getElementById('modal-condonar').style.display = 'flex';
    form.elements.motivo.focus();
};
window.cerrarCondonar = function () {
    document.getElementById('modal-condonar').style.display = 'none';
};
document.addEventListener('click', function (event) {
    const boton = event.target.closest('[data-abrir-reactivar]');
    if (boton) window.abrirCondonar(boton.dataset.abrirReactivar, true);
});
document.getElementById('modal-condonar')?.addEventListener('click', function (event) {
    if (event.target === this) window.cerrarCondonar();
});

// T65: Panel de confirmación para eliminar alumno
function initEliminarAlumno() {
    const btnAbrir = document.getElementById('btn-abrir-eliminar-alumno');
    const panel = document.getElementById('panel-eliminar-alumno');
    const btnCerrar = document.getElementById('btn-cerrar-eliminar-alumno');

    if (btnAbrir && panel) {
        btnAbrir.addEventListener('click', function () {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (btnCerrar && panel) {
        btnCerrar.addEventListener('click', function () {
            panel.style.display = 'none';
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEliminarAlumno);
} else {
    initEliminarAlumno();
}
