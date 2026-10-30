@extends('layouts.app')
@section('title', 'Ingresos y egresos – Wings')
@section('module-title', 'Ingresos y egresos')
@section('ayuda-slug', 'reportes-ingresos-egresos')
@section('content')
@vite(['resources/css/reportes-visual.css', 'resources/css/reportes-analiticos.css'])
@php
    $title = 'Ingresos y egresos';
    $dinero = fn ($n) => $n === null ? 'Sin datos' : \App\Support\FormatoImporte::pesos($n / 100);
    // Gráficos excluidos de T30 por Carlos: conservar su presentación anterior.
    $dineroGrafico = fn ($n) => $n === null ? 'Sin datos' : '$'.number_format($n / 100, 0, ',', '.');
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
    $cajas = $nombresCajas;
    $paleta = ['success','info','warning','danger','text-muted'];
@endphp
<div class="reporte-visual reporte-analitico">
<div class="stats-bar mb-4"><div class="stats-info"><strong>Reportes</strong></div><a class="ds-btn ds-btn--secondary" href="{{ route('admin.dashboard') }}">Volver</a></div>
<nav class="ra-subnav" aria-label="Reportes"><strong aria-current="page">Ingresos y egresos</strong><a href="{{ route('web.reportes.alumnos',array_filter(['mes'=>$reporte['mes'],'deporte_id'=>$reporte['deporte_id']],fn ($v) => $v !== null)) }}">Alumnos</a><a href="{{ route('web.reportes.sueldos',array_filter(['mes'=>$reporte['mes'],'deporte_id'=>$reporte['deporte_id']],fn ($v) => $v !== null)) }}">Sueldos</a></nav>
@if($errors->any())<p class="rv-note" style="color:var(--color-danger);margin-bottom:12px">{{ $errors->first() }}</p>@endif
<form method="GET" action="{{ route('web.reportes.index') }}" class="filtros-card rv-filter" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:18px">
    <select name="mes" class="filtros-control" aria-label="Mes" style="flex:1;min-width:130px">@foreach($meses as $opcion)<option value="{{ $opcion }}" @selected($opcion === $reporte['mes'])>{{ ucfirst($fecha($opcion)->translatedFormat('F Y')) }}</option>@endforeach</select>
    <select name="deporte_id" class="filtros-control" aria-label="Deporte" style="flex:1;min-width:130px"><option value="">Todo el negocio</option>@foreach($deportes as $deporte)<option value="{{ $deporte->id }}" @selected($deporte->id === $reporte['deporte_id'])>{{ $deporte->nombre }}</option>@endforeach</select>
    <button type="submit" class="ds-btn ds-btn--secondary">Filtrar</button>
</form>
@if($ultimo && !$ultimo['sin_clasificar'])
<div class="rv-section"><h2>{{ ucfirst($fecha($ultimo['mes'])->translatedFormat('F')) }}{{ $anterior ? ' vs '.$fecha($anterior['mes'])->translatedFormat('F') : '' }}</h2><span class="rv-note">Meses cerrados · {{ $fecha($ultimo['mes'])->format('Y') }}</span></div>
<div class="rv-kpis">
@foreach([['Ingresos','ingresos','success'],['Egresos','egresos','danger'],['Resultado','resultado','info']] as [$nombre,$clave,$tono])
    @php
        $delta = $anterior ? $ultimo[$clave]-$anterior[$clave] : null;
        $bueno = $delta === null || ($clave === 'egresos' ? $delta <= 0 : $delta >= 0);
        $cambio = $delta === null ? 'Sin comparación' : ($delta === 0 ? 'Sin cambio' : ($delta > 0 ? '↑ ' : '↓ ').($clave !== 'resultado' && $anterior[$clave] > 0 ? number_format(abs($delta)*100/$anterior[$clave],1,',','.').'%' : $dinero(abs($delta))));
    @endphp
    <section class="rv-kpi"><div class="rv-kpi-label"><span class="rv-swatch" style="background:var(--color-{{ $tono }})"></span>{{ $nombre }}</div><div class="rv-amount"><span class="ds-money ds-money--total">{{ $dinero($ultimo[$clave]) }}</span></div><div class="rv-spark"><canvas data-rv-spark="{{ json_encode(array_column($series,$clave)) }}" data-tone="{{ $tono }}" role="img" aria-label="Tendencia mensual de {{ strtolower($nombre) }}"></canvas></div><span class="rv-change {{ $bueno ? 'rv-good' : 'rv-bad' }}"><span class="ds-money ds-money--row">{{ $cambio }}</span></span></section>
