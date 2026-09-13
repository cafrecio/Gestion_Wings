/* Comportamientos globales del sistema.
 *
 * Vivian dentro de layouts/ds-app.blade.php, incrustados en la pagina.
 * Se movieron aca el 30/08/2026 para poder aplicar una politica de seguridad
 * que rechace el codigo incrustado en las paginas: eso cierra 55 de las 85
 * violaciones medidas, porque este layout esta en todas las pantallas.
 *
 * El contenido NO se modifico: es el mismo codigo, movido.
 */

/* ── toggle activo global: fetch PATCH sin recarga ─────────────────────
   Aplica a cualquier .ds-toggle[data-url] del sistema.
   Toggles de formulario (permite_descubierto, afecta_caja, etc.) no
   tienen data-url y no son interceptados.
──────────────────────────────────────────────────────────────────── */
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    document.querySelectorAll('.ds-toggle[data-url]').forEach(function (label) {
        var input = label.querySelector('.ds-toggle__input');
        if (!input || input.disabled) return;

        input.addEventListener('change', function () {
            var url  = label.dataset.url;
            var card = label.closest('.alumno-card');

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/json',
                },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.activo !== undefined && card) {
                    card.style.opacity = data.activo ? '' : '0.6';
                }
            })
            .catch(function () {
                // revertir estado visual si la petición falló
                input.checked = !input.checked;
            });
        });
    });
})();

/* ── anti doble-submit global: la segunda pulsación no dispara otro POST ── */
(function () {
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return; // un confirm() cancelado no cuenta
        var form = e.target;
        if (form.dataset.submitted === '1') { e.preventDefault(); return; }
        form.dataset.submitted = '1';
        setTimeout(function () {
            form.querySelectorAll('button[type=submit], input[type=submit]')
                .forEach(function (b) { b.disabled = true; });
        }, 0);
    });
    // volver con el botón atrás (bfcache) debe rehabilitar los formularios
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitted]').forEach(function (form) {
            delete form.dataset.submitted;
            form.querySelectorAll('button[type=submit], input[type=submit]')
                .forEach(function (b) { b.disabled = false; });
        });
    });
})();

/* ── menú móvil: sidebar colapsable con botón hamburguesa (UP1.0) ── */
(function () {
    var toggle  = document.getElementById('ds-menu-toggle');
    var sidebar = document.querySelector('.ds-sidebar');
    var overlay = document.getElementById('ds-sidebar-overlay');
    if (!toggle || !sidebar || !overlay) return;

    function abrir() {
        sidebar.classList.add('ds-sidebar--open');
        overlay.classList.add('ds-sidebar-overlay--visible');
    }
    function cerrar() {
        sidebar.classList.remove('ds-sidebar--open');
        overlay.classList.remove('ds-sidebar-overlay--visible');
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.contains('ds-sidebar--open') ? cerrar() : abrir();
    });
    overlay.addEventListener('click', cerrar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrar();
    });
    // Cerrar automáticamente al tocar un link del menú (si no, queda
    // tapando la pantalla después de navegar).
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', cerrar);
    });
})();

/* ── flash auto-dismiss (3s + fade 0.5s) ── */
(function () {
    setTimeout(function () {
        document.querySelectorAll('.ds-flash').forEach(function (el) {
            if (el.querySelector('a, button')) return;
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        });
    }, 3000);
})();

