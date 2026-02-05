<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liquidacion extends Model
{
    const TIPO_HORA = 'HORA';
    const TIPO_COMISION = 'COMISION';

    const ESTADO_ABIERTA = 'ABIERTA';
    const ESTADO_CERRADA = 'CERRADA';

    const ESTADO_PAGO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PAGO_PAGADA = 'PAGADA';

    protected $table = 'liquidaciones';

    protected $fillable = [
        'profesor_id',
        'mes',
        'anio',
        'tipo',
        'total_calculado',
        'estado',
        'estado_pago',
        'pagada_at',
        'pagada_por_admin_id',
        'pagada_fecha',
        'pagada_tipo_caja_id',
        'pagada_subrubro_id',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'total_calculado' => 'decimal:2',
        'pagada_at' => 'datetime',
        'pagada_fecha' => 'date',
    ];

    /**
     * Campos que se pueden modificar aunque la liquidación esté cerrada.
     * Son los campos relacionados con el pago.
     */
    protected static array $camposPagoPermitidos = [
        'estado_pago',
        'pagada_at',
        'pagada_por_admin_id',
        'pagada_fecha',
        'pagada_tipo_caja_id',
        'pagada_subrubro_id',
    ];

    /**
     * Boot del modelo para prevenir modificación de liquidaciones cerradas
     * (excepto campos de pago)
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($liquidacion) {
            $original = $liquidacion->getOriginal();

            if ($original['estado'] === self::ESTADO_CERRADA) {
                // Verificar si solo se están modificando campos de pago (permitido)
                $dirty = $liquidacion->getDirty();
                $camposModificados = array_keys($dirty);
                $soloModificaPago = empty(array_diff($camposModificados, self::$camposPagoPermitidos));

                if (!$soloModificaPago) {
                    throw new \Exception('No se puede modificar una liquidación cerrada (solo se permite registrar el pago).');
                }
            }
        });

        static::deleting(function ($liquidacion) {
            if ($liquidacion->estado === self::ESTADO_CERRADA) {
                throw new \Exception('No se puede eliminar una liquidación cerrada.');
            }
        });
    }

    /**
     * Relación con Profesor
     */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class);
    }

    /**
     * Relación con Detalles
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(LiquidacionDetalle::class);
    }

    /**
     * Verificar si la liquidación está abierta
     */
    public function estaAbierta(): bool
    {
        return $this->estado === self::ESTADO_ABIERTA;
    }

    /**
     * Verificar si la liquidación está cerrada
     */
    public function estaCerrada(): bool
    {
        return $this->estado === self::ESTADO_CERRADA;
    }

    /**
     * Scope para liquidaciones abiertas
     */
    public function scopeAbiertas($query)
    {
        return $query->where('estado', self::ESTADO_ABIERTA);
    }

    /**
     * Scope para liquidaciones cerradas
     */
    public function scopeCerradas($query)
    {
        return $query->where('estado', self::ESTADO_CERRADA);
    }

    /**
     * Scope para filtrar por mes y año
     */
    public function scopePeriodo($query, int $mes, int $anio)
    {
        return $query->where('mes', $mes)->where('anio', $anio);
    }

    /**
     * Verificar si la liquidación está pagada
     */
    public function estaPagada(): bool
    {
        return $this->estado_pago === self::ESTADO_PAGO_PAGADA;
    }

    /**
     * Scope para liquidaciones pendientes de pago
     */
    public function scopePendientesPago($query)
    {
        return $query->where('estado_pago', self::ESTADO_PAGO_PENDIENTE);
    }

    /**
     * Scope para liquidaciones pagadas
     */
    public function scopePagadas($query)
    {
        return $query->where('estado_pago', self::ESTADO_PAGO_PAGADA);
    }

    /**
     * Relación con el admin que pagó
     */
    public function pagadaPorAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagada_por_admin_id');
    }

    /**
     * Relación con el tipo de caja del pago
     */
    public function pagadaTipoCaja(): BelongsTo
    {
        return $this->belongsTo(TipoCaja::class, 'pagada_tipo_caja_id');
    }

    /**
     * Relación con el subrubro del pago
     */
    public function pagadaSubrubro(): BelongsTo
    {
        return $this->belongsTo(Subrubro::class, 'pagada_subrubro_id');
    }
}
