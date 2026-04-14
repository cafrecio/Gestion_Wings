@extends('layouts.app')

@section('title', 'Rubros – Wings')
@section('module-title', 'Rubros')

@section('content')

@php
    $ingresos = $rubros->where('tipo', 'INGRESO')->values();
    $egresos  = $rubros->where('tipo', 'EGRESO')->values();

    // ── Objeto B: botones de pie de card — ancho FIJO igual para todos ──────
    $btnB = 'display:inline-flex; align-items:center; justify-content:center;'
          . ' width:96px; height:32px; font-size:0.82rem; font-weight:600;'
          . ' border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;'
          . ' text-decoration:none; border:none; font-family:inherit;';
    $btnBSec  = $btnB . ' background:var(--color-btn-secondary); color:var(--color-surface);';
    $btnBDang = $btnB . ' background:var(--color-btn-danger);    color:var(--color-surface);';
    $btnBPrim = $btnB . ' background:var(--color-btn-primary);   color:var(--color-surface);';

    // ── Objeto C: botones de fila de tabla (Editar / Eliminar del subrubro) ─
    $btnC = 'display:inline-block; width:64px; height:26px; line-height:26px;'
          . ' font-size:0.72rem; font-weight:600; text-align:center;'
          . ' border-radius:var(--radius-btn); cursor:pointer; white-space:nowrap;'
          . ' background:none; border:1px solid; padding:0; font-family:inherit;';
    $btnCSec  = $btnC . ' color:var(--color-btn-secondary); border-color:var(--color-btn-secondary);';
    $btnCDang = $btnC . ' color:var(--color-btn-danger);    border-color:var(--color-btn-danger);';
@endphp


{{-- ══════════════════════════════════════════
     INGRESOS
     ══════════════════════════════════════════ --}}

<div class="stats-bar mb-3">
    <div class="stats-info">
        <span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; padding:0.2rem 0.65rem; border-radius:999px; background:color-mix(in srgb, var(--color-success) 15%, transparent); color:var(--color-success);">Ingresos</span>
        &nbsp;{{ $ingresos->count() }} {{ $ingresos->count() === 1 ? 'rubro' : 'rubros' }}
    </div>
    <a href="{{ route('web.rubros.create') }}" style="{{ $btnBPrim }}">Nuevo</a>
</div>

@forelse($ingresos as $rubro)
    <div class="alumno-card" style="border-left-color:var(--color-success);">

        {{-- Header: SOLO dot + nombre ─────────────────────────────────────── --}}
        <div class="alumno-card-header">
            <span class="alumno-dot" style="background:var(--color-success);"></span>
            <span class="alumno-nombre">{{ $rubro->nombre }}</span>
        </div>

        {{-- Observación (fuera del header) ────────────────────────────────── --}}
        @if($rubro->observacion)
            <p style="font-size:0.75rem; color:var(--color-text-muted); margin:0 0 0.75rem 1.5rem;">{{ $rubro->observacion }}</p>
        @endif

        {{-- Tabla de subrubros ─────────────────────────────────────────────── --}}
        @if($rubro->subrubros->isNotEmpty())
            <table style="width:100%; table-layout:fixed; border-collapse:collapse; font-size:0.8rem; margin-bottom:0.5rem;">
                <colgroup>
                    <col>                        {{-- Nombre: toma el espacio restante --}}
                    <col style="width:140px;">   {{-- Permitido para --}}
                    <col style="width:70px;">    {{-- Caja --}}
                    <col style="width:148px;">   {{-- Acciones: 2×64px + gap --}}
                </colgroup>
                <thead>
                    <tr style="border-bottom:1px solid var(--color-border);">
                        <th style="text-align:left; padding:0.3rem 0.5rem 0.3rem 0; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Nombre</th>
                        <th style="text-align:left; padding:0.3rem 0.5rem; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Permitido para</th>
                        <th style="text-align:center; padding:0.3rem 0.5rem; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Caja</th>
                        <th style="padding:0.3rem 0;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rubro->subrubros as $subrubro)
                        <tr style="border-bottom:1px solid var(--color-border);">
                            <td style="padding:0.45rem 0.5rem 0.45rem 0; color:var(--color-text); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                {{ $subrubro->nombre }}
                                @if($subrubro->es_reservado_sistema)
                                    <span style="font-size:0.65rem; color:var(--color-text-muted); font-weight:400; margin-left:0.3rem;">(sistema)</span>
                                @endif
                            </td>
                            <td style="padding:0.45rem 0.5rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $subrubro->permitido_para ?: '–' }}</td>
                            <td style="padding:0.45rem 0.5rem; text-align:center; color:var(--color-text-muted);">
                                @if($subrubro->afecta_caja)<span style="color:var(--color-success); font-weight:600;">✓</span>@else –@endif
                            </td>
                            <td style="padding:0.45rem 0; text-align:right;">
                                @unless($subrubro->es_reservado_sistema)
                                    <div style="display:inline-flex; gap:0.4rem;">
                                        <a href="{{ route('web.subrubros.edit', [$rubro->id, $subrubro->id]) }}" style="{{ $btnCSec }}">Editar</a>
                                        <form method="POST" action="{{ route('web.subrubros.destroy', [$rubro->id, $subrubro->id]) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar {{ addslashes($subrubro->nombre) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="{{ $btnCDang }}">Eliminar</button>
                                        </form>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Acciones del card ──────────────────────────────────────────────── --}}
        <div class="alumno-actions" style="border-top:1px solid var(--color-border); padding-top:0.6rem; margin-top:0.25rem;">
            <a href="{{ route('web.rubros.edit', $rubro->id) }}" style="{{ $btnBSec }}">Editar</a>
            <form method="POST" action="{{ route('web.rubros.destroy', $rubro->id) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar {{ addslashes($rubro->nombre) }}?')">
                @csrf @method('DELETE')
                <button type="submit" style="{{ $btnBDang }}">Eliminar</button>
            </form>
            <a href="{{ route('web.subrubros.create', $rubro->id) }}" style="{{ $btnBPrim }} margin-left:auto;">+ Subrubro</a>
        </div>

    </div>
