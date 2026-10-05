// Maqueta aislada: sin peticiones ni escrituras. Estilos existentes de Wings.
const formulario = document.querySelector('form[method="POST"]');
const resumen = document.getElementById('alumno-error-resumen');
if (resumen) { resumen.focus({ preventScroll: true }); resumen.scrollIntoView({ block: 'nearest' }); }
if (formulario) {
    formulario.noValidate = true;
    formulario.addEventListener('submit', event => {
        event.preventDefault();
        const aviso = document.getElementById('alumno-error-resumen');
        if (aviso) { aviso.focus(); aviso.scrollIntoView({ block: 'start' }); }
    });
    const plan = document.getElementById('plan_id');
    if (plan) {
        plan.add(new Option('2 veces/semana — $30.000', '97401', true, true));
        document.getElementById('plan-section').style.display = '';
    }
    let cambios = false;
    formulario.addEventListener('input', () => { cambios = true; });
    formulario.addEventListener('change', () => { cambios = true; });
    window.addEventListener('beforeunload', event => {
        if (cambios) { event.preventDefault(); event.returnValue = ''; }
    });
}
const grupo = document.getElementById('grupo_id');
function filtrarProfesores() {
    const deporte = grupo?.selectedOptions[0]?.dataset.deporte ?? '';
    let disponibles = 0;
    document.querySelectorAll('input[name="profesores[]"]').forEach(casilla => {
        const label = casilla.closest('label');
        const coincide = deporte && label.querySelector('span')?.textContent.trim() === deporte;
        label.hidden = !coincide;
        label.style.display = coincide ? 'flex' : 'none';
        casilla.disabled = !coincide;
        if (!coincide) casilla.checked = false;
        if (coincide) disponibles++;
    });
    const info = document.getElementById('deporte-info');
    if (info) { info.style.display = deporte ? '' : 'none'; info.textContent = 'Deporte: ' + deporte; }
    let ayuda = document.getElementById('profesores-ayuda');
    const fila = document.querySelector('input[name="profesores[]"]')?.closest('label')?.parentElement;
    if (fila && !ayuda) {
        ayuda = document.createElement('p'); ayuda.id = 'profesores-ayuda';
        ayuda.className = 'text-xs text-wings-muted'; fila.after(ayuda);
    }
    if (ayuda) ayuda.textContent = deporte ? (disponibles ? 'Solo se muestran profesores de ' + deporte + '.' : 'No hay profesores activos para este deporte. La clase puede quedar sin asignar.') : 'Seleccioná un grupo para elegir profesores de su deporte.';
}
if (grupo && document.querySelector('input[name="profesores[]"]')) {
    grupo.addEventListener('change', filtrarProfesores); filtrarProfesores();
}
const panel = document.getElementById('panel-profesores');
if (panel) panel.style.display = '';
document.getElementById('btn-modificar-profesores')?.addEventListener('click', () => { panel.style.display = panel.style.display === 'none' ? '' : 'none'; });
document.getElementById('ds-menu-toggle')?.addEventListener('click', () => { document.querySelector('.ds-sidebar').classList.toggle('ds-sidebar--open'); });