/* ── money-input: formato numérico con separador de miles (punto) ── */
(function () {
    function toDisplay(raw) {
        // Eliminar todo excepto dígitos
        const digits = raw.replace(/\D/g, '');
        if (!digits) return '';
        return Number(digits).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    function toRaw(display) {
        return display.replace(/\./g, '').replace(/,/g, '');
    }

    function initMoneyInput(input) {
        // Formatear al mostrar la página
        if (input.value) {
            input.value = toDisplay(input.value);
        }

        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            const before = this.value.slice(0, pos).replace(/\./g, '').length;
            const raw = toRaw(this.value);
            this.value = raw ? toDisplay(raw) : '';
            // Reposicionar cursor aproximadamente
            let count = 0, newPos = 0;
            for (let i = 0; i < this.value.length; i++) {
                if (this.value[i] !== '.') count++;
                if (count === before) { newPos = i + 1; break; }
            }
            this.setSelectionRange(newPos, newPos);
        });
    }

    function stripMoneyInputs(form) {
        form.querySelectorAll('[data-money="true"]').forEach(function (input) {
            input.value = toRaw(input.value);
        });
    }

    function init() {
        document.querySelectorAll('[data-money="true"]').forEach(initMoneyInput);
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () { stripMoneyInputs(form); });
        });
    }

    // Exponer para uso externo (filas dinámicas)
    window.initMoneyInput = initMoneyInput;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* ── filtros que se envían solos al elegir una opción ──────────────────
   Reemplaza a onchange="this.form.submit()", que estaba escrito dentro del
   HTML de las vistas. La política de seguridad declara script-src 'self':
   el navegador rechaza el código incrustado en la página, así que mientras
   estos atributos existieran no se podía pasar de modo aviso a modo bloqueo.

   Se usa poniendo data-enviar-al-cambiar en el <select>. El comportamiento
   es exactamente el mismo que antes: al cambiar la opción, se envía el
   formulario que lo contiene.

   Está delegado en el documento a propósito, para que también funcione en
   filtros que se agreguen a la página después de cargarla.
──────────────────────────────────────────────────────────────────── */
(function () {
    document.addEventListener('change', function (evento) {
        var campo = evento.target;

        if (!campo || !campo.matches || !campo.matches('[data-enviar-al-cambiar]')) {
            return;
        }

        var formulario = campo.form || campo.closest('form');

        if (formulario) {
            formulario.submit();
        }
    });
})();

