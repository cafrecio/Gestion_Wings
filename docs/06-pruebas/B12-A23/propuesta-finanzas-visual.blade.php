@extends('layouts.app')
@section('title', 'Ingresos y egresos – Wings')
@section('module-title', 'Ingresos y egresos')
@section('content')
@vite('resources/css/reportes-visual.css')
@php
    $title = 'Ingresos y egresos';
    $dinero = fn ($n) => $n === null ? 'Sin datos' : '$'.number_format($n / 100, 0, ',', '.');
    $fecha = fn ($m) => \Carbon\CarbonImmutable::createFromFormat('!Y-m', $m)->locale('es');
    $ultimo = collect($evolucion)->last();
    $anterior = count($evolucion) > 1 ? $evolucion[count($evolucion)-2] : null;
    if ($anterior && $anterior['sin_clasificar']) $anterior = null;
    $comparacionDisponible = $ultimo && !$ultimo['sin_clasificar'] && $anterior;
    $desglosar = fn ($r, $tipo) => collect($r['filas'])->filter(fn ($f) => $f['clasificacion'] === 'NEGOCIO' && $f['tipo'] === $tipo)
        ->groupBy('concepto')->map(fn ($filas, $nombre) => ['nombre' => $nombre, 'importe' => $filas->sum('centavos') * ($tipo === 'INGRESO' ? 1 : -1), 'filas' => $filas->all()])->sortByDesc('importe')->values();
    $series = array_map(fn ($r) => ['mes' => ucfirst($fecha($r['mes'])->translatedFormat('M')), 'ingresos' => $r['sin_clasificar'] ? null : $r['ingresos'], 'egresos' => $r['sin_clasificar'] ? null : $r['egresos'], 'resultado' => $r['resultado']], $evolucion);
    $cambios = collect();
    if ($comparacionDisponible) {
        foreach (['INGRESO', 'EGRESO'] as $tipo) {
            $actuales = $desglosar($ultimo,$tipo)->keyBy('nombre');
            $previos = $desglosar($anterior,$tipo)->keyBy('nombre');
            foreach ($actuales->keys()->merge($previos->keys())->unique() as $nombre) {
                $delta = (($actuales[$nombre]['importe'] ?? 0)-($previos[$nombre]['importe'] ?? 0)) * ($tipo === 'INGRESO' ? 1 : -1);
                if ($delta !== 0) $cambios->push(['nombre' => $nombre, 'importe' => $delta]);
            }
        }
        $cambios = $cambios->sortByDesc(fn ($c) => abs($c['importe']))->values();
        if ($cambios->count() > 4) $cambios = $cambios->take(4)->push(['nombre' => 'Otros', 'importe' => $cambios->slice(4)->sum('importe')]);
    }
    $cajas = collect($reporte['cajas'] ?? [])->pluck('nombre','id');
    $paleta = ['success','info','warning','danger','text-muted'];
@endphp
<div class="reporte-visual">
<div class="stats-bar mb-4"><div class="stats-info"><strong>Reportes</strong> · Datos ficticios</div><a class="ds-btn ds-btn--secondary" href="inicio-aplicado.html">Volver</a></div>
<div class="filtros-card rv-filter" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:18px">
    <select class="filtros-control" aria-label="Mes" style="flex:1;min-width:130px"><option>{{ ucfirst($fecha($reporte['mes'])->translatedFormat('F Y')) }}</option></select>
    <select class="filtros-control" aria-label="Deporte" style="flex:1;min-width:130px"><option>Todo el negocio</option><option>Patín</option><option>Fútbol</option></select>
    <button type="button" class="ds-btn ds-btn--secondary">Filtrar</button>
