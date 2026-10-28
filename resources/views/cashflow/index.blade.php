@extends('layouts.ds-app')

@section('title', 'Cashflow histórico – Wings')
@section('module-title', 'Cashflow')
@section('ayuda-slug', 'corregir-movimiento-caja-validada')

@section('content')

@php
$mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$balance = (float)$totalIngresos - (float)$totalEgresos;
$balanceColor = $balance >= 0 ? 'var(--color-success)' : 'var(--color-danger)';
@endphp

{{-- Filtros --}}
<form method="GET" action="{{ route('web.cashflow.index') }}" id="filtros-form">
<div class="filtros-card mb-4">
    <div class="cashflow-periodos-grid">
        <div>
            <label for="periodo" style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Período</label>
            <select id="periodo" name="periodo" class="w-full px-3 py-2 text-sm wings-input" style="min-width:0; box-sizing:border-box;" data-enviar-al-cambiar>
                @foreach(['dia'=>'Día','semana'=>'Semana','mes'=>'Mes','anio'=>'Año'] as $valor=>$nombre)
                    <option value="{{ $valor }}" @selected($modo === $valor)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        @if(in_array($modo, ['dia','semana']))
        <div>
            <label for="fecha" style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">{{ $modo === 'semana' ? 'Fecha de la semana' : 'Fecha' }}</label>
            <input id="fecha" name="fecha" type="date" value="{{ $fechaReferencia->toDateString() }}" class="w-full px-3 py-2 text-sm wings-input" style="min-width:0; box-sizing:border-box;" data-enviar-al-cambiar>
        </div>
        @else
        <input type="hidden" name="fecha" value="{{ $fechaReferencia->toDateString() }}">
        <div>
            <label for="anio" style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Año</label>
            <select id="anio" name="anio" class="w-full px-3 py-2 text-sm wings-input" style="min-width:0; box-sizing:border-box;" data-enviar-al-cambiar>
                @foreach($aniosDisponibles as $a)
                    <option value="{{ $a }}" @selected($anio == $a)>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        @if($modo === 'mes')
        <div>
            <label for="mes" style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Mes</label>
            <select id="mes" name="mes" class="w-full px-3 py-2 text-sm wings-input" style="min-width:0; box-sizing:border-box;" data-enviar-al-cambiar>
                @foreach($mesesNombres as $num=>$nombre)
                    @if($num > 0)<option value="{{ $num }}" @selected($mes == $num)>{{ $nombre }}</option>@endif
                @endforeach
            </select>
        </div>
        @endif
        @endif

        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Tipo de caja</label>
            <select name="tipo_caja_id" class="w-full px-3 py-2 text-sm wings-input" data-enviar-al-cambiar>
                <option value="">Todos</option>
                @foreach($tiposCaja as $tc)
                    <option value="{{ $tc->id }}" {{ $tipoCajaId == $tc->id ? 'selected' : '' }}>{{ $tc->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;">Tipo</label>
            <select name="tipo" class="w-full px-3 py-2 text-sm wings-input" data-enviar-al-cambiar>
                <option value="">Todos</option>
                <option value="INGRESO" {{ $tipo === 'INGRESO' ? 'selected' : '' }}>Ingresos</option>
                <option value="EGRESO"  {{ $tipo === 'EGRESO'  ? 'selected' : '' }}>Egresos</option>
            </select>
        </div>

    </div>
    @if($tipoCajaId || $tipo)
        <div class="filtros-actions mt-3">
            <x-ds.button variant="secondary" :href="route('web.cashflow.index', ['periodo' => $modo, 'anio' => $anio, 'mes' => $mes, 'fecha' => $fechaReferencia->toDateString()])">Limpiar</x-ds.button>
        </div>
    @endif
</div>
</form>

<p class="flex flex-wrap items-center gap-1.5 text-sm text-wings-muted mb-3" id="cashflow-periodo">
    <svg class="info-icon" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
    <span>Período:</span>
    <strong>{{ $periodoTexto }}</strong>
</p>

<section class="alumno-card mb-4" aria-label="Saldos por medio">
    <div class="alumno-card-header">
        <span class="alumno-dot alumno-dot--info"></span>
        <h2 class="alumno-nombre">Saldos por medio</h2>
    </div>
    <p class="text-sm text-wings-muted mb-3">Al {{ today()->format('d/m/Y') }} · No dependen del período del listado.</p>
    @if($saldosPorMedio === null)
        <p class="text-sm text-wings-muted">Sin historial disponible.</p>
    @else
        @foreach($saldosPorMedio as $saldo)
            <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-wings-border">
                <span>{{ $tiposCaja->firstWhere('id', $saldo['id'])?->nombre ?? $saldo['nombre'] }}</span>
                <strong class="ds-money ds-money--total">{{ \App\Support\FormatoImporte::pesos($saldo['total'] / 100) }}</strong>
                @if($saldo['pendiente'] !== 0)
                    <p class="w-full text-sm text-wings-muted">Confirmado: {{ \App\Support\FormatoImporte::pesos($saldo['confirmado'] / 100) }} · En cajas sin validar: {{ \App\Support\FormatoImporte::pesos($saldo['pendiente'] / 100) }}</p>
                @endif
            </div>
        @endforeach
    @endif
</section>

{{-- Stats --}}
<div class="stats-bar mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3" id="cashflow-totales-periodo">
    <div class="stats-info" style="gap:10px 16px; display:flex; align-items:center; flex-wrap:wrap;">
        <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-success);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
            </svg>
            <strong style="color:var(--color-success);"><span class="ds-money ds-money--total">{{ \App\Support\FormatoImporte::pesos($totalIngresos) }}</span></strong>
            <span style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; color:var(--color-text-muted);">ingresos</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-danger);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
            </svg>
            <strong style="color:var(--color-danger);"><span class="ds-money ds-money--total">{{ \App\Support\FormatoImporte::pesos($totalEgresos) }}</span></strong>
            <span style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; color:var(--color-text-muted);">egresos</span>
        </span>

        <span class="inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $balanceColor }};">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 18h12l3-18H3z"/>
            </svg>
            <strong style="color:{{ $balanceColor }};"><span class="ds-money ds-money--total">{{ \App\Support\FormatoImporte::pesos($balance) }}</span></strong>
            <span style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; color:var(--color-text-muted);">resultado del período</span>
        </span>

    </div>

</div>

<div class="stats-bar mb-3" id="cashflow-acciones">
    <div class="stats-info">
        <strong>{{ $movimientos->total() }}</strong> movimiento{{ $movimientos->total() !== 1 ? 's' : '' }}
    </div>
    <div class="flex justify-end gap-2 acciones-celular-derecha">
        <x-ds.button variant="secondary" href="{{ route('web.cashflow.transferencia') }}">Transferir</x-ds.button>
        <x-ds.button variant="primary" href="{{ route('web.cashflow.movimiento') }}">Nuevo</x-ds.button>
    </div>
</div>

{{-- Tabla --}}
@if($movimientos->isEmpty())
<div class="empty-state" style="padding:2rem 0;">
    <p style="color:var(--color-text-muted); font-size:0.9rem;">Sin movimientos para los filtros seleccionados.</p>
</div>
@else
<div class="alumno-card cashflow-desktop-table" style="padding:0; overflow:hidden; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed; min-width:700px;">
        <colgroup>
            <col style="width:95px">
            <col style="width:35px">
            <col style="width:90px">
            <col style="width:210px">
            <col>
            <col class="col-importe" style="width:125px">
            <col style="width:145px">
            <col style="width:50px">
            <col style="width:112px">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Fecha</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">I/E</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Tipo caja</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Rubro — Subrubro</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Detalle</th>
                <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Monto</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Registró</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Val.</th>
                <th style="padding:8px; text-align:right; font-size:0.7rem; color:var(--color-text-muted); text-transform:uppercase;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            @php
                $tipoRubro = $mov->subrubro?->rubro?->tipo;
                $ieLabel   = $tipoRubro === 'INGRESO' ? 'I' : ($tipoRubro === 'EGRESO' ? 'E' : '–');
                $ieColor   = $tipoRubro === 'EGRESO' ? 'var(--color-danger)' : 'var(--color-text)';
                $rubroNom  = $mov->subrubro?->rubro?->nombre ?? '';
                $subNom    = $mov->subrubro?->nombre ?? '–';
                // El signo sale del importe, no del rubro: la devolución de un cobro
                // anulado vive en un subrubro de ingresos con importe negativo, y pintarla
                // por rubro la mostraba como si hubiera entrado plata otra vez.
                $saleDeLaCaja = (float) $mov->monto < 0;
                $montoColor = $saleDeLaCaja ? 'var(--color-danger)' : 'var(--color-success)';
                if ($saleDeLaCaja && $tipoRubro !== null) {
                    $ieLabel = 'E';
                    $ieColor = 'var(--color-danger)';
                } elseif ($tipoRubro !== null) {
                    $ieLabel = 'I';
                    $ieColor = 'var(--color-text)';
                }
            @endphp
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">
                    {{ $mov->fecha?->format('d/m/Y') ?? '–' }}
                </td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:{{ $ieColor }};">
                    {{ $ieLabel }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">
                    {{ $mov->tipoCaja?->abreviatura ?? $mov->tipoCaja?->nombre ?? '–' }}
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    @if($rubroNom)
                        <span style="color:var(--color-text-muted);">{{ $rubroNom }}</span> — {{ $subNom }}
                    @else
                        {{ $subNom }}
                    @endif
                </td>
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $mov->alumnoNombre ?? $mov->observaciones ?? '' }}">
                    {{ $mov->alumnoNombre ?? $mov->observaciones ?? '–' }}
                    <small style="display:block; margin-top:3px; font-size:0.7rem;">{{ $mov->origenCorreccion }}</small>
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; text-align:right; color:{{ $montoColor }};">
                    <span class="ds-money ds-money--row">{{ $saleDeLaCaja ? '−' : '' }}{{ \App\Support\FormatoImporte::pesos(abs((float)$mov->monto)) }}</span>
                </td>
                <td style="padding:8px 12px; font-size:0.75rem; color:var(--color-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $mov->quienRegistro ?? $mov->usuarioAdmin?->name ?? '' }}">
                    {{ $mov->quienRegistro ?? $mov->usuarioAdmin?->name ?? '–' }}
                </td>
                <td style="padding:8px 12px; text-align:center; font-size:0.75rem; font-weight:700; color:var(--color-text-muted);" title="{{ $mov->validadorNombre ?? '' }}">
                    {{ $mov->validadorIniciales ?? '' }}
                </td>
                <td style="padding:8px; text-align:right;">@include('cashflow._acciones', ['acciones' => $mov->accionesCorreccion])</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Vista móvil: tarjetas verticales compactas (sin scroll horizontal) --}}
