(function () {
    var checks      = document.querySelectorAll('.cuota-check');
    var montoInputs = document.querySelectorAll('.monto-cuota');
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

    function parseMonto(str) {
        // Remove thousand separators (dots) and replace comma decimal with dot
        return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
    }

    // El mes de alta llega ya descontado desde el servidor, así que el tope de cada
    // período es el que se ve en pantalla. No hay que volver a aplicar el porcentaje
    // acá: hacerlo descontaba también las señas y anunciaba menos de lo que se cobra.
    var periodoConDescuento = document.getElementById('cobrar-form').dataset.periodoDescuento || null;

    function calcularTotal() {
        var total = 0;
        checks.forEach(function (chk) {
            if (chk.checked) {
                var periodo = chk.dataset.periodo;
                var inp = document.querySelector('.monto-cuota[data-periodo="' + periodo + '"]');
                var val = inp ? parseMonto(inp.value) : 0;
                var saldo = parseFloat(chk.dataset.saldo) || 0;
                total += Math.min(Math.max(val, 0), saldo);
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
        var total = entregado && entregado.value !== '' ? Number(entregado.value) : maximo;
        var detalle = document.getElementById('inscripcion-distribucion');
        if (detalle) detalle.textContent = 'Inscripción: $' + Math.min(total, saldoInscripcion).toLocaleString('es-AR') + ' · Cuotas: $' + Math.max(0, total - saldoInscripcion).toLocaleString('es-AR');

        if ((algunaCuota || saldoInscripcion > 0) && tipoCaja && total > 0 && total <= maximo) {
            resumen.style.display = '';
            totalLabel.textContent = '$' + total.toLocaleString('es-AR', { maximumFractionDigits: 0 });
            btnCobrar.disabled = false;
            btnCobrar.style.opacity = '1';
            btnCobrar.style.cursor = 'pointer';
        } else {
            resumen.style.display = 'none';
            btnCobrar.disabled = true;
            btnCobrar.style.opacity = '0.4';
            btnCobrar.style.cursor = 'not-allowed';
        }
    }

    checks.forEach(function (chk) {
        chk.addEventListener('change', function () {
            var periodo = this.dataset.periodo;
            var inp = document.querySelector('.monto-cuota[data-periodo="' + periodo + '"]');
            if (inp) {
                if (this.checked) {
                    inp.removeAttribute('disabled');
                } else {
                    inp.setAttribute('disabled', 'disabled');
                }
            }
            actualizar();
        });
    });

    montoInputs.forEach(function (inp) {
        inp.addEventListener('input', actualizar);
    });

    selectTipo.addEventListener('change', actualizar);
    if (entregado) entregado.addEventListener('input', actualizar);

    function cerrarModalDeuda() {
        modalDeuda.style.display = 'none';
        formConfirmarDeuda.reset();
        datosCobroPendientes = null;
    }

    function abrirModalDeuda(datos, formData) {
        datosCobroPendientes = formData;
        mensajeDeuda.textContent = datos.message;
        modalDeuda.style.display = 'flex';
        formConfirmarDeuda.querySelector('textarea[name="motivo"]').focus();
    }

    function enviarCobro(formData) {
        btnCobrar.disabled = true;

        fetch(cobrarForm.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        }).then(function (response) {
            if (response.redirected) {
                window.location.assign(response.url);
                return null;
            }

            return response.json().then(function (datos) {
                return { status: response.status, ok: response.ok, datos: datos };
            });
        }).then(function (resultado) {
            if (!resultado) return;

            if (resultado.status === 409 && resultado.datos.requiere_confirmacion) {
                abrirModalDeuda(resultado.datos, formData);
                return;
            }

            if (resultado.status === 409 && resultado.datos.requiere_confirmacion_fecha_vieja) {
                if (window.confirm(resultado.datos.message + '\n\n¿Desea continuar con el cobro?')) {
                    formData.set('confirmar_fecha_vieja', '1');
                    enviarCobro(formData);
                }
                return;
            }

            if (!resultado.ok) {
                window.alert(resultado.datos.message || 'No se pudo registrar el cobro.');
            }
        }).catch(function () {
            window.alert('No se pudo registrar el cobro.');
        }).finally(function () {
            actualizar();
        });
    }

    cobrarForm.addEventListener('submit', function (event) {
        event.preventDefault();
        enviarCobro(new FormData(cobrarForm));
    });

    formConfirmarDeuda.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!datosCobroPendientes) return;

        var motivo = formConfirmarDeuda.querySelector('textarea[name="motivo"]').value.trim();
        if (!motivo) return;

        datosCobroPendientes.set('confirmar_deuda_anterior', '1');
        datosCobroPendientes.set('motivo', motivo);
        modalDeuda.style.display = 'none';
        enviarCobro(datosCobroPendientes);
    });

    cerrarDeuda.addEventListener('click', cerrarModalDeuda);
    modalDeuda.addEventListener('click', function (event) {
        if (event.target === modalDeuda) cerrarModalDeuda();
    });

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
            checks.forEach(function (chk) {
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
                inp.value = sugerido.toLocaleString('es-AR', { maximumFractionDigits: 0 });
            });

            actualizar();
        });
    });

    aplicarEstiloPlan();
})();