</div>
@if($ultimo && !$ultimo['sin_clasificar'])
<div class="rv-section"><h2>{{ ucfirst($fecha($ultimo['mes'])->translatedFormat('F')) }}{{ $anterior ? ' vs '.$fecha($anterior['mes'])->translatedFormat('F') : '' }}</h2><span class="rv-note">Meses cerrados · {{ $fecha($ultimo['mes'])->format('Y') }}</span></div>
<div class="rv-kpis">
@foreach([['Ingresos','ingresos','success'],['Egresos','egresos','danger'],['Resultado','resultado','info']] as [$nombre,$clave,$tono])
    @php
        $delta = $anterior ? $ultimo[$clave]-$anterior[$clave] : null;
        $bueno = $delta === null || ($clave === 'egresos' ? $delta <= 0 : $delta >= 0);
        $cambio = $delta === null ? 'Sin comparación' : ($delta === 0 ? 'Sin cambio' : ($delta > 0 ? '↑ ' : '↓ ').($clave !== 'resultado' && $anterior[$clave] > 0 ? number_format(abs($delta)*100/$anterior[$clave],1,',','.').'%' : $dinero(abs($delta))));
    @endphp
    <section class="rv-kpi"><div class="rv-kpi-label"><span class="rv-swatch" style="background:var(--color-{{ $tono }})"></span>{{ $nombre }}</div><div class="rv-amount">{{ $dinero($ultimo[$clave]) }}</div><div class="rv-spark"><canvas data-rv-spark="{{ json_encode(array_column($series,$clave)) }}" data-tone="{{ $tono }}" role="img" aria-label="Tendencia mensual de {{ strtolower($nombre) }}"></canvas></div><span class="rv-change {{ $bueno ? 'rv-good' : 'rv-bad' }}">{{ $cambio }}</span></section>
@endforeach
@php($relacion = $ultimo['ingresos'] > 0 ? $ultimo['egresos']*100/$ultimo['ingresos'] : null)
<section class="rv-kpi"><div class="rv-kpi-label"><span class="rv-swatch" style="background:var(--color-warning)"></span>Egresos / ingresos</div><div class="rv-amount">{{ $relacion === null ? 'Sin datos' : number_format($relacion,1,',','.').'%' }}</div><div class="rv-ring-mini"><canvas data-rv-ratio="{{ $relacion ?? '' }}" role="img" aria-label="Porcentaje de ingresos equivalente a egresos"></canvas></div><span class="rv-change rv-muted">{{ $relacion === null ? 'Sin ingresos positivos' : 'Del ingreso del mes' }}</span></section>
</div>
@endif
<div class="rv-main-grid">
    <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Evolución mensual</h3></div><div class="rv-body"><div class="rv-legend"><span><i class="rv-swatch" style="background:var(--color-success)"></i>Ingresos</span><span><i class="rv-swatch" style="background:var(--color-danger)"></i>Egresos</span><span class="rv-muted">Meses cerrados</span></div><div class="rv-chart"><canvas data-rv-evolution="{{ json_encode($series) }}" role="img" aria-label="Evolución de ingresos y egresos de los meses cerrados disponibles"></canvas></div></div><details class="rv-detail"><summary>Detalle</summary>@foreach($evolucion as $r)<div class="rv-detail-row"><span>{{ ucfirst($fecha($r['mes'])->translatedFormat('F')) }}<small>{{ $r['sin_clasificar'] ? 'Parcial · ' : '' }}Ingresos {{ $dinero($r['ingresos']) }} · Egresos {{ $dinero($r['egresos']) }}@if($r['sin_clasificar']) · {{ $r['sin_clasificar'] }} sin clasificar @endif</small></span><strong>{{ $dinero($r['resultado']) }}</strong></div>@endforeach</details></section>
    <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--success"></span><h3 class="alumno-nombre">Cambio en el resultado</h3></div><div class="rv-body"><p class="rv-note" style="margin-bottom:12px">{{ $ultimo ? ucfirst($fecha($ultimo['mes'])->translatedFormat('F')) : '' }}{{ $anterior ? ' vs '.$fecha($anterior['mes'])->translatedFormat('F') : '' }}</p>@if($cambios->isNotEmpty())<div class="rv-change-chart"><canvas data-rv-impact="{{ json_encode($cambios) }}" role="img" aria-label="Aporte de cada rubro al cambio del resultado"></canvas></div><ul class="rv-sr-only">@foreach($cambios as $cambio)<li>{{ $cambio['nombre'] }}: {{ $cambio['importe'] >= 0 ? '+' : '−' }}{{ $dinero(abs($cambio['importe'])) }}</li>@endforeach</ul><div class="rv-impact"><span>Variación total</span><strong class="{{ $cambios->sum('importe') >= 0 ? 'rv-good' : 'rv-bad' }}">{{ $cambios->sum('importe') >= 0 ? '+' : '−' }}{{ $dinero(abs($cambios->sum('importe'))) }}</strong></div>@else<p class="rv-note">{{ $comparacionDisponible ? 'Sin cambios por rubro' : 'Sin comparación disponible' }}</p>@endif</div></section>