<div class="alumno-card cashflow-mobile-cards" style="padding:0; overflow:hidden;">
    @foreach($movimientos as $mov)
    @php
        $tipoRubro = $mov->subrubro?->rubro?->tipo;
        $ieLabel   = $tipoRubro === 'INGRESO' ? 'I' : ($tipoRubro === 'EGRESO' ? 'E' : '–');
        $ieColor   = $tipoRubro === 'EGRESO' ? 'var(--color-danger)' : 'var(--color-text)';
        $rubroNom  = $mov->subrubro?->rubro?->nombre ?? '';
        $subNom    = $mov->subrubro?->nombre ?? '–';
        $saleDeLaCaja = (float) $mov->monto < 0;
        $montoColor = $saleDeLaCaja ? 'var(--color-danger)' : 'var(--color-success)';
        if ($saleDeLaCaja && $tipoRubro !== null) {
            $ieLabel = 'E';
            $ieColor = 'var(--color-danger)';
        } elseif ($tipoRubro !== null) {
            $ieLabel = 'I';
            $ieColor = 'var(--color-text)';
        }
        $tieneAlumno = !empty($mov->alumnoNombre);
        $quienReg = $mov->quienRegistro ?? $mov->usuarioAdmin?->name ?? '–';
        $cajaAbrev = $mov->tipoCaja?->abreviatura ?? $mov->tipoCaja?->nombre ?? '–';
    @endphp
    <div style="padding:12px 14px; {{ !$loop->last ? 'border-bottom:1px solid var(--color-border);' : '' }}">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:0.75rem; color:var(--color-text-muted);">{{ $mov->fecha?->format('d/m/Y') ?? '–' }}</span>
                <span style="font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:4px; background:var(--color-surface-alt); border:1px solid var(--color-border); color:var(--color-text);">{{ $cajaAbrev }}</span>
                <span style="font-size:0.7rem; font-weight:700; color:{{ $ieColor }};">{{ $ieLabel }}</span>
            </div>
            <span style="font-size:1rem; font-weight:800; color:{{ $montoColor }};">
                <span class="ds-money ds-money--row">{{ $saleDeLaCaja ? '−' : '' }}{{ \App\Support\FormatoImporte::pesos(abs((float)$mov->monto)) }}</span>
            </span>
        </div>
        <div style="font-size:0.85rem; font-weight:600; color:var(--color-text); margin-bottom:2px;">
            @if($rubroNom)
                <span style="color:var(--color-text-muted);">{{ $rubroNom }}</span> — {{ $subNom }}
            @else
                {{ $subNom }}
            @endif
        </div>
        @if($tieneAlumno)
        <div style="font-size:0.8rem; font-weight:600; color:var(--color-brand); margin-bottom:4px;">
            {{ $mov->alumnoNombre }}
        </div>
        @elseif(!empty($mov->observaciones))
        <div style="font-size:0.78rem; color:var(--color-text-muted); margin-bottom:4px;">
            {{ $mov->observaciones }}
        </div>
        @endif
        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.7rem; color:var(--color-text-muted);">
            <span>Registró: {{ $quienReg }}</span>
            @if(!empty($mov->validadorIniciales))
            <span style="font-weight:600; padding:1px 6px; border-radius:4px; background:var(--color-surface-alt); border:1px solid var(--color-border);" title="{{ $mov->validadorNombre ?? '' }}">Val: {{ $mov->validadorIniciales }}</span>
            @endif
        </div>
        <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end; gap:8px;">
            <small style="margin-right:auto; font-size:0.7rem; color:var(--color-text-muted);">{{ $mov->origenCorreccion }}</small>
            @include('cashflow._acciones', ['acciones' => $mov->accionesCorreccion])
        </div>
    </div>
    @endforeach
