(function () {
    const grupoPlanes = JSON.parse(document.getElementById('plan-section').dataset.planes);

    function actualizarPlanes(grupoId) {
        const section  = document.getElementById('plan-section');
        if (!section) return;

        const select = document.getElementById('plan_id');
        const planes = grupoPlanes[grupoId] || [];

        select.innerHTML = '<option value="">Seleccionar frecuencia...</option>';

        if (planes.length > 0) {
            planes.forEach(p => {
                const opt     = document.createElement('option');
                opt.value     = p.id;
                const veces   = p.clases === 1 ? '1 vez/semana' : p.clases + ' veces/semana';
                const precio  = parseFloat(p.precio).toLocaleString('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
                opt.textContent = veces + ' — ' + precio;
                if (String(document.getElementById('plan-section').dataset.planActual) === String(p.id)) opt.selected = true;
                select.appendChild(opt);
            });
            section.style.display = '';
        } else {
            section.style.display = 'none';
        }
    }

    function filtrarGruposPorDeporte(deporteId) {
        const grupoSelect = document.getElementById('grupo_id');
        const options     = grupoSelect.querySelectorAll('option[data-deporte]');

        grupoSelect.value = '';
        options.forEach(opt => {
            opt.style.display = (!deporteId || opt.dataset.deporte === deporteId) ? '' : 'none';
        });

        const section = document.getElementById('plan-section');
        if (section) section.style.display = 'none';
    }

    // Celular: "mismo que tutor"
    const chkMismo = document.getElementById('celular-mismo-tutor');
    if (chkMismo) {
        const celularInput   = document.getElementById('celular');
        const tutorTelInput  = document.getElementById('telefono_tutor');

        function sincronizarCelular() {
            if (chkMismo.checked) {
                celularInput.value    = tutorTelInput.value;
                celularInput.readOnly = true;
                celularInput.style.opacity = '0.6';
            } else {
                celularInput.readOnly = false;
                celularInput.style.opacity = '';
            }
        }

        chkMismo.addEventListener('change', sincronizarCelular);
        tutorTelInput.addEventListener('input', () => {
            if (chkMismo.checked) celularInput.value = tutorTelInput.value;
        });
    }

    const deporteSelect = document.getElementById('deporte_id');
    if (deporteSelect && deporteSelect.tagName === 'SELECT') {
        deporteSelect.addEventListener('change', function () {
            filtrarGruposPorDeporte(this.value);
        });
    }

    document.getElementById('grupo_id').addEventListener('change', function () {
        actualizarPlanes(this.value);
    });

    (() => {
        const deporteEl = document.getElementById('deporte_id');
        const deporteId = deporteEl ? deporteEl.value : '';
        if (deporteId) {
            document.getElementById('grupo_id').querySelectorAll('option[data-deporte]').forEach(opt => {
                opt.style.display = opt.dataset.deporte === deporteId ? '' : 'none';
            });
        }
        const grupoId = document.getElementById('grupo_id').value;
        if (grupoId) actualizarPlanes(grupoId);
    })();

    // Validación fecha de nacimiento en tiempo real
    const fechaInput   = document.getElementById('fecha_nacimiento');
    const fechaError   = document.getElementById('fecha-nacimiento-error');
    const fechaErrorSv = document.getElementById('error-fecha-nacimiento');
    if (fechaInput && fechaError) {
        fechaInput.addEventListener('input', function () {
            const val = this.value;
            if (!val) { fechaError.style.display = 'none'; fechaError.textContent = ''; return; }
            const year = parseInt(val.split('-')[0], 10);
            const today = new Date().toISOString().split('T')[0];
            if (year < 1900 || year > new Date().getFullYear()) {
                fechaError.textContent = 'El año ingresado no es válido.';
                fechaError.style.display = '';
                this.setCustomValidity('Año inválido');
            } else if (val > today) {
                fechaError.textContent = 'La fecha debe ser anterior a hoy.';
                fechaError.style.display = '';
                this.setCustomValidity('Fecha futura');
            } else {
                fechaError.style.display = 'none';
                fechaError.textContent = '';
                if (fechaErrorSv) fechaErrorSv.style.display = 'none';
                this.setCustomValidity('');
            }
        });
    }

    // Validación email en tiempo real
    const emailInput   = document.getElementById('email');
    const emailError   = document.getElementById('email-error');
    const emailErrorSv = document.getElementById('error-email-alumno');
    if (emailInput && emailError) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        function validarEmail() {
            if (emailInput.value && !emailRegex.test(emailInput.value)) {
                emailError.textContent = 'El email no tiene un formato válido.';
                emailError.style.display = '';
            } else {
                emailError.style.display = 'none';
                emailError.textContent = '';
                if (emailErrorSv) emailErrorSv.style.display = 'none';
            }
        }
        emailInput.addEventListener('blur', validarEmail);
        emailInput.addEventListener('input', function () {
            if (!this.value || emailRegex.test(this.value)) {
                emailError.style.display = 'none';
                emailError.textContent = '';
                if (emailErrorSv) emailErrorSv.style.display = 'none';
            }
        });
    }
})();

const form = document.querySelector('[data-alumno-form]');
const resumen = document.getElementById('alumno-error-resumen');
if (form && resumen) {
    const campos = [...form.querySelectorAll('input:not([type="hidden"]), select, textarea')];
    const valores = () => JSON.stringify(campos.map(campo =>
        ['checkbox', 'radio'].includes(campo.type) ? campo.checked : campo.value
    ));
    const originales = valores();
    let ultimoEnvio = null;
    let descartando = false;
    const hayCambios = () => form.dataset.conErrores === '1' || valores() !== originales;

    function mostrarResumen() {
        resumen.classList.add('ds-flash', 'ds-flash--error');
        resumen.hidden = false;
        resumen.focus({preventScroll: true});
        resumen.scrollIntoView({block: 'nearest'});
    }

    if (!resumen.hidden) mostrarResumen();
    form.addEventListener('submit', event => {
        // No desactivar el aviso si otro validador impidió el envío.
        ultimoEnvio = event;
    });
    // El menú y Cancelar preguntan explícitamente; cerrar/recargar conserva
    // la protección nativa, igual que Configuración.
    document.addEventListener('click', event => {
        const enlace = event.target.closest('a[href]');
        if (!enlace || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || enlace.target === '_blank' || enlace.hasAttribute('download')) return;
        const destino = new URL(enlace.href, location.href);
        if (destino.origin === location.origin && destino.pathname === location.pathname && destino.search === location.search) return;
        if (!hayCambios()) return;
        if (window.confirm('Tenés cambios sin guardar. ¿Querés salir y perder lo cargado?')) {
            descartando = true;
        } else {
            event.preventDefault();
        }
    });
    window.addEventListener('pageshow', () => { ultimoEnvio = null; descartando = false; });
    window.addEventListener('beforeunload', event => {
        const enviando = ultimoEnvio && !ultimoEnvio.defaultPrevented;
        if (!enviando && !descartando && hayCambios()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}

// Consultar inscripción/cuota después de restaurar el plan seleccionado.
import('./alumnos-inscripcion.js');