@endforeach
@php($relacion = $ultimo['ingresos'] > 0 ? $ultimo['egresos']*100/$ultimo['ingresos'] : null)
<section class="rv-kpi"><div class="rv-kpi-label"><span class="rv-swatch" style="background:var(--color-warning)"></span>Egresos / ingresos</div><div class="rv-amount">{{ $relacion === null ? 'Sin datos' : number_format($relacion,1,',','.').'%' }}</div><div class="rv-ring-mini"><canvas data-rv-ratio="{{ $relacion ?? '' }}" role="img" aria-label="Porcentaje de ingresos equivalente a egresos"></canvas></div><span class="rv-change rv-muted">{{ $relacion === null ? 'Sin ingresos positivos' : 'Del ingreso del mes' }}</span></section>
</div>
@endif
<div class="rv-main-grid">
    <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Evolución mensual</h3></div><div class="rv-body"><div class="rv-legend"><span><i class="rv-swatch" style="background:var(--color-success)"></i>Ingresos</span><span><i class="rv-swatch" style="background:var(--color-danger)"></i>Egresos</span><span class="rv-muted">Meses cerrados</span></div><div class="rv-chart"><canvas data-rv-evolution="{{ json_encode($series) }}" role="img" aria-label="Evolución de ingresos y egresos de los meses cerrados disponibles"></canvas></div></div><details class="rv-detail"><summary>Detalle</summary>@foreach($evolucion as $r)<div class="rv-detail-row"><span>{{ ucfirst($fecha($r['mes'])->translatedFormat('F')) }}<small>{{ $r['sin_clasificar'] ? 'Parcial · ' : '' }}Ingresos <span class="ds-money ds-money--row">{{ $dinero($r['ingresos']) }}</span> · Egresos <span class="ds-money ds-money--row">{{ $dinero($r['egresos']) }}</span>@if($r['sin_clasificar']) · {{ $r['sin_clasificar'] }} sin clasificar @endif</small></span><strong><span class="ds-money ds-money--row">{{ $dinero($r['resultado']) }}</span></strong></div>@endforeach</details></section>
    <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--success"></span><h3 class="alumno-nombre">Cambio en el resultado</h3></div><div class="rv-body"><p class="rv-note" style="margin-bottom:12px">{{ $ultimo ? ucfirst($fecha($ultimo['mes'])->translatedFormat('F')) : '' }}{{ $anterior ? ' vs '.$fecha($anterior['mes'])->translatedFormat('F') : '' }}</p>@if($cambios->isNotEmpty())<div class="rv-change-chart"><canvas data-rv-impact="{{ json_encode($cambios) }}" role="img" aria-label="Aporte de cada rubro al cambio del resultado"></canvas></div><ul class="rv-sr-only">@foreach($cambios as $cambio)<li>{{ $cambio['nombre'] }}: {{ $cambio['importe'] >= 0 ? '+' : '−' }}<span class="ds-money ds-money--row">{{ $dinero(abs($cambio['importe'])) }}</span></li>@endforeach</ul><div class="rv-impact"><span>Variación total</span><strong class="{{ $cambios->sum('importe') >= 0 ? 'rv-good' : 'rv-bad' }}">{{ $cambios->sum('importe') >= 0 ? '+' : '−' }}<span class="ds-money ds-money--row">{{ $dinero(abs($cambios->sum('importe'))) }}</span></strong></div>@else<p class="rv-note">{{ $comparacionDisponible ? 'Sin cambios por rubro' : 'Sin comparación disponible' }}</p>@endif</div></section>
