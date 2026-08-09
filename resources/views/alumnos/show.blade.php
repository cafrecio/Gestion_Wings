@extends('layouts.app')

@section('title', $alumno->apellido . ', ' . $alumno->nombre . ' – Wings')
@section('module-title', $alumno->apellido . ', ' . $alumno->nombre)

@section('content')

@php
    $dep = mb_strtolower($alumno->deporte->nombre ?? '');
    $dep = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $sport = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
    $sportColor = "var(--color-sport-{$sport})";
@endphp

{{-- Barra de acciones --}}
<div class="filtros-actions mb-4" style="justify-content: flex-end;">
    <span style="
        font-size: 0.7rem; font-weight: 600;
        padding: 0.2rem 0.65rem; border-radius: 999px;
        background: {{ $alumno->activo ? 'color-mix(in srgb, var(--color-success) 15%, transparent)' : 'color-mix(in srgb, var(--color-danger) 15%, transparent)' }};
        color: {{ $alumno->activo ? 'var(--color-success)' : 'var(--color-danger)' }};
    ">{{ $alumno->activo ? 'Activo' : 'Inactivo' }}</span>
    @if(!$alumno->activo)
    <form method="POST" action="{{ route('web.alumnos.toggle-activo', $alumno->id) }}" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit" class="ds-btn ds-btn--primary"
                onclick="return confirm('¿Reactivar a {{ $alumno->nombre }} {{ $alumno->apellido }}?')">
            Reactivar
        </button>
    </form>
    @endif
    <x-ds.button variant="secondary" href="{{ route('web.alumnos.edit', $alumno->id) }}">Editar</x-ds.button>
</div>

