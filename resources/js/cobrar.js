import {pesos} from './formato-importe';

(function () {
    // Las filas de cobro adelantado se agregan después de cargar la página (T18), así
    // que las cuotas se buscan cada vez en lugar de guardarlas al inicio.
    function checks() { return document.querySelectorAll('.cuota-check'); }
    var selectTipo  = document.getElementById('tipo_caja_id');
    var btnCobrar   = document.getElementById('btn-cobrar');
    var resumen     = document.getElementById('resumen-total');
    var totalLabel  = document.getElementById('total-label');
    var cobrarForm  = document.getElementById('cobrar-form');
    var modalDeuda  = document.getElementById('modal-deuda-anterior');
    var mensajeDeuda = document.getElementById('mensaje-deuda-anterior');
    var formConfirmarDeuda = document.getElementById('form-confirmar-deuda-anterior');
    var cerrarDeuda = document.getElementById('cerrar-deuda-anterior');
    var datosCobroPendientes = null;
    var confirmacionCampo = null;
    var requiereMotivo = false;
    var enviando = false;
    var errorCobro = document.getElementById('error-cobro');
    var motivoBloque = document.getElementById('motivo-confirmacion-cobro');
    var motivoInput = formConfirmarDeuda.querySelector('textarea[name="motivo"]');
    function mostrarError(texto) { errorCobro.textContent = texto; errorCobro.hidden = !texto; }


    // El mes de alta llega ya descontado desde el servidor, así que el tope de cada
    // período es el que se ve en pantalla. No hay que volver a aplicar el porcentaje
    // acá: hacerlo descontaba también las señas y anunciaba menos de lo que se cobra.
    var periodoConDescuento = document.getElementById('cobrar-form').dataset.periodoDescuento || null;

    function calcularTotal() {
        var total = 0;
        checks().forEach(function (chk) {
            if (chk.checked) {
                var periodo = chk.dataset.periodo;
                var inp = document.querySelector('.monto-cuota[data-periodo="' + periodo + '"]');
                var val = inp ? window.WingsMoney.numero(inp) : 0;
                // Un mes adelantado no tiene saldo que lo tope: vale lo que se cobre.
                if (chk.dataset.adelantado) { total += Math.max(val, 0); return; }
                var saldo = parseFloat(chk.dataset.saldo) || 0;
                total += Math.min(Math.max(val, 0), saldo);
                if (val >= saldo) total += parseFloat(chk.dataset.recargo || '0');
            }
        });
        return total;
    }

    var entregado = document.getElementById('monto-entregado');
    var saldoInscripcion = parseFloat(cobrarForm.dataset.inscripcion || '0');
    function actualizar() {
        var algunaCuota = document.querySelectorAll('.cuota-check:checked').length > 0;
        var tipoCaja    = selectTipo.value !== '';
        var maximo = calcularTotal() + saldoInscripcion;
        var total = entregado && entregado.value !== '' ? window.WingsMoney.numero(entregado) : maximo;
        var detalle = document.getElementById('inscripcion-distribucion');
        if (detalle) {
            var resto = Math.max(0, total - saldoInscripcion);
            var cuotas = 0, recargos = 0;
            Array.from(checks()).filter(chk => chk.checked).sort((a, b) => a.dataset.periodo.localeCompare(b.dataset.periodo)).forEach(function (chk) {
                var inp = document.querySelector('.monto-cuota[data-periodo="' + chk.dataset.periodo + '"]');
                var capital = inp ? Math.max(0, window.WingsMoney.numero(inp)) : 0;
                var saldo = parseFloat(chk.dataset.saldo) || 0;
                var cubierto = Math.min(resto, chk.dataset.adelantado ? capital : Math.min(capital, saldo));
                cuotas += cubierto;
                resto = Math.max(0, resto - cubierto);
                if (cubierto >= saldo && !chk.dataset.adelantado) {
                    var recargo = Math.min(resto, parseFloat(chk.dataset.recargo || '0'));
                    recargos += recargo;
                    resto = Math.max(0, resto - recargo);
                }
            });
            detalle.querySelector('[data-distribucion-inscripcion]').textContent = 'Inscripción ' + pesos(Math.min(total, saldoInscripcion));
            detalle.querySelector('[data-distribucion-cuotas]').textContent = 'Cuotas ' + pesos(cuotas);
            var detalleRecargos = detalle.querySelector('[data-distribucion-recargos]');
            if (detalleRecargos) detalleRecargos.textContent = 'Recargos ' + pesos(recargos);
        }

        if ((algunaCuota || saldoInscripcion > 0) && tipoCaja && total > 0 && total <= maximo) {
            resumen.style.display = '';
            totalLabel.textContent = pesos(total);
            btnCobrar.disabled = false;
            btnCobrar.style.opacity = '1';
            btnCobrar.style.cursor = 'pointer';
        } else {
            resumen.style.display = 'none';
            btnCobrar.disabled = true;
            btnCobrar.style.opacity = '0.4';
            btnCobrar.style.cursor = 'not-allowed';
        }
        if (enviando || datosCobroPendientes) btnCobrar.disabled = true;
    }

    cobrarForm.addEventListener('change', function (event) {
        var chk = event.target;
        if (!chk.classList || !chk.classList.contains('cuota-check')) return;
        var inp = document.querySelector('.monto-cuota[data-periodo="' + chk.dataset.periodo + '"]');
        if (inp) {
            if (chk.checked) {
                inp.removeAttribute('disabled');
            } else {
                inp.setAttribute('disabled', 'disabled');
            }
        }
        actualizar();
    });

    cobrarForm.addEventListener('input', function (event) {
        if (event.target.classList && event.target.classList.contains('monto-cuota')) actualizar();
    });

    selectTipo.addEventListener('change', actualizar);
    if (entregado) entregado.addEventListener('input', actualizar);

    // T25: a un inactivo se le pregunta si vuelve. «No» falta cuando el cobro incluye el mes.
    var modalReactivar = document.getElementById('modal-reactivar');
    var mensajeReactivar = document.getElementById('mensaje-reactivar');
    var reactivarNo = document.getElementById('reactivar-no');
    var reactivarSi = document.getElementById('reactivar-si');

    function cerrarModalDeuda() {
        modalDeuda.style.display = 'none';
        modalReactivar.style.display = 'none';
        formConfirmarDeuda.reset();
        datosCobroPendientes = null;
        actualizar();
    }

    function abrirModalReactivar(datos, formData) {
        datosCobroPendientes = formData;
        mensajeReactivar.textContent = datos.message;
        reactivarNo.style.display = datos.permite_no ? '' : 'none';
        modalReactivar.style.display = 'block';
        modalReactivar.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        reactivarSi.focus({preventScroll: true});
    }

    function responderReactivar(valor) {
        if (!datosCobroPendientes || enviando) return;
        var datos = datosCobroPendientes;
        datos.set('reactivar', valor);
        cerrarModalDeuda();
        enviarCobro(datos);
    }

    function abrirModalDeuda(datos, formData, campo) {
        datosCobroPendientes = formData;
        confirmacionCampo = campo;
        requiereMotivo = !!datos.requiere_motivo;
        motivoBloque.hidden = !requiereMotivo;
        motivoInput.required = requiereMotivo;
        mensajeDeuda.textContent = datos.message;
        modalDeuda.style.display = 'block';
        modalDeuda.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        if (requiereMotivo) motivoInput.focus({preventScroll: true});
        else formConfirmarDeuda.querySelector('button[type="submit"]').focus({preventScroll: true});
    }

    function enviarCobro(formData) {
        if (enviando) return;
        enviando = true;
        btnCobrar.disabled = true;
        formConfirmarDeuda.querySelector('button[type="submit"]').disabled = true;
        mostrarError('');
        fetch(cobrarForm.action, {
            method: 'POST',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        }).then(function (response) {
            if (response.redirected) { window.location.assign(response.url); return null; }
            return response.json().then(function (datos) { return {status: response.status, ok: response.ok, datos: datos}; });
        }).then(function (resultado) {
            if (!resultado) return;
            var datos = resultado.datos;
            if (resultado.status === 409) {
                var campo = datos.requiere_confirmacion ? 'confirmar_deuda_anterior'
                    : datos.requiere_confirmacion_adelantado ? 'confirmar_pago_adelantado'
                    : datos.requiere_confirmacion_fecha_vieja ? 'confirmar_fecha_vieja' : null;
                if (campo) { abrirModalDeuda(datos, formData, campo); return; }
                if (datos.requiere_reactivacion) { abrirModalReactivar(datos, formData); return; }
            }
            if (!resultado.ok) {
                var errores = datos.errors ? Object.values(datos.errors).flat().join(' ') : '';
                mostrarError(errores || datos.message || 'No se pudo registrar el cobro.');
            }
        }).catch(function () { mostrarError('No se pudo registrar el cobro. Revisá la conexión e intentá nuevamente.'); })
        .finally(function () {
            enviando = false;
            formConfirmarDeuda.querySelector('button[type="submit"]').disabled = false;
            actualizar();
        });
    }

    cobrarForm.addEventListener('submit', function (event) {
        event.preventDefault();
        cerrarModalDeuda();
        enviarCobro(window.WingsMoney.prepararDatos(cobrarForm, new FormData(cobrarForm)));
    });
    // Si cambia el cobro, se descarta la confirmación pendiente de los datos anteriores.
    cobrarForm.addEventListener('input', cerrarModalDeuda);
    cobrarForm.addEventListener('change', cerrarModalDeuda);
    formConfirmarDeuda.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!datosCobroPendientes || enviando) return;
        var motivo = motivoInput.value.trim();
        if (requiereMotivo && !motivo) { motivoInput.focus(); return; }
        var datos = datosCobroPendientes;
        datos.set(confirmacionCampo, '1');
        if (requiereMotivo) datos.set('motivo', motivo);
        cerrarModalDeuda();
        enviarCobro(datos);
    });
    cerrarDeuda.addEventListener('click', cerrarModalDeuda);
    document.getElementById('reactivar-cancelar').addEventListener('click', cerrarModalDeuda);
    reactivarNo.addEventListener('click', function () { responderReactivar('0'); });
    reactivarSi.addEventListener('click', function () { responderReactivar('1'); });

    document.querySelectorAll('.cuota-row').forEach(function (row) {
        row.addEventListener('mouseenter', function () { this.style.background = 'var(--color-surface-alt)'; });
        row.addEventListener('mouseleave', function () { this.style.background = 'var(--color-surface)'; });
    });

    // Cambio de plan: actualizar montos sugeridos
    var planRadios = document.querySelectorAll('input[name="nuevo_plan_id"]');

    function aplicarEstiloPlan() {
        planRadios.forEach(function (r) {
            var label = r.closest('.plan-label');
            if (!label) return;
            if (r.checked) {
                label.style.borderColor = 'var(--color-btn-primary)';
                label.style.background  = 'var(--color-surface-alt)';
            } else {
                label.style.borderColor = 'var(--color-border)';
                label.style.background  = 'var(--color-surface)';
            }
        });
    }

    planRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            aplicarEstiloPlan();
            // Lo que cuesta el mes con este plan lo calcula el servidor, con la bajada
            // diferida y el descuento de primer pago ya aplicados. Aca no se hace cuenta:
            // hacerla con el precio de lista anunciaba 60.000 donde se cobraban 42.000.
            var nuevoPrecio = parseFloat(this.dataset.precioMes);
            if (isNaN(nuevoPrecio) || nuevoPrecio <= 0) return;

            // Solo la cuota del mes en curso toma el precio nuevo. Las deudas
            // anteriores no se tocan.
            var periodoActual = cobrarForm.dataset.periodoActual;
            var opcionActual = document.querySelector('#adelanto-periodo option[value="' + periodoActual + '"]');
            if (opcionActual) {
                opcionActual.dataset.precio = nuevoPrecio;
                if (adelantoPeriodo.value === periodoActual) window.WingsMoney.asignar(adelantoMonto, nuevoPrecio);
            }
            checks().forEach(function (chk) {
                if (chk.dataset.periodo !== periodoActual) return;
                var inp = document.querySelector('.monto-cuota[data-periodo="' + periodoActual + '"]');
                if (!inp) return;
                var pagado = parseFloat(chk.dataset.pagado) || 0;
                var sugerido = Math.max(nuevoPrecio - pagado, 0);
                // El tope tiene que moverse junto con el precio. El servidor sube la
                // deuda del mes al plan nuevo y cobra eso; si data-saldo se queda con
                // el precio viejo, calcularTotal() topea ahi y la pantalla anuncia
                // menos de lo que se registra.
                chk.dataset.saldo = sugerido;
                window.WingsMoney.asignar(inp, sugerido);
            });

            actualizar();
        });
    });

    // ── Cobro adelantado (T18) ──────────────────────────────────────────────
    // El mes se elige aparte de la deuda y el importe se puede cambiar: pagar
    // adelantado congela el precio, y a veces el aumento ya está anunciado.
    var adelantoPeriodo = document.getElementById('adelanto-periodo');
    var adelantoMonto   = document.getElementById('adelanto-monto');
    var adelantoAgregar = document.getElementById('adelanto-agregar');
    var adelantosLista  = document.getElementById('adelantos-lista');

    function actualizarAdelanto() {
        var listo = adelantoPeriodo.value !== '' && window.WingsMoney.validar(adelantoMonto) && window.WingsMoney.numero(adelantoMonto) > 0;
        adelantoAgregar.disabled = !listo;
        adelantoAgregar.style.opacity = listo ? '1' : '0.4';
        adelantoAgregar.style.cursor = listo ? 'pointer' : 'not-allowed';
    }

    function agregarAdelanto() {
        var opcion = adelantoPeriodo.options[adelantoPeriodo.selectedIndex];
        if (!window.WingsMoney.validar(adelantoMonto)) return;
        var monto = window.WingsMoney.numero(adelantoMonto);
        if (!opcion || !opcion.value || monto <= 0) return;

        var fila = document.createElement('div');
        fila.className = 'cuota-row cuota-row--adelantado';
        fila.style.cssText = 'display:flex; align-items:center; gap:12px; padding:10px 14px; border:1px solid var(--color-border); border-radius:8px; background:var(--color-surface);';

        var chk = document.createElement('input');
        chk.type = 'checkbox';
        chk.name = 'periodos[]';
        chk.value = opcion.value;
        chk.className = 'cuota-check';
        chk.checked = true;
        chk.dataset.periodo = opcion.value;
        chk.dataset.adelantado = '1';
        chk.style.cssText = 'width:16px; height:16px; cursor:pointer; flex-shrink:0; accent-color:var(--color-btn-primary);';

        var nombre = document.createElement('div');
        nombre.className = 'adelanto-nombre';
        nombre.style.cssText = 'flex:1; cursor:default;';
        var etiqueta = document.createElement('span');
        etiqueta.className = 'adelanto-mes';
        etiqueta.style.cssText = 'font-size:0.85rem; font-weight:600; color:var(--color-text);';
        etiqueta.textContent = opcion.textContent;
        var marca = document.createElement('span');
        marca.className = 'adelanto-marca';
        marca.style.cssText = 'font-size:0.7rem; font-weight:600; padding:2px 8px; border-radius:999px; margin-left:8px; background:color-mix(in srgb, var(--color-info) 15%, transparent); color:var(--color-info);';
        var esMesActual = opcion.value === cobrarForm.dataset.periodoActual;
        marca.textContent = esMesActual ? 'Mes actual' : 'Adelantado';
        nombre.appendChild(etiqueta);
        nombre.appendChild(marca);

        var importeWrap = document.getElementById('adelanto-importe-template').content.firstElementChild.cloneNode(true);
        var inp = importeWrap.querySelector('[data-money="true"]');
        inp.name = 'montos_cuota[' + opcion.value + ']';
        inp.dataset.periodo = opcion.value;
        window.WingsMoney.asignar(inp, monto);

        fila.appendChild(chk);
        if (esMesActual) {
            var eleccionManual = document.createElement('input');
            eleccionManual.type = 'hidden';
            eleccionManual.name = 'periodos_manuales[]';
            eleccionManual.value = opcion.value;
            fila.appendChild(eleccionManual);
        }
        fila.appendChild(nombre);
        fila.appendChild(importeWrap);
        adelantosLista.appendChild(fila);

        // Un mes no se agrega dos veces.
        opcion.disabled = true;
        adelantoPeriodo.value = '';
        adelantoMonto.value = '';
        actualizarAdelanto();
        actualizar();
    }

    if (adelantoPeriodo && adelantoMonto && adelantoAgregar && adelantosLista) {
        adelantoPeriodo.addEventListener('change', function () {
            var opcion = this.options[this.selectedIndex];
            window.WingsMoney.asignar(adelantoMonto, opcion && opcion.dataset.precio ? opcion.dataset.precio : '');
            actualizarAdelanto();
        });
        adelantoMonto.addEventListener('input', actualizarAdelanto);
        adelantoAgregar.addEventListener('click', agregarAdelanto);
    }

    aplicarEstiloPlan();
})();
