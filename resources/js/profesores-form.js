// Comportamiento existente de HORA/COMISION; datos del deporte en las opciones, sin JS en Blade.
const deporteSelect = document.getElementById('deporte_id');
const fieldValorHora = document.getElementById('field-valor-hora');
const fieldComision = document.getElementById('field-porcentaje-comision');
const inputValorHora = document.getElementById('valor_hora');
const inputComision = document.getElementById('porcentaje_comision');
function actualizarCamposLiquidacion() {
    const tipo = deporteSelect?.selectedOptions[0]?.dataset.tipo;
    fieldValorHora.style.display = tipo === 'HORA' ? '' : 'none';
    fieldComision.style.display = tipo === 'COMISION' ? '' : 'none';
    if (tipo === 'HORA') inputComision.value = '';
    else if (tipo === 'COMISION') window.WingsMoney.asignar(inputValorHora, '');
}
if (deporteSelect && fieldValorHora && fieldComision) {
    deporteSelect.addEventListener('change', actualizarCamposLiquidacion);
    actualizarCamposLiquidacion();
}
