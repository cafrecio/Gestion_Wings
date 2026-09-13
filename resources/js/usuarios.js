/* ── usuarios.js ───────────────────────────────────────────────────────────
   Lógica de cliente para el formulario de usuarios (usuarios/_form.blade.php):
   - Verificación de email único vía AJAX (/usuarios/check-email).
   - Validación cruzada de contraseñas (coincidencia).
   - Resalte visual de selección de rol (.rol-label).
   - Visibilidad condicional de #panel-profesor según rol seleccionado.
───────────────────────────────────────────────────────────────────────── */
(function () {
    function initUsuarios() {
        /* ── Email único ─────────────────────────────────────────────── */
        const emailInput   = document.getElementById('email');
        const emailError   = document.getElementById('error-email-usuario');
        const btnSubmit    = document.querySelector('[type="submit"]');
        const usuarioIdEl  = document.getElementById('usuario-id-actual');
        const usuarioId    = usuarioIdEl ? usuarioIdEl.value : '';
        const emailErrorSv = document.getElementById('error-email-usuario-sv');

        async function verificarEmail() {
            const email = emailInput ? emailInput.value.trim() : '';
            if (!email) {
                if (emailError) emailError.style.display = 'none';
                habilitarSubmit();
                return;
            }
            let url = '/usuarios/check-email?email=' + encodeURIComponent(email);
            if (usuarioId) url += '&usuario_id=' + encodeURIComponent(usuarioId);
            try {
                const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!data.disponible) {
                    if (emailError) emailError.style.display = 'block';
                    deshabilitarSubmit();
                } else {
                    if (emailError) emailError.style.display = 'none';
                    if (emailErrorSv) emailErrorSv.style.display = 'none';
                    habilitarSubmit();
                }
            } catch(e) {
                if (emailError) emailError.style.display = 'none';
                habilitarSubmit();
            }
        }

        function deshabilitarSubmit() { if (btnSubmit) btnSubmit.disabled = true; }
        function habilitarSubmit()    { if (btnSubmit && !hayErrorPassword()) btnSubmit.disabled = false; }

        if (emailInput) {
            emailInput.addEventListener('blur', verificarEmail);
            emailInput.addEventListener('input', function () {
                if (emailError && emailError.style.display !== 'none') verificarEmail();
            });
        }

        /* ── Confirmación contraseña ─────────────────────────────────── */
        const pwInput    = document.getElementById('password');
        const pwConfirm  = document.getElementById('password_confirmation');
        const pwError    = document.getElementById('error-password-confirm');
        const pwErrorSv  = document.getElementById('error-password-usuario');

        function hayErrorPassword() {
            const pw  = pwInput  ? pwInput.value  : '';
            const pwc = pwConfirm ? pwConfirm.value : '';
            return pw !== '' && pwc !== '' && pw !== pwc;
        }

        function verificarPassword() {
            if (hayErrorPassword()) {
                if (pwError) pwError.style.display = 'block';
                deshabilitarSubmit();
            } else {
                if (pwError) pwError.style.display = 'none';
                if (pwErrorSv) pwErrorSv.style.display = 'none';
                if (!emailError || emailError.style.display === 'none') habilitarSubmit();
            }
        }

        if (pwConfirm) pwConfirm.addEventListener('input', verificarPassword);
        if (pwInput)   pwInput.addEventListener('input',   verificarPassword);

        /* ── Highlight rol seleccionado ──────────────────────────────── */
        document.querySelectorAll('.rol-label input[type=radio]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll('.rol-label').forEach(function (lbl) {
                    const checked = lbl.querySelector('input[type=radio]').checked;
                    lbl.style.border     = checked
                        ? '1px solid var(--color-btn-primary)'
                        : '1px solid var(--color-border)';
                    lbl.style.background = checked
                        ? 'color-mix(in srgb, var(--color-btn-primary) 8%, var(--color-surface))'
                        : 'var(--color-surface)';
                });
            });
        });

        /* ── Mostrar/ocultar panel profesor según rol ────────────────── */
        const panelProfesor = document.getElementById('panel-profesor');

        function actualizarPanelProfesor() {
            const rolSeleccionado = document.querySelector('.rol-label input[type=radio]:checked');
            if (panelProfesor) {
                panelProfesor.style.display =
                    (rolSeleccionado && rolSeleccionado.value === 'PROFESOR') ? 'block' : 'none';
            }
        }

        document.querySelectorAll('.rol-label input[type=radio]').forEach(function (radio) {
            radio.addEventListener('change', actualizarPanelProfesor);
        });

        // Ejecutar al cargar para el caso de edición con rol ya seleccionado
        actualizarPanelProfesor();

        /* ── Ocultar error profesor_id al seleccionar un valor ────────── */
        const profesorSelect = document.getElementById('profesor_id');
        if (profesorSelect) {
            profesorSelect.addEventListener('change', function () {
                const errorEl = document.getElementById('error-profesor-id');
                if (errorEl && this.value) errorEl.style.display = 'none';
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUsuarios);
    } else {
        initUsuarios();
    }
})();