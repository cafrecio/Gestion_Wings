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
    const ESTADO_CANCELADA = 'CANCELADA';

    const ESTADO_PAGO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PAGO_PAGADA = 'PAGADA';

    protected $table = 'liquidaciones';

    protected $fillable = [
        'profesor_id',
        'mes',
        'anio',
        'tipo',
        'porcentaje_comision_aplicado',
        'valor_hora_aplicado',
        'total_calculado',
        'estado',
        'usuario_cancelacion_id',
        'cancelada_at',
        'motivo_cancelacion',
        'reemplazada_por_id',
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
        'porcentaje_comision_aplicado' => 'decimal:2',
        'valor_hora_aplicado' => 'decimal:2',
        'total_calculado' => 'decimal:2',
        'cancelada_at' => 'datetime',
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
     * Campos que se pueden modificar al cancelar una liquidación cerrada no pagada (FIN-12).
     */
    protected static array $camposCancelacionPermitidos = [
        'estado',
        'usuario_cancelacion_id',
        'cancelada_at',
        'motivo_cancelacion',
    ];

    /**
     * Boot del modelo para prevenir modificación de liquidaciones cerradas
     * (excepto campos de pago o cancelación administrativa si no está pagada)
     * y liquidaciones canceladas (excepto asignación de liquidación de reemplazo).
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($liquidacion) {
            $original = $liquidacion->getOriginal();

            if ($original['estado'] === self::ESTADO_CERRADA) {
                $dirty = $liquidacion->getDirty();
                $camposModificados = array_keys($dirty);

                $soloModificaPago = empty(array_diff($camposModificados, self::$camposPagoPermitidos));

                $esCancelacion = ($liquidacion->estado === self::ESTADO_CANCELADA)
                    && $original['estado_pago'] === self::ESTADO_PAGO_PENDIENTE
                    && empty(array_diff($camposModificados, self::$camposCancelacionPermitidos));

                if (!$soloModificaPago && !$esCancelacion) {
                    throw new \Exception('No se puede modificar una liquidación cerrada (solo se permite registrar el pago o cancelar si no está pagada).');
                }
            }

            if ($original['estado'] === self::ESTADO_CANCELADA) {
                $dirty = $liquidacion->getDirty();
                $camposModificados = array_keys($dirty);
                $soloAsignaReemplazo = empty(array_diff($camposModificados, ['reemplazada_por_id']));

                if (!$soloAsignaReemplazo) {
                    throw new \Exception('No se puede modificar una liquidación cancelada.');
                }
            }
        });

        static::deleting(function ($liquidacion) {
            if ($liquidacion->estado === self::ESTADO_CERRADA || $liquidacion->estado === self::ESTADO_CANCELADA) {
                throw new \Exception('No se puede eliminar una liquidación cerrada o cancelada.');
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
     * Verificar si la liquidación está cancelada (FIN-12)
     */
    public function estaCancelada(): bool
    {
        return $this->estado === self::ESTADO_CANCELADA;
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
     * Scope para liquidaciones canceladas (FIN-12)
     */
    public function scopeCanceladas($query)
    {
        return $query->where('estado', self::ESTADO_CANCELADA);
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

    /**
     * Relación con el usuario admin que canceló la liquidación (FIN-12)
     */
    public function usuarioCancelacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cancelacion_id');
    }

    /**
     * Relación con la liquidación que reemplazó a esta si fue cancelada (FIN-12)
     */
    public function reemplazadaPor(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class, 'reemplazada_por_id');
    }

    /**
     * Relación con las liquidaciones canceladas a las que esta liquidación reemplazó (FIN-12)
     */
    public function liquidacionesCanceladas(): HasMany
    {
        return $this->hasMany(Liquidacion::class, 'reemplazada_por_id');
    }
}