</div>

{{-- Paginación --}}
@if($movimientos->hasPages())
<div style="margin-top:1rem;">
    {{ $movimientos->links() }}
</div>
@endif
@endif

@if($enCajasEditables->isNotEmpty())
<section class="alumno-card mt-4" id="cashflow-cajas-editables">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Mostrador · sin validar</h3></div>
    <div class="card-body-alineado" style="padding:0 24px 16px; font-size:0.85rem;">
        <p class="mb-3" style="color:var(--color-text-muted);">Se corrigen por el recorrido de su caja. No suman a los totales de Cashflow de arriba.</p>
        @foreach($enCajasEditables as $movCaja)
        <div style="margin-top:12px; border-top:1px solid var(--color-border); padding-top:12px;">
            <p>{{ $movCaja->observaciones ?? $movCaja->subrubro?->nombre }}</p>
            <small style="color:var(--color-text-muted);">Caja {{ strtolower($movCaja->cajaOperativa->estado) }} · {{ $movCaja->usuario?->name }}</small>
            <p><strong><span class="ds-money ds-money--row">{{ \App\Support\FormatoImporte::pesos($movCaja->monto) }}</span></strong></p>
            <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end;">
                @php
                    $accionesCaja = [['nombre' => 'Ver', 'url' => route('web.caja.detalle', $movCaja->caja_operativa_id)]];
                    if (!$movCaja->subrubro?->es_reservado_sistema && !$movCaja->alumno_id) {
                        $accionesCaja[] = ['nombre' => 'Editar', 'url' => route('web.caja.movimientos.editar', [$movCaja->caja_operativa_id, $movCaja->id])];
                    }
                @endphp
                @include('cashflow._acciones', ['acciones' => $accionesCaja])
            </div>
        </div>
        @endforeach
        {{ $enCajasEditables->links() }}
    </div>
