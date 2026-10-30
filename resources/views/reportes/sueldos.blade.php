@extends('layouts.app')
@section('title', 'Reporte de sueldos – Wings')
@section('module-title', 'Reporte de sueldos')
@section('ayuda-slug', 'reportes-sueldos')
@section('content')
@vite(['resources/css/reportes-visual.css', 'resources/css/reportes-analiticos.css'])
@php
    $title = 'Reporte de sueldos';
    $plata = fn ($n) => $n === null ? 'Sin historial' : \App\Support\FormatoImporte::pesos($n/100);
    $numero = fn ($n) => $n === null ? 'Sin historial' : number_format($n,0,',','.');
    $fecha = fn ($m) => \Carbon\CarbonImmutable::createFromFormat('!Y-m',$m)->locale('es');
    $contexto = array_filter(['mes'=>$reporte['mes'],'deporte_id'=>$reporte['deporte_id']],fn ($v) => $v !== null);
    $grafico = fn ($tipo,$etiquetas,$series,$horizontal=false) => json_encode(['type'=>$tipo,'labels'=>$etiquetas,'series'=>$series,'horizontal'=>$horizontal]);
    $ejes = array_map(fn ($r) => $fecha($r['mes'])->translatedFormat('M'),$evolucion);
    $enPesos = fn ($filas,$campo) => array_map(fn ($f) => $f[$campo] === null ? null : $f[$campo]/100,$filas);
