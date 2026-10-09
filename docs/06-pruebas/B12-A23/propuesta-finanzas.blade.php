@extends('layouts.app')
@section('title', 'Ingresos y egresos – Wings')
@section('module-title', 'Ingresos y egresos')
@section('content')
@php
    $title = 'Ingresos y egresos';
    $dinero = fn ($valor) => $valor === null ? 'Sin datos' : '$'.number_format($valor / 100, 0, ',', '.');
    $fecha = fn ($mes) => \Carbon\CarbonImmutable::createFromFormat('!Y-m', $mes)->locale('es');
    $ultimos = collect($evolucion)->values();
    $ultimo = $ultimos->last();
    $anterior = $ultimos->count() > 1 ? $ultimos[$ultimos->count() - 2] : null;
    if ($anterior && $anterior['sin_clasificar'] !== 0) $anterior = null;
    $porcentaje = fn ($actual, $previo) => $previo > 0 ? number_format(($actual - $previo) * 100 / $previo, 1, ',', '.').'%' : 'Sin comparación';
    $desglosar = fn ($r, $tipo) => collect($r['filas'])->filter(fn ($f) => $f['clasificacion'] === 'NEGOCIO' && $f['tipo'] === $tipo)
        ->groupBy('concepto')->map(fn ($filas, $concepto) => ['concepto' => $concepto, 'importe' => $filas->sum('centavos') * ($tipo === 'INGRESO' ? 1 : -1), 'filas' => $filas->all()])->sortByDesc('importe');
    $series = array_map(fn ($r) => ['etiqueta' => ucfirst($fecha($r['mes'])->translatedFormat('M')), 'ingresos' => $r['sin_clasificar'] ? null : $r['ingresos'], 'egresos' => $r['sin_clasificar'] ? null : $r['egresos']], $evolucion);
    $nombresCaja = collect($reporte['cajas'] ?? [])->pluck('nombre', 'id');
@endphp
<div class="stats-bar mb-4"><div class="stats-info"><strong>Reportes</strong> · Ingresos y egresos</div><a class="ds-btn ds-btn--secondary" href="inicio-aplicado.html">Volver</a></div>
<div class="filtros-card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:20px">
    <div style="flex:1;min-width:130px"><label style="display:block;font-size:.75rem;color:var(--color-text-muted);margin-bottom:6px">Mes</label><select class="ds-input" style="width:100%"><option>{{ ucfirst($fecha($reporte['mes'])->translatedFormat('F Y')) }}</option></select></div>
    <div style="flex:1;min-width:130px"><label style="display:block;font-size:.75rem;color:var(--color-text-muted);margin-bottom:6px">Deporte</label><select class="ds-input" style="width:100%"><option>Todo el negocio</option><option>Patín</option><option>Fútbol</option></select></div>
    <button type="button" class="ds-btn ds-btn--secondary">Filtrar</button>