</section>
@endif

@if($liquidacionesPendientes->isNotEmpty())
<section class="alumno-card mt-4" id="cashflow-liquidaciones-pendientes">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--warning"></span><h3 class="alumno-nombre">Liquidaciones · sin pagar</h3></div>
    <div class="card-body-alineado" style="padding:0 24px 16px; font-size:0.85rem;">
        <p class="mb-3" style="color:var(--color-text-muted);">No son egresos pagados. Editar lleva a la liquidación y conserva sus reglas.</p>
        @foreach($liquidacionesPendientes as $liqPendiente)
        <div style="margin-top:12px; border-top:1px solid var(--color-border); padding-top:12px;">
            <p>{{ $liqPendiente->profesor?->apellido }}, {{ $liqPendiente->profesor?->nombre }} · {{ $mesesNombres[$liqPendiente->mes] }} {{ $liqPendiente->anio }}</p>
            <p><strong><span class="ds-money ds-money--row">{{ \App\Support\FormatoImporte::pesos($liqPendiente->monto_a_pagar) }}</span></strong></p>
            <div class="alumno-actions acciones-celular-derecha" style="justify-content:flex-end;">
                @include('cashflow._acciones', ['acciones' => [['nombre' => 'Editar', 'url' => route('web.liquidaciones.show', [$liqPendiente->id, 'editar' => 1])]]])
            </div>
        </div>
        @endforeach
        {{ $liquidacionesPendientes->links() }}
    </div>
</section>
@endif

<style>
.cashflow-periodos-grid {display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; align-items:end;}
.cashflow-periodos-grid > div {min-width:0;}
@media(min-width:1024px) {.cashflow-periodos-grid {grid-template-columns:repeat({{ $modo === 'mes' ? 5 : 4 }},minmax(0,1fr));}}

.cashflow-desktop-table { display: block; }
.cashflow-mobile-cards { display: none; }
@media(max-width: 767px) {
    .cashflow-desktop-table { display: none !important; }
    .cashflow-mobile-cards { display: block !important; }
}
</style>
@endsection
