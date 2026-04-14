@extends('layouts.app')

@section('title', 'Dashboard – Wings')
@section('module-title', 'Dashboard')

@section('content')

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

        <div class="filtros-card" style="text-align: center;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted); margin-bottom: 6px;">Alumnos activos</p>
            <p style="font-size: 2rem; font-weight: 800; color: var(--color-btn-primary); line-height: 1;">{{ $alumnosActivos }}</p>
        </div>

        <div class="filtros-card" style="text-align: center;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted); margin-bottom: 6px;">Con deuda</p>
            <p style="font-size: 2rem; font-weight: 800; color: var(--color-danger); line-height: 1;">{{ $alumnosConDeuda }}</p>
        </div>

        <div class="filtros-card" style="text-align: center;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted); margin-bottom: 6px;">Deuda total</p>
            <p style="font-size: 1.6rem; font-weight: 800; color: var(--color-danger); line-height: 1;">
                ${{ number_format($totalDeudaPendiente, 0, ',', '.') }}
            </p>
        </div>

        <div class="filtros-card" style="text-align: center;">
            <p style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--color-text-muted); margin-bottom: 6px;">Altas este mes</p>
            <p style="font-size: 2rem; font-weight: 800; color: var(--color-success); line-height: 1;">{{ $alumnosNuevosMes }}</p>
        </div>

    </div>

    {{-- Accesos rápidos --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <a href="{{ route('web.alumnos.index') }}" class="filtros-card" style="
            text-decoration: none; display: block;
            transition: box-shadow 0.15s, transform 0.15s;
        " onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'"
           onmouseleave="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: color-mix(in srgb, var(--color-btn-primary) 12%, transparent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg class="w-5 h-5" style="color: var(--color-btn-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-wings">Alumnos</p>
                    <p style="font-size: 0.72rem; color: var(--color-text-muted);">{{ $alumnosActivos }} activos · {{ $alumnosInactivos }} inactivos</p>
                </div>
            </div>
        </a>

        <a href="{{ route('web.grupos.index') }}" class="filtros-card" style="
            text-decoration: none; display: block;
            transition: box-shadow 0.15s, transform 0.15s;
        " onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'"
           onmouseleave="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: color-mix(in srgb, var(--color-success) 12%, transparent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg class="w-5 h-5" style="color: var(--color-success);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-wings">Grupos</p>
                    <p style="font-size: 0.72rem; color: var(--color-text-muted);">Gestión de grupos y planes</p>
                </div>
            </div>
        </a>

        <a href="{{ route('web.rubros.index') }}" class="filtros-card" style="
            text-decoration: none; display: block;
            transition: box-shadow 0.15s, transform 0.15s;
        " onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'"
           onmouseleave="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: color-mix(in srgb, var(--color-warning) 12%, transparent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg class="w-5 h-5" style="color: var(--color-warning);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-wings">Rubros</p>
                    <p style="font-size: 0.72rem; color: var(--color-text-muted);">Rubros y subrubros</p>
                </div>
            </div>
        </a>

    </div>

@endsection
