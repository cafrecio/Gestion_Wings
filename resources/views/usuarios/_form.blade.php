@php
$iconAttr   = 'class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-btn-primary)"';
$labelClass = 'flex items-center gap-1.5 text-xs font-medium mb-1.5 text-wings-muted';
$esSelf     = isset($usuario) && auth()->id() === $usuario->id;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Nombre --}}
    <div>
        <label for="name" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Nombre <span class="form-required">*</span>
        </label>
        <input type="text" id="name" name="name"
               value="{{ old('name', $usuario->name ?? '') }}"
               required autofocus maxlength="255" tabindex="1"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Nombre completo">
        @error('name') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Email <span class="form-required">*</span>
        </label>
        <input type="email" id="email" name="email"
               value="{{ old('email', $usuario->email ?? '') }}"
               required maxlength="255" tabindex="2"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="usuario@ejemplo.com">
        @error('email') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
        <div id="error-email-usuario"
             style="display:none; color:var(--color-danger); font-size:0.75rem; margin-top:4px;">
            Ya existe un usuario con ese email.
        </div>
    </div>

    {{-- Contraseña --}}
    <div>
        <label for="password" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Contraseña {{ isset($usuario) ? '' : '*' }}
        </label>
        <input type="password" id="password" name="password"
               {{ !isset($usuario) ? 'required' : '' }}
               minlength="8" tabindex="3"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="{{ isset($usuario) ? 'Dejar en blanco para no cambiar' : 'Mínimo 8 caracteres' }}">
        @error('password') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Confirmar contraseña --}}
    <div>
        <label for="password_confirmation" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Confirmar contraseña {{ isset($usuario) ? '' : '*' }}
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               {{ !isset($usuario) ? 'required' : '' }}
               minlength="8" tabindex="4"
               class="w-full px-4 py-2.5 text-sm wings-input"
               placeholder="Repetir contraseña">
        <div id="error-password-confirm"
             style="display:none; color:var(--color-danger); font-size:0.75rem; margin-top:4px;">
            Las contraseñas no coinciden.
        </div>
    </div>

    {{-- Rol --}}
    <div class="md:col-span-2">
        <label class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            Rol <span class="form-required">*</span>
        </label>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            @foreach($roles as $valor => $etiqueta)
            @php
                $seleccionado = old('rol', $usuario->rol ?? '') === $valor;
                $estaDeshabilitado = $esSelf && $valor !== $usuario->rol;
            @endphp
            <label style="display:flex; align-items:center; gap:8px; padding:10px 16px;
                          border-radius:var(--radius-card); cursor:{{ $estaDeshabilitado ? 'not-allowed' : 'pointer' }};
                          opacity:{{ $estaDeshabilitado ? '0.45' : '1' }};
                          border:1px solid {{ $seleccionado ? 'var(--color-btn-primary)' : 'var(--color-border)' }};
                          background:{{ $seleccionado ? 'color-mix(in srgb, var(--color-btn-primary) 8%, var(--color-surface))' : 'var(--color-surface)' }};
                          transition:all 0.12s;"
                   class="rol-label">
                <input type="radio" name="rol" value="{{ $valor }}"
                       {{ $seleccionado ? 'checked' : '' }}
                       {{ $estaDeshabilitado ? 'disabled' : '' }}
                       tabindex="{{ 4 + $loop->iteration }}"
                       style="accent-color:var(--color-btn-primary);">
                <div>
                    <div style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $etiqueta }}</div>
                    @if($valor === 'ADMIN')
                        <div style="font-size:0.72rem; color:var(--color-text-muted);">Acceso total al sistema</div>
                    @elseif($valor === 'OPERATIVO')
                        <div style="font-size:0.72rem; color:var(--color-text-muted);">Caja, alumnos y clases</div>
                    @else
                        <div style="font-size:0.72rem; color:var(--color-text-muted);">Solo clases y asistencias</div>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
        @if($esSelf)
        <p style="font-size:0.75rem; color:var(--color-text-muted); margin-top:6px;">
            No podés cambiar tu propio rol.
        </p>
        @endif
        @error('rol') <p class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror

        {{-- Panel profesor: visible solo cuando se selecciona rol PROFESOR --}}
        <div id="panel-profesor" style="display:none; margin-top:12px;">
            <label class="{{ $labelClass }}">
                Profesor vinculado <span class="form-required">*</span>
            </label>

            @if($profesoresSinUsuario->isEmpty())
                <p style="color:var(--color-warning); font-size:0.82rem;">
                    Todos los profesores activos ya tienen usuario asignado.
                </p>
            @else
                <select name="profesor_id" id="profesor_id"
                        class="w-full px-4 py-2.5 text-sm wings-input">
                    <option value="">Seleccionar profesor...</option>
                    @foreach($profesoresSinUsuario as $prof)
                        <option value="{{ $prof->id }}"
                            {{ old('profesor_id', $usuario->profesor_id ?? '') == $prof->id ? 'selected' : '' }}>
                            {{ $prof->apellido }}, {{ $prof->nombre }}
                            — {{ $prof->deporte->nombre ?? 'Sin deporte' }}
                        </option>
                    @endforeach
                </select>
                @error('profesor_id')
                    <p class="text-xs mt-1" style="color:var(--color-danger);">
                        {{ $message }}
                    </p>
                @enderror
            @endif
        </div>
    </div>

