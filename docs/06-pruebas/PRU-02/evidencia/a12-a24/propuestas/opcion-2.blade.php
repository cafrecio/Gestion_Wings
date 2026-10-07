@extends('layouts.ds-app')

@section('title', 'Inicio – Wings (Opción 2)')
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

{{-- 1. Alertas prioritarias (Bloqueo o Rechazo) --}}
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

{{-- 2. Encabezado de fecha --}}
<p style="font-size:0.82rem; color:var(--color-text-muted); margin-bottom:1rem;">{{ $fechaLabel }}</p>

{{-- 3. TARJETA PRINCIPAL DE MOSTRADOR: Lo primero que se ve al llegar --}}
@if($cajaPropia)
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-success); padding:1.25rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--color-success);"></span>
                <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-success);">Mostrador activo</span>
            </div>
            <p style="font-size:1.05rem; font-weight:700; color:var(--color-text); margin-bottom:2px;">
                Caja abierta
            </p>
            <p style="font-size:0.85rem; color:var(--color-text-muted);">
                Abierta por vos a las {{ \Carbon\Carbon::parse($cajaPropia->apertura_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}
            </p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('web.caja.cobrar-cuota') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 20px;
                      font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-primary); color:#fff;">
                Cobrar
            </a>
            <a href="{{ route('web.caja.movimiento') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 18px;
                      font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Registrar
            </a>
            <a href="{{ route('web.caja.resumen', $cajaPropia->id) }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 18px;
                      font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Resumen
            </a>
        </div>
    </div>
</div>
@elseif($cajaClub)
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-warning); padding:1.25rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--color-warning);"></span>
                <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-warning);">Cajón compartido en curso</span>
            </div>
            <p style="font-size:1.05rem; font-weight:700; color:var(--color-text); margin-bottom:2px;">
                Turno de {{ $cajaClub->usuarioOperativo->name ?? 'Operativo' }}
            </p>
            <p style="font-size:0.85rem; color:var(--color-text-muted);">
                Abierto a las {{ \Carbon\Carbon::parse($cajaClub->apertura_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}. Podés cobrar y registrar en este cajón.
            </p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('web.caja.cobrar-cuota') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 20px;
                      font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-primary); color:#fff;">
                Cobrar
            </a>
            <a href="{{ route('web.caja.movimiento') }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 18px;
                      font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Registrar
            </a>
            <a href="{{ route('web.caja.detalle', $cajaClub->id) }}"
               style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 18px;
                      font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                      text-decoration:none; background:var(--color-btn-secondary); color:var(--color-surface);">
                Detalle
            </a>
        </div>
    </div>
</div>
@elseif($ultimaCaja)
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-success); padding:1.25rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--color-success);"></span>
                <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-success);">Turno cerrado</span>
            </div>
            <p style="font-size:1.05rem; font-weight:700; color:var(--color-text); margin-bottom:2px;">
                Última caja: {{ strtoupper($ultimaCaja->estado) }}
            </p>
            <p style="font-size:0.85rem; color:var(--color-text-muted);">
                @if($ultimaCaja->cierre_at)
                Cerrada a las {{ \Carbon\Carbon::parse($ultimaCaja->cierre_at)->setTimezone('America/Argentina/Buenos_Aires')->format('H:i') }}.
                @endif
                Para atender un nuevo turno, abrí la caja.
            </p>
        </div>
        @if(!$bloqueo['activo'])
        <a href="{{ route('web.caja.apertura') }}"
           style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 24px;
                  font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                  text-decoration:none; background:var(--color-btn-primary); color:#fff;">
            Abrir
        </a>
        @endif
    </div>
</div>
@else
<div class="filtros-card mb-4" style="border-left:4px solid var(--color-brand); padding:1.25rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--color-brand);"></span>
                <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-brand);">Inicio de mostrador</span>
            </div>
            <p style="font-size:1.05rem; font-weight:700; color:var(--color-text); margin-bottom:2px;">
                Cajón listo para iniciar
            </p>
            <p style="font-size:0.85rem; color:var(--color-text-muted);">
                Para cobrar cuotas o registrar movimientos en efectivo, primero declará el cambio inicial.
            </p>
        </div>
        @if(!$bloqueo['activo'])
        <a href="{{ route('web.caja.apertura') }}"
           style="display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 24px;
                  font-size:0.85rem; font-weight:600; border-radius:var(--radius-btn);
                  text-decoration:none; background:var(--color-btn-primary); color:#fff;">
            Abrir
        </a>
        @endif
    </div>
</div>
@endif

{{-- 4. TAREAS OPERATIVAS: Clases de hoy y Atención de alumnos --}}
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

    {{-- Atención a Alumnos --}}
    <div>
        <div class="stats-bar mb-2">
            <div class="stats-info">Atención a alumnos</div>
        </div>
        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
            <a href="{{ route('web.cobranza.index') }}" class="filtros-card" style="text-align:center; padding:1.2rem 1rem; text-decoration:none;">
                <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Con deuda</p>
                <p style="font-size:1.8rem; font-weight:800; color:{{ $alumnosConDeuda > 0 ? 'var(--color-danger)' : 'var(--color-success)' }};">
                    {{ $alumnosConDeuda }}
                </p>
                <p style="font-size:0.75rem; color:var(--color-brand); font-weight:600; margin-top:4px;">Ir a cobranza →</p>
            </a>
            <a href="{{ route('web.revision-cobranza.index') }}" class="filtros-card" style="text-align:center; padding:1.2rem 1rem; text-decoration:none;">
                <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Posibles inactivos</p>
                <p style="font-size:1.8rem; font-weight:800; color:{{ $posiblesInactivos > 0 ? 'var(--color-warning)' : 'var(--color-text)' }};">
                    {{ $posiblesInactivos }}
                </p>
                <p style="font-size:0.75rem; color:var(--color-brand); font-weight:600; margin-top:4px;">Ir a revisión →</p>
            </a>
        </div>
    </div>

</div>

{{-- 5. RESUMEN FINANCIERO DEL DÍA (Stats de soporte al pie) --}}
<div class="stats-bar mb-2">
    <div class="stats-info">Recaudación de hoy</div>
</div>
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:1.5rem;">
    <div class="filtros-card" style="text-align:center; padding:0.85rem 1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:2px;">Cobrado hoy</p>
        <p style="font-size:1.5rem; font-weight:800; color:var(--color-success);">
            ${{ number_format($totalCobradoHoy, 0, ',', '.') }}
        </p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:0.85rem 1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:2px;">Cobros registrados</p>
        <p style="font-size:1.5rem; font-weight:800; color:var(--color-text);">{{ $numCobrosHoy }}</p>
    </div>
    <div class="filtros-card" style="text-align:center; padding:0.85rem 1rem;">
        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:2px;">Cajas del día</p>
        <p style="font-size:1.5rem; font-weight:800; color:var(--color-text);">{{ $cajas->count() }}</p>
    </div>
</div>

{{-- 6. Historial de Cajas del día --}}
@if($cajas->isNotEmpty())
<div class="stats-bar mb-2">
    <div class="stats-info">Movimientos por caja</div>
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
