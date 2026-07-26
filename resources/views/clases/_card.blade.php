{{-- Card de clase reutilizable. Props: $clase, $ahora, $esAdmin, $diasSemana,
     $modo ('hoy' = título HH:mm–HH:mm con id para auto-scroll | 'listado' = título con fecha completa). --}}
@php
    $estado = claseEstado($clase, $ahora);
    $dot    = estadoDot($estado);
    $label  = estadoLabel($estado);
    $color  = estadoColor($estado);
    $dep    = mb_strtolower($clase->grupo->deporte->nombre ?? '');
    $dep    = strtr($dep, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $rail   = str_contains($dep, 'pat') ? 'patin' : (str_contains($dep, 'fut') ? 'futbol' : 'otro');
    $modo   = $modo ?? 'listado';
@endphp

<div class="alumno-card alumno-card--{{ $rail }}"
     @if($modo === 'hoy') id="clase-hoy-{{ $clase->id }}" @endif
     data-estado="{{ $estado }}"
     style="{{ $clase->cancelada ? 'opacity:0.6;' : '' }}">

    <div class="alumno-card-header">
        <span class="alumno-dot alumno-dot--{{ $dot }}" style="color:{{ $color }};"></span>
        <h3 class="alumno-nombre">
            @if($modo === 'hoy')
                {{ $clase->hora_inicio->format('H:i') }}
                –
                {{ $clase->hora_fin->format('H:i') }}
            @else
                {{ $diasSemana[$clase->fecha->dayOfWeek] }}
                {{ $clase->fecha->format('d/m/Y') }}
                —
                {{ $clase->hora_inicio->format('H:i') }}
                a
                {{ $clase->hora_fin->format('H:i') }}
            @endif
            <span style="font-weight:400; color:var(--color-text-muted);
                         font-size:0.82rem; margin-left:6px;">
                {{ $label }}
            </span>
        </h3>
    </div>

    <div class="alumno-info" style="grid-template-columns: repeat(3, 1fr);">
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="info-label">Grupo:</span>
            <span class="info-value">{{ $clase->grupo->nombre_completo }}</span>
        </div>
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="info-label">Profesores:</span>
            <span class="info-value">
                {{ $clase->profesores->isNotEmpty()
                   ? $clase->profesores->map(fn($p) => $p->apellido)->implode(' · ')
                   : '–' }}
            </span>
        </div>
        <div class="info-item">
            <svg class="info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="info-label">Asistencia:</span>
            <span class="info-value"
                  style="color:{{ $clase->asistencias->where('presente', true)->count() > 0 ? 'var(--color-success)' : 'var(--color-text-muted)' }};">
                {{ $clase->asistencias->where('presente', true)->count() > 0 ? 'Cargada' : 'Pendiente' }}
            </span>
        </div>
    </div>

    <div class="alumno-actions">
        <x-ds.button variant="primary"
                     href="{{ route('web.clases.show', $clase->id) }}">
            Ver
        </x-ds.button>
        @if($esAdmin)
            <x-ds.button variant="secondary"
                         href="{{ route('web.clases.edit', $clase->id) }}">
                Editar
            </x-ds.button>
        @endif
    </div>

</div>
