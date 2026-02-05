<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReglaPrimerPago extends Model
{
    protected $table = 'reglas_primer_pago';

    protected $fillable = [
        'nombre',
        'dia_desde',
        'dia_hasta',
        'porcentaje',
        'activo',
    ];

    protected $casts = [
        'dia_desde' => 'integer',
        'dia_hasta' => 'integer',
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Relación con Pagos
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'regla_primer_pago_id');
    }

    /**
     * Obtener regla aplicable para un día específico
     */
    public static function obtenerReglaPorDia(int $dia)
    {
        return self::where('activo', true)
            ->where('dia_desde', '<=', $dia)
            ->where('dia_hasta', '>=', $dia)
            ->get();
    }
}
