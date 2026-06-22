@extends('layouts.ds-app')

@section('title', 'Cancelar cobro – Wings')
@section('module-title', 'Cancelar cobro')

@section('content')

@php
$alumnoNombre = $movimiento->alumno
    ? $movimiento->alumno->apellido . ', ' . $movimiento->alumno->nombre
    : '–';
@endphp

<div class="filtros-card mb-4">
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem;">
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Alumno</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $alumnoNombre }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Fecha</p>
            <p style="font-size:0.85rem; font-weight:600; color:var(--color-text);">{{ $movimiento->fecha?->format('d/m/Y') ?? '–' }}</p>
        </div>
        <div>
            <p style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:600; color:var(--color-text-muted);">Monto</p>
            <p style="font-size:0.85rem; font-weight:700; color:var(--color-danger);">${{ number_format($movimiento->monto, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('web.caja.movimientos.cancelar.store', [$caja->id, $movimiento->id]) }}">
    @csrf
    <div class="filtros-card mb-4">
        <label style="display:block; font-size:0.75rem; font-weight:600; color:var(--color-text-muted); margin-bottom:6px;">
            Motivo de la cancelación <span class="form-required">*</span>
        </label>
        <textarea name="motivo" required maxlength="500" rows="4"
                  class="w-full px-4 py-2.5 text-sm wings-input"
                  style="resize:vertical;"
                  placeholder="Describí el motivo...">{{ old('motivo') }}</textarea>
        @error('motivo')
            <p style="font-size:0.75rem; color:var(--color-danger); margin-top:4px;">{{ $message }}</p>
        @enderror
        <p style="font-size:0.75rem; color:var(--color-text-muted); margin-top:8px;">
            La deuda del alumno volverá a estado pendiente.
        </p>
    </div>

    <div class="filtros-actions" style="justify-content:flex-end;">
        <a href="{{ route('web.caja.detalle', $caja->id) }}"
           class="ds-btn" style="background:var(--color-btn-secondary); color:var(--color-surface);">Volver</a>
        <button type="submit"
                class="ds-btn" style="background:var(--color-danger); color:#fff;">Cancelar</button>
    </div>
</form>

@endsection
