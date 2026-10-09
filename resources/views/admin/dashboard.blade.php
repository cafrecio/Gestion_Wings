@extends('layouts.app')
@section('title', 'Inicio – Wings')
@section('module-title', 'Inicio')
@section('content')
@php
    $dinero = fn ($centavos) => $centavos === null ? 'Sin historial' : '$'.number_format($centavos / 100, 0, ',', '.');
    $title = 'Inicio';
    $mes = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $reporte['mes'])->locale('es');
    $corte = \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->locale('es');
    $reportesDisponibles = \Illuminate\Support\Facades\Route::has('web.reportes.index');
    $enlaceReportes = $reportesDisponibles ? route('web.reportes.index', ['mes' => $reporte['mes']]) : null;
@endphp
<div class="stats-bar mb-4"><div class="stats-info"><strong>{{ ucfirst($mes->translatedFormat('F Y')) }}</strong> · Todo el negocio</div>
    @if($enlaceReportes)<a class="ds-btn ds-btn--secondary" href="{{ $enlaceReportes }}">Ver</a>@else<button type="button" class="ds-btn ds-btn--secondary" disabled>Ver</button>@endif
</div>
<p style="color:var(--color-text-muted);font-size:.8rem;margin-bottom:16px">Mes en curso · hasta el {{ $corte->translatedFormat('j \d\e F') }}</p>
@if($reporte['sin_clasificar'] > 0)
<p style="color:var(--color-warning);font-size:.8rem;margin-bottom:16px">{{ $reporte['sin_clasificar'] }} movimientos por clasificar. El resultado está incompleto.</p>
@endif
<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:20px">
@foreach([['Ingresos del mes',$reporte['ingresos'],'success','ingresos'],['Egresos del mes',$reporte['egresos'],'danger','egresos'],['Alumnos por cobrar',$reporte['deuda']['total'],'warning','deuda'],['Profesores por pagar',$reporte['por_pagar']['total'],'info','profesores']] as [$nombre,$valor,$color,$ancla])
    <a @if($enlaceReportes) href="{{ $enlaceReportes }}#{{ $ancla }}" @else role="link" aria-disabled="true" @endif class="filtros-card" style="text-decoration:none;display:block;min-width:0">
        <p style="color:var(--color-text-muted);font-size:.78rem;margin-bottom:6px">{{ $nombre }}</p>
        <p style="font-size:clamp(1.15rem,3vw,1.9rem);font-weight:750;color:var(--color-{{ $color }});overflow-wrap:anywhere">{{ $dinero($valor) }}</p>
    </a>
@endforeach
</div>
<div class="filtros-card" style="margin-bottom:20px;display:flex;justify-content:space-between;gap:12px;align-items:center">
    <span style="font-weight:600">Resultado de {{ $mes->translatedFormat('F') }}</span><strong style="font-size:1.35rem;color:var(--color-{{ $reporte['resultado'] < 0 ? 'danger' : 'success' }})">{{ $reporte['resultado'] === null && $reporte['sin_clasificar'] > 0 ? 'Sin clasificar' : $dinero($reporte['resultado']) }}</strong>
</div>
<div class="alumno-card" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--success"></span><h3 class="alumno-nombre">Disponible</h3></div>
    <p style="font-size:1.7rem;font-weight:750;padding:0 16px 12px">{{ $dinero($reporte['disponible']) }}</p>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:0 16px 16px">
    @foreach($reporte['cajas'] ?? [] as $caja)
        <div><p style="font-size:.75rem;color:var(--color-text-muted)">{{ $caja['nombre'] }}</p><strong>{{ $dinero($caja['total']) }}</strong></div>
    @endforeach
    </div>
    <div class="alumno-actions" style="border-top:1px solid var(--color-border);font-size:.75rem;color:var(--color-text-muted)">Incluye arrastre · {{ $dinero($reporte['cajas'] === null ? null : array_sum(array_column($reporte['cajas'],'pendiente'))) }} por validar</div>
</div>
<div class="alumno-card">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Para revisar</h3></div>
    @foreach([['Cajas por validar','cajas',route('web.caja.index',['pendientes'=>1])],['Liquidaciones pendientes','liquidaciones',route('web.liquidaciones.index',['pendientes'=>1])],['Clases sin asistencia','asistencia',route('web.clases.index',['estado'=>'finalizada'])],['Revisión de alumnos','revision',route('web.revision-cobranza.index',['estado'=>'PENDIENTE'])]] as [$etiqueta,$clave,$enlace])
        <a href="{{ $enlace }}" style="display:flex;justify-content:space-between;align-items:center;padding:13px 16px;border-top:1px solid var(--color-border);text-decoration:none;color:var(--color-text);font-size:.86rem"><span>{{ $etiqueta }}</span><strong>{{ $reporte['avisos'][$clave] }} →</strong></a>
    @endforeach
</div>
@endsection
