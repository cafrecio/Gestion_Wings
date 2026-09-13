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
        @error('email') <p id="error-email-usuario-sv" class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
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
        <div style="position:relative;">
            <input type="password" id="password" name="password"
                   {{ !isset($usuario) ? 'required' : '' }}
                   minlength="{{ $minimoContrasena }}" tabindex="3"
                   class="w-full px-4 py-2.5 text-sm wings-input"
                   style="padding-right: 2.75rem;"
                   placeholder="{{ isset($usuario) ? 'Dejar en blanco para no cambiar' : 'Mínimo ' . $minimoContrasena . ' caracteres' }}">
            <button type="button" class="btn-toggle-password" data-target="password" aria-label="Mostrar contraseña"
                    style="position:absolute; right:0.6rem; top:50%; transform:translateY(-50%);
                           background:none; border:none; cursor:pointer; padding:6px;
                           display:flex; color:var(--color-text-muted);">
                <svg class="icon-eye w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <svg class="icon-eye-off w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                </svg>
            </button>
        </div>
        @error('password') <p id="error-password-usuario" class="text-xs mt-1" style="color: var(--color-danger);">{{ $message }}</p> @enderror
    </div>

    {{-- Confirmar contraseña --}}
    <div>
        <label for="password_confirmation" class="{{ $labelClass }}">
            <svg {!! $iconAttr !!}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Confirmar contraseña {{ isset($usuario) ? '' : '*' }}
        </label>
        <div style="position:relative;">
            <input type="password" id="password_confirmation" name="password_confirmation"
                   {{ !isset($usuario) ? 'required' : '' }}
                   minlength="{{ $minimoContrasena }}" tabindex="4"
                   class="w-full px-4 py-2.5 text-sm wings-input"
                   style="padding-right: 2.75rem;"
                   placeholder="Repetir contraseña">
            <button type="button" class="btn-toggle-password" data-target="password_confirmation" aria-label="Mostrar contraseña"
                    style="position:absolute; right:0.6rem; top:50%; transform:translateY(-50%);
                           background:none; border:none; cursor:pointer; padding:6px;
                           display:flex; color:var(--color-text-muted);">
                <svg class="icon-eye w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <svg class="icon-eye-off w-4 h-4" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                </svg>
            </button>
        </div>
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
                    <p id="error-profesor-id" class="text-xs mt-1" style="color:var(--color-danger);">
                        {{ $message }}
                    </p>
                @enderror
            @endif
        </div>
    </div>

</div>

<input type="hidden" id="usuario-id-actual" value="{{ $usuario->id ?? '' }}">

@push('scripts')
    @vite('resources/js/usuarios.js')
@endpush