</div>
@if($ultimo && $ultimo['sin_clasificar'] === 0)
<section class="alumno-card" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Qué cambió en {{ $fecha($ultimo['mes'])->translatedFormat('F') }}</h3></div>
    <div style="padding:0 16px 16px">
        <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:14px">Último mes cerrado{{ $anterior ? ' · comparado con '.$fecha($anterior['mes'])->translatedFormat('F') : '' }}</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:18px">
            <div><p style="font-size:.75rem;color:var(--color-text-muted)">Ingresos</p><strong style="display:block;font-size:1.5rem;color:var(--color-success)">{{ $dinero($ultimo['ingresos']) }}</strong>@if($anterior)<p style="font-size:.8rem;color:var(--color-{{ $ultimo['ingresos'] >= $anterior['ingresos'] ? 'success' : 'danger' }});margin-top:5px">{{ $ultimo['ingresos'] >= $anterior['ingresos'] ? '↑' : '↓' }} {{ $porcentaje($ultimo['ingresos'],$anterior['ingresos']) }}</p>@endif</div>
            <div><p style="font-size:.75rem;color:var(--color-text-muted)">Egresos</p><strong style="display:block;font-size:1.5rem;color:var(--color-danger)">{{ $dinero($ultimo['egresos']) }}</strong>@if($anterior)<p style="font-size:.8rem;color:var(--color-text-muted);margin-top:5px">{{ $ultimo['egresos'] === $anterior['egresos'] ? 'Se mantuvieron' : $porcentaje($ultimo['egresos'],$anterior['egresos']) }}</p>@endif</div>
            <div><p style="font-size:.75rem;color:var(--color-text-muted)">Resultado del mes</p><strong style="display:block;font-size:1.5rem">{{ $dinero($ultimo['resultado']) }}</strong>@if($anterior)<p style="font-size:.8rem;color:var(--color-text-muted);margin-top:5px">{{ $dinero($ultimo['resultado']-$anterior['resultado']) }} de diferencia</p>@endif</div>
        </div>
        @if($anterior)
            @php
                $rubrosAntes = $desglosar($anterior,'INGRESO');
                $rubrosAhora = $desglosar($ultimo,'INGRESO');
                $cambios = $rubrosAhora->keys()->merge($rubrosAntes->keys())->unique()->map(fn ($nombre) => ['nombre' => $nombre,
                    'diferencia' => ($rubrosAhora[$nombre]['importe'] ?? 0)-($rubrosAntes[$nombre]['importe'] ?? 0)])
                    ->filter(fn ($r) => $r['diferencia'] !== 0)->sortByDesc(fn ($r) => abs($r['diferencia']));
            @endphp
            @foreach($cambios->take(2) as $cambio)<p style="font-size:.82rem;margin-top:15px;padding-top:12px;border-top:1px solid var(--color-border)"><strong>{{ $cambio['nombre'] }}</strong> aportó {{ $dinero(abs($cambio['diferencia'])) }} {{ $cambio['diferencia'] > 0 ? 'más' : 'menos' }} que {{ $fecha($anterior['mes'])->translatedFormat('F') }}.</p>@endforeach
        @endif
    </div>
</section>
@endif
<section class="alumno-card" style="margin-bottom:20px">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--neutral"></span><h3 class="alumno-nombre">Cómo evoluciona la plata del negocio</h3></div>
    <div style="padding:0 16px 16px">
        <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:16px">Meses cerrados · {{ $evolucion ? $fecha($evolucion[0]['mes'])->translatedFormat('F') : '' }} a {{ $ultimo ? $fecha($ultimo['mes'])->translatedFormat('F Y') : '' }}</p>
        <div style="display:flex;gap:20px;font-size:.82rem;margin-bottom:12px"><span style="display:flex;align-items:center;gap:8px"><i style="display:block;width:20px;height:3px;background:var(--color-success)"></i>Ingresos</span><span style="display:flex;align-items:center;gap:8px"><i style="display:block;width:20px;height:3px;background:var(--color-danger)"></i>Egresos</span></div>
        <div style="height:280px;position:relative"><canvas data-evolucion-financiera="{{ json_encode($series) }}" role="img" aria-label="Evolución mensual de ingresos y egresos. Importes exactos debajo."></canvas></div>
        <p style="font-size:.75rem;color:var(--color-text-muted);margin-top:14px">Pasá por un punto para ver los importes. {{ ucfirst($fecha($reporte['mes'])->translatedFormat('F')) }} está en curso y se muestra aparte.</p>
    </div>
    <details style="padding:12px 16px;border-top:1px solid var(--color-border)"><summary style="font-size:.8rem;cursor:pointer">Ver importes por mes</summary>@foreach($evolucion as $r)<p style="font-size:.8rem;margin-top:10px">{{ ucfirst($fecha($r['mes'])->translatedFormat('F')) }} · Ingresos {{ $dinero($r['ingresos']) }} · Egresos {{ $dinero($r['egresos']) }} · Resultado {{ $dinero($r['resultado']) }}</p>@endforeach</details>
