@extends('layouts.ds-app')

@section('title', 'Revisión de cobranza – Wings')
@section('module-title', 'Revisión')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="filtros-card mb-3" style="border-left:4px solid var(--color-success);">
    <p style="font-size:0.85rem; color:var(--color-success);">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="filtros-card mb-3" style="border-left:4px solid var(--color-danger);">
    <p style="font-size:0.85rem; color:var(--color-danger);">{{ session('error') }}</p>
</div>
@endif

{{-- Stats --}}
@if($totalPendientes > 0)
<div class="filtros-card mb-3" style="border-left:4px solid var(--color-warning);">
    <p style="font-size:0.85rem; color:var(--color-warning); font-weight:600;">
        {{ $totalPendientes }} alumno{{ $totalPendientes !== 1 ? 's' : '' }} pendiente{{ $totalPendientes !== 1 ? 's' : '' }} de revisión
    </p>
    <p style="font-size:0.8rem; color:var(--color-text-muted); margin-top:4px;">
        Estos alumnos no generaron deuda automática porque no tuvieron asistencias el mes anterior. Contactar al tutor y resolver.
    </p>
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('web.revision-cobranza.index') }}">
<div class="filtros-card mb-3">
    <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end;">
        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Estado</label>
            <select name="estado" class="w-full px-3 py-2 text-sm wings-input" onchange="this.form.submit()">
                <option value="PENDIENTE" {{ $estadoFiltro === 'PENDIENTE' ? 'selected' : '' }}>Pendientes</option>
                <option value="RESUELTO"  {{ $estadoFiltro === 'RESUELTO'  ? 'selected' : '' }}>Resueltos</option>
                <option value=""          {{ $estadoFiltro === '' || $estadoFiltro === null ? 'selected' : '' }}>Todos</option>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Período</label>
            <select name="periodo" class="w-full px-3 py-2 text-sm wings-input" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach($periodos as $p)
                    <option value="{{ $p }}" {{ $periodoFiltro === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div>
            @if($estadoFiltro || $periodoFiltro)
            <a href="{{ route('web.revision-cobranza.index') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:38px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Limpiar
            </a>
            @endif
        </div>
    </div>
</div>
</form>

{{-- Lista --}}
@if($revisiones->isEmpty())
<div style="padding:2rem 0; text-align:center;">
    <p style="color:var(--color-text-muted); font-size:0.9rem;">No hay revisiones para los filtros seleccionados.</p>
</div>
@else
<div class="stats-bar mb-2">
    <div class="stats-info"><strong>{{ $revisiones->total() }}</strong> revisione{{ $revisiones->total() !== 1 ? 's' : '' }}</div>
</div>

@foreach($revisiones as $revision)
@php
    $alumno  = $revision->alumno;
    $isPendiente = $revision->estado_revision === 'PENDIENTE';
    $resolucionColor = match($revision->resolucion) {
        'CONTINUA'         => 'var(--color-success)',
        'INACTIVO'         => 'var(--color-danger)',
        'REACTIVADO_AUTO'  => 'var(--color-text-muted)',
        default            => 'var(--color-warning)',
    };
    $resolucionLabel = match($revision->resolucion) {
        'CONTINUA'         => 'Confirmado activo',
        'INACTIVO'         => 'Inactivado',
        'REACTIVADO_AUTO'  => 'Reactivado automáticamente',
        default            => 'Pendiente',
    };
@endphp

<div class="alumno-card mb-3" id="rev-{{ $revision->id }}">
    <div class="alumno-card-header">
        <span class="alumno-dot" style="background:{{ $isPendiente ? 'var(--color-warning)' : $resolucionColor }};"></span>
        <h3 class="alumno-nombre">
            @if($alumno)
                {{ $alumno->apellido }}, {{ $alumno->nombre }}
            @else
                Alumno eliminado (#{{ $revision->alumno_id }})
            @endif
        </h3>
        <span style="margin-left:auto; font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:999px;
                     background:color-mix(in srgb, {{ $isPendiente ? 'var(--color-warning)' : $resolucionColor }} 12%, transparent);
                     color:{{ $isPendiente ? 'var(--color-warning)' : $resolucionColor }};">
            {{ $isPendiente ? 'PENDIENTE' : strtoupper($resolucionLabel) }}
        </span>
    </div>

    <div class="alumno-info">
        <div class="info-item">
            <span class="info-label">Período:</span>
            <span class="info-value" style="font-weight:600;">{{ $revision->periodo_objetivo }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Deporte:</span>
            <span class="info-value">{{ $alumno?->deporte?->nombre ?? '–' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Grupo:</span>
            <span class="info-value">{{ $alumno?->grupo?->nombre ?? '–' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Motivo:</span>
            <span class="info-value" style="color:var(--color-text-muted); font-size:0.78rem;">{{ $revision->motivo }}</span>
        </div>
        @if($alumno)
        <div class="info-item" style="margin-left:auto;">
            <a href="{{ route('web.alumnos.show', $alumno->id) }}" class="ds-btn-row ds-btn-row--sec">Ver</a>
        </div>
        @endif
    </div>

    {{-- Nota de resolución si ya está resuelto --}}
    @if(!$isPendiente && $revision->nota_resolucion)
    <div style="padding:8px 16px 8px; border-top:1px solid var(--color-border); font-size:0.8rem; color:var(--color-text-muted);">
        <strong>Nota:</strong> {{ $revision->nota_resolucion }}
        @if($revision->usuarioResolucion)
            — {{ $revision->usuarioResolucion->name }},
            {{ \Carbon\Carbon::parse($revision->resuelto_at)->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}
        @endif
    </div>
    @endif

    {{-- Formulario de resolución (solo pendientes) --}}
    @if($isPendiente && $alumno)
    <div class="alumno-actions" style="border-top:1px solid var(--color-border); padding:10px 12px; gap:8px; flex-direction:column; align-items:stretch;">
        <div id="form-{{ $revision->id }}" style="display:none;">
            <form method="POST" action="{{ route('web.revision-cobranza.resolver', $revision->id) }}">
                @csrf
                <input type="hidden" name="resolucion" id="res-tipo-{{ $revision->id }}">
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">
                        Nota (contacto y motivo)
                    </label>
                    <textarea name="nota_resolucion" rows="2" required minlength="5" maxlength="500"
                              placeholder="Ej: WhatsApp con Margarita — alumno de vacaciones en julio"
                              style="width:100%; padding:8px 10px; font-size:0.83rem; border:1px solid var(--color-border);
                                     border-radius:var(--radius-btn); background:var(--color-surface); color:var(--color-text);
                                     font-family:inherit; resize:vertical;"></textarea>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit"
                            style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                                   font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                                   border:none; font-family:inherit; background:var(--color-btn-primary); color:#fff;">
                        Confirmar
                    </button>
                    <button type="button" onclick="cerrarForm({{ $revision->id }})"
                            style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                                   font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                                   border:1px solid var(--color-border); font-family:inherit;
                                   background:transparent; color:var(--color-text-muted);">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
        <div id="botones-{{ $revision->id }}" style="display:flex; gap:8px;">
            <button onclick="abrirForm({{ $revision->id }}, 'CONTINUA')"
                    style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                           font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                           border:none; font-family:inherit; background:var(--color-success); color:#fff;">
                Continúa
            </button>
            <button onclick="abrirForm({{ $revision->id }}, 'INACTIVO')"
                    style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                           font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn); cursor:pointer;
                           border:none; font-family:inherit; background:var(--color-danger); color:#fff;">
                Inactivo
            </button>
        </div>
    </div>
    @endif
</div>
@endforeach

{{ $revisiones->links() }}
@endif

<script>
function abrirForm(id, tipo) {
    document.getElementById('res-tipo-' + id).value = tipo;
    document.getElementById('form-' + id).style.display = 'block';
    document.getElementById('botones-' + id).style.display = 'none';
    document.querySelector('#form-' + id + ' textarea').focus();
}
function cerrarForm(id) {
    document.getElementById('form-' + id).style.display = 'none';
    document.getElementById('botones-' + id).style.display = 'flex';
}
</script>

@endsection
