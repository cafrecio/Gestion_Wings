<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionDetalle extends Model
{
    const TIPO_CLASE = 'clase';
    const TIPO_ALUMNO = 'alumno';

    protected $table = 'liquidacion_detalles';

    protected $fillable = [
        'liquidacion_id',
        'tipo_referencia',
        'referencia_id',
        'monto',
        'descripcion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'referencia_id' => 'integer',
    ];

    /**
     * Boot del modelo para prevenir modificación si liquidación está cerrada
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($detalle) {
            $liquidacion = Liquidacion::find($detalle->liquidacion_id);
            if ($liquidacion && $liquidacion->estaCerrada()) {
                throw new \Exception('No se pueden agregar detalles a una liquidación cerrada.');
            }
        });

        static::updating(function ($detalle) {
            if ($detalle->liquidacion->estaCerrada()) {
                throw new \Exception('No se pueden modificar detalles de una liquidación cerrada.');
            }
        });

        static::deleting(function ($detalle) {
            if ($detalle->liquidacion->estaCerrada()) {
                throw new \Exception('No se pueden eliminar detalles de una liquidación cerrada.');
            }
        });
    }

    /**
     * Relación con Liquidacion
     */
    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class);
    }

    /**
     * Obtener la entidad referenciada (Clase o Alumno)
     */
    public function getReferencia()
    {
        if ($this->tipo_referencia === self::TIPO_CLASE) {
            return Clase::find($this->referencia_id);
        }

        if ($this->tipo_referencia === self::TIPO_ALUMNO) {
            return Alumno::find($this->referencia_id);
        }

        return null;
    }
}
