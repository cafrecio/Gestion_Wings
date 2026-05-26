@extends('layouts.app')

@section('title', 'Liquidaciones – Wings')
@section('module-title', 'Liquidaciones')

@section('content')

@php
    $btnB     = 'display:inline-flex; align-items:center; justify-content:center;'
              . ' height:32px; font-size:0.82rem; font-weight:600;'
              . ' border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;'
              . ' text-decoration:none; border:none; font-family:inherit;';
    $btnBPrim = $btnB . ' width:96px; background:var(--color-btn-primary); color:var(--color-surface);';
    $btnBSec  = $btnB . ' width:96px; background:var(--color-btn-secondary); color:var(--color-surface);';
    $btnBDang = $btnB . ' width:96px; background:var(--color-btn-danger); color:var(--color-surface);';

    function dotLiquidacion(string $estado, string $estadoPago): string {
        if ($estadoPago === 'PAGADA') {
            return 'background:var(--color-success)';
        }
        return match($estado) {
            'ABIERTA' => 'background:var(--color-warning)',
            'CERRADA' => 'background:var(--color-text-muted)',
            default   => 'background:var(--color-text-muted)',
        };
    }
@endphp

{{-- Stats bar --}}
<div class="stats-bar mb-3">
    <div class="stats-info">
        <strong>{{ $liquidaciones->total() }}</strong>
        {{ $liquidaciones->total() === 1 ? 'liquidación' : 'liquidaciones' }}
    </div>
    <a href="{{ route('web.liquidaciones.create') }}" style="{{ $btnBPrim }}">Nuevo</a>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('web.liquidaciones.index') }}" class="filtros-card mb-4">
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">

        {{-- Profesor --}}
        <div style="flex:1; min-width:180px;">
            <label style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Profesor</label>
            <select name="profesor_id" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                @foreach($profesores as $p)
                    <option value="{{ $p->id }}" {{ request('profesor_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->apellido }}, {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Mes --}}
        <div style="flex:0 0 130px;">
            <label style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Mes</label>
            <select name="mes" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                @foreach($meses as $num => $nombre)
                    <option value="{{ $num }}" {{ request('mes') == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

        {{-- Año --}}
        <div style="flex:0 0 100px;">
            <label style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Año</label>
            <select name="anio" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                @foreach($aniosDisponibles as $anio)
                    <option value="{{ $anio }}" {{ request('anio') == $anio ? 'selected' : '' }}>{{ $anio }}</option>
                @endforeach
            </select>
        </div>

        {{-- Estado --}}
        <div style="flex:0 0 130px;">
            <label style="display:block; font-size:0.72rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px;">Estado</label>
            <select name="estado" class="w-full px-3 py-2 text-sm wings-input">
                <option value="">Todos</option>
                <option value="abierta" {{ request('estado') === 'abierta' ? 'selected' : '' }}>Abierta</option>
                <option value="cerrada" {{ request('estado') === 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                <option value="pagada"  {{ request('estado') === 'pagada'  ? 'selected' : '' }}>Pagada</option>
            </select>
        </div>

        <div style="display:flex; gap:8px; align-items:flex-end;">
            <button type="submit" style="{{ $btnBPrim }}">Filtrar</button>
            @if(request()->hasAny(['profesor_id','mes','anio','estado']))
                <a href="{{ route('web.liquidaciones.index') }}" style="{{ $btnBSec }}">Limpiar</a>
            @endif
        </div>

    </div>
</form>

{{-- Listado --}}
@forelse($liquidaciones as $liq)

@php
    $dep  = mb_strtolower($liq->profesor->deporte->nombre ?? '');
    $dep  = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $rail = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');

    $esPagada  = $liq->estaPagada();
    $esCerrada = $liq->estaCerrada();
    $dotStyle  = dotLiquidacion($liq->estado, $liq->estado_pago);

    $periodoParts = $meses[$liq->mes] . ' ' . $liq->anio;
    $tipoLabel    = $liq->tipo === 'HORA' ? 'Por hora' : 'Por comisión';
@endphp

<div class="alumno-card alumno-card--{{ $rail }}">

    <div class="alumno-card-header">
        <span class="alumno-dot" style="{{ $dotStyle }};"></span>
        <h3 class="alumno-nombre">{{ $liq->profesor->apellido }}, {{ $liq->profesor->nombre }} — {{ $periodoParts }}</h3>
        <span style="margin-left:auto; font-size:0.65rem; font-weight:700; padding:2px 8px;
                     border-radius:999px; white-space:nowrap;
                     background:color-mix(in srgb, var(--color-sport-{{ $rail }}) 15%, transparent);
                     color:var(--color-sport-{{ $rail }});">
            {{ $tipoLabel }}
        </span>
    </div>

    <div class="alumno-info">
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="info-label">Total:</span>
            <span class="info-value" style="font-weight:700;">
                ${{ number_format((float)$liq->total_calculado, 0, ',', '.') }}
            </span>
        </div>
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="info-label">Estado:</span>
            @if($esPagada)
                <span style="font-size:0.65rem; font-weight:700; padding:1px 7px; border-radius:999px;
                             background:color-mix(in srgb, var(--color-success) 15%, transparent);
                             color:var(--color-success);">Pagada</span>
            @elseif($esCerrada)
                <span style="font-size:0.65rem; font-weight:700; padding:1px 7px; border-radius:999px;
                             background:color-mix(in srgb, var(--color-text-muted) 15%, transparent);
                             color:var(--color-text-muted);">Cerrada</span>
            @else
                <span style="font-size:0.65rem; font-weight:700; padding:1px 7px; border-radius:999px;
                             background:color-mix(in srgb, var(--color-warning) 15%, transparent);
                             color:var(--color-warning);">Abierta</span>
            @endif
        </div>
    </div>

    <div class="alumno-actions">
        <a href="{{ route('web.liquidaciones.show', $liq->id) }}" style="{{ $btnBSec }}">Ver</a>
        @if($liq->estaAbierta())
            <form method="POST" action="{{ route('web.liquidaciones.eliminar', $liq->id) }}"
                  onsubmit="return confirm('¿Eliminar esta liquidación?')">
                @csrf @method('DELETE')
                <button type="submit" style="{{ $btnBDang }}">Eliminar</button>
            </form>
        @endif
    </div>

</div>

@empty
    <div class="empty-state">
        <svg class="empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        <h3>Sin liquidaciones</h3>
        <p>Generá la primera liquidación mensual para un profesor.</p>
    </div>
@endforelse

{{-- Paginación --}}
@if($liquidaciones->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $liquidaciones->links() }}
    </div>
@endif

@endsection
