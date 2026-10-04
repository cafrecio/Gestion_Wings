@extends('layouts.app')
@section('title', 'Configuración – Wings')
@section('module-title', 'Configuración')
@php($title = 'Configuración')

@section('content')
@php($claveError = session('configuracion_error_clave'))
<div id="configuracion-error-resumen" class="ds-flash ds-flash--error" role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif>
    <strong>No se guardó.</strong>
    {{-- Un enlace mantiene el aviso fuera del cierre automático de ds-app.js. --}}
    <a href="#cfg-inscripcion_importe">Revisar</a>
    <ul id="configuracion-error-lista">
        @if($errors->any())
            @foreach($errors->all() as $error)
                <li><a href="#cfg-{{ $claveError }}">{{ $campos[$claveError]['titulo'] ?? 'Configuración' }}: {{ $error }}</a></li>
            @endforeach
        @endif
    </ul>
</div>

<section aria-labelledby="configuracion-plata">
    <div class="stats-bar mb-3"><h2 id="configuracion-plata" class="alumno-nombre">La plata</h2></div>
    @include('configuraciones._campo', ['clave' => 'inscripcion_importe'])
</section>

<section aria-labelledby="configuracion-cobranza">
    <div class="stats-bar mb-3"><h2 id="configuracion-cobranza" class="alumno-nombre">La cobranza</h2></div>
    @include('configuraciones._campo', ['clave' => 'dias_gracia_cobranza'])
    <article class="alumno-card">
        <div class="alumno-card-header"><h3 class="alumno-nombre">Generación de cuotas mensuales</h3></div>
        <p class="text-wings-muted mb-3">El sistema genera las cuotas automáticamente el día 1 de cada mes, a las 06:00.</p>
        <div class="alumno-info">
            <div class="info-item"><span class="info-label">Día:</span><strong class="info-value">1 · Fijo</strong></div>
            <div class="info-item"><span class="info-label">Horario:</span><strong class="info-value">06:00 · Hora Argentina</strong></div>
        </div>
        <p class="text-wings-muted">No se cambia desde esta pantalla.</p>
    </article>
    <article class="alumno-card">
        <div class="alumno-card-header"><h3 class="alumno-nombre">Importe de la primera cuota</h3></div>
        <p class="text-wings-muted">Al dar de alta un alumno, su fecha real de ingreso determina qué porcentaje del plan se cobra ese primer mes. No modifica cuotas ya creadas.</p>
    </article>
<div class="stats-bar mb-3">
    <div class="stats-info">
        <span id="reglas-count"><strong>{{ $reglasPrimerPago->count() }}</strong> {{ $reglasPrimerPago->count() === 1 ? 'regla configurada' : 'reglas configuradas' }}</span>
    </div>
    <x-ds.button variant="primary" id="btn-agregar-regla">Nuevo</x-ds.button>
</div>

<div id="reglas-list">
@foreach($reglasPrimerPago as $regla)
    @include('configuraciones._regla', ['id' => $regla->id, 'nombre' => $regla->nombre,
        'desde' => $regla->dia_desde, 'hasta' => $regla->dia_hasta, 'porcentaje' => $regla->porcentaje])
@endforeach
</div>{{-- /#reglas-list --}}

{{-- Panel agregar nueva regla --}}
<div id="panel-add-regla" style="display:none; margin-bottom:12px;">
    <div class="alumno-card">
        <div class="alumno-card-header">
            <span class="alumno-dot alumno-dot--neutral"></span>
            <h3 class="alumno-nombre" style="font-size:0.85rem;">Nueva regla</h3>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;
                    max-width:480px; padding:0 0 12px 1.5rem;">
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Nombre</label>
                <input type="text" id="add-nombre" maxlength="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="Ej: Primera quincena">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Porcentaje (%)</label>
                <input type="number" id="add-porcentaje" min="1" max="100"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="100">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día desde</label>
                <input type="number" id="add-dia-desde" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="1">
            </div>
            <div>
                <label style="font-size:0.75rem; font-weight:600; display:block; margin-bottom:4px; color:var(--color-text-muted);">Día hasta</label>
                <input type="number" id="add-dia-hasta" min="1" max="31"
                       class="wings-input" style="width:100%; padding:6px 10px; font-size:0.85rem;"
                       placeholder="31">
            </div>
        </div>
        <div id="add-error"
             style="display:none; color:var(--color-danger); font-size:0.75rem; padding:0 0 8px 1.5rem;"></div>
        <div class="alumno-actions">
            <x-ds.button variant="primary"   id="btn-guardar-add">Guardar</x-ds.button>
            <x-ds.button variant="secondary" id="btn-cancelar-add">Cancelar</x-ds.button>
        </div>
    </div>
</div>

<template id="regla-template">
    @include('configuraciones._regla', ['id' => '__ID__', 'nombre' => '', 'desde' => 1, 'hasta' => 31, 'porcentaje' => 100])
</template>

</section>
<section aria-labelledby="configuracion-avisos">
    <div class="stats-bar mb-3"><h2 id="configuracion-avisos" class="alumno-nombre">Los avisos</h2></div>
    @include('configuraciones._campo', ['clave' => 'avisos_email'])
    @include('configuraciones._campo', ['clave' => 'avisos_telegram_chat_id'])
</section>
@endsection

@push('scripts')
    @vite('resources/js/configuraciones.js')
@endpush