</div>
<div class="rv-section"><h2>{{ ucfirst($fecha($reporte['mes'])->translatedFormat('F')) }} en curso</h2><span class="rv-note">Hasta {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</span></div>
@if($reporte['sin_clasificar'])<p class="rv-note" style="color:var(--color-warning);margin-bottom:12px">{{ $reporte['sin_clasificar'] }} movimientos sin clasificar · importes parciales</p>@endif
<div class="rv-current">@foreach([['Ingresos','ingresos','success'],['Egresos','egresos','danger'],['Resultado','resultado','info']] as [$label,$clave,$tono])<div class="rv-current-item"><span>{{ $label }}</span><strong style="color:var(--color-{{ $tono }})">{{ $dinero($reporte[$clave]) }}</strong></div>@endforeach</div>
<div class="rv-pair">
@foreach([['Origen de ingresos','INGRESO','ingresos','success'],['Destino de gastos','EGRESO','egresos','danger']] as [$titulo,$tipo,$clave,$tono])
@php($rubros = $desglosar($reporte,$tipo))
<section class="alumno-card rv-panel" id="{{ $clave }}"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $tono }}"></span><h3 class="alumno-nombre">{{ $titulo }}</h3></div><div class="rv-body">
@if($tipo === 'INGRESO' && $reporte['ingresos'] > 0 && $rubros->every(fn ($r) => $r['importe'] >= 0))
<div class="rv-donut-grid"><div class="rv-donut"><canvas data-rv-income="{{ json_encode($rubros->map(fn ($r) => ['nombre'=>$r['nombre'],'importe'=>$r['importe']])) }}" role="img" aria-label="Distribución de ingresos por rubro"></canvas><div class="rv-donut-center"><strong>{{ $dinero($reporte['ingresos']) }}</strong><span class="rv-note">Ingresos</span></div></div><div class="rv-breakdown">@foreach($rubros as $rubro)<div class="rv-breakdown-row"><i class="rv-swatch" style="background:var(--color-{{ $paleta[$loop->index % count($paleta)] }})"></i><div>{{ $rubro['nombre'] }}<strong>{{ $dinero($rubro['importe']) }}<span class="rv-row-share">{{ number_format($rubro['importe']*100/$reporte['ingresos'],1,',','.') }}%</span></strong></div></div>@endforeach</div></div>
@else
<div class="rv-chart"><canvas data-rv-bars="{{ json_encode($rubros->map(fn ($r) => ['nombre'=>$r['nombre'],'importe'=>$r['importe']])) }}" data-tone="{{ $tono }}" role="img" aria-label="Importes netos por rubro"></canvas></div>
@endif
</div><details class="rv-detail"><summary>Detalle</summary>@foreach($rubros as $rubro)<details style="margin-top:12px"><summary>{{ $rubro['nombre'] }} · {{ $dinero($rubro['importe']) }}</summary>@foreach($rubro['filas'] as $f)<div class="rv-detail-row"><span>{{ \Carbon\CarbonImmutable::parse($f['fecha'])->format('d/m') }} · {{ $cajas[$f['tipo_caja_id']] ?? 'Caja' }}<small>{{ $f['confirmado'] ? 'Confirmado' : 'Por validar' }}</small></span><strong>{{ $dinero($f['centavos']*($tipo === 'INGRESO' ? 1 : -1)) }}</strong></div>@endforeach</details>@endforeach</details></section>
@endforeach
</div>
<div class="rv-section"><h2>Situación al {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</h2><span class="rv-note">Saldos acumulados</span></div>
<div class="rv-current">@foreach([['Disponible',$reporte['disponible']],['Por cobrar',$reporte['deuda']['total']],['Por pagar',$reporte['por_pagar']['total']]] as [$label,$importe])<div class="rv-current-item"><span>{{ $label }}</span><strong>{{ $dinero($importe) }}</strong></div>@endforeach</div>
<details class="filtros-card rv-detail" style="margin-top:12px"><summary>Detalle de cajas y aportes</summary>@foreach($reporte['cajas'] ?? [] as $caja)<div class="rv-detail-row"><span>{{ $caja['nombre'] }}<small>Confirmado {{ $dinero($caja['confirmado']) }} · Por validar {{ $dinero($caja['pendiente']) }}</small></span><strong>{{ $dinero($caja['total']) }}</strong></div>@endforeach<div class="rv-detail-row"><span>Aportes / retiros<small>Fuera del resultado</small></span><strong>{{ $dinero($reporte['aportes']) }} / {{ $dinero($reporte['retiros']) }}</strong></div></details>
<div class="rv-footer"><span>Cobros y pagos · confirmado + por validar</span><span>Alumnos y Sueldos: pantallas pendientes</span></div>
</div>
@endsection
@push('scripts')
@vite('resources/js/reportes-visual.js')
@endpush
