// Solo Detalle de caja. Los modales compartidos los resuelve ds-app.js.
document.querySelectorAll('form[data-confirmar-eliminacion]').forEach(form => {
    // En el formulario, antes del anti doble-submit delegado en document.
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirmarEliminacion)) event.preventDefault();
    });
});

const checks = [...document.querySelectorAll('.mov-check[data-verificar-url]')];
const counter = document.getElementById('verificados-counter');
const validar = document.getElementById('btn-validar');
const aviso = document.getElementById('verificacion-error');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
let pendientes = 0;

checks.forEach(check => { check.dataset.guardado = check.checked ? '1' : '0'; });

function actualizar() {
    const guardados = checks.filter(check => check.dataset.guardado === '1').length;
    if (counter) counter.textContent = `${guardados} de ${checks.length} verificados`;
    if (validar) {
        validar.disabled = pendientes > 0;
        validar.style.background = guardados === checks.length && checks.length > 0
            ? 'var(--color-success)' : 'var(--color-btn-primary)';
    }
}

checks.forEach(check => {
    if (check.disabled) return;
    check.addEventListener('change', async () => {
        const anterior = check.dataset.guardado === '1';
        const solicitado = check.checked;
        check.disabled = true;
        pendientes++;
        actualizar();
        try {
            const response = await fetch(check.dataset.verificarUrl, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ verificado: solicitado, huella: check.dataset.huella }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || typeof data.verificado !== 'boolean') {
                const mensaje = Object.values(data.errors || {}).flat()[0];
                throw new Error(mensaje || (response.status === 419
                    ? 'Tu sesión venció. Volvé a ingresar.'
                    : 'No se pudo guardar la verificación. Recargá el detalle e intentá otra vez.'));
            }
            check.checked = data.verificado;
            check.dataset.guardado = data.verificado ? '1' : '0';
            if (aviso) aviso.hidden = true;
        } catch (error) {
            check.checked = anterior;
            if (aviso) {
                aviso.textContent = error instanceof TypeError
                    ? 'No se pudo guardar la verificación. Revisá la conexión e intentá otra vez.'
                    : (error.message || 'No se pudo guardar la verificación.');
                aviso.hidden = false;
            }
        } finally {
            pendientes--;
            check.disabled = false;
            actualizar();
        }
    });
});

actualizar();