/* ── efectos al pasar el mouse ─────────────────────────────────────────
   Reemplazan a onmouseenter/onmouseleave/onmouseover/onmouseout escritos
   dentro del HTML. La política de seguridad declara script-src 'self': el
   navegador rechaza el código incrustado en la página.

   El efecto es exactamente el mismo de antes. No se tocó ninguna hoja de
   estilos: se siguen escribiendo los mismos estilos en línea, solo que
   desde acá.

   Dos comportamientos:

     data-elevar          la tarjeta se levanta y toma sombra
     data-hover-fondo="…" el elemento toma ese fondo mientras está el mouse

   Al salir se restaura el valor que el elemento tenía, capturado en el
   momento de entrar. Así da igual si venía vacío o con "none".
──────────────────────────────────────────────────────────────────── */
(function () {
    function init() {
        document.querySelectorAll('[data-elevar]').forEach(function (elemento) {
            elemento.addEventListener('mouseenter', function () {
                elemento.style.transform = 'translateY(-2px)';
                elemento.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)';
            });
            elemento.addEventListener('mouseleave', function () {
                elemento.style.transform = '';
                elemento.style.boxShadow = '';
            });
        });

        document.querySelectorAll('[data-hover-fondo]').forEach(function (elemento) {
            var original = null;

            elemento.addEventListener('mouseenter', function () {
                original = elemento.style.background;
                elemento.style.background = elemento.getAttribute('data-hover-fondo');
            });
            elemento.addEventListener('mouseleave', function () {
                elemento.style.background = original === null ? '' : original;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* ── toggle-password: ver/ocultar contraseña en inputs ──────────────────
   Permite ver u ocultar la contraseña mientras se tipea.
   Aplica a cualquier botón con clase .btn-toggle-password y atributo
   data-target="<id-del-input>".
   Conmuta el type del input entre 'password' y 'text',
   alterna la visibilidad de los íconos .icon-eye e .icon-eye-off,
   y actualiza el aria-label.
──────────────────────────────────────────────────────────────────── */
(function () {
    document.addEventListener('click', function (evento) {
        var btn = evento.target && evento.target.closest ? evento.target.closest('.btn-toggle-password') : null;
        if (!btn) return;

        var targetId = btn.getAttribute('data-target');
        if (!targetId) return;

        var input = document.getElementById(targetId);
        if (!input) return;

        var eye = btn.querySelector('.icon-eye');
        var eyeOff = btn.querySelector('.icon-eye-off');

        var mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';

        if (eye) eye.style.display = mostrar ? 'none' : 'block';
        if (eyeOff) eyeOff.style.display = mostrar ? 'block' : 'none';

        btn.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
})();

/* ── delegación de eventos onclick de vistas (SEG-11) ──────────────────
   Reemplaza los manejadores onclick escritos dentro del HTML de las vistas.
   La política de seguridad declara script-src 'self': el navegador
   rechaza el código incrustado en la página, por lo que los eventos en línea
   deben migrar a archivos JS externos.

   Maneja:
     [data-confirmar]        pide confirmación antes de enviar/ejecutar
     [data-abrir-condonar]   abre el modal de condonación de deuda (alumnos/show)
     [data-cerrar-condonar]  cierra el modal de condonación de deuda
     [data-abrir-rechazar]   abre el modal de rechazo de caja (caja/detalle y resumen)
     [data-cerrar-rechazar]  cierra el modal de rechazo de caja
     [data-abrir-cancelar]   abre el modal de cancelación de cobro de cuota (caja/detalle)
     [data-cerrar-cancelar]  cierra el modal de cancelación de cobro
     [data-incluir-hoy]      conmuta inclusión de clases de hoy (liquidaciones/create)
     [data-abrir-revision]   abre formulario de revisión de cobranza (revision-cobranza)
     [data-cerrar-revision]  cierra formulario de revisión de cobranza
──────────────────────────────────────────────────────────────────── */
(function () {
    document.addEventListener('click', function (evento) {
        if (!evento.target || !evento.target.closest) return;

        // 1. Confirmación de acción (ej. reactivar alumno)
        var elConfirmar = evento.target.closest('[data-confirmar]');
        if (elConfirmar) {
            var mensaje = elConfirmar.getAttribute('data-confirmar');
            if (mensaje && !window.confirm(mensaje)) {
                evento.preventDefault();
                evento.stopPropagation();
                return;
            }
        }

        // 2. Condonar deuda (alumnos/show)
        var btnAbrirCondonar = evento.target.closest('[data-abrir-condonar]');
        if (btnAbrirCondonar) {
            var urlCondonar = btnAbrirCondonar.getAttribute('data-abrir-condonar');
            if (typeof window.abrirCondonar === 'function') {
                window.abrirCondonar(urlCondonar);
            } else {
                var formCond = document.getElementById('form-condonar');
                if (formCond && urlCondonar) formCond.action = urlCondonar;
                var modalCond = document.getElementById('modal-condonar');
                if (modalCond) modalCond.style.display = 'flex';
            }
            return;
        }

        var btnCerrarCondonar = evento.target.closest('[data-cerrar-condonar]');
        if (btnCerrarCondonar) {
            if (typeof window.cerrarCondonar === 'function') {
                window.cerrarCondonar();
            } else {
                var modalCond = document.getElementById('modal-condonar');
                if (modalCond) modalCond.style.display = 'none';
            }
            return;
        }

        // 3. Rechazar caja (caja/detalle y caja/resumen)
        var btnAbrirRechazar = evento.target.closest('[data-abrir-rechazar]');
        if (btnAbrirRechazar) {
            if (typeof window.abrirRechazar === 'function') {
                window.abrirRechazar();
            } else {
                var modalRech = document.getElementById('modal-rechazar');
                if (modalRech) modalRech.style.display = 'flex';
            }
            return;
        }

        var btnCerrarRechazar = evento.target.closest('[data-cerrar-rechazar]');
        if (btnCerrarRechazar) {
            var modalRech = document.getElementById('modal-rechazar');
            if (modalRech) modalRech.style.display = 'none';
            return;
        }

        // 4. Cancelar cobro de cuota (caja/detalle)
        var btnAbrirCancelar = evento.target.closest('[data-abrir-cancelar]');
        if (btnAbrirCancelar) {
            var urlCancelar = btnAbrirCancelar.getAttribute('data-abrir-cancelar');
            var movId = btnAbrirCancelar.getAttribute('data-mov-id');
            if (typeof window.abrirCancelar === 'function') {
                window.abrirCancelar(movId, urlCancelar);
            } else {
                var formCanc = document.getElementById('form-cancelar');
                if (formCanc && urlCancelar) formCanc.action = urlCancelar;
                var modalCanc = document.getElementById('modal-cancelar');
                if (modalCanc) modalCanc.style.display = 'flex';
            }
            return;
        }

        var btnCerrarCancelar = evento.target.closest('[data-cerrar-cancelar]');
        if (btnCerrarCancelar) {
            if (typeof window.cerrarCancelar === 'function') {
                window.cerrarCancelar();
            } else {
                var modalCanc = document.getElementById('modal-cancelar');
                if (modalCanc) modalCanc.style.display = 'none';
            }
            return;
        }

        // 5. Incluir clases de hoy en liquidación (liquidaciones/create)
        var btnIncluirHoy = evento.target.closest('[data-incluir-hoy]');
        if (btnIncluirHoy) {
            var incluir = btnIncluirHoy.getAttribute('data-incluir-hoy') === 'true';
            if (typeof window.toggleIncluirHoy === 'function') {
                window.toggleIncluirHoy(incluir);
            } else {
                var inputInc = document.getElementById('incluir-hoy');
                if (inputInc) inputInc.value = incluir ? '1' : '0';
                var btnInc = document.getElementById('btn-incluir');
                var btnNoInc = document.getElementById('btn-no-incluir');
                if (btnInc && btnNoInc) {
                    if (incluir) {
                        btnInc.style.background    = 'var(--color-warning)';
                        btnInc.style.color         = '#fff';
                        btnInc.style.border        = 'none';
                        btnNoInc.style.background  = 'transparent';
                        btnNoInc.style.border      = '1px solid var(--color-warning)';
                        btnNoInc.style.color       = 'var(--color-warning)';
                    } else {
                        btnNoInc.style.background  = 'var(--color-warning)';
                        btnNoInc.style.color       = '#fff';
                        btnNoInc.style.border      = 'none';
                        btnInc.style.background    = 'transparent';
                        btnInc.style.border        = '1px solid var(--color-warning)';
                        btnInc.style.color         = 'var(--color-warning)';
                    }
                }
            }
            return;
        }

        // 6. Revisión de cobranza (revision-cobranza/index)
        var btnAbrirRev = evento.target.closest('[data-abrir-revision]');
        if (btnAbrirRev) {
            var idRev = btnAbrirRev.getAttribute('data-abrir-revision');
            var tipoRev = btnAbrirRev.getAttribute('data-tipo');
            if (typeof window.abrirForm === 'function') {
                window.abrirForm(idRev, tipoRev);
            } else {
                var tipoEl = document.getElementById('res-tipo-' + idRev);
                if (tipoEl) tipoEl.value = tipoRev;
                var formEl = document.getElementById('form-' + idRev);
                if (formEl) formEl.style.display = 'block';
                var botEl = document.getElementById('botones-' + idRev);
                if (botEl) botEl.style.display = 'none';
                var txt = document.querySelector('#form-' + idRev + ' textarea');
                if (txt) txt.focus();
            }
            return;
        }

        var btnCerrarRev = evento.target.closest('[data-cerrar-revision]');
        if (btnCerrarRev) {
            var idRev = btnCerrarRev.getAttribute('data-cerrar-revision');
            if (typeof window.cerrarForm === 'function') {
                window.cerrarForm(idRev);
            } else {
                var formEl = document.getElementById('form-' + idRev);
                if (formEl) formEl.style.display = 'none';
                var botEl = document.getElementById('botones-' + idRev);
                if (botEl) botEl.style.display = 'flex';
            }
            return;
        }
    });
})();

