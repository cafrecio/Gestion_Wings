@extends('layouts.app')
@section('title', 'Reportes – Wings')
@section('module-title', 'Reportes')
@section('content')
@php
    $dinero = fn ($c) => $c === null ? 'Sin historial' : '$'.number_format($c / 100, 0, ',', '.');
    $title = 'Reportes';
    $maximo = max(1, ...array_column($evolucion, 'ingresos'));
    $meses = ['04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep'];
@endphp
<div class="filtros-card" style="margin-bottom:20px;display:flex;align-items:end;gap:12px;flex-wrap:wrap">
    <div style="flex:1;min-width:120px"><label style="display:block;font-size:.75rem;margin-bottom:5px;color:var(--color-text-muted)">Mes</label><select class="ds-input" style="width:100%"><option>Octubre 2026</option><option>Septiembre 2026</option></select></div>
    <div style="flex:1;min-width:120px"><label style="display:block;font-size:.75rem;margin-bottom:5px;color:var(--color-text-muted)">Deporte</label><select class="ds-input" style="width:100%"><option>Todo el negocio</option><option>Patín</option><option>Fútbol</option></select></div>
    <button class="ds-btn ds-btn--secondary" type="button">Filtrar</button>
</div>
<p style="color:var(--color-text-muted);font-size:.8rem;margin-bottom:14px">Octubre en curso · hasta el día 9 · Datos ficticios</p>
<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:20px">
@foreach([['Ingresos',$reporte['ingresos'],'success','ingresos'],['Egresos',$reporte['egresos'],'danger','egresos'],['Por cobrar',$reporte['deuda']['total'],'warning','deuda'],['Por pagar',$reporte['por_pagar']['total'],'info','profesores']] as [$nombre,$valor,$color,$ancla])
    <a class="filtros-card" href="#{{ $ancla }}" style="display:block;text-decoration:none;min-width:0"><p style="color:var(--color-text-muted);font-size:.78rem;margin-bottom:5px">{{ $nombre }}</p><strong style="font-size:clamp(1.15rem,3vw,1.9rem);color:var(--color-{{ $color }})">{{ $dinero($valor) }}</strong></a>
@endforeach
</div>
<div class="filtros-card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:12px"><span>Resultado de octubre</span><strong style="font-size:1.35rem;color:var(--color-success)">{{ $dinero($reporte['resultado']) }}</strong></div>
<section class="alumno-card" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--neutral"></span><h3 class="alumno-nombre">Últimos seis meses cerrados</h3></div>
    <p style="font-size:.76rem;color:var(--color-text-muted);padding:0 16px 8px">Ingresos y egresos del negocio · el mes en curso queda afuera</p>
    <div style="padding:0 12px 12px">
    <svg viewBox="0 0 360 215" role="img" aria-label="Ingresos y egresos mensuales de abril a septiembre" style="width:100%;max-width:680px;height:auto;display:block;margin:auto">
        @foreach([0,1,2,3] as $linea)
        <line x1="35" y1="{{ 174-$linea*48 }}" x2="355" y2="{{ 174-$linea*48 }}" stroke="var(--color-border)" stroke-dasharray="3 5"/>
        <text x="29" y="{{ 178-$linea*48 }}" text-anchor="end" fill="var(--color-text-muted)" font-size="10">{{ number_format($maximo*$linea/3/100000,0) }}k</text>
        @endforeach
        @foreach($evolucion as $i => $r)
            @php($x=46+$i*51)
            <rect x="{{ $x }}" y="{{ 174-144*$r['ingresos']/$maximo }}" width="14" height="{{ 144*$r['ingresos']/$maximo }}" rx="3" fill="var(--color-success)"><title>{{ $r['mes'] }} · Ingresos {{ $dinero($r['ingresos']) }}</title></rect>
            <rect x="{{ $x+17 }}" y="{{ 174-144*$r['egresos']/$maximo }}" width="14" height="{{ 144*$r['egresos']/$maximo }}" rx="3" fill="color-mix(in srgb,var(--color-danger) 72%,var(--color-surface))"><title>{{ $r['mes'] }} · Egresos {{ $dinero($r['egresos']) }}</title></rect>
            <text x="{{ $x+15 }}" y="195" text-anchor="middle" font-size="12" fill="var(--color-text-muted)">{{ $meses[substr($r['mes'],5)] }}</text>
        @endforeach
    </svg>
    <div style="display:flex;gap:18px;justify-content:center;font-size:.76rem"><span style="color:var(--color-success)">● Ingresos</span><span style="color:var(--color-danger)">● Egresos</span></div>
    </div>
    <details style="padding:12px 16px;border-top:1px solid var(--color-border)"><summary style="font-size:.8rem;cursor:pointer">Ver importes</summary>
        @foreach($evolucion as $r)<p style="font-size:.78rem;padding-top:8px">{{ $r['mes'] }} · Ingresos {{ $dinero($r['ingresos']) }} · Egresos {{ $dinero($r['egresos']) }}</p>@endforeach
    </details>