</div>
<div class="rv-section"><h2>{{ ucfirst($fecha($reporte['mes'])->translatedFormat('F')) }}{{ $reporte['mes'] === today()->format('Y-m') ? ' en curso' : ' cerrado' }}</h2><span class="rv-note">Hasta {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</span></div>
@if($reporte['sin_clasificar'])<p class="rv-note" style="color:var(--color-warning);margin-bottom:12px">{{ $reporte['sin_clasificar'] }} movimientos sin clasificar · importes parciales</p>@endif
<div class="rv-current">@foreach([['Ingresos','ingresos','success'],['Egresos','egresos','danger'],['Resultado','resultado','info']] as [$label,$clave,$tono])<div class="rv-current-item"><span>{{ $label }}</span><strong style="color:var(--color-{{ $tono }})"><span class="ds-money ds-money--total">{{ $dinero($reporte[$clave]) }}</span></strong></div>@endforeach</div>
<div class="rv-pair">
@foreach([['Origen de ingresos','INGRESO','ingresos','success'],['Destino de gastos','EGRESO','egresos','danger']] as [$titulo,$tipo,$clave,$tono])
@php($rubros = $desglosar($reporte,$tipo))
<section class="alumno-card rv-panel" id="{{ $clave }}"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $tono }}"></span><h3 class="alumno-nombre">{{ $titulo }}</h3></div><div class="rv-body">
@if($rubros->isEmpty())
<div class="rv-chart" style="display:flex;align-items:center;justify-content:center"><p class="rv-note">{{ $tipo === 'INGRESO' ? 'Sin ingresos registrados' : 'Sin egresos registrados' }}</p></div>
@elseif($tipo === 'INGRESO' && $reporte['ingresos'] > 0 && $rubros->every(fn ($r) => $r['importe'] >= 0))
<div class="rv-donut-grid"><div class="rv-donut"><canvas data-rv-income="{{ json_encode($rubros->map(fn ($r) => ['nombre'=>$r['nombre'],'importe'=>$r['importe']])) }}" role="img" aria-label="Distribución de ingresos por rubro"></canvas><div class="rv-donut-center"><strong>{{ $dineroGrafico($reporte['ingresos']) }}</strong><span class="rv-note">Ingresos</span></div></div><div class="rv-breakdown">@foreach($rubros as $rubro)<div class="rv-breakdown-row"><i class="rv-swatch" style="background:var(--color-{{ $paleta[$loop->index % count($paleta)] }})"></i><div class="rv-breakdown-info"><span class="rv-breakdown-title">{{ $rubro['nombre'] }}</span><strong><span class="ds-money ds-money--summary">{{ $dinero($rubro['importe']) }}</span></strong><span class="rv-row-share">{{ number_format($rubro['importe']*100/$reporte['ingresos'],1,',','.') }}%</span></div></div>@endforeach</div></div>
@else
<div class="rv-chart"><canvas data-rv-bars="{{ json_encode($rubros->map(fn ($r) => ['nombre'=>$r['nombre'],'importe'=>$r['importe']])) }}" data-tone="{{ $tono }}" role="img" aria-label="Importes netos por rubro"></canvas></div>
@endif
</div><details class="rv-detail"><summary>Detalle</summary>
@foreach([true => 'Confirmado', false => 'Por validar'] as $confirmado => $label)
<div class="rv-detail-row"><span>{{ $label }}</span><strong><span class="ds-money ds-money--row">{{ $dinero(collect($rubros)->flatMap(fn ($r) => $r['filas'])->where('confirmado', (bool) $confirmado)->sum('centavos')*($tipo === 'INGRESO' ? 1 : -1)) }}</span></strong></div>
@endforeach
@foreach($rubros as $rubro)<details style="margin-top:12px"><summary>{{ $rubro['nombre'] }} · <span class="ds-money ds-money--summary">{{ $dinero($rubro['importe']) }}</span></summary>@foreach($rubro['filas'] as $f)<div class="rv-detail-row"><span>{{ \Carbon\CarbonImmutable::parse($f['fecha'])->format('d/m') }} · {{ $cajas[$f['tipo_caja_id']] ?? 'Caja' }}<small>{{ $f['confirmado'] ? 'Confirmado' : 'Por validar' }}</small></span><strong><span class="ds-money ds-money--row">{{ $dinero($f['centavos']*($tipo === 'INGRESO' ? 1 : -1)) }}</span></strong></div>@endforeach</details>@endforeach</details></section>
@endforeach
</div>
<div class="rv-section"><h2>Situación al {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</h2><span class="rv-note">Saldos acumulados</span></div>
<div class="rv-current">@foreach([['Disponible'.($reporte['deporte_id'] ? ' · todo el negocio' : ''),$reporte['disponible']],['Por cobrar',$reporte['deuda']['total']],['Por pagar',$reporte['por_pagar']['total']]] as [$label,$importe])<div class="rv-current-item"><span>{{ $label }}</span><strong><span class="ds-money ds-money--total">{{ $dinero($importe) }}</span></strong></div>@endforeach</div>
<details class="filtros-card rv-detail" style="margin-top:12px"><summary>Detalle de cajas y aportes</summary>@foreach($reporte['cajas'] ?? [] as $caja)<div class="rv-detail-row"><span>{{ $caja['nombre'] }}<small>Confirmado <span class="ds-money ds-money--row">{{ $dinero($caja['confirmado']) }}</span> · Por validar <span class="ds-money ds-money--row">{{ $dinero($caja['pendiente']) }}</span></small></span><strong><span class="ds-money ds-money--row">{{ $dinero($caja['total']) }}</span></strong></div>@endforeach<div class="rv-detail-row"><span>Aportes / retiros<small>Fuera del resultado · todo el negocio</small></span><strong><span class="ds-money ds-money--row">{{ $dinero($reporte['aportes']) }}</span> / <span class="ds-money ds-money--row">{{ $dinero($reporte['retiros']) }}</span></strong></div><div class="rv-detail-row"><span>Resultado global<small>Movimientos del mes, incluidos aportes, retiros y traspasos</small></span><strong><span class="ds-money ds-money--row">{{ $dinero($reporte['resultado_global']) }}</span></strong></div></details>
<details class="filtros-card rv-detail" id="deuda" style="margin-top:12px"><summary>Por cobrar · <span class="ds-money ds-money--summary">{{ $dinero($reporte['deuda']['total']) }}</span></summary>
@if(!$reporte['deuda']['disponible'])<p class="rv-note">Sin historial para este corte. Disponible desde {{ $reporte['historial_desde'] ?? 'su preparación' }}.</p>@else
@foreach(['mes' => 'Del mes', 'anteriores' => 'Meses anteriores'] as $clave => $label)
<details style="margin-top:12px"><summary>{{ $label }} · <span class="ds-money ds-money--summary">{{ $dinero($reporte['deuda'][$clave]) }}</span></summary>
@foreach(collect($reporte['deuda']['filas'])->filter(fn ($f) => $clave === 'mes' ? $f['periodo'] === $reporte['mes'] : $f['periodo'] < $reporte['mes']) as $fila)
<div class="rv-detail-row"><span>{{ isset($alumnos[$fila['persona_id']]) ? $alumnos[$fila['persona_id']]->apellido.', '.$alumnos[$fila['persona_id']]->nombre : 'Alumno #'.$fila['persona_id'] }}<small>{{ $fila['concepto'] ?? ucfirst($fecha($fila['periodo'])->translatedFormat('F Y')) }}</small></span><strong><span class="ds-money ds-money--row">{{ $dinero($fila['centavos']) }}</span></strong></div>
@endforeach
</details>
@endforeach
@endif
</details>
<details class="filtros-card rv-detail" id="profesores" style="margin-top:12px"><summary>Por pagar · <span class="ds-money ds-money--summary">{{ $dinero($reporte['por_pagar']['total']) }}</span></summary>
@if(!$reporte['por_pagar']['disponible'])<p class="rv-note">Sin historial para este corte. Disponible desde {{ $reporte['historial_desde'] ?? 'su preparación' }}.</p>@else
@foreach($reporte['por_pagar']['filas'] as $fila)<div class="rv-detail-row"><span>{{ isset($profesores[$fila['persona_id']]) ? $profesores[$fila['persona_id']]->apellido.', '.$profesores[$fila['persona_id']]->nombre : 'Profesor #'.$fila['persona_id'] }}<small>{{ ucfirst($fecha($fila['periodo'])->translatedFormat('F Y')) }} · Liquidación #{{ $fila['id'] }}</small></span><strong><span class="ds-money ds-money--row">{{ $dinero($fila['centavos']) }}</span></strong></div>@endforeach
@endif
</details>
@if($reporte['deporte_id'])
<details class="filtros-card rv-detail" style="margin-top:12px"><summary>Gastos del club · <span class="ds-money ds-money--summary">{{ $dinero($reporte['gastos_club']) }}</span></summary><p class="rv-note">Gastos generales separados: no descontados del deporte. Disponible y cajas corresponden a todo el negocio.</p></details>
@endif
@if($reporte['sin_clasificar'])
<details class="filtros-card rv-detail" style="margin-top:12px"><summary>Sin clasificar · {{ $reporte['sin_clasificar'] }}</summary><p class="rv-note">El resultado es parcial: falta clasificar movimientos del negocio.</p></details>
@endif
@include('cobranza._deuda-inactivos')
<div class="rv-footer"><span>Cobros y pagos · confirmado + por validar</span><span><a href="{{ route('web.reportes.alumnos',array_filter(['mes'=>$reporte['mes'],'deporte_id'=>$reporte['deporte_id']],fn ($v) => $v !== null)) }}">Alumnos</a> · <a href="{{ route('web.reportes.sueldos',array_filter(['mes'=>$reporte['mes'],'deporte_id'=>$reporte['deporte_id']],fn ($v) => $v !== null)) }}">Sueldos</a></span></div>
</div>
@endsection
@push('scripts')
@vite('resources/js/reportes-visual.js')
@endpush
