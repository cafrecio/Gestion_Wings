@extends('layouts.ds-app')

@section('content')
<div class="container mx-auto px-4 py-4" style="max-width:1100px;">

    {{-- Encabezado unificado --}}
    <div class="mb-4" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 class="ds-h1" style="font-size:1.5rem; margin-bottom:2px;">Mostrador Operativo</h1>
            <p style="font-size:0.85rem; color:var(--color-text-muted);">
                {{ ucfirst($hoyAr->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }} · Turno de {{ auth()->user()->name }}
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('web.caja.index') }}" class="ds-btn ds-btn--sec">Caja</a>
            <a href="{{ route('web.cobranza.index') }}" class="ds-btn ds-btn--sec">Cobranza</a>
            <a href="{{ route('web.alumnos.index') }}" class="ds-btn ds-btn--sec">Alumnos</a>
        </div>
    </div>

    {{-- 1. Alerta de Cajas Rechazadas (si existen) --}}
    @if($cajasRechazadasCount > 0)
    <div class="filtros-card mb-4" style="border-left:4px solid var(--color-danger); padding:1rem 1.25rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div>
                <p style="font-size:0.95rem; font-weight:700; color:var(--color-danger); margin-bottom:2px;">
                    Tenés {{ $cajasRechazadasCount }} caja{{ $cajasRechazadasCount > 1 ? 's' : '' }} rechazada{{ $cajasRechazadasCount > 1 ? 's' : '' }} pendiente{{ $cajasRechazadasCount > 1 ? 's' : '' }} de corrección
                </p>
                <p style="font-size:0.82rem; color:var(--color-text-muted);">
                    El administrador rechazó el cierre. Revisá las observaciones y corregí el arqueo.
                </p>
            </div>
            <a href="{{ route('web.caja.index') }}" class="ds-btn ds-btn--primary">Ver</a>
        </div>
    </div>
    @endif

    {{-- 2. Alerta de Bloqueo por turno pendiente --}}
    @if($bloqueo['activo'])
    <div class="filtros-card mb-4" style="border-left:4px solid var(--color-warning); padding:1rem 1.25rem;">
        <p style="font-size:0.95rem; font-weight:700; color:var(--color-warning); margin-bottom:2px;">
            {{ $bloqueo['titulo'] }}
        </p>
        <p style="font-size:0.82rem; color:var(--color-text-muted); margin-bottom:8px;">
            {{ $bloqueo['mensaje'] }}
        </p>
        <a href="{{ $bloqueo['url'] }}" class="ds-btn ds-btn--primary">Ver</a>
    </div>
    @endif

    {{-- 3. ESTADO DEL CAJÓN --}}
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

    {{-- VARIANTE C: 2 COLUMNAS DONDE ALUMNOS TIENE TARJETA COMPLETA CON ACCIONES DE MOSTRADOR --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" style="align-items:stretch;">

        {{-- Clases de hoy --}}
        <div style="display:flex; flex-direction:column;">
            <div class="stats-bar mb-2">
                <div class="stats-info">
                    Clases de hoy — <strong>{{ $clasesHoy->count() }}</strong>
                </div>
            </div>
            @if($clasesHoy->isEmpty())
            <div class="filtros-card" style="text-align:center; padding:1.25rem; flex:1;">
                <p style="font-size:0.82rem; color:var(--color-text-muted);">No hay clases programadas para hoy.</p>
            </div>
            @else
            <div style="display:flex; flex-direction:column; gap:6px; flex:1;">
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

        {{-- Atención a Alumnos con Panel Unificado que cubre la altura --}}
        <div style="display:flex; flex-direction:column;">
            <div class="stats-bar mb-2">
                <div class="stats-info">Atención a alumnos</div>
            </div>
            <div class="filtros-card" style="padding:1.25rem; flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:16px;">
                {{-- Bloque superior: métricas --}}
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <a href="{{ route('web.cobranza.index') }}" style="text-align:center; padding:0.85rem 0.5rem; background:rgba(0,0,0,0.02); border-radius:var(--radius-card); border:1px solid var(--color-border); text-decoration:none;">
                        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:2px;">Con deuda</p>
                        <p style="font-size:1.6rem; font-weight:800; color:{{ $alumnosConDeuda > 0 ? 'var(--color-danger)' : 'var(--color-success)' }};">
                            {{ $alumnosConDeuda }}
                        </p>
                        <p style="font-size:0.72rem; color:var(--color-brand); font-weight:600; margin-top:2px;">Cobranza →</p>
                    </a>
                    <a href="{{ route('web.revision-cobranza.index') }}" style="text-align:center; padding:0.85rem 0.5rem; background:rgba(0,0,0,0.02); border-radius:var(--radius-card); border:1px solid var(--color-border); text-decoration:none;">
                        <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:2px;">Posibles inactivos</p>
                        <p style="font-size:1.6rem; font-weight:800; color:{{ $posiblesInactivos > 0 ? 'var(--color-warning)' : 'var(--color-text)' }};">
                            {{ $posiblesInactivos }}
                        </p>
                        <p style="font-size:0.72rem; color:var(--color-brand); font-weight:600; margin-top:2px;">Revisión →</p>
                    </a>
                </div>

                {{-- Bloque inferior: accesos rápidos de mostrador para nivelar altura --}}
                <div style="border-top:1px solid var(--color-border); padding-top:12px;">
                    <p style="font-size:0.75rem; font-weight:700; color:var(--color-text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.05em;">
                        Accesos rápidos
                    </p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('web.alumnos.index') }}" class="ds-btn ds-btn--sec" style="font-size:0.8rem; padding:4px 12px; height:32px;">Buscar alumno</a>
                        <a href="{{ route('web.alumnos.create') }}" class="ds-btn ds-btn--sec" style="font-size:0.8rem; padding:4px 12px; height:32px;">Nuevo alumno</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RESUMEN FINANCIERO DEL DÍA (Stats de soporte al pie) --}}
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
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted); margin-bottom:2px;">Cajas del turno</p>
            <p style="font-size:1.5rem; font-weight:800; color:var(--color-brand);">{{ $cajas->count() }}</p>
        </div>
    </div>

</div>
@endsection
