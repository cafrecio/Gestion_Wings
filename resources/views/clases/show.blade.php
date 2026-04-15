@extends('layouts.app')

@php
    $dep = mb_strtolower($clase->grupo->deporte->nombre ?? '');
    $dep = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');

    $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $fechaTexto = $diasSemana[$clase->fecha->dayOfWeek]
        . ' ' . $clase->fecha->day
        . ' de ' . $meses[$clase->fecha->month]
        . ' de ' . $clase->fecha->year;

    $cantPresentes = $asistenciasMap->where('presente', true)->count();
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
<div class="filtros-card mb-4" style="padding:0; overflow:hidden;">
    <div style="display:flex; gap:0;">

        {{-- Rail de color de deporte --}}
        <div style="width:6px; flex-shrink:0; background:var(--color-sport-{{ $rail }});"></div>

        {{-- Contenido del header --}}
        <div style="flex:1; padding:1.25rem 1.5rem;">

            {{-- Fecha + hora --}}
            <div style="display:flex; align-items:baseline; gap:1rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                <h2 style="font-size:1.1rem; font-weight:800; color:var(--color-text); margin:0; text-transform:capitalize;">
                    {{ $fechaTexto }}
                </h2>
                <span style="font-size:0.9rem; font-weight:600; color:var(--color-text-muted);">
                    {{ $clase->hora_inicio->format('H:i') }} – {{ $clase->hora_fin->format('H:i') }}
                </span>
            </div>

            {{-- Grupo + deporte badge --}}
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.6rem;">
                <span style="font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:999px;
                             background:color-mix(in srgb, var(--color-sport-{{ $rail }}) 18%, transparent);
                             color:var(--color-sport-{{ $rail }}); white-space:nowrap;">
                    {{ $clase->grupo->deporte->nombre ?? '–' }}
                </span>
                <span style="font-size:0.88rem; font-weight:600; color:var(--color-text);">
                    {{ $clase->grupo->nombre }}
                </span>
            </div>

            {{-- Profesores --}}
            <div style="font-size:0.8rem; color:var(--color-text-muted); margin-bottom:0.75rem;">
                @if($clase->profesores->isNotEmpty())
                    {{ $clase->profesores->map(fn($p) => $p->apellido . ', ' . $p->nombre)->implode(' · ') }}
                @else
                    <span style="opacity:0.5;">Sin profesor asignado</span>
                @endif
            </div>

            {{-- Badges de estado --}}
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:1rem;">
                @if($clase->cancelada)
                    <span style="font-size:0.65rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-danger) 15%, transparent);
                                 color:var(--color-danger);">Cancelada</span>
                @else
                    <span style="font-size:0.65rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-success) 15%, transparent);
                                 color:var(--color-success);">Activa</span>
                @endif
                @if($clase->validada_para_liquidacion)
                    <span style="font-size:0.65rem; font-weight:700; padding:3px 9px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-btn-primary) 15%, transparent);
                                 color:var(--color-btn-primary);">Validada para liquidación</span>
                @endif
            </div>

            {{-- Acciones (solo Admin) --}}
            @if(Auth::user()->rol === 'ADMIN')
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <form method="POST" action="{{ route('web.clases.toggle-cancelada', $clase->id) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" style="
                        display:inline-flex; align-items:center; justify-content:center;
                        width:96px; height:32px; font-size:0.82rem; font-weight:600;
                        border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                        border:none; font-family:inherit;
                        background:{{ $clase->cancelada ? 'var(--color-btn-primary)' : 'var(--color-btn-danger)' }};
                        color:var(--color-surface);">
                        {{ $clase->cancelada ? 'Activar' : 'Cancelar' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('web.clases.toggle-validada', $clase->id) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" style="
                        display:inline-flex; align-items:center; justify-content:center;
                        width:96px; height:32px; font-size:0.82rem; font-weight:600;
                        border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                        border:none; font-family:inherit;
                        background:{{ $clase->validada_para_liquidacion ? 'var(--color-btn-secondary)' : 'var(--color-btn-primary)' }};
                        color:var(--color-surface);">
                        {{ $clase->validada_para_liquidacion ? 'Desvalidar' : 'Validar' }}
                    </button>
                </form>
                <a href="{{ route('web.clases.edit', $clase->id) }}" style="
                    display:inline-flex; align-items:center; justify-content:center;
                    width:96px; height:32px; font-size:0.82rem; font-weight:600;
                    border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                    text-decoration:none; border:none; font-family:inherit;
                    background:var(--color-btn-secondary); color:var(--color-surface);">
                    Editar
                </a>
            </div>
            @endif

        </div>

        {{-- Botón Volver — alineado a la derecha del panel --}}
        <div style="display:flex; align-items:flex-start; padding:1.25rem 1.5rem 1.25rem 0; flex-shrink:0;">
            <a href="{{ route('web.clases.index') }}" style="
                display:inline-flex; align-items:center; justify-content:center;
                width:96px; height:32px; font-size:0.82rem; font-weight:600;
                border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                text-decoration:none; border:none; font-family:inherit;
                background:var(--color-btn-secondary); color:var(--color-surface);">
                Volver
            </a>
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
        Mostrando <strong>{{ $alumnos->count() }}</strong> alumnos
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

    if (!$planSemana)       $dotEstado = 'neutral';
    elseif ($excede)        $dotEstado = 'danger';
    elseif ($asisSemana >= $planSemana) $dotEstado = 'warning';
    else                    $dotEstado = 'success';
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

    {{-- Rail de deporte --}}
    <div class="ds-card__rail ds-rail ds-rail--{{ $rail }}"></div>

    <div class="ds-card__content">

        {{-- Header: dot + nombre --}}
        <div class="ds-card__header">
            <span class="ds-dot ds-dot--{{ $dotEstado }}"></span>
            <span style="font-weight:800; font-size:0.95rem;">
                {{ $alumno->apellido }}, {{ $alumno->nombre }}
            </span>
        </div>

        {{-- Info grid --}}
        <div class="ds-card__info" data-cols="3" style="margin-top:8px;">

            {{-- Plan semana --}}
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

            {{-- DNI --}}
            <div>
                <span class="ds-info-label">DNI</span>
                <span style="font-size:0.82rem; color:var(--color-text);">{{ $alumno->dni ?? '–' }}</span>
            </div>

            <div></div>
        </div>

        {{-- Panel de exceso — oculto por defecto, JS lo muestra si corresponde --}}
        <div id="exceso-{{ $alumno->id }}"
             style="display:none; margin-top:8px; padding:10px 12px;
                    background:color-mix(in srgb, var(--color-danger) 8%, transparent);
                    border-radius:var(--radius-btn);
                    border:1px solid color-mix(in srgb, var(--color-danger) 25%, transparent);
                    transition: all 200ms ease;">
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

        {{-- Acciones: checkbox presente --}}
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
        <p>No hay alumnos activos asignados al grupo {{ $clase->grupo->nombre }}.</p>
    </div>
@endforelse

{{-- Botón guardar --}}
@if($alumnos->isNotEmpty())
<div style="display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top:16px; margin-bottom:32px;">
    <span id="flash-asistencias" style="display:none; font-size:0.82rem; font-weight:600;"></span>
    <button id="btn-guardar-asistencias"
            {{ $clase->cancelada ? 'disabled' : '' }}
            style="display:inline-flex; align-items:center; justify-content:center;
                   width:96px; height:32px; font-size:0.82rem; font-weight:600;
                   border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;
                   border:none; font-family:inherit;
                   background:var(--color-btn-primary); color:var(--color-surface);">
        Guardar
    </button>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const infoSemana  = @json($infoSemana);
    const claseId     = {{ $clase->id }};
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;
    const contadorEl  = document.getElementById('contador-presentes');

    function actualizarContador() {
        const presentes = document.querySelectorAll('.presente-checkbox:checked').length;
        contadorEl.innerHTML = '<strong>' + presentes + '</strong>';
    }

    document.querySelectorAll('.presente-checkbox').forEach(function (checkbox) {
        // Inicializar estado de exceso para los ya marcados al cargar
        if (checkbox.checked) {
            const alumnoId = checkbox.dataset.alumnoId;
            const info = infoSemana[alumnoId];
            if (info && info.excede) {
                document.getElementById('exceso-' + alumnoId).style.display = 'block';
            }
        }

        checkbox.addEventListener('change', function () {
            const alumnoId = this.dataset.alumnoId;
            const card     = document.getElementById('card-' + alumnoId);
            const exceso   = document.getElementById('exceso-' + alumnoId);
            const info     = infoSemana[alumnoId] || {};

            if (this.checked) {
                // +1 porque aún no se guardó
                const nuevasCuenta = (info.asistencias_semana || 0) + 1;
                const planSemana   = info.plan_semana || 0;

                if (planSemana && nuevasCuenta > planSemana) {
                    exceso.style.display = 'block';
                    card.style.background = 'color-mix(in srgb, var(--color-warning) 8%, var(--color-surface))';
                } else {
                    exceso.style.display = 'none';
                    card.style.background = 'color-mix(in srgb, var(--color-success) 8%, var(--color-surface))';
                }
            } else {
                card.style.background = '';
                exceso.style.display = 'none';
            }

            actualizarContador();
        });
    });

    actualizarContador();

    const btnGuardar = document.getElementById('btn-guardar-asistencias');
    if (!btnGuardar) return;

    btnGuardar.addEventListener('click', function () {
        const flash = document.getElementById('flash-asistencias');

        const items = [];
        document.querySelectorAll('.alumno-asistencia-card').forEach(function (card) {
            const alumnoId     = card.dataset.alumnoId;
            const checkbox     = card.querySelector('.presente-checkbox');
            const motivoSelect = card.querySelector('select[id^="motivo-"]');
            items.push({
                alumno_id:    parseInt(alumnoId),
                presente:     checkbox && checkbox.checked ? 1 : 0,
                motivo_exceso: motivoSelect ? motivoSelect.value : null,
            });
        });

        btnGuardar.disabled = true;
        btnGuardar.textContent = '…';
        flash.style.display = 'none';

        fetch('/clases/' + claseId + '/asistencias', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            },
            body: JSON.stringify({ items: items }),
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            flash.style.display = 'inline';
            if (data.success) {
                flash.textContent = '✓ ' + data.message;
                flash.style.color = 'var(--color-success)';
            } else {
                flash.textContent = '✗ ' + (data.message || 'Error al guardar.');
                flash.style.color = 'var(--color-danger)';
            }
        })
        .catch(function () {
            flash.style.display = 'inline';
            flash.textContent = '✗ Error de conexión.';
            flash.style.color = 'var(--color-danger)';
        })
        .finally(function () {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar';
        });
    });
})();
</script>
@endpush
