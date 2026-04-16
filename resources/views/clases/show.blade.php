@extends('layouts.app')

@php
    $dep  = mb_strtolower($clase->grupo->deporte->nombre ?? '');
    $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');

    $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $fechaTexto = $diasSemana[$clase->fecha->dayOfWeek]
        . ' ' . $clase->fecha->day
        . ' de ' . $meses[$clase->fecha->month]
        . ' de ' . $clase->fecha->year;

    $cantPresentes = $asistenciasMap->where('presente', true)->count();
    $profesoresActualesIds = $clase->profesores->pluck('id')->toArray();
@endphp

@section('title', $fechaTexto . ' — ' . $clase->grupo->nombre . ' – Wings')
@section('module-title', 'Clase — ' . $clase->grupo->nombre)

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="ds-flash ds-flash--success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="ds-flash ds-flash--error mb-4">{{ session('error') }}</div>
@endif

{{-- ── Header de la clase ─────────────────────────────────────────────────── --}}
<div class="filtros-card mb-3" style="padding:0; overflow:hidden;">
    <div style="display:flex;">

        {{-- Rail de deporte --}}
        <div style="width:6px; flex-shrink:0; background:var(--color-sport-{{ $rail }});"></div>

        <div style="flex:1; padding:1.25rem 1.5rem;">

            {{-- Fecha + hora --}}
            <div style="display:flex; align-items:baseline; gap:1rem; flex-wrap:wrap; margin-bottom:0.6rem;">
                <h2 style="font-size:1.1rem; font-weight:800; color:var(--color-text); margin:0; text-transform:capitalize;">
                    {{ $fechaTexto }}
                </h2>
                <span style="font-size:0.9rem; font-weight:600; color:var(--color-text-muted);">
                    {{ $clase->hora_inicio->format('H:i') }} – {{ $clase->hora_fin->format('H:i') }}
                </span>
            </div>

            {{-- Grupo + deporte badge --}}
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.5rem;">
                <span style="font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:999px;
                             background:color-mix(in srgb, var(--color-sport-{{ $rail }}) 18%, transparent);
                             color:var(--color-sport-{{ $rail }}); white-space:nowrap;">
                    {{ $clase->grupo->deporte->nombre ?? '–' }}
                </span>
                <span style="font-size:0.88rem; font-weight:600; color:var(--color-text);">
                    {{ $clase->grupo->nombre }}
                </span>
            </div>

            {{-- Profesores + botón modificar --}}
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.75rem; flex-wrap:wrap;">
                <span id="texto-profesores" style="font-size:0.8rem; color:var(--color-text-muted);">
                    @if($clase->profesores->isNotEmpty())
                        {{ $clase->profesores->map(fn($p) => $p->apellido . ', ' . $p->nombre)->implode(' · ') }}
                    @else
                        <span style="opacity:0.5;">Sin profesor asignado</span>
                    @endif
                </span>
                @if(!$clase->cancelada)
                    <button id="btn-modificar-profesores"
                            style="font-size:0.72rem; font-weight:600; padding:2px 10px; border-radius:var(--radius-btn);
                                   border:1px solid var(--color-border); background:transparent;
                                   color:var(--color-text-muted); cursor:pointer; font-family:inherit;">
                        Modificar profesores
                    </button>
                @endif
            </div>

            {{-- Badges de estado --}}
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:1rem;">
                @if($clase->cancelada)
                    <span id="badge-estado" style="font-size:0.65rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-danger) 15%, transparent);
                                 color:var(--color-danger);">Cancelada</span>
                    @if($clase->motivo_cancelacion)
                        <span style="font-size:0.78rem; color:var(--color-text-muted);">— {{ $clase->motivo_cancelacion }}</span>
                    @endif
                @else
                    <span id="badge-estado" style="font-size:0.65rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-success) 15%, transparent);
                                 color:var(--color-success);">Activa</span>
                @endif
                @if($clase->validada_para_liquidacion)
                    <span style="font-size:0.65rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-btn-primary) 15%, transparent);
                                 color:var(--color-btn-primary);">Validada para liquidación</span>
                @endif
            </div>

            {{-- Acciones --}}
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">

                {{-- Cancelar clase: panel inline (solo si no cancelada) --}}
                @if(!$clase->cancelada)
                    <button id="btn-abrir-cancelar"
                            style="display:inline-flex; align-items:center; justify-content:center;
                                   width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                   border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                   border:none; font-family:inherit;
                                   background:var(--color-btn-danger); color:var(--color-surface);">
                        Cancelar
                    </button>
                @endif

                {{-- Reactivar clase: solo admin, solo si cancelada --}}
                @if($clase->cancelada && $esAdmin)
                    <form method="POST" action="{{ route('web.clases.toggle-cancelada', $clase->id) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <input type="hidden" name="motivo_cancelacion" value="">
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:var(--color-btn-primary); color:var(--color-surface);">
                            Activar
                        </button>
                    </form>
                @endif

                {{-- Validar/Desvalidar: solo admin --}}
                @if($esAdmin)
                    <form method="POST" action="{{ route('web.clases.toggle-validada', $clase->id) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit"
                                style="display:inline-flex; align-items:center; justify-content:center;
                                       width:96px; height:32px; font-size:0.82rem; font-weight:600;
                                       border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                                       border:none; font-family:inherit;
                                       background:{{ $clase->validada_para_liquidacion ? 'var(--color-btn-secondary)' : 'var(--color-btn-primary)' }};
                                       color:var(--color-surface);">
                            {{ $clase->validada_para_liquidacion ? 'Desvalidar' : 'Validar' }}
                        </button>
                    </form>
                    <a href="{{ route('web.clases.edit', $clase->id) }}"
                       style="display:inline-flex; align-items:center; justify-content:center;
                              width:96px; height:32px; font-size:0.82rem; font-weight:600;
                              border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                              text-decoration:none; border:none; font-family:inherit;
                              background:var(--color-btn-secondary); color:var(--color-surface);">
                        Editar
                    </a>
                @endif

                <a href="{{ route('web.clases.index') }}"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          width:96px; height:32px; font-size:0.82rem; font-weight:600;
                          border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                          text-decoration:none; border:none; font-family:inherit;
                          background:var(--color-btn-secondary); color:var(--color-surface);">
                    Volver
                </a>
            </div>

        </div>
    </div>