@empty
    <p style="font-size:0.82rem; color:var(--color-text-muted); padding:0.5rem 0 1.5rem;">Sin rubros de ingreso.</p>
@endforelse


{{-- ══════════════════════════════════════════
     EGRESOS
     ══════════════════════════════════════════ --}}

<div class="stats-bar mb-3 mt-4">
    <div class="stats-info">
        <span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; padding:0.2rem 0.65rem; border-radius:999px; background:color-mix(in srgb, var(--color-danger) 15%, transparent); color:var(--color-danger);">Egresos</span>
        &nbsp;{{ $egresos->count() }} {{ $egresos->count() === 1 ? 'rubro' : 'rubros' }}
    </div>
    <a href="{{ route('web.rubros.create') }}" style="{{ $btnBPrim }}">Nuevo</a>
</div>

@forelse($egresos as $rubro)
    <div class="alumno-card" style="border-left-color:var(--color-danger);">

        {{-- Header: SOLO dot + nombre ─────────────────────────────────────── --}}
        <div class="alumno-card-header">
            <span class="alumno-dot" style="background:var(--color-danger);"></span>
            <span class="alumno-nombre">{{ $rubro->nombre }}</span>
        </div>

        {{-- Observación (fuera del header) ────────────────────────────────── --}}
        @if($rubro->observacion)
            <p style="font-size:0.75rem; color:var(--color-text-muted); margin:0 0 0.75rem 1.5rem;">{{ $rubro->observacion }}</p>
        @endif

        {{-- Tabla de subrubros ─────────────────────────────────────────────── --}}
        @if($rubro->subrubros->isNotEmpty())
            <table style="width:100%; table-layout:fixed; border-collapse:collapse; font-size:0.8rem; margin-bottom:0.5rem;">
                <colgroup>
                    <col>
                    <col style="width:140px;">
                    <col style="width:70px;">
                    <col style="width:148px;">
                </colgroup>
                <thead>
                    <tr style="border-bottom:1px solid var(--color-border);">
                        <th style="text-align:left; padding:0.3rem 0.5rem 0.3rem 0; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Nombre</th>
                        <th style="text-align:left; padding:0.3rem 0.5rem; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Permitido para</th>
                        <th style="text-align:center; padding:0.3rem 0.5rem; font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-text-muted);">Caja</th>
                        <th style="padding:0.3rem 0;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rubro->subrubros as $subrubro)
                        <tr style="border-bottom:1px solid var(--color-border);">
                            <td style="padding:0.45rem 0.5rem 0.45rem 0; color:var(--color-text); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                {{ $subrubro->nombre }}
                                @if($subrubro->es_reservado_sistema)
                                    <span style="font-size:0.65rem; color:var(--color-text-muted); font-weight:400; margin-left:0.3rem;">(sistema)</span>
                                @endif
                            </td>
                            <td style="padding:0.45rem 0.5rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $subrubro->permitido_para ?: '–' }}</td>
                            <td style="padding:0.45rem 0.5rem; text-align:center; color:var(--color-text-muted);">
                                @if($subrubro->afecta_caja)<span style="color:var(--color-success); font-weight:600;">✓</span>@else –@endif
                            </td>
                            <td style="padding:0.45rem 0; text-align:right;">
                                @unless($subrubro->es_reservado_sistema)
                                    <div style="display:inline-flex; gap:0.4rem;">
                                        <a href="{{ route('web.subrubros.edit', [$rubro->id, $subrubro->id]) }}" style="{{ $btnCSec }}">Editar</a>
                                        <form method="POST" action="{{ route('web.subrubros.destroy', [$rubro->id, $subrubro->id]) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar {{ addslashes($subrubro->nombre) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="{{ $btnCDang }}">Eliminar</button>
                                        </form>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Acciones del card ──────────────────────────────────────────────── --}}
        <div class="alumno-actions" style="border-top:1px solid var(--color-border); padding-top:0.6rem; margin-top:0.25rem;">
            <a href="{{ route('web.rubros.edit', $rubro->id) }}" style="{{ $btnBSec }}">Editar</a>
            <form method="POST" action="{{ route('web.rubros.destroy', $rubro->id) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar {{ addslashes($rubro->nombre) }}?')">
                @csrf @method('DELETE')
                <button type="submit" style="{{ $btnBDang }}">Eliminar</button>
            </form>
            <a href="{{ route('web.subrubros.create', $rubro->id) }}" style="{{ $btnBPrim }} margin-left:auto;">+ Subrubro</a>
        </div>

    </div>
@empty
    <p style="font-size:0.82rem; color:var(--color-text-muted); padding:0.5rem 0;">Sin rubros de egreso.</p>
@endforelse

@endsection
