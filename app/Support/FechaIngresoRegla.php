<?php

namespace App\Support;

use Carbon\Carbon;

class FechaIngresoRegla
{
    /**
     * Límite máximo permitido para la fecha de ingreso:
     * Último día del mes siguiente a la fecha de referencia (por defecto Carbon::now()).
     */
    public static function limiteMaximo(?Carbon $hoy = null): Carbon
    {
        $base = $hoy ? $hoy->copy() : Carbon::now();

        return $base->startOfMonth()->addMonth()->endOfMonth();
    }

    /**
     * Determina si la fecha de ingreso está dentro del límite permitido (pasada, hoy o hasta fin del mes siguiente).
     */
    public static function esValida(Carbon|string $fecha, ?Carbon $hoy = null): bool
    {
        try {
            $f = $fecha instanceof Carbon ? $fecha->copy()->startOfDay() : Carbon::parse($fecha)->startOfDay();
            $limite = self::limiteMaximo($hoy)->startOfDay();

            return $f->lte($limite);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Mensaje de validación en castellano con el límite exacto formateado en d/m/Y.
     */
    public static function mensajeError(?Carbon $hoy = null): string
    {
        $limite = self::limiteMaximo($hoy);

        return 'La fecha de ingreso no puede ser posterior al ' . $limite->format('d/m/Y') . '.';
    }
}