</div>

{{-- Panel: motivo de cancelación --}}
<div id="panel-cancelar" style="display:none; margin-bottom:12px;">
    <div style="padding:16px 20px; background:color-mix(in srgb, var(--color-danger) 6%, var(--color-surface));
                border:1px solid color-mix(in srgb, var(--color-danger) 30%, transparent);
                border-radius:var(--radius-card);">
        <p style="font-size:0.82rem; font-weight:700; color:var(--color-danger); margin:0 0 10px;">
            Indicá el motivo de cancelación
        </p>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="text" id="input-motivo-cancelacion"
                   placeholder="Ej: Feriado, Pista insegura, Lluvia..."
                   maxlength="255"
                   style="flex:1; min-width:220px; padding:8px 12px; font-size:0.82rem;
                          border:1px solid var(--color-border); border-radius:var(--radius-btn);
                          background:var(--color-surface); color:var(--color-text); font-family:inherit;">
            <button id="btn-confirmar-cancelar"
                    style="display:inline-flex; align-items:center; justify-content:center;
                           width:96px; height:32px; font-size:0.82rem; font-weight:600;
                           border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                           border:none; font-family:inherit;
                           background:var(--color-btn-danger); color:var(--color-surface);">
                Confirmar
            </button>
            <button id="btn-cerrar-cancelar"
                    style="display:inline-flex; align-items:center; justify-content:center;
                           width:96px; height:32px; font-size:0.82rem; font-weight:600;
                           border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                           border:none; font-family:inherit;
                           background:var(--color-btn-secondary); color:var(--color-surface);">
                Cancelar
            </button>
        </div>
        <span id="flash-cancelar" style="display:none; font-size:0.78rem; font-weight:600; margin-top:6px;"></span>
    </div>
</div>

