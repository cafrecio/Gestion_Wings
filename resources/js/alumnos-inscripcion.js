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