</div>

<input type="hidden" id="usuario-id-actual" value="{{ $usuario->id ?? '' }}">

<script>
(function () {
    /* Email único */
    const emailInput  = document.getElementById('email');
    const emailError  = document.getElementById('error-email-usuario');
    const btnSubmit   = document.querySelector('[type="submit"]');
    const usuarioId   = document.getElementById('usuario-id-actual').value;

    async function verificarEmail() {
        const email = emailInput ? emailInput.value.trim() : '';
        if (!email) { emailError.style.display = 'none'; habilitarSubmit(); return; }
        let url = '/usuarios/check-email?email=' + encodeURIComponent(email);
        if (usuarioId) url += '&usuario_id=' + usuarioId;
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!data.disponible) {
                emailError.style.display = 'block';
                deshabilitarSubmit();
            } else {
                emailError.style.display = 'none';
                habilitarSubmit();
            }
        } catch(e) {
            emailError.style.display = 'none';
            habilitarSubmit();
        }
    }

    function deshabilitarSubmit() { if (btnSubmit) btnSubmit.disabled = true; }
    function habilitarSubmit()    { if (btnSubmit && !hayErrorPassword()) btnSubmit.disabled = false; }

    if (emailInput) {
        emailInput.addEventListener('blur', verificarEmail);
        emailInput.addEventListener('input', function () {
            if (emailError.style.display !== 'none') verificarEmail();
        });
    }

    /* Confirmación contraseña */
    const pwInput    = document.getElementById('password');
    const pwConfirm  = document.getElementById('password_confirmation');
    const pwError    = document.getElementById('error-password-confirm');

    function hayErrorPassword() {
        const pw  = pwInput  ? pwInput.value  : '';
        const pwc = pwConfirm ? pwConfirm.value : '';
        return pw !== '' && pwc !== '' && pw !== pwc;
    }

    function verificarPassword() {
        if (hayErrorPassword()) {
            pwError.style.display = 'block';
            deshabilitarSubmit();
        } else {
            pwError.style.display = 'none';
            if (emailError.style.display === 'none') habilitarSubmit();
        }
    }

    if (pwConfirm) pwConfirm.addEventListener('input', verificarPassword);
    if (pwInput)   pwInput.addEventListener('input',   verificarPassword);

    /* Highlight rol seleccionado */
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

    /* Mostrar/ocultar panel profesor según rol */
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
})();
</script>