{{-- Panel: modificar profesores --}}
<div id="panel-profesores" style="display:none; margin-bottom:12px;">
    <div style="padding:16px 20px; background:var(--color-surface-alt);
                border:1px solid var(--color-border); border-radius:var(--radius-card);">
        <p style="font-size:0.82rem; font-weight:700; color:var(--color-text); margin:0 0 12px;">
            Profesores asignados a esta clase
        </p>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
            @foreach($profesoresDisponibles as $prof)
                <label style="display:flex; align-items:center; gap:6px; font-size:0.82rem;
                              cursor:pointer; user-select:none;">
                    <input type="checkbox"
                           class="prof-checkbox"
                           value="{{ $prof->id }}"
                           {{ in_array($prof->id, $profesoresActualesIds) ? 'checked' : '' }}
                           style="width:16px; height:16px; cursor:pointer; accent-color:var(--color-btn-primary);">
                    {{ $prof->apellido }}, {{ $prof->nombre }}
                </label>
            @endforeach
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <button id="btn-guardar-profesores"
                    style="display:inline-flex; align-items:center; justify-content:center;
                           width:96px; height:32px; font-size:0.82rem; font-weight:600;
                           border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                           border:none; font-family:inherit;
                           background:var(--color-btn-primary); color:var(--color-surface);">
                Guardar
            </button>
            <button id="btn-cerrar-profesores"
                    style="display:inline-flex; align-items:center; justify-content:center;
                           width:96px; height:32px; font-size:0.82rem; font-weight:600;
                           border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                           border:none; font-family:inherit;
                           background:var(--color-btn-secondary); color:var(--color-surface);">
                Cancelar
            </button>
            <span id="flash-profesores" style="display:none; font-size:0.78rem; font-weight:600;"></span>
        </div>
    </div>
</div>

{{-- Banner de cancelada --}}
@if($clase->cancelada)
    <div class="ds-flash ds-flash--error mb-4">
        Esta clase está cancelada. No se pueden registrar asistencias.
    </div>
@endif

{{-- ── Stats bar ──────────────────────────────────────────────────────────── --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $alumnos->count() }}</strong> alumnos en el grupo
        &nbsp;·&nbsp;
        <span id="contador-presentes"><strong>{{ $cantPresentes }}</strong></span> presentes
    </div>
</div>

{{-- ── Cards de alumnos ───────────────────────────────────────────────────── --}}
@forelse($alumnos as $alumno)
@php
    $asistencia   = $asistenciasMap->get($alumno->id);
    $estaPresente = $asistencia ? (bool) $asistencia->presente : false;
    $info         = $infoSemana[$alumno->id] ?? ['asistencias_semana' => 0, 'plan_semana' => null, 'excede' => false];
    $planSemana   = $info['plan_semana'];
    $asisSemana   = $info['asistencias_semana'];
    $excede       = $info['excede'];

    if (!$planSemana)                   $dotEstado = 'neutral';
    elseif ($excede)                    $dotEstado = 'danger';
    elseif ($asisSemana >= $planSemana) $dotEstado = 'warning';
    else                                $dotEstado = 'success';
@endphp

