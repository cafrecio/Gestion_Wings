const aviso = document.getElementById('inscripcion-aviso');
if (aviso) {
    const dni = document.getElementById('dni');
    const fecha = document.getElementById('fecha_alta');
    const importe = document.getElementById('inscripcion-importe-visto');
    let secuencia = 0;
    async function actualizar() {
        const turno = ++secuencia;
        importe.value = '';
        if (!dni.value || !fecha.value) {
            aviso.textContent = 'Ingrese el DNI y la fecha real de ingreso para consultar la inscripción.';
            return;
        }
        try {
            const query = new URLSearchParams({dni: dni.value, fecha_alta: fecha.value});
            if (aviso.dataset.alumno) query.set('alumno_id', aviso.dataset.alumno);
            const respuesta = await fetch(`${aviso.dataset.url}?${query}`, {headers: {Accept: 'application/json'}});
            const datos = await respuesta.json();
            if (turno !== secuencia) return;
            aviso.textContent = respuesta.ok ? datos.mensaje : datos.message;
            if (respuesta.ok) importe.value = datos.importe;
        } catch {
            if (turno === secuencia) aviso.textContent = 'No se pudo consultar la inscripción. Revise la conexión antes de guardar.';
        }
    }
    dni.addEventListener('change', actualizar);
    fecha.addEventListener('change', actualizar);
    actualizar();
}

const cuotaAlta = document.getElementById('cuota-alta-aviso');
if (cuotaAlta) {
    const fecha = document.getElementById('fecha_alta');
    const plan = document.getElementById('plan_id');
    const grupo = document.getElementById('grupo_id');
    const mensaje = document.getElementById('cuota-alta-mensaje');
    const opcionSi = document.getElementById('cuota-alta-si');
    const periodoVisto = document.getElementById('cuota-periodo-visto');
    const importeVisto = document.getElementById('cuota-importe-visto');
    const opciones = [...cuotaAlta.querySelectorAll('input[type="radio"]')];
    const pesos = new Intl.NumberFormat('es-AR', {style: 'currency', currency: 'ARS', maximumFractionDigits: 2});
    let secuencia = 0;

    async function actualizarCuota(limpiarDecision = false) {
        const turno = ++secuencia;
        periodoVisto.value = '';
        importeVisto.value = '';
        opciones.forEach(opcion => {
            opcion.disabled = true;
            opcion.required = false;
            if (limpiarDecision) opcion.checked = false;
        });
        fecha.setCustomValidity('Esperá a verificar la cuota del alta.');
        if (!fecha.value) {
            cuotaAlta.hidden = true;
            fecha.setCustomValidity('Ingresá la fecha real de ingreso.');
            return;
        }
        try {
            const query = new URLSearchParams({fecha_alta: fecha.value});
            if (plan.value) query.set('plan_id', plan.value);
            const respuesta = await fetch(`${cuotaAlta.dataset.previewUrl}?${query}`, {headers: {Accept: 'application/json'}});
            const datos = await respuesta.json();
            if (turno !== secuencia) return;
            if (!respuesta.ok) throw new Error('No se pudo verificar la cuota. Revisá la fecha y el plan antes de guardar.');
            cuotaAlta.hidden = !datos.mes_cerrado;
            fecha.setCustomValidity('');
            if (!datos.mes_cerrado) return;
            mensaje.textContent = `Este alumno ingresó en ${datos.mes_ingreso}, un mes ya cerrado. ¿Le generamos la cuota de este mes?`;
            const tienePlan = datos.importe !== null;
            opcionSi.textContent = tienePlan
                ? `Generar ${datos.mes_cuota} por ${pesos.format(datos.importe)}, la cuota completa.`
                : 'Elegí la frecuencia semanal para ver el importe de la cuota.';
            opciones.forEach(opcion => {
                opcion.disabled = !tienePlan;
                opcion.required = tienePlan;
            });
            if (tienePlan) {
                periodoVisto.value = datos.periodo;
                importeVisto.value = datos.importe;
            }
        } catch (error) {
            if (turno !== secuencia) return;
            cuotaAlta.hidden = false;
            mensaje.textContent = error.message;
            fecha.setCustomValidity('No se pudo verificar la cuota. Revisá la conexión y la fecha antes de guardar.');
        }
    }

    fecha.addEventListener('input', () => actualizarCuota(true));
    plan.addEventListener('change', () => actualizarCuota(true));
    grupo.addEventListener('change', () => actualizarCuota(true));
    actualizarCuota();
}
