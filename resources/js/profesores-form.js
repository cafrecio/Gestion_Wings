// Comportamiento existente de HORA/COMISION; datos del deporte en las opciones, sin JS en Blade.
const deporteSelect = document.getElementById('deporte_id');
const fieldValorHora = document.getElementById('field-valor-hora');
const fieldComision = document.getElementById('field-porcentaje-comision');
function actualizarCamposLiquidacion() {
    const opcion = deporteSelect?.selectedOptions[0];
    const tipo = opcion?.dataset.tipo;
    const siguiente = opcion?.dataset.tipoSiguiente;
    const programado = siguiente && tipo !== siguiente;
    fieldValorHora.style.display = tipo === 'HORA' || (programado && siguiente === 'HORA') ? '' : 'none';
    fieldComision.style.display = tipo === 'COMISION' || (programado && siguiente === 'COMISION') ? '' : 'none';
    const aviso = document.getElementById('profesor-vigencia');
    if (aviso) {
        aviso.hidden = !programado;
        aviso.textContent = programado ? 'Desde el ' + opcion.dataset.vigenteDesde + ': ' + (siguiente === 'HORA' ? 'por hora' : 'por comisión') + '. Podés completar ambos valores sin borrar el anterior.' : '';
    }
}
if (deporteSelect && fieldValorHora && fieldComision) {
    deporteSelect.addEventListener('change', actualizarCamposLiquidacion);
    actualizarCamposLiquidacion();
}