<div class="ds-card ds-surface alumno-asistencia-card"
     id="card-{{ $alumno->id }}"
     data-alumno-id="{{ $alumno->id }}"
     data-plan="{{ $planSemana ?? 0 }}"
     data-asistencias-semana="{{ $asisSemana }}"
     style="margin-bottom:10px;
            {{ $estaPresente ? 'background:color-mix(in srgb, var(--color-success) 8%, var(--color-surface));' : '' }}
            {{ $clase->cancelada ? 'opacity:0.6; pointer-events:none;' : '' }}
            transition: background 200ms ease;">

    <div class="ds-card__rail ds-rail ds-rail--{{ $rail }}"></div>

    <div class="ds-card__content">

        <div class="ds-card__header">
            <span class="ds-dot ds-dot--{{ $dotEstado }}"></span>
            <span style="font-weight:800; font-size:0.95rem;">
                {{ $alumno->apellido }}, {{ $alumno->nombre }}
            </span>
        </div>

        <div class="ds-card__info" data-cols="3" style="margin-top:8px;">
            <div>
                <span class="ds-info-label">Plan semana</span>
                @if($planSemana)
                    <span style="font-size:0.82rem; font-weight:600;
                        color:{{ $excede ? 'var(--color-danger)' : ($asisSemana >= $planSemana ? 'var(--color-warning)' : 'var(--color-success)') }};">
                        {{ $asisSemana }} de {{ $planSemana }}
                        @if($excede) — Excede @elseif($asisSemana >= $planSemana) — Completo @endif
                    </span>
                @else
                    <span style="font-size:0.82rem; color:var(--color-text-muted);">Sin plan</span>
                @endif
            </div>
            <div>
                <span class="ds-info-label">DNI</span>
                <span style="font-size:0.82rem; color:var(--color-text);">{{ $alumno->dni ?? '–' }}</span>
            </div>
            <div></div>
        </div>

        {{-- Panel de exceso --}}
        <div id="exceso-{{ $alumno->id }}"
             style="display:none; margin-top:8px; padding:10px 12px;
                    background:color-mix(in srgb, var(--color-danger) 8%, transparent);
                    border-radius:var(--radius-btn);
                    border:1px solid color-mix(in srgb, var(--color-danger) 25%, transparent);">
            <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase;
                         letter-spacing:0.05em; color:var(--color-danger); display:block; margin-bottom:6px;">
                Excede el plan — indicar motivo
            </span>
            <select id="motivo-{{ $alumno->id }}"
                    style="font-size:0.82rem; padding:4px 10px; border-radius:var(--radius-btn);
                           border:1px solid var(--color-border); background:var(--color-surface);
                           color:var(--color-text); cursor:pointer; font-family:inherit;">
                <option value="EXTRA">Clase adicional (EXTRA)</option>
                <option value="RECUPERA">Recupera clase perdida (RECUPERA)</option>
            </select>
        </div>

        <div class="ds-card__actions" style="margin-top:12px; padding-top:10px; border-top:1px solid var(--color-border);">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;
                          font-size:0.9rem; font-weight:700; user-select:none; width:fit-content;"
                   for="presente-{{ $alumno->id }}">
                <input type="checkbox"
                       id="presente-{{ $alumno->id }}"
                       class="presente-checkbox"
                       data-alumno-id="{{ $alumno->id }}"
                       {{ $estaPresente ? 'checked' : '' }}
                       style="width:22px; height:22px; cursor:pointer; accent-color:var(--color-success);">
                Presente
            </label>
        </div>

    </div>
</div>
@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
        </svg>
        <h3>Sin alumnos en este grupo</h3>
        <p>No hay alumnos activos en el grupo {{ $clase->grupo->nombre }}.</p>
    </div>
@endforelse

