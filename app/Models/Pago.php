<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    protected $fillable = [
        'alumno_id',
        'plan_id',
        'regla_primer_pago_id',
        'mes',
        'anio',
        'monto_base',
        'porcentaje_aplicado',
        'monto_final',
        'forma_pago_id',
        'fecha_pago',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'monto_base' => 'decimal:2',
        'porcentaje_aplicado' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'fecha_pago' => 'date',
        'estado' => 'string',
    ];

    /**
     * IMPORTANTE: Los montos son INMUTABLES
     * No se recalculan nunca después de la creación
     */
    protected static function boot()
    {
        parent::boot();

        // Prevenir modificaciones de montos
        static::updating(function ($pago) {
            if ($pago->isDirty(['monto_base', 'porcentaje_aplicado', 'monto_final'])) {
                throw new \Exception('Los montos de un pago no pueden modificarse una vez creados.');
            }
        });
    }

    /**
     * Relación con Alumno
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * Relación con GrupoPlan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(GrupoPlan::class, 'plan_id');
    }

    /**
     * Relación con ReglaPrimerPago (nullable)
     */
    public function reglaPrimerPago(): BelongsTo
    {
        return $this->belongsTo(ReglaPrimerPago::class, 'regla_primer_pago_id');
    }

    /**
     * Relación con FormaPago
     */
    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }

    /**
     * Relación con DeudaCuotas (a través de la tabla pivote)
     */
    public function deudasCuota(): BelongsToMany
    {
        return $this->belongsToMany(DeudaCuota::class, 'pago_deuda_cuota')
            ->withPivot('monto_aplicado')
            ->withTimestamps();
    }

    /**
     * Relación directa con la tabla pivote
     */
    public function pagosDeuda(): HasMany
    {
        return $this->hasMany(PagoDeudaCuota::class);
    }
}