</section>
<div class="stats-bar mb-4"><div class="stats-info"><strong>{{ ucfirst($fecha($reporte['mes'])->translatedFormat('F')) }} en curso</strong> · hasta {{ \Carbon\CarbonImmutable::parse($reporte['fecha_corte'])->format('d/m') }}</div></div>
<p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:16px">Ingresos {{ $dinero($reporte['ingresos']) }} · Egresos {{ $dinero($reporte['egresos']) }} · Resultado {{ $dinero($reporte['resultado']) }}</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-bottom:20px">
@foreach([['De dónde vienen los ingresos','INGRESO','success','ingresos'],['En qué se gasta','EGRESO','danger','egresos']] as [$nombre,$tipo,$tono,$ancla])
<section class="alumno-card" id="{{ $ancla }}">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $tono }}"></span><h3 class="alumno-nombre">{{ $nombre }}</h3></div>
    @foreach($desglosar($reporte,$tipo) as $rubro)
    @php($total = $tipo === 'INGRESO' ? $reporte['ingresos'] : $reporte['egresos'])
    <details style="padding:14px 16px;border-top:1px solid var(--color-border)">
        <summary style="cursor:pointer;font-size:.85rem"><strong>{{ $rubro['concepto'] }}</strong><span style="display:block;margin:6px 0 0 16px;font-weight:600">{{ $dinero($rubro['importe']) }} <span style="font-weight:400;color:var(--color-text-muted)">· {{ $total ? number_format($rubro['importe']*100/$total,1,',','.') : '—' }}% del total</span></span></summary>
        @foreach($rubro['filas'] as $f)<p style="font-size:.76rem;margin-top:12px;padding-top:10px;border-top:1px solid var(--color-border)">{{ \Carbon\CarbonImmutable::parse($f['fecha'])->format('d/m') }} · {{ $nombresCaja[$f['tipo_caja_id']] ?? 'Caja' }} · {{ $f['confirmado'] ? 'Confirmado' : 'Por validar' }}<strong style="display:block;margin-top:4px">{{ $dinero($f['centavos'] * ($tipo === 'INGRESO' ? 1 : -1)) }}</strong></p>@endforeach
    </details>
    @endforeach
</section>
@endforeach
</div>
<section class="alumno-card" style="margin-bottom:20px"><div class="alumno-card-header"><span class="alumno-dot alumno-dot--info"></span><h3 class="alumno-nombre">Disponible y compromisos</h3></div><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:18px;padding:0 16px 16px"><div><p style="font-size:.75rem;color:var(--color-text-muted)">Disponible acumulado</p><strong>{{ $dinero($reporte['disponible']) }}</strong></div><div id="deuda"><p style="font-size:.75rem;color:var(--color-text-muted)">Cuotas por cobrar</p><strong>{{ $dinero($reporte['deuda']['total']) }}</strong></div><div id="profesores"><p style="font-size:.75rem;color:var(--color-text-muted)">Profesores por pagar</p><strong>{{ $dinero($reporte['por_pagar']['total']) }}</strong></div></div>
    <details style="padding:12px 16px;border-top:1px solid var(--color-border)"><summary style="font-size:.8rem;cursor:pointer">Ver cajas y aportes</summary>@foreach($reporte['cajas'] ?? [] as $caja)<p style="font-size:.8rem;margin-top:10px">{{ $caja['nombre'] }} · Confirmado {{ $dinero($caja['confirmado']) }} · Por validar {{ $dinero($caja['pendiente']) }}</p>@endforeach<p style="font-size:.8rem;margin-top:12px">Aportes {{ $dinero($reporte['aportes']) }} · Retiros {{ $dinero($reporte['retiros']) }}. Cambian el disponible; se excluyen del resultado del negocio.</p></details>
</section>
<p style="font-size:.76rem;color:var(--color-text-muted)">Fecha real de cobro o pago · confirmado y por validar · gastos del club descontados una vez. El análisis de sueldos tendrá su propia pantalla.</p>
@endsection
@push('scripts')
@vite('resources/js/reportes.js')
@endpush