@endphp
<div class="reporte-visual reporte-analitico">
    <div class="stats-bar mb-4"><div class="stats-info"><strong>Reportes</strong></div><a class="ds-btn ds-btn--secondary" href="{{ route('web.reportes.index',$contexto) }}">Volver</a></div>
    <div class="ra-subnav"><a href="{{ route('web.reportes.index',$contexto) }}">Ingresos y egresos</a><a href="{{ route('web.reportes.alumnos',$contexto) }}">Alumnos</a><strong aria-current="page">Sueldos</strong></div>
    @if($errors->any())<p class="rv-note" style="color:var(--color-danger)">{{ $errors->first() }}</p>@endif
    <form class="filtros-card ra-filters" method="GET" action="{{ route('web.reportes.sueldos') }}">
        <select name="mes" class="filtros-control" aria-label="Mes">@foreach($meses as $m)<option value="{{ $m }}" @selected($m === $reporte['mes'])>{{ ucfirst($fecha($m)->translatedFormat('F Y')) }}</option>@endforeach</select>
        <select name="deporte_id" class="filtros-control" aria-label="Deporte"><option value="">Todos los deportes</option>@foreach($deportes as $d)<option value="{{ $d->id }}" @selected($d->id === $reporte['deporte_id'])>{{ $d->nombre }}</option>@endforeach</select>
        <button class="ds-btn ds-btn--secondary" type="submit">Filtrar</button>
    </form>
    <div class="rv-section"><h2>{{ ucfirst($fecha($reporte['mes'])->translatedFormat('F Y')) }}</h2><span class="rv-note">Costo del mes · hasta {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</span></div>
    <div class="rv-kpis">
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-warning)"></i>Costo de profesores</div><div class="rv-amount"><span class="ds-money ds-money--total">{{ $plata($reporte['costo']) }}</span></div><div class="ra-mini">Aunque se pague después</div></section>
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-success)"></i>Cuotas atribuidas</div><div class="rv-amount"><span class="ds-money ds-money--total">{{ $plata($reporte['ingreso']) }}</span></div><div class="ra-mini">Importe real del mismo mes</div></section>
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-info)"></i>Sueldos / cuotas</div><div class="ra-count">{{ $reporte['porcentaje_costo'] === null ? 'Sin datos' : number_format($reporte['porcentaje_costo'],1,',','.').'%' }}</div><div class="ra-mini">Parte de las cuotas destinada a profesores</div></section>
        <section class="rv-kpi"><div class="rv-kpi-label"><i class="rv-swatch" style="background:var(--color-danger)"></i>Costo por asistencia</div><div class="rv-amount"><span class="ds-money ds-money--total">{{ $reporte['costo_por_asistencia'] === null ? 'Sin datos' : $plata($reporte['costo_por_asistencia']) }}</span></div><div class="ra-mini">{{ $numero($reporte['asistencias']) }} asistencias del mes</div></section>
    </div>
    <div class="rv-section"><h2>Pagos de operativas</h2><span class="rv-note">Registrados en Cashflow durante el mes</span></div>
    <section class="alumno-card rv-panel" id="pagos-operativas">
        <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Total pagado</h3></div>
        <div class="rv-body">
            <div class="rv-amount"><span class="ds-money ds-money--total">{{ $plata($reporte['pagos_operativas']['total_centavos']) }}</span></div>
            <p class="ra-mini">Gasto del club, sin reparto por deporte. Los indicadores de profesores corresponden a sus clases.</p>
            @forelse($reporte['pagos_operativas']['filas'] as $operativa)
                <div class="rv-detail-row">
                    <span>{{ $operativa['nombre'] }}<small>{{ $operativa['movimientos'] }} {{ $operativa['movimientos'] === 1 ? 'pago' : 'pagos' }}</small></span>
                    <strong><span class="ds-money ds-money--row">{{ $plata($operativa['centavos']) }}</span></strong>
                </div>
            @empty
                <p class="rv-note">Sin pagos de operativas en este mes</p>
            @endforelse
        </div>
    </section>
    <div class="rv-main-grid">
        <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Evolución: cuotas y sueldos</h3></div><div class="rv-body"><div class="rv-legend"><span><i class="rv-swatch" style="background:var(--color-success)"></i>Cuotas atribuidas</span><span><i class="rv-swatch" style="background:var(--color-warning)"></i>Costo de profesores</span></div><div class="rv-chart"><canvas data-ra-chart="{{ $grafico('line',$ejes,[['label'=>'Cuotas ($)','values'=>$enPesos($evolucion,'ingreso'),'tone'=>'success'],['label'=>'Sueldos ($)','values'=>$enPesos($evolucion,'costo'),'tone'=>'warning']]) }}" role="img" aria-label="Evolución de cuotas atribuidas y costo de profesores"></canvas></div></div><details class="rv-detail"><summary>Detalle</summary>@foreach($evolucion as $r)<div class="rv-detail-row"><span>{{ ucfirst($fecha($r['mes'])->translatedFormat('F Y')) }}<small>Cuotas <span class="ds-money ds-money--row">{{ $plata($r['ingreso']) }}</span></small></span><strong><span class="ds-money ds-money--row">{{ $plata($r['costo']) }}</span></strong></div>@endforeach</details></section>
        <section class="alumno-card rv-panel"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Por deporte</h3></div><div class="rv-body">@if(count($reporte['deportes']))<div class="rv-chart"><canvas data-ra-chart="{{ $grafico('bar',array_column($reporte['deportes'],'nombre'),[['label'=>'Cuotas ($)','values'=>$enPesos($reporte['deportes'],'ingreso'),'tone'=>'success'],['label'=>'Sueldos ($)','values'=>$enPesos($reporte['deportes'],'costo'),'tone'=>'warning']],true) }}" role="img" aria-label="Cuotas y sueldos por deporte"></canvas></div>@else<div class="ra-empty">Sin clases ni liquidaciones en este mes</div>@endif</div><details class="rv-detail"><summary>Detalle</summary>@foreach($reporte['deportes'] as $d)<div class="rv-detail-row"><span>{{ $d['nombre'] }}<small>Cuotas <span class="ds-money ds-money--row">{{ $plata($d['ingreso']) }}</span></small></span><strong><span class="ds-money ds-money--row">{{ $plata($d['costo']) }}</span></strong></div>@endforeach</details></section>
    </div>
    <div class="rv-section"><h2>Por profesor</h2><span class="rv-note">Cada asistencia cuenta</span></div>
    <div class="rv-pair">
    @forelse($reporte['profesores'] as $p)
    <section class="alumno-card rv-panel mb-3"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">{{ $p['nombre'] }}</h3></div><div class="rv-body">
        <div class="ra-duo"><div><strong><span class="ds-money ds-money--summary">{{ $plata($p['costo']) }}</span></strong><span>Costo · {{ $p['deporte'] }}</span></div><div><strong><span class="ds-money ds-money--summary">{{ $plata($p['ingreso']) }}</span></strong><span>Cuotas atribuidas</span></div><div><strong>{{ $p['porcentaje_costo'] === null ? 'Sin datos' : number_format($p['porcentaje_costo'],1,',','.').'%' }}</strong><span>Sueldos / cuotas</span></div><div><strong><span class="ds-money ds-money--summary">{{ $p['costo_por_asistencia'] === null ? 'Sin datos' : $plata($p['costo_por_asistencia']) }}</span></strong><span>Por asistencia</span></div></div>
        <div class="ra-duo mt-3"><div><strong>{{ $numero($p['alumnos_con_pago']) }}</strong><span>Alumnos con pagos registrados</span></div><div><strong>{{ $numero($p['asistencias_sin_pago']) }}</strong><span>Asistencias sin pago de cuota</span></div><div><strong>{{ $numero($p['asistencias']) }}</strong><span>Asistencias totales</span></div></div>
        <p class="rv-note mt-3">{{ $p['base'] }}</p>
    </div></section>
    @empty<div class="filtros-card ra-empty">Sin clases ni liquidaciones en este mes</div>@endforelse
    </div>
    <details class="filtros-card rv-detail"><summary>Cómo se calcula</summary><p class="ra-mini">Las cuotas reales del mes se reparten entre profesores según las asistencias. La comisión usa cuotas cobradas; un pago parcial cuenta como cobro. El monto final ajustado por ADMIN se conserva en la liquidación.</p><p class="ra-mini">Cuotas sin asistencias para atribuir: <span class="ds-money ds-money--row">{{ $plata($reporte['sin_asistencia']) }}</span>. Los meses sin respaldo muestran Sin historial.</p><p class="ra-mini">Cuotas, tarifas, cobros y liquidaciones se consultan al corte del mes. El reparto y los estimados usan las asistencias y horarios registrados actualmente: corregir una clase puede cambiar esos indicadores. Una liquidación cerrada conserva su importe.</p></details>
    <div class="rv-footer"><span>Comparación económica · no determina ganancias del club</span><span>Datos faltantes no se dibujan como cero</span></div>
</div>
@endsection
@push('scripts')
@vite('resources/js/reportes-analiticos.js')
@endpush
