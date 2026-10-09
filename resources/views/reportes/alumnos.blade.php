@extends('layouts.app')
@section('title', 'Reporte de alumnos – Wings')
@section('module-title', 'Reporte de alumnos')
@section('content')
@vite(['resources/css/reportes-visual.css', 'resources/css/reportes-analiticos.css'])
@php
    $title = 'Reporte de alumnos';
    $numero = fn ($n) => number_format($n, 0, ',', '.');
    $fecha = fn ($p) => \Carbon\CarbonImmutable::createFromFormat('!Y-m', $p)->locale('es');
    $mesNombre = ucfirst($fecha($reporte['mes'])->translatedFormat('F Y'));
    $paleta = ['info', 'success', 'warning', 'danger', 'text-muted'];
    $grafico = fn ($tipo, $etiquetas, $series, $horizontal = false) => json_encode(['type'=>$tipo, 'labels'=>$etiquetas, 'series'=>$series, 'horizontal'=>$horizontal]);
    $serie = array_map(fn ($r) => $fecha($r['mes'])->translatedFormat('M'), $evolucion);
    $contexto = array_filter(['mes'=>$reporte['mes'], 'deporte_id'=>$reporte['deporte_id']], fn ($v) => $v !== null);
@endphp
<div class="reporte-visual reporte-analitico">
    <div class="stats-bar mb-4"><div class="stats-info"><strong>Reportes</strong></div><a class="ds-btn ds-btn--secondary" href="{{ route('web.reportes.index',$contexto) }}">Volver</a></div>
    <div class="ra-subnav"><a href="{{ route('web.reportes.index',$contexto) }}">Ingresos y egresos</a><strong aria-current="page">Alumnos</strong><a href="{{ route('web.reportes.sueldos',$contexto) }}">Sueldos</a></div>
    @if($errors->any())<p class="rv-note" style="color:var(--color-danger);margin-bottom:12px">{{ $errors->first() }}</p>@endif
    <form class="filtros-card ra-filters" method="GET" action="{{ route('web.reportes.alumnos') }}">
        <select name="mes" class="filtros-control" aria-label="Mes">@foreach($meses as $m)<option value="{{ $m }}" @selected($m === $reporte['mes'])>{{ ucfirst($fecha($m)->translatedFormat('F Y')) }}</option>@endforeach</select>
        <select name="deporte_id" class="filtros-control" aria-label="Deporte"><option value="">Todos los deportes</option>@foreach($deportes as $d)<option value="{{ $d->id }}" @selected($d->id === $reporte['deporte_id'])>{{ $d->nombre }}</option>@endforeach</select>
        <button class="ds-btn ds-btn--secondary" type="submit">Filtrar</button>
    </form>
    <div class="rv-section"><h2>{{ $mesNombre }}</h2><span class="rv-note">Asistencia hasta {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</span></div>
    <div class="rv-kpis">
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-info)"></i>Activos hoy</div><div class="ra-count">{{ $numero($reporte['activos']) }}</div><div class="ra-mini">{{ $numero($reporte['inactivos']) }} inactivos</div><div class="ra-meter"><i style="width:{{ $reporte['activos'] + $reporte['inactivos'] ? $reporte['activos'] * 100 / ($reporte['activos'] + $reporte['inactivos']) : 0 }}%;background:var(--color-info)"></i></div><span class="rv-note">Matrícula actual · {{ \Carbon\CarbonImmutable::parse($reporte['fecha_matricula'])->format('d/m') }}</span></section>
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-success)"></i>Asistencias del mes</div><div class="ra-count">{{ $numero($reporte['presentes']) }}</div><div class="ra-mini">{{ $numero($reporte['alumnos_asistieron']) }} alumnos distintos</div><div class="ra-meter"><i style="width:{{ $reporte['porcentaje_presencia'] ?? 0 }}%"></i></div><span class="rv-note">Cada presente cuenta una vez</span></section>
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-warning)"></i>Presencia registrada</div><div class="ra-count">{{ $reporte['porcentaje_presencia'] === null ? 'Sin datos' : number_format($reporte['porcentaje_presencia'],1,',','.').'%' }}</div><div class="ra-mini">{{ $numero($reporte['ausentes']) }} ausencias registradas</div><div class="ra-meter"><i style="width:{{ $reporte['porcentaje_presencia'] ?? 0 }}%;background:var(--color-warning)"></i></div><span class="rv-note">Sobre presentes + ausentes cargados</span></section>
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-danger)"></i>Clases sin registros</div><div class="ra-count">{{ $numero($reporte['clases_sin_registros']) }}</div><div class="ra-mini">De {{ $numero($reporte['clases']) }} clases no canceladas</div><div class="ra-meter"><i style="width:{{ $reporte['clases'] ? $reporte['clases_sin_registros'] * 100 / $reporte['clases'] : 0 }}%;background:var(--color-danger)"></i></div><span class="rv-note">No cuentan como ausencias</span></section>
    </div>
    <div class="rv-main-grid">
        <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Evolución de asistencia</h3></div><div class="rv-body"><div class="rv-legend"><span><i class="rv-swatch" style="background:var(--color-success)"></i>Presentes</span><span><i class="rv-swatch" style="background:var(--color-warning)"></i>Ausentes</span><span class="rv-muted">Meses cerrados</span></div>
        @if(count($evolucion))
        <div class="rv-chart"><canvas data-ra-chart="{{ $grafico('line',$serie,[['label'=>'Presentes','values'=>array_column($evolucion,'presentes'),'tone'=>'success'],['label'=>'Ausentes','values'=>array_column($evolucion,'ausentes'),'tone'=>'warning']]) }}" role="img" aria-label="Evolución de presentes y ausentes registrados"></canvas></div>
        @else
        <div class="ra-empty">Sin clases en meses anteriores</div>
        @endif
        </div><details class="rv-detail"><summary>Detalle</summary>@foreach($evolucion as $r)<div class="rv-detail-row"><span>{{ ucfirst($fecha($r['mes'])->translatedFormat('F Y')) }}<small>{{ $r['sin_registros'] ? $r['sin_registros'].' clases sin registros · serie incompleta' : 'Registros de clases no canceladas' }} · {{ $numero($r['ausentes_registrados']) }} ausentes</small></span><strong>{{ $numero($r['registrados']) }} presentes</strong></div>@endforeach</details></section>
        <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Activos por deporte</h3></div><div class="rv-body"><div class="ra-note">Hoy · {{ \Carbon\CarbonImmutable::parse($reporte['fecha_matricula'])->format('d/m') }}</div>
        @if($reporte['activos'])
        <div class="rv-donut"><canvas data-ra-chart="{{ $grafico('doughnut',array_column($reporte['por_deporte'],'nombre'),[['label'=>'Activos','values'=>array_column($reporte['por_deporte'],'cantidad')]]) }}" role="img" aria-label="Distribución actual de alumnos activos por deporte"></canvas><div class="rv-donut-center"><strong>{{ $numero($reporte['activos']) }}</strong><span class="rv-note">Activos</span></div></div>
        <div class="ra-duo">@foreach($reporte['por_deporte'] as $d)<div><strong style="color:var(--color-{{ $paleta[$loop->index % count($paleta)] }})">{{ $numero($d['cantidad']) }}</strong><span>{{ $d['nombre'] }}</span></div>@endforeach</div>
        @else
        <div class="ra-empty">Sin alumnos activos</div>
        @endif
        </div></section>
    </div>
    <div class="rv-section"><h2>Deporte y nivel</h2><span class="rv-note">Matrícula hoy · Asistencia del mes</span></div>
    <div class="rv-pair">
        <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Activos por nivel</h3></div><div class="rv-body">@if(count($reporte['por_nivel']))<div class="rv-chart"><canvas data-ra-chart="{{ $grafico('bar',array_column($reporte['por_nivel'],'nombre'),[['label'=>'Activos hoy','values'=>array_column($reporte['por_nivel'],'cantidad'),'tone'=>'info']],true) }}" role="img" aria-label="Activos actuales por deporte y nivel"></canvas></div>@else<div class="ra-empty">Sin alumnos activos</div>@endif</div><details class="rv-detail"><summary>Detalle</summary>@foreach($reporte['por_nivel'] as $g)<div class="rv-detail-row"><span>{{ $g['nombre'] }}</span><strong>{{ $numero($g['cantidad']) }}</strong></div>@endforeach</details></section>
        <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--success"></span><h3 class="alumno-nombre">Asistencia por nivel</h3></div><div class="rv-body"><div class="rv-legend"><span><i class="rv-swatch" style="background:var(--color-success)"></i>Presentes</span><span><i class="rv-swatch" style="background:var(--color-warning)"></i>Ausentes</span></div>@if(count($reporte['grupos']))<div class="rv-chart"><canvas data-ra-chart="{{ $grafico('bar',array_column($reporte['grupos'],'nombre'),[['label'=>'Presentes','values'=>array_column($reporte['grupos'],'presentes'),'tone'=>'success'],['label'=>'Ausentes','values'=>array_column($reporte['grupos'],'ausentes'),'tone'=>'warning']],true) }}" role="img" aria-label="Asistencias y ausencias registradas por deporte y nivel"></canvas></div>@else<div class="ra-empty">Sin clases en este mes</div>@endif</div><details class="rv-detail"><summary>Detalle</summary>@foreach($reporte['grupos'] as $g)<div class="rv-detail-row"><span>{{ $g['nombre'] }}<small>{{ $g['ausentes'] }} ausencias · {{ $g['sin_registros'] }} clases sin registros</small></span><strong>{{ $numero($g['presentes']) }} presentes</strong></div>@endforeach</details></section>
    </div>
    <div class="rv-section"><h2>Seguimiento</h2><span class="rv-note">Sin cambios automáticos de estado</span></div>
    <details class="filtros-card rv-detail"><summary>Activos hoy sin presentes en {{ $fecha($reporte['mes'])->translatedFormat('F') }} · {{ count($reporte['sin_presentes_actuales']) }}</summary><p class="ra-mini">No determina que hayan dejado de venir. Revisar clases y registros antes de decidir.</p>@foreach($reporte['sin_presentes_actuales'] as $a)<div class="rv-detail-row"><span>{{ $a['nombre'] }}<small>{{ $a['deporte'] }} · {{ $a['nivel'] }}</small></span><a href="{{ route('web.alumnos.show',$a['id']) }}" class="rv-note">Ver</a></div>@endforeach</details>
    <div class="ra-note"><i class="rv-swatch" style="background:var(--color-warning)"></i>{{ $reporte['revisiones'] }} revisiones pendientes <a href="{{ route('web.revision-cobranza.index') }}" style="margin-left:auto">Ver</a></div>
    <div class="rv-footer"><span>Matrícula actual · asistencia del mes elegido</span><span>Clases canceladas y futuras excluidas</span></div>
</div>
@endsection
@push('scripts')
@vite('resources/js/reportes-analiticos.js')
@endpush
