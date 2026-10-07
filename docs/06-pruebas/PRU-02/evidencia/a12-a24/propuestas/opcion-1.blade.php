@extends('layouts.ds-app')

@section('title', 'Inicio – Wings (Opción 1)')
@section('module-title', 'Inicio')

@section('content')

@php
$cajaPropia   = $cajaPropia ?? null;
$cajaClub     = $cajaClub ?? null;
$ultimaCaja   = $ultimaCaja ?? null;
$bloqueo      = $bloqueo ?? ['activo' => false];
$diasSemana   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$meses        = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$diaSemana    = $diasSemana[$hoyAr->dayOfWeek];
$fechaLabel   = $diaSemana . ', ' . $hoyAr->day . ' de ' . $meses[$hoyAr->month] . ' ' . $hoyAr->year;
@endphp

{{-- Bloqueo por caja de día anterior --}}
@if($bloqueo['activo'])
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-danger);">
    <p style="font-size:0.75rem; color:var(--color-danger); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
        Caja pendiente de cierre
    </p>
    <p style="font-size:0.9rem; color:var(--color-text); margin-bottom:12px;">
        {{ $bloqueo['mensaje'] }}
    </p>
    <a href="{{ route('web.caja.resumen', $bloqueo['caja_id']) }}"
       style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
              font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
              text-decoration:none; background:var(--color-danger); color:#fff;">
        Cerrar
    </a>
</div>
@endif

{{-- Cajas rechazadas --}}
@if($cajasRechazadasCount > 0)
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-danger);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <p style="font-size:0.75rem; color:var(--color-danger); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                {{ $cajasRechazadasCount === 1 ? 'Tenés 1 caja rechazada' : 'Tenés ' . $cajasRechazadasCount . ' cajas rechazadas' }}
            </p>
            <p style="font-size:0.85rem; color:var(--color-text);">
                El administrador rechazó {{ $cajasRechazadasCount === 1 ? 'una caja tuya' : 'cajas tuyas' }}. Revisá el motivo y corregí lo observado.
            </p>
        </div>
        <a href="{{ route('web.caja.index') }}" class="ds-btn-row ds-btn-row--sec">Ver</a>
    </div>
</div>
@endif

{{-- Fecha --}}
<p style="font-size:0.82rem; color:var(--color-text-muted); margin-bottom:1rem;">{{ $fechaLabel }}</p>

{{-- Stats del día --}}
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:1.5rem;">
    <div class="filtros-card" style="text-align:center; padding:1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Cobrado hoy</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-success);">
            ${{ number_format($totalCobradoHoy, 0, ',', '.') }}
        </p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Cobros registrados</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-text);">{{ $numCobrosHoy }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Cajas hoy</p>
        <p style="font-size:1.8rem; font-weight:800; color:var(--color-text);">{{ $cajas->count() }}</p>
    </div>
</div>

{{-- Estado de caja + acciones guiadas --}}
@if($cajaPropia)
{{-- Caso 1: Turno propio abierto --}}
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-warning);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-warning); margin-bottom:4px;">Caja abierta</p>
            <p style="font-size:0.85rem; color:var(--color-text);">
                Abierta desde {{ \Carbon\Carbon::parse($cajaPropia->apertura_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}
            </p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('web.caja.cobrar-cuota') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-primary); color:#fff;">
                Cobrar
            </a>
            <a href="{{ route('web.caja.movimiento') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Registrar
            </a>
            <a href="{{ route('web.caja.resumen', $cajaPropia->id) }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Resumen
            </a>
        </div>
    </div>
</div>
@elseif($cajaClub)
{{-- Caso 2: Turno de otro compañero abierto (cajón compartido) --}}
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-warning);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-warning); margin-bottom:4px;">Caja en curso</p>
            <p style="font-size:0.85rem; color:var(--color-text);">
                Abierta por <strong>{{ $cajaClub->usuarioOperativo->name ?? 'Operativo' }}</strong> desde las {{ \Carbon\Carbon::parse($cajaClub->apertura_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}. El cajón es compartido.
            </p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('web.caja.cobrar-cuota') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-primary); color:#fff;">
                Cobrar
            </a>
            <a href="{{ route('web.caja.movimiento') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Registrar
            </a>
            <a href="{{ route('web.caja.detalle', $cajaClub->id) }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                      font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Detalle
            </a>
        </div>
    </div>
</div>
@elseif($ultimaCaja)
{{-- Caso 3: Turno cerrado hoy --}}
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-success);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-success); margin-bottom:4px;">Caja cerrada</p>
            <p style="font-size:0.85rem; color:var(--color-text);">
                Última caja: {{ strtoupper($ultimaCaja->estado) }}
                @if($ultimaCaja->cierre_at)
                — cerrada a las {{ \Carbon\Carbon::parse($ultimaCaja->cierre_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}
                @endif
            </p>
        </div>
        @if(!$bloqueo['activo'])
        <a href="{{ route('web.caja.apertura') }}"
           style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                  font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                  text-decoration:none; background:var(--color-btn-primary); color:#fff;">
            Abrir
        </a>
        @endif
    </div>
</div>
@else
{{-- Caso 4: Recién llega, sin caja abierta en el club --}}
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-warning);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-warning); margin-bottom:4px;">Sin caja abierta</p>
            <p style="font-size:0.85rem; color:var(--color-text);">
                Abrí la caja y declará el cambio inicial para empezar a cobrar.
            </p>
        </div>
        @if(!$bloqueo['activo'])
        <a href="{{ route('web.caja.apertura') }}"
           style="display:inline-flex; align-items:center; justify-content:center; height:32px; padding:0 16px;
                  font-size:0.82rem; font-weight:600; border-radius:var(--radius-btn);
                  text-decoration:none; background:var(--color-btn-primary); color:#fff;">
            Abrir
        </a>
        @endif
    </div>
