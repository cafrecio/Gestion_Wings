<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Un mes cuya generación de cuotas terminó (T40, DU-52).
 */
class GeneracionCuotas extends Model
{
    protected $table = 'generaciones_cuotas';

    protected $fillable = [
        'periodo',
        'terminada_at',
        'creadas',
        'a_revision',
        'ya_existian',
        'sin_plan',
    ];

    protected $casts = [
        'terminada_at' => 'datetime',
    ];

    /**
     * ¿Hay que generar hoy las cuotas del mes?
     *
     * El día 1, siempre que no se hayan generado. Después del 1, solo si el mes
     * anterior sí se generó: eso prueba que el sistema venía funcionando y que
     * lo que faltó es la corrida del día 1. Sin esa condición, el primer día de
     * un servidor nuevo, o el de una base recién instalada a mitad de mes,
     * generaría las cuotas del mes en curso a destiempo.
     */
    public static function faltaGenerar(Carbon $hoy): bool
    {
        if (static::where('periodo', $hoy->format('Y-m'))->exists()) {
            return false;
        }

        if ($hoy->day === 1) {
            return true;
        }

        return static::where('periodo', $hoy->copy()->startOfMonth()->subMonth()->format('Y-m'))->exists();
    }
}