</section>
<section class="alumno-card" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--success"></span><h3 class="alumno-nombre">Disponible por caja</h3></div>
    <p style="font-size:.76rem;padding:0 16px 12px;color:var(--color-text-muted)">Del negocio entero · incluye saldo anterior · validación actual</p>
    <div style="padding:0 16px 16px">
    @foreach(array_filter($reporte['cajas'],fn($c)=>$c['total']!==0) as $caja)
        <div style="padding:12px 0;border-top:1px solid var(--color-border)"><div style="display:flex;justify-content:space-between;margin-bottom:8px"><strong>{{ $caja['nombre'] }}</strong><strong>{{ $dinero($caja['total']) }}</strong></div><div style="display:flex;justify-content:space-between;gap:8px;font-size:.75rem;color:var(--color-text-muted)"><span>Confirmado {{ $dinero($caja['confirmado']) }}</span><span>Pendiente {{ $dinero($caja['pendiente']) }}</span></div></div>
    @endforeach
    <div style="display:flex;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:14px"><strong>Total</strong><strong>{{ $dinero($reporte['disponible']) }}</strong></div>
    </div>
</section>
<section class="alumno-card" id="deuda" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Deuda de alumnos</h3></div>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:0 16px 16px"><div><p style="font-size:.75rem;color:var(--color-text-muted)">Octubre</p><strong>{{ $dinero($reporte['deuda']['mes']) }}</strong></div><div><p style="font-size:.75rem;color:var(--color-text-muted)">Meses anteriores</p><strong>{{ $dinero($reporte['deuda']['anteriores']) }}</strong></div></div>
    <p style="font-size:.75rem;color:var(--color-text-muted);padding:0 16px 12px">Cierre histórico del mes elegido · octubre se consulta hasta hoy</p>
    <details style="padding:12px 16px;border-top:1px solid var(--color-border)"><summary style="font-size:.8rem;cursor:pointer">Ver detalle</summary>@foreach($reporte['deuda']['filas'] as $f)<p style="font-size:.78rem;padding-top:8px">Alumno de prueba #{{ $f['persona_id'] }} · {{ $f['periodo'] }} · {{ $dinero($f['centavos']) }}</p>@endforeach</details>
</section>
<section class="alumno-card" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--neutral"></span><h3 class="alumno-nombre">Alumnos activos hoy</h3></div>
    <div style="padding:0 16px 16px">@foreach($reporte['alumnos'] as $fila)<div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--color-border);font-size:.82rem"><span>{{ $fila['deporte'] }} · {{ $fila['nivel'] }}</span><strong>{{ $fila['cantidad'] }}</strong></div>@endforeach</div>
</section>
@foreach([['ingresos','Ingresos','INGRESO'],['egresos','Egresos','EGRESO']] as [$id,$titulo,$tipo])
<section class="alumno-card" id="{{ $id }}" style="margin-bottom:20px"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--neutral"></span><h3 class="alumno-nombre">{{ $titulo }} de octubre</h3></div><details style="padding:0 16px 16px"><summary style="cursor:pointer;font-size:.8rem">Ver detalle</summary>@foreach(array_filter($reporte['filas'],fn($f)=>$f['tipo']===$tipo && $f['clasificacion']==='NEGOCIO') as $f)<p style="font-size:.78rem;padding-top:8px">{{ $f['fecha'] }} · {{ $f['concepto'] }} · {{ $dinero(abs($f['centavos'])) }} · {{ $f['confirmado'] ? 'Confirmado' : 'Pendiente' }}</p>@endforeach</details></section>
@endforeach
<section class="alumno-card" id="profesores"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Profesores por pagar</h3></div><div style="padding:0 16px 16px"><strong>{{ $dinero($reporte['por_pagar']['total']) }}</strong><p style="font-size:.75rem;color:var(--color-text-muted);margin-top:8px">Liquidaciones cerradas · {{ $reporte['avisos']['liquidaciones_abiertas'] }} abierta aparte</p>@foreach($reporte['por_pagar']['filas'] as $f)<p style="font-size:.78rem;margin-top:8px">Docente de prueba #{{ $f['persona_id'] }} · {{ $f['periodo'] }} · {{ $dinero($f['centavos']) }}</p>@endforeach</div></section>
@endsection
