(function () {
    const radios        = document.querySelectorAll('input[name="tipo_creacion"]');
    const secUnica      = document.getElementById('seccion-unica');
    const secRecurrente = document.getElementById('seccion-recurrente');
    const lblUnica      = document.getElementById('lbl-unica');
    const lblRec        = document.getElementById('lbl-recurrente');
    const grupoSelect   = document.getElementById('grupo_id');
    const deporteInfo   = document.getElementById('deporte-info');
    const horaInicio    = document.getElementById('hora_inicio');
    const horaFin       = document.getElementById('hora_fin');
    const horaFinError  = document.getElementById('hora-fin-error');

    function actualizarModo() {
        const tipo = document.querySelector('input[name="tipo_creacion"]:checked').value;
        document.querySelectorAll('[data-hora-unica]').forEach(bloque => {
            bloque.style.display = tipo === 'unica' ? '' : 'none';
            bloque.querySelectorAll('input').forEach(input => { input.disabled = tipo !== 'unica'; input.required = tipo === 'unica'; });
        });
        if (tipo === 'unica') {
            secUnica.style.display = '';
            secRecurrente.style.display = 'none';
            lblUnica.style.background = 'color-mix(in srgb, var(--color-btn-primary) 12%, transparent)';
            lblUnica.style.borderColor = 'var(--color-btn-primary)';
            lblRec.style.background = '';
            lblRec.style.borderColor = 'var(--color-border)';
        } else {
            secUnica.style.display = 'none';
            secRecurrente.style.display = '';
            lblRec.style.background = 'color-mix(in srgb, var(--color-btn-primary) 12%, transparent)';
            lblRec.style.borderColor = 'var(--color-btn-primary)';
            lblUnica.style.background = '';
            lblUnica.style.borderColor = 'var(--color-border)';
        }
    }

    radios.forEach(r => r.addEventListener('change', actualizarModo));
    if (radios.length) actualizarModo();

    function actualizarDias() {
        const recurrente = document.querySelector('input[name="tipo_creacion"]:checked')?.value === 'recurrente';
        document.querySelectorAll('[data-horario-dia]').forEach(fila => {
            const elegido = document.querySelector('input[name="dias_semana[]"][value="' + fila.dataset.horarioDia + '"]')?.checked;
            const habilitado = recurrente && elegido;
            fila.style.display = habilitado ? '' : 'none';
            fila.querySelectorAll('input').forEach(input => { input.disabled = !habilitado; input.required = habilitado; });
        });
    }
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(input => input.addEventListener('change', actualizarDias));
    radios.forEach(input => input.addEventListener('change', actualizarDias));
    actualizarDias();
    document.querySelectorAll('[data-horario-dia]').forEach(fila => {
        const inputs = fila.querySelectorAll('input[type="time"]');
        inputs.forEach(input => input.addEventListener('change', () => {
            const invalido = inputs[0].value && inputs[1].value && inputs[1].value <= inputs[0].value;
            inputs[1].setCustomValidity(invalido ? 'La hora de fin debe ser posterior a la hora de inicio.' : '');
            fila.querySelector('[data-error-horario]').style.display = invalido ? '' : 'none';
        }));
    });

    // Deporte info dinámico al seleccionar grupo
    if (grupoSelect && deporteInfo) {
        const etiquetas = [...document.querySelectorAll('[data-profesor-deporte]')];
        const avisoProfesores = document.getElementById('profesores-deporte-aviso');
        function filtrarProfesores() {
            const seleccion = grupoSelect.options[grupoSelect.selectedIndex];
            const deporteId = seleccion?.dataset.deporteId || '';
            let disponibles = 0;
            etiquetas.forEach(etiqueta => {
                const pertenece = deporteId !== '' && etiqueta.dataset.profesorDeporte === deporteId;
                const casilla = etiqueta.querySelector('input');
                etiqueta.style.display = pertenece ? 'flex' : 'none';
                casilla.disabled = !pertenece;
                if (!pertenece) casilla.checked = false;
                if (pertenece) disponibles++;
            });
            if (avisoProfesores) avisoProfesores.textContent = !deporteId
                ? 'Elegí un grupo para ver sus profesores activos.'
                : disponibles
                    ? 'Solo se muestran profesores activos de ' + seleccion.dataset.deporte + '.'
                    : 'No hay profesores activos de este deporte.';
        }
        grupoSelect.addEventListener('change', filtrarProfesores);
        filtrarProfesores();
        grupoSelect.addEventListener('change', function () {
            const sel = this.options[this.selectedIndex];
            const dep = sel.dataset.deporte || '';
            if (dep) {
                deporteInfo.textContent = 'Deporte: ' + dep;
                deporteInfo.style.display = '';
            } else {
                deporteInfo.style.display = 'none';
            }
        });
        // Init
        const sel = grupoSelect.options[grupoSelect.selectedIndex];
        if (sel && sel.dataset.deporte) {
            deporteInfo.textContent = 'Deporte: ' + sel.dataset.deporte;
            deporteInfo.style.display = '';
        }
    }

    const horaFinErrorSv = document.getElementById('error-hora-fin');

    // Validación hora fin
    function validarHoras() {
        if (!horaInicio.value || !horaFin.value) return;
        if (horaFin.value <= horaInicio.value) {
            horaFinError.style.display = '';
            horaFin.setCustomValidity('La hora de fin debe ser posterior a la hora de inicio.');
        } else {
            horaFinError.style.display = 'none';
            if (horaFinErrorSv) horaFinErrorSv.style.display = 'none';
            horaFin.setCustomValidity('');
        }
    }

    if (horaInicio && horaFin) {
        horaInicio.addEventListener('change', validarHoras);
        horaFin.addEventListener('change', validarHoras);
    }
})();
