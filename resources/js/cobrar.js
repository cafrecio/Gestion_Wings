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
        checks().forEach(function (chk) {
            if (chk.checked) {
                var periodo = chk.dataset.periodo;
                var inp = document.querySelector('.monto-cuota[data-periodo="' + periodo + '"]');
                var val = inp ? parseMonto(inp.value) : 0;
                // Un mes adelantado no tiene saldo que lo tope: vale lo que se cobre.
                if (chk.dataset.adelantado) { total += Math.max(val, 0); return; }
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

            if (resultado.status === 409 && resultado.datos.requiere_confirmacion_adelantado) {
                if (window.confirm(resultado.datos.message + '\n\n¿Confirmás el cobro?')) {
                    formData.set('confirmar_pago_adelantado', '1');
                    enviarCobro(formData);
                }
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
                inp.value = sugerido.toLocaleString('es-AR', { maximumFractionDigits: 0 });
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

    function formatoMonto(numero) {
        return Number(numero).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    function actualizarAdelanto() {
        var listo = adelantoPeriodo.value !== '' && parseMonto(adelantoMonto.value) > 0;
        adelantoAgregar.disabled = !listo;
        adelantoAgregar.style.opacity = listo ? '1' : '0.4';
        adelantoAgregar.style.cursor = listo ? 'pointer' : 'not-allowed';
    }

    function agregarAdelanto() {
        var opcion = adelantoPeriodo.options[adelantoPeriodo.selectedIndex];
        var monto = parseMonto(adelantoMonto.value);
        if (!opcion || !opcion.value || monto <= 0) return;

        var fila = document.createElement('div');
        fila.className = 'cuota-row';
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
        nombre.style.cssText = 'flex:1; cursor:default;';
        var etiqueta = document.createElement('span');
        etiqueta.style.cssText = 'font-size:0.85rem; font-weight:600; color:var(--color-text);';
        etiqueta.textContent = opcion.textContent;
        var marca = document.createElement('span');
        marca.style.cssText = 'font-size:0.7rem; font-weight:600; padding:2px 8px; border-radius:999px; margin-left:8px; background:color-mix(in srgb, var(--color-info) 15%, transparent); color:var(--color-info);';
        marca.textContent = 'Adelantado';
        nombre.appendChild(etiqueta);
        nombre.appendChild(marca);

        var inp = document.createElement('input');
        inp.type = 'text';
        inp.inputMode = 'numeric';
        inp.name = 'montos_cuota[' + opcion.value + ']';
        inp.className = 'monto-cuota wings-input';
        inp.dataset.periodo = opcion.value;
        inp.value = formatoMonto(monto);
        inp.style.cssText = 'width:110px; padding:4px 10px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-text);';

        fila.appendChild(chk);
        fila.appendChild(nombre);
        fila.appendChild(inp);
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
            adelantoMonto.value = opcion && opcion.dataset.precio ? formatoMonto(opcion.dataset.precio) : '';
            actualizarAdelanto();
        });
        adelantoMonto.addEventListener('input', actualizarAdelanto);
        adelantoAgregar.addEventListener('click', agregarAdelanto);
    }

    aplicarEstiloPlan();
})();