{{-- Layout 2 columnas --}}
<div class="grid grid-cols-1 md:grid-cols-5 gap-4">

    {{-- Columna izquierda: información --}}
    <div class="md:col-span-3 filtros-card">

        {{-- Datos personales --}}
        <div class="grid grid-cols-2 gap-x-6 gap-y-4 mb-4">

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                    DNI
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->dni ?: '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Nacimiento
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->format('d/m/Y') : '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Celular
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->celular ?: '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->email ?: '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Deporte
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->deporte->nombre ?? '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Grupo
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->grupo->nombre_completo ?? '–' }}</p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Plan
                </p>
                <p class="text-sm font-medium text-wings">
                    @if($alumno->planActivo && $alumno->planActivo->plan)
                        {{ $alumno->planActivo->plan->clases_por_semana }}x sem. — ${{ number_format($alumno->planActivo->plan->precio_mensual, 0, ',', '.') }}/mes
                    @else
                        <span class="text-wings-muted">Sin plan asignado</span>
                    @endif
                </p>
            </div>

            <div>
                <p class="flex items-center gap-1.5 mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                    <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Alta
                </p>
                <p class="text-sm font-medium text-wings">{{ $alumno->fecha_alta ? $alumno->fecha_alta->format('d/m/Y') : '–' }}</p>
            </div>

        </div>

        {{-- Estado de cobranza --}}
        @php
            $ecEstado = $estadoCobranza['estado'];
            $ecColor  = match($ecEstado) {
                'AL_DIA' => 'var(--color-success)',
                'EN_PLAZO' => 'var(--color-info)',
                'MOROSO' => 'var(--color-warning)',
                'DEUDOR' => 'var(--color-danger)',
                default  => 'var(--color-text-muted)',
            };
            $ecLabel  = match($ecEstado) {
                'AL_DIA' => 'Al día',
                'EN_PLAZO' => 'En plazo',
                'MOROSO' => 'Moroso',
                'DEUDOR' => 'Deudor',
                default  => $ecEstado,
            };
            $ecDeudas = $estadoCobranza['deudas_pendientes'];
            $ecGracia = $estadoCobranza['dias_gracia_restantes'];
            $meses    = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        @endphp
        <div class="pt-3 mb-4" style="border-top: 1px solid var(--color-border);">
            <p class="flex items-center gap-1.5 mb-2" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Estado de cobranza
            </p>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="font-size:0.72rem; font-weight:700; padding:3px 10px; border-radius:999px;
                             background:color-mix(in srgb, {{ $ecColor }} 15%, transparent);
                             color:{{ $ecColor }};">{{ $ecLabel }}</span>
                @if($ecGracia > 0)
                    <span style="font-size:0.72rem; color:var(--color-text-muted);">{{ $ecGracia }} día{{ $ecGracia !== 1 ? 's' : '' }} de gracia</span>
                @endif
            </div>
            @if($ecDeudas->isNotEmpty())
            <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px;">
                @foreach($ecDeudas as $deuda)
                @php
                    [$yr, $mo] = explode('-', $deuda->periodo);
                    $periodoLabel = ($meses[(int)$mo] ?? $mo) . ' ' . $yr;
                    $saldo = (float)$deuda->saldo_pendiente;
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center;
                            padding:4px 8px; border-radius:6px;
                            background:color-mix(in srgb, {{ $ecColor }} 8%, transparent);">
                    <span style="font-size:0.75rem; color:var(--color-text-muted);">{{ $periodoLabel }}</span>
                    <span style="font-size:0.75rem; font-weight:700; color:{{ $ecColor }};">
                        ${{ number_format($saldo, 0, ',', '.') }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Tutor --}}
        @if($alumno->nombre_tutor)
        <div class="pt-3" style="border-top: 1px solid var(--color-border);">
            <p class="flex items-center gap-1.5 mb-3" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Tutor
            </p>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <p class="text-wings-muted mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;">Nombre</p>
                    <p class="text-sm font-medium text-wings">{{ $alumno->nombre_tutor }}</p>
                </div>
                @if($alumno->telefono_tutor)
                <div>
                    <p class="text-wings-muted mb-0.5" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;">Teléfono</p>
                    <p class="text-sm font-medium text-wings">{{ $alumno->telefono_tutor }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- Columna derecha: widgets --}}
    <div class="md:col-span-2 flex flex-col gap-4">

        {{-- Pagos --}}
        <div class="filtros-card flex-1">
            <p class="flex items-center gap-1.5 mb-3" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historial de pagos
            </p>
            @if($ultimosPagos->isEmpty())
                <p class="text-xs" style="color:var(--color-text-muted); opacity:.6;">Sin pagos registrados.</p>
            @else
                <div style="display:flex; flex-direction:column; gap:5px;">
                @foreach($ultimosPagos as $pago)
                @php
                    $periodos = $pago->deudasCuota->pluck('periodo')
                        ->map(fn($p) => ($meses[(int) explode('-', $p)[1]] ?? explode('-', $p)[1]) . ' ' . explode('-', $p)[0])
                        ->join(', ');
                    if (!$periodos && $pago->mes && $pago->anio) {
                        $periodos = ($meses[$pago->mes] ?? $pago->mes) . ' ' . $pago->anio;
                    }
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;
                            padding:5px 8px; border-radius:6px;
                            background:color-mix(in srgb, var(--color-border) 40%, transparent);">
                    <div>
                        <span style="font-size:0.72rem; color:var(--color-text-muted);">{{ $pago->fecha_pago?->format('d/m/Y') ?? '–' }}</span>
                        @if($periodos)
                        <span style="font-size:0.65rem; color:var(--color-text-muted); opacity:.7;"> · {{ $periodos }}</span>
                        @endif
                    </div>
                    <span style="font-size:0.75rem; font-weight:700; color:var(--color-success); white-space:nowrap;">
                        ${{ number_format($pago->monto_final, 0, ',', '.') }}
                    </span>
                </div>
                @endforeach
                </div>
            @endif
        </div>

        {{-- Asistencias --}}
        <div class="filtros-card flex-1">
            @php
                $mesActual = \Carbon\Carbon::now('America/Argentina/Buenos_Aires');
                $mesLabel  = ($meses[$mesActual->month] ?? '') . ' ' . $mesActual->year;
            @endphp
            <p class="flex items-center gap-1.5 mb-3" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted);">
                <svg class="w-3 h-3 flex-shrink-0" style="color: {{ $sportColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Asistencias — {{ $mesLabel }}
            </p>
            @if($asistenciasMes->isEmpty())
                <p class="text-xs" style="color:var(--color-text-muted); opacity:.6;">Sin asistencias en {{ $mesLabel }}.</p>
            @else
                <p style="font-size:1rem; font-weight:700; color:var(--color-success); margin-bottom:8px;">
                    {{ $asistenciasMes->count() }} clase{{ $asistenciasMes->count() !== 1 ? 's' : '' }}
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                @foreach($asistenciasMes as $as)
                    <span style="font-size:0.72rem; padding:2px 8px; border-radius:999px;
                                 background:color-mix(in srgb, var(--color-success) 12%, transparent);
                                 color:var(--color-success);">
                        {{ $as->clase?->fecha?->format('d/m') ?? '–' }}
                    </span>
                @endforeach
                </div>
            @endif
        </div>

    </div>

</div>

{{-- Volver --}}
<div class="filtros-actions mt-4" style="justify-content: flex-end;">
    <x-ds.button variant="secondary" href="{{ route('web.alumnos.index') }}">Volver</x-ds.button>
</div>

@endsection
