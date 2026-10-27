// Ficha de la clase: asistencia, cancelación y profesoras (T73, antes incrustado en clases/show).
(function () {
    const datos      = document.getElementById('clase-show-datos').dataset;
    const infoSemana = JSON.parse(datos.infoSemana);
    const claseId    = Number(datos.claseId);
    // El motivo solo se exige cuando se corrige algo YA cargado, no en la
    // primera carga de una clase que quedó sin tomar.
    const requiereMotivoAsistencia = datos.correccionAsistencia === '1';
    const requiereMotivoProfesores = datos.correccionProfesores === '1';
    const csrf       = document.querySelector('meta[name="csrf-token"]').content;

    // ── Contador de presentes ───────────────────────────────────────────────
    function actualizarContador() {
        const presentes = document.querySelectorAll('.presente-checkbox:checked').length;
        document.getElementById('contador-presentes').innerHTML = '<strong>' + presentes + '</strong>';
    }

    // ── Checkboxes de asistencia ────────────────────────────────────────────
    function mostrarExcepcion(card, presente) {
        const panel = document.getElementById('excepcion-asistencia-' + card.dataset.alumnoId);
        panel.hidden = !presente || (card.dataset.requiereExcepcion !== '1' && card.dataset.motivoGuardado !== '1');
    }
    document.querySelectorAll('.presente-checkbox').forEach(function (cb) {
        mostrarExcepcion(document.getElementById('card-' + cb.dataset.alumnoId), cb.checked);
        if (cb.checked) {
            const info = infoSemana[cb.dataset.alumnoId] || {};
            if (info.excede) {
                document.getElementById('exceso-' + cb.dataset.alumnoId).style.display = 'block';
            }
        }

        cb.addEventListener('change', function () {
            const aid    = this.dataset.alumnoId;
            const card   = document.getElementById('card-' + aid);
            const exceso = document.getElementById('exceso-' + aid);
            const info   = infoSemana[aid] || {};

            if (this.checked) {
                const nuevaCuenta = (info.asistencias_semana || 0) + 1;
                if (info.plan_semana && nuevaCuenta > info.plan_semana) {
                    exceso.style.display = 'block';
                    card.style.background = 'color-mix(in srgb, var(--color-warning) 8%, var(--color-surface))';
                } else {
                    exceso.style.display = 'none';
                    card.style.background = 'color-mix(in srgb, var(--color-success) 8%, var(--color-surface))';
                }
            } else {
                card.style.background = '';
                exceso.style.display  = 'none';
            }
            mostrarExcepcion(card, this.checked);
            actualizarContador();
        });
    });
    actualizarContador();

    // ── Guardar asistencias ─────────────────────────────────────────────────
    const btnGuardar = document.getElementById('btn-guardar-asistencias');
    const toast       = document.getElementById('toast-asistencias');
    let toastTimeout  = null;

    function mostrarToast(mensaje, ok) {
        if (!toast) return;
        clearTimeout(toastTimeout);
        toast.textContent = (ok ? '✓ ' : '✗ ') + mensaje;
        toast.style.background = ok ? 'var(--color-success)' : 'var(--color-danger)';
        toast.style.color = '#fff';
        toast.style.display = 'block';
        toastTimeout = setTimeout(function () {
            toast.style.display = 'none';
        }, ok ? 3000 : 4500);
    }

    const inputMotivoAsistencias = document.getElementById('input-motivo-asistencias');

    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            const motivo = inputMotivoAsistencias ? inputMotivoAsistencias.value.trim() : '';
            if (requiereMotivoAsistencia && !motivo) {
                mostrarToast('Indicá el motivo de la corrección.', false);
                if (inputMotivoAsistencias) inputMotivoAsistencias.focus();
                return;
            }

            const items = [];
            let faltaMotivo = null;
            document.querySelectorAll('.alumno-asistencia-card').forEach(function (card) {
                const aid    = card.dataset.alumnoId;
                const cb     = card.querySelector('.presente-checkbox');
                const sel    = card.querySelector('select[id^="motivo-"]');
                const motivoExcepcion = document.getElementById('motivo-excepcion-' + aid);
                if (cb.checked && card.dataset.requiereExcepcion === '1' && !motivoExcepcion.value.trim() && !faltaMotivo) faltaMotivo = motivoExcepcion;
                items.push({ alumno_id: parseInt(aid), presente: cb && cb.checked ? 1 : 0, motivo_exceso: sel ? sel.value : null,
                    motivo_excepcion: motivoExcepcion.value.trim() });
            });
            if (faltaMotivo) {
                mostrarToast('Indicá el motivo para admitir al alumno.', false);
                faltaMotivo.focus();
                return;
            }
            btnGuardar.disabled = true;
            btnGuardar.textContent = '…';

            fetch('/clases/' + claseId + '/asistencias', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ items: items, motivo: motivo }),
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success && d.condiciones) {
                    const etiquetas = { AL_DIA: 'Al día', EN_PLAZO: 'En plazo', MOROSO: 'Moroso', DEUDOR: 'Deudor' };
                    Object.entries(d.condiciones).forEach(function ([aid, condicion]) {
                        const card = document.getElementById('card-' + aid);
                        if (!card) return;
                        card.dataset.requiereExcepcion = condicion.requiere_motivo ? '1' : '0';
                        card.dataset.motivoGuardado = condicion.motivo_guardado ? '1' : '0';
                        card.querySelector('[data-condicion-cobranza]').textContent = (condicion.nuevo ? 'Nuevo · ' : '') + etiquetas[condicion.estado];
                        card.querySelector('[data-aviso-excepcion]').hidden = !condicion.requiere_motivo;
                        const input = document.getElementById('motivo-excepcion-' + aid);
                        input.value = condicion.motivo_guardado || '';
                        input.readOnly = !!condicion.motivo_guardado;
                        card.querySelector('[data-ayuda-excepcion]').textContent = (condicion.motivo_guardado ? 'Motivo registrado.' : condicion.message) + ' El dueño lo verá en Inicio y en el resumen diario.';
                        mostrarExcepcion(card, card.querySelector('.presente-checkbox').checked);
                    });
                } else if (d.requiere_motivo && d.alumno_id) {
                    const card = document.getElementById('card-' + d.alumno_id);
                    if (card) {
                        card.dataset.requiereExcepcion = '1';
                        card.querySelector('[data-aviso-excepcion]').hidden = false;
                        card.querySelector('[data-ayuda-excepcion]').textContent = d.message;
                        mostrarExcepcion(card, true);
                        document.getElementById('motivo-excepcion-' + d.alumno_id).focus();
                    }
                }
                mostrarToast(d.success ? (d.message || 'Lista guardada.') : (d.message || 'Error.'), !!d.success);
            })
            .catch(function () {
                mostrarToast('Error de conexión.', false);
            })
            .finally(function () {
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar';
            });
        });
    }

    // ── Panel de cancelación ────────────────────────────────────────────────
    const btnAbrirCancelar     = document.getElementById('btn-abrir-cancelar');
    const panelCancelar        = document.getElementById('panel-cancelar');
    const btnConfirmarCancelar = document.getElementById('btn-confirmar-cancelar');
    const btnCancelarSerie     = document.getElementById('btn-cancelar-serie');
    const btnCerrarCancelar    = document.getElementById('btn-cerrar-cancelar');
    const inputMotivo          = document.getElementById('input-motivo-cancelacion');
    const flashCancelar        = document.getElementById('flash-cancelar');

    if (btnAbrirCancelar) {
        btnAbrirCancelar.addEventListener('click', function () {
            panelCancelar.style.display = 'block';
            if (inputMotivo) inputMotivo.focus();
        });
    }
    if (btnCerrarCancelar) {
        btnCerrarCancelar.addEventListener('click', function () {
            panelCancelar.style.display = 'none';
        });
    }

    function ejecutarCancelacion(cancelarSerie) {
        const motivo = inputMotivo ? inputMotivo.value.trim() : '';
        if (!motivo) {
            flashCancelar.style.display = 'block';
            flashCancelar.textContent = 'El motivo es obligatorio.';
            flashCancelar.style.color = 'var(--color-danger)';
            return;
        }
        if (btnConfirmarCancelar) { btnConfirmarCancelar.disabled = true; btnConfirmarCancelar.textContent = '…'; }
        if (btnCancelarSerie)     { btnCancelarSerie.disabled = true;     btnCancelarSerie.textContent = '…'; }
        flashCancelar.style.display = 'none';

        fetch('/clases/' + claseId + '/cancelar', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: JSON.stringify({ motivo_cancelacion: motivo, cancelar_serie: cancelarSerie }),
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                if (cancelarSerie) {
                    // Recargar para reflejar el estado de toda la serie
                    flashCancelar.style.display = 'block';
                    flashCancelar.textContent = '✓ ' + d.message;
                    flashCancelar.style.color = 'var(--color-success)';
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    panelCancelar.style.display = 'none';
                    btnAbrirCancelar.style.display = 'none';
                    const badge = document.getElementById('badge-estado');
                    if (badge) {
                        badge.textContent = 'Cancelada';
                        badge.style.background = 'color-mix(in srgb, var(--color-danger) 15%, transparent)';
                        badge.style.color = 'var(--color-danger)';
                    }
                    document.querySelectorAll('.alumno-asistencia-card').forEach(function (c) {
                        c.style.opacity = '0.6';
                        c.style.pointerEvents = 'none';
                    });
                    if (btnGuardar) btnGuardar.disabled = true;
                    const banner = document.createElement('div');
                    banner.className = 'ds-flash ds-flash--error mb-4';
                    banner.textContent = 'Esta clase está cancelada. No se pueden registrar asistencias.';
                    panelCancelar.insertAdjacentElement('afterend', banner);
                }
            } else {
                flashCancelar.style.display = 'block';
                flashCancelar.textContent = '✗ ' + (d.message || 'Error.');
                flashCancelar.style.color = 'var(--color-danger)';
            }
        })
        .catch(function () {
            flashCancelar.style.display = 'block';
            flashCancelar.textContent = '✗ Error de conexión.';
            flashCancelar.style.color = 'var(--color-danger)';
        })
        .finally(function () {
            if (btnConfirmarCancelar) { btnConfirmarCancelar.disabled = false; btnConfirmarCancelar.textContent = 'Cancelar esta'; }
            if (btnCancelarSerie)     { btnCancelarSerie.disabled = false;     btnCancelarSerie.textContent = 'Cancelar serie'; }
        });
    }

    if (btnConfirmarCancelar) {
        btnConfirmarCancelar.addEventListener('click', function () { ejecutarCancelacion(false); });
    }
    if (btnCancelarSerie) {
        btnCancelarSerie.addEventListener('click', function () { ejecutarCancelacion(true); });
    }

    // ── Panel de profesores ─────────────────────────────────────────────────
    const btnModificarProfs  = document.getElementById('btn-modificar-profesores');
    const panelProfs         = document.getElementById('panel-profesores');
    const btnGuardarProfs    = document.getElementById('btn-guardar-profesores');
    const btnCerrarProfs     = document.getElementById('btn-cerrar-profesores');
    const flashProfs         = document.getElementById('flash-profesores');
    const textoProfs         = document.getElementById('texto-profesores');

    if (btnModificarProfs) {
        btnModificarProfs.addEventListener('click', function () {
            panelProfs.style.display = 'block';
        });
    }
    if (btnCerrarProfs) {
        btnCerrarProfs.addEventListener('click', function () {
            panelProfs.style.display = 'none';
        });
    }
    const inputMotivoProfs = document.getElementById('input-motivo-profesores');

    function guardarProfesores(confirmar) {
        const ids = [];
        document.querySelectorAll('.prof-checkbox:checked').forEach(function (cb) {
            ids.push(parseInt(cb.value));
        });
        const motivo = inputMotivoProfs ? inputMotivoProfs.value.trim() : '';

        btnGuardarProfs.disabled = true;
        btnGuardarProfs.textContent = '…';
        flashProfs.style.display = 'none';

        fetch('/clases/' + claseId + '/profesores', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: JSON.stringify({ profesores: ids, motivo: motivo, confirmar: !!confirmar }),
        })
        .then(function (r) { return r.json().then(function (d) { return { status: r.status, body: d }; }); })
        .then(function (res) {
            const d = res.body;
            if (d.success) {
                panelProfs.style.display = 'none';
                if (textoProfs) textoProfs.textContent = d.profesores;
            } else if (res.status === 409 && d.requiere_confirmacion) {
                if (confirm(d.message)) {
                    guardarProfesores(true);
                    return;
                }
                flashProfs.style.display = 'inline';
                flashProfs.textContent = 'Cambio no aplicado.';
                flashProfs.style.color = 'var(--color-text-muted)';
            } else {
                flashProfs.style.display = 'inline';
                flashProfs.textContent = '✗ ' + (d.message || 'Error.');
                flashProfs.style.color = 'var(--color-danger)';
            }
        })
        .catch(function () {
            flashProfs.style.display = 'inline';
            flashProfs.textContent = '✗ Error de conexión.';
            flashProfs.style.color = 'var(--color-danger)';
        })
        .finally(function () {
            btnGuardarProfs.disabled = false;
            btnGuardarProfs.textContent = 'Guardar';
        });
    }

    if (btnGuardarProfs) {
        btnGuardarProfs.addEventListener('click', function () {
            const motivo = inputMotivoProfs ? inputMotivoProfs.value.trim() : '';
            if (requiereMotivoProfesores && !motivo) {
                flashProfs.style.display = 'inline';
                flashProfs.textContent = '✗ Indicá el motivo del cambio.';
                flashProfs.style.color = 'var(--color-danger)';
                if (inputMotivoProfs) inputMotivoProfs.focus();
                return;
            }
            guardarProfesores(false);
        });
    }

    // ── Panel: eliminar clase (T65) ──────────────────────────────────────────
    const btnAbrirEliminar  = document.getElementById('btn-abrir-eliminar-clase');
    const panelEliminar     = document.getElementById('panel-eliminar-clase');
    const btnCerrarEliminar = document.getElementById('btn-cerrar-eliminar-clase');

    if (btnAbrirEliminar && panelEliminar) {
        btnAbrirEliminar.addEventListener('click', function () {
            panelEliminar.style.display = panelEliminar.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (btnCerrarEliminar && panelEliminar) {
        btnCerrarEliminar.addEventListener('click', function () {
            panelEliminar.style.display = 'none';
        });
    }
})();
