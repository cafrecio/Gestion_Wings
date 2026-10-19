<?php

namespace App\Services;

use App\Models\{Alumno, AlumnoPlan, DeudaCuota, GrupoPlan};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** T44, Carlos: bajas al mes siguiente; subidas ahora si la cuota no está saldada. */
class CambioPlanService
{
    public function __construct(private PagoCuotaService $cuotas) {}

    public function periodoAplicacion(Alumno $alumno, GrupoPlan $nuevo): string
    {
        $actual = $this->cuotas->obtenerPlanParaPeriodo($alumno->id, now()->format('Y-m'));
        return $actual?->plan_id !== $nuevo->id && $this->seDifiere($alumno, $nuevo)
            ? today()->addMonthNoOverflow()->format('Y-m') : now()->format('Y-m');
    }

    public function seDifiere(Alumno $alumno, GrupoPlan $nuevo): bool
    {
        $actual = $this->cuotas->obtenerPlanParaPeriodo($alumno->id, now()->format('Y-m'))?->plan;
        if ($actual && $nuevo->clases_por_semana < $actual->clases_por_semana) {
            return true;
        }
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', now()->format('Y-m'))->first();
        return $deuda && ($deuda->estado === DeudaCuota::ESTADO_CONDONADA || $deuda->saldo_pendiente <= 0);
    }

    /** Misma base para la pantalla y el ajuste al guardar. */
    public function precioDelMes(Alumno $alumno, GrupoPlan $nuevo, bool $precioBase = false): float
    {
        $periodo = now()->format('Y-m');
        $actual = $this->cuotas->obtenerPlanParaPeriodo($alumno->id, $periodo)?->plan;
        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', $periodo)->first();
        if ($actual && ($actual->id === $nuevo->id || $this->seDifiere($alumno, $nuevo))) {
            return $deuda && !$precioBase ? (float) $deuda->monto_original : $actual->precioParaPeriodo($periodo, $alumno->fecha_alta);
        }
        $precio = $nuevo->precioParaPeriodo($periodo, $alumno->fecha_alta);
        if ($precioBase) return $precio;
        if ($deuda?->porcentaje_alta !== null) {
            return $this->cuotas->aplicarPorcentaje($precio, (float) $deuda->porcentaje_alta);
        }
        [$porcentaje, , $periodoDescuento] = $this->cuotas->calcularReglaPrimerPago($alumno->id, [['periodo' => $periodo, 'monto' => $precio]]);
        return $periodoDescuento === $periodo ? $this->cuotas->aplicarPorcentaje($precio, $porcentaje) : $precio;
    }

    public function cambiar(Alumno $alumno, GrupoPlan $nuevo): AlumnoPlan
    {
        return DB::transaction(function () use ($alumno, $nuevo) {
            $alumno = Alumno::whereKey($alumno->id)->lockForUpdate()->firstOrFail();
            $actual = $this->cuotas->obtenerPlanParaPeriodo($alumno->id, now()->format('Y-m'));
            $desde = $this->seDifiere($alumno, $nuevo) ? today()->addMonthNoOverflow()->startOfMonth() : today();
            $precio = $this->precioDelMes($alumno, $nuevo);

            // Una elección futura todavía no empezó: se reemplaza en vez de duplicarla.
            $pendiente = $alumno->planes()->where('fecha_desde', '>', today())->where('activo', true)->lockForUpdate()->first();
            if ($pendiente && $desde->isFuture()) {
                $pendiente->update(['plan_id' => $nuevo->id]);
                return $pendiente;
            }
            if ($pendiente) {
                $pendiente->delete();
            }
            $asignacion = AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $nuevo->id,
                'fecha_desde' => $desde, 'activo' => true]);
            $actual?->update(['fecha_hasta' => $desde->copy()->subDay(), 'activo' => null]);

            if ($desde->isToday()) {
                $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', now()->format('Y-m'))
                    ->whereIn('estado', [DeudaCuota::ESTADO_PENDIENTE, DeudaCuota::ESTADO_AJUSTADA])->lockForUpdate()->first();
                if ($deuda) {
                    if ((float) $deuda->monto_pagado > $precio) {
                        throw ValidationException::withMessages(['plan_id' => 'El nuevo importe es menor que lo ya pagado. Revisá la cuota antes de cambiar el plan.']);
                    }
                    $deuda->update(['monto_original' => $precio]);
                }
            }
            return $asignacion;
        });
    }
}
