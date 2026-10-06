document.addEventListener('DOMContentLoaded', function () {
    var card = document.getElementById('tipo-card');
    if (!card) return;

    var tipoIngreso = document.getElementById('tipo-ingreso');
    var tipoEgreso  = document.getElementById('tipo-egreso');
    var btnIngreso  = document.getElementById('btn-ingreso');
    var btnEgreso   = document.getElementById('btn-egreso');
    var selectRubro = document.getElementById('rubro_id');
    var selectSub   = document.getElementById('subrubro_id');

    if (!tipoIngreso || !tipoEgreso || !btnIngreso || !btnEgreso || !selectRubro || !selectSub) return;

    var rubroActual    = card.dataset.rubroActual || '';
    var subrubroActual = card.dataset.subrubroActual || '';

    function actualizarBotonesTipo(tipo) {
        if (tipo === 'INGRESO') {
            btnIngreso.style.borderColor = 'var(--color-success)';
            btnIngreso.style.color       = 'var(--color-success)';
            btnIngreso.style.background  = 'color-mix(in srgb, var(--color-success) 8%, transparent)';
            btnEgreso.style.borderColor  = 'var(--color-border)';
            btnEgreso.style.color        = 'var(--color-text-muted)';
            btnEgreso.style.background   = 'transparent';
            card.style.borderLeft        = '4px solid var(--color-success)';
        } else {
            btnEgreso.style.borderColor  = 'var(--color-danger)';
            btnEgreso.style.color        = 'var(--color-danger)';
            btnEgreso.style.background   = 'color-mix(in srgb, var(--color-danger) 8%, transparent)';
            btnIngreso.style.borderColor = 'var(--color-border)';
            btnIngreso.style.color       = 'var(--color-text-muted)';
            btnIngreso.style.background  = 'transparent';
            card.style.borderLeft        = '4px solid var(--color-danger)';
        }
        filtrarRubros(tipo);
    }

    function filtrarRubros(tipo) {
        var opts = selectRubro.querySelectorAll('option[data-tipo]');
        opts.forEach(function (opt) {
            opt.style.display = (!tipo || opt.dataset.tipo === tipo) ? '' : 'none';
        });
        if (selectRubro.value) {
            var selected = selectRubro.querySelector('option[value="' + selectRubro.value + '"]');
            if (selected && selected.style.display === 'none') {
                selectRubro.value = '';
                filtrarSubrubros('');
            } else {
                filtrarSubrubros(selectRubro.value);
            }
        }
    }

    function filtrarSubrubros(rubroId) {
        var opts = selectSub.querySelectorAll('option[data-rubro]');
        opts.forEach(function (opt) {
            opt.style.display = (!rubroId || opt.dataset.rubro === String(rubroId)) ? '' : 'none';
        });
        if (selectSub.value) {
            var sel = selectSub.querySelector('option[value="' + selectSub.value + '"]:not([style*="display: none"])');
            if (!sel) selectSub.value = '';
        }
        var defaultOpt = selectSub.querySelector('option[value=""]');
        if (defaultOpt) {
            if (!rubroId) {
                defaultOpt.textContent = 'Seleccionar rubro primero...';
            } else {
                defaultOpt.textContent = 'Seleccionar...';
            }
        }
    }

    tipoIngreso.addEventListener('change', function () { if (this.checked) actualizarBotonesTipo('INGRESO'); });
    tipoEgreso.addEventListener('change',  function () { if (this.checked) actualizarBotonesTipo('EGRESO'); });
    selectRubro.addEventListener('change', function () { filtrarSubrubros(this.value); });

    // Inicialización
    var tipoInicial = tipoIngreso.checked ? 'INGRESO' : 'EGRESO';
    actualizarBotonesTipo(tipoInicial);
    filtrarSubrubros(rubroActual);
    if (subrubroActual) selectSub.value = subrubroActual;
});
