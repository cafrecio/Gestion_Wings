@extends('layouts.panel')

@section('title', 'Panel Administración – Wings')
@php $title = 'Dashboard'; @endphp

@section('panel-content')

{{-- Stats rápidos --}}
<div class="ds-grid-3 mb-6">

    <div class="ds-card">
        <div class="ds-card__body">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--color-text-muted)">Alumnos</p>
            <p class="text-3xl font-bold mt-1" style="color:var(--color-text)">{{ $totalAlumnos }}</p>
        </div>
    </div>

    <div class="ds-card">
        <div class="ds-card__body">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--color-text-muted)">Deportes</p>
            <p class="text-3xl font-bold mt-1" style="color:var(--color-text)">{{ $deportes->count() }}</p>
        </div>
    </div>

    <div class="ds-card">
        <div class="ds-card__body">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--color-text-muted)">Rubros</p>
            <p class="text-3xl font-bold mt-1" style="color:var(--color-text)">{{ $rubros->count() }}</p>
        </div>
    </div>

</div>

<div class="ds-grid-2">

    {{-- Deportes --}}
    <div class="ds-card">
        <div class="ds-card__header">
            <span class="ds-card__title">Deportes</span>
        </div>
        <div class="ds-card__body p-0">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="text-right">Alumnos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deportes as $deporte)
                        <tr>
                            <td>{{ $deporte->nombre }}</td>
                            <td class="text-right">{{ $deporte->alumnos_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center" style="color:var(--color-text-muted)">Sin deportes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rubros y subrubros --}}
    <div class="ds-card">
        <div class="ds-card__header">
            <span class="ds-card__title">Rubros y Subrubros</span>
        </div>
        <div class="ds-card__body p-0">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>Rubro</th>
                        <th>Subrubro</th>
                        <th>Tipo</th>
                        <th>Permitido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rubros as $rubro)
                        @if($rubro->subrubros->isEmpty())
                            <tr>
                                <td class="font-medium">{{ $rubro->nombre }}</td>
                                <td style="color:var(--color-text-muted)">—</td>
                                <td>
                                    <span class="ds-badge {{ $rubro->tipo === 'INGRESO' ? 'ds-badge--success' : 'ds-badge--danger' }}">
                                        {{ $rubro->tipo }}
                                    </span>
                                </td>
                                <td>—</td>
                            </tr>
                        @else
                            @foreach($rubro->subrubros as $i => $sub)
                                <tr>
                                    @if($i === 0)
                                        <td class="font-medium" rowspan="{{ $rubro->subrubros->count() }}">{{ $rubro->nombre }}</td>
                                    @endif
                                    <td>{{ $sub->nombre }}</td>
                                    <td>
                                        @if($i === 0)
                                        <span class="ds-badge {{ $rubro->tipo === 'INGRESO' ? 'ds-badge--success' : 'ds-badge--danger' }}">
                                            {{ $rubro->tipo }}
                                        </span>
                                        @endif
                                    </td>
                                    <td style="color:var(--color-text-muted);font-size:12px">{{ $sub->permitido_para }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