{{-- Botón guardar asistencias --}}
@if($alumnos->isNotEmpty())
<div style="display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top:16px; margin-bottom:32px;">
    <span id="flash-asistencias" style="display:none; font-size:0.82rem; font-weight:600;"></span>
    <button id="btn-guardar-asistencias"
            {{ $clase->cancelada ? 'disabled' : '' }}
            style="display:inline-flex; align-items:center; justify-content:center;
                   height:32px; padding:0 1.25rem; font-size:0.82rem; font-weight:600;
                   border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                   border:none; font-family:inherit;
                   background:var(--color-btn-primary); color:var(--color-surface);">
        Guardar asistencias
    </button>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const infoSemana = @json($infoSemana);
    const claseId    = {{ $clase->id }};
    const csrf       = document.querySelector('meta[name="csrf-token"]').content;

    // ── Contador de presentes ───────────────────────────────────────────────
    function actualizarContador() {
        const presentes = document.querySelectorAll('.presente-checkbox:checked').length;
        document.getElementById('contador-presentes').innerHTML = '<strong>' + presentes + '</strong>';
    }

    // ── Checkboxes de asistencia ────────────────────────────────────────────
    document.querySelectorAll('.presente-checkbox').forEach(function (cb) {
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
            actualizarContador();
        });
    });
    actualizarContador();

    // ── Guardar asistencias ─────────────────────────────────────────────────
    const btnGuardar = document.getElementById('btn-guardar-asistencias');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            const flash = document.getElementById('flash-asistencias');
            const items = [];
            document.querySelectorAll('.alumno-asistencia-card').forEach(function (card) {
                const aid    = card.dataset.alumnoId;
                const cb     = card.querySelector('.presente-checkbox');
                const sel    = card.querySelector('select[id^="motivo-"]');
                items.push({ alumno_id: parseInt(aid), presente: cb && cb.checked ? 1 : 0, motivo_exceso: sel ? sel.value : null });
            });
            btnGuardar.disabled = true;
            btnGuardar.textContent = '…';
            flash.style.display = 'none';

            fetch('/clases/' + claseId + '/asistencias', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ items: items }),
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                flash.style.display = 'inline';
                flash.textContent = d.success ? ('✓ ' + d.message) : ('✗ ' + (d.message || 'Error.'));
                flash.style.color = d.success ? 'var(--color-success)' : 'var(--color-danger)';
            })
            .catch(function () {
                flash.style.display = 'inline';
                flash.textContent = '✗ Error de conexión.';
                flash.style.color = 'var(--color-danger)';
            })
            .finally(function () {
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar asistencias';
            });
        });
    }

    // ── Panel de cancelación ────────────────────────────────────────────────
    const btnAbrirCancelar   = document.getElementById('btn-abrir-cancelar');
    const panelCancelar      = document.getElementById('panel-cancelar');
    const btnConfirmarCancelar = document.getElementById('btn-confirmar-cancelar');
    const btnCerrarCancelar  = document.getElementById('btn-cerrar-cancelar');
    const inputMotivo        = document.getElementById('input-motivo-cancelacion');
    const flashCancelar      = document.getElementById('flash-cancelar');

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
    if (btnConfirmarCancelar) {
        btnConfirmarCancelar.addEventListener('click', function () {
            const motivo = inputMotivo ? inputMotivo.value.trim() : '';
            if (!motivo) {
                flashCancelar.style.display = 'block';
                flashCancelar.textContent = 'El motivo es obligatorio.';
                flashCancelar.style.color = 'var(--color-danger)';
                return;
            }
            btnConfirmarCancelar.disabled = true;
            btnConfirmarCancelar.textContent = '…';

            fetch('/clases/' + claseId + '/cancelar', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ motivo_cancelacion: motivo }),
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    // Actualizar UI sin recargar
                    panelCancelar.style.display = 'none';
                    btnAbrirCancelar.style.display = 'none';
                    const badge = document.getElementById('badge-estado');
                    if (badge) {
                        badge.textContent = 'Cancelada';
                        badge.style.background = 'color-mix(in srgb, var(--color-danger) 15%, transparent)';
                        badge.style.color = 'var(--color-danger)';
                    }
                    // Deshabilitar cards de alumnos
                    document.querySelectorAll('.alumno-asistencia-card').forEach(function (c) {
                        c.style.opacity = '0.6';
                        c.style.pointerEvents = 'none';
                    });
                    if (btnGuardar) btnGuardar.disabled = true;
                    // Mostrar banner
                    const banner = document.createElement('div');
                    banner.className = 'ds-flash ds-flash--error mb-4';
                    banner.textContent = 'Esta clase está cancelada. No se pueden registrar asistencias.';
                    panelCancelar.insertAdjacentElement('afterend', banner);
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
                btnConfirmarCancelar.disabled = false;
                btnConfirmarCancelar.textContent = 'Confirmar';
            });
        });
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
    if (btnGuardarProfs) {
        btnGuardarProfs.addEventListener('click', function () {
            const ids = [];
            document.querySelectorAll('.prof-checkbox:checked').forEach(function (cb) {
                ids.push(parseInt(cb.value));
            });
            btnGuardarProfs.disabled = true;
            btnGuardarProfs.textContent = '…';
            flashProfs.style.display = 'none';

            fetch('/clases/' + claseId + '/profesores', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ profesores: ids }),
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    panelProfs.style.display = 'none';
                    if (textoProfs) textoProfs.textContent = d.profesores;
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
        });
    }
})();
</script>
@endpush