</div>
@endif

{{-- Clases de hoy + Alumnos --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

    {{-- Clases de hoy --}}
    <div>
        <div class="stats-bar mb-2">
            <div class="stats-info">
                Clases de hoy — <strong>{{ $clasesHoy->count() }}</strong>
            </div>
        </div>
        @if($clasesHoy->isEmpty())
        <div class="filtros-card" style="text-align:center; padding:1.25rem;">
            <p style="font-size:0.82rem; color:var(--color-text-muted);">No hay clases programadas para hoy.</p>
        </div>
        @else
        <div style="display:flex; flex-direction:column; gap:6px;">
            @foreach($clasesHoy as $clase)
            @php $conLista = $clase->presentes_count > 0; @endphp
            <div class="filtros-card" style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:0.6rem 0.9rem;">
                <div style="min-width:0;">
                    <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">
                        {{ \Carbon\Carbon::parse($clase->hora_inicio)->format('H:i') }}
                        — {{ $clase->grupo->nombre_completo ?? 'Grupo' }}
                    </p>
                    <p style="font-size:0.72rem; color:{{ $conLista ? 'var(--color-success)' : 'var(--color-warning)' }}; font-weight:600;">
                        {{ $conLista ? $clase->presentes_count . ' presente' . ($clase->presentes_count !== 1 ? 's' : '') : 'Sin lista' }}
                    </p>
                </div>
                <a href="{{ route('web.clases.show', $clase->id) }}" class="ds-btn-row ds-btn-row--sec">Lista</a>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Alumnos --}}
    <div>
        <div class="stats-bar mb-2">
            <div class="stats-info">Alumnos</div>
        </div>
        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
            <a href="{{ route('web.cobranza.index') }}" class="filtros-card" style="text-align:center; padding:1rem; text-decoration:none;">
                <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Con deuda</p>
                <p style="font-size:1.8rem; font-weight:800; color:{{ $alumnosConDeuda > 0 ? 'var(--color-danger)' : 'var(--color-success)' }};">
                    {{ $alumnosConDeuda }}
                </p>
            </a>
            <a href="{{ route('web.revision-cobranza.index') }}" class="filtros-card" style="text-align:center; padding:1rem; text-decoration:none;">
                <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Posibles inactivos</p>
                <p style="font-size:1.8rem; font-weight:800; color:{{ $posiblesInactivos > 0 ? 'var(--color-warning)' : 'var(--color-text)' }};">
                    {{ $posiblesInactivos }}
                </p>
            </a>
        </div>
    </div>

</div>

{{-- Cajas del día --}}
@if($cajas->isNotEmpty())
<div class="stats-bar mb-2">
    <div class="stats-info">Movimientos del día</div>
</div>
@foreach($cajas as $caja)
@php
    $estadoColor = match($caja->estado) {
        'ABIERTA'   => 'var(--color-warning)',
        'CERRADA'   => 'var(--color-text-muted)',
        'VALIDADA'  => 'var(--color-success)',
        'RECHAZADA' => 'var(--color-danger)',
        default     => 'var(--color-text-muted)',
    };
    $movActivos = $caja->movimientos->where('estado', 'ACTIVO');
    $ingHoy = $movActivos->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'INGRESO')->sum('monto');
    $egrHoy = $movActivos->filter(fn($m) => $m->subrubro?->rubro?->tipo === 'EGRESO')->sum('monto');
@endphp
<div class="alumno-card" style="margin-bottom:8px;">
    <div class="alumno-card-header">
        <span class="alumno-dot" style="background:{{ $estadoColor }};"></span>
        <h3 class="alumno-nombre">Caja {{ \Carbon\Carbon::parse($caja->apertura_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}</h3>
        <span style="margin-left:auto; font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:999px;
                     white-space:nowrap;
                     background:color-mix(in srgb, {{ $estadoColor }} 12%, transparent);
                     color:{{ $estadoColor }};">
            {{ $caja->estado }}
        </span>
    </div>
    <div class="alumno-info">
        <div class="info-item">
            <span class="info-label">Movimientos:</span>
            <span class="info-value">{{ $movActivos->count() }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Ingresos:</span>
            <span class="info-value" style="color:var(--color-success);">${{ number_format($ingHoy, 0, ',', '.') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Egresos:</span>
            <span class="info-value" style="color:var(--color-danger);">${{ number_format($egrHoy, 0, ',', '.') }}</span>
        </div>
        <div class="info-item" style="margin-left:auto;">
            <a href="{{ route('web.caja.detalle', $caja->id) }}" class="ds-btn-row ds-btn-row--sec">Detalle</a>
        </div>
    </div>
</div>
@endforeach
@endif

@endsection
