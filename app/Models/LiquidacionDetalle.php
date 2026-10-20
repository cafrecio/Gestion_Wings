<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionDetalle extends Model
{
    const TIPO_CLASE = 'clase';
    const TIPO_ALUMNO = 'alumno';
    const ORIGEN_ATRASADO = 'atrasado';
    const DECISION_PENDIENTE = 'PENDIENTE';
    const DECISION_APROBADA = 'APROBADA';
    const DECISION_RECHAZADA = 'RECHAZADA';

    protected $table = 'liquidacion_detalles';

    protected $fillable = [
        'liquidacion_id',
        'tipo_referencia',
        'referencia_id',
        'monto',
        'minutos',
        'descripcion',
        'pago_id', 'deuda_cuota_id', 'periodo_cuota', 'fecha_cobro', 'base_cuota',
        'origen_cuota', 'decision_atrasada', 'decision_por', 'decision_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'referencia_id' => 'integer',
        'minutos' => 'integer',
        'fecha_cobro' => 'date', 'base_cuota' => 'decimal:2', 'decision_at' => 'datetime',
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

    public function decisionAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_por');
    }

    public function incluidaEnRecibo(): bool
    {
        return $this->origen_cuota !== self::ORIGEN_ATRASADO
            || $this->decision_atrasada === self::DECISION_APROBADA;
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
