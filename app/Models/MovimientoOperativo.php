<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoOperativo extends Model
{
    protected $table = 'movimientos_operativos';

    // Estados válidos (coinciden con el ENUM de la columna estado). D6.
    const ESTADO_ACTIVO    = 'ACTIVO';
    const ESTADO_CANCELADO = 'CANCELADO';

    protected $fillable = [
        'reporte_deporte_id',
        'reporte_clasificacion',
        'reporte_tipo',
        'caja_operativa_id',
        'fecha',
        'tipo_caja_id',
        'subrubro_id',
        'monto',
        'observaciones',
        'usuario_id',
        'alumno_id',
        'pago_id',
        'estado',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'monto'  => 'decimal:2',
        'estado' => 'string',
        'verificado_at' => 'datetime',
        'verificado_por' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $movimiento) {
            if ($movimiento->isDirty($movimiento->getFillable())) {
                $movimiento->verificado_at = null;
                $movimiento->verificado_por = null;
                $movimiento->verificacion_huella = null;
            }
        });
    }

    public function huellaVerificacion(): string
    {
        // Incluye todo el contenido del movimiento, no la marca ni updated_at.
        // También detecta actualizaciones masivas que no disparan eventos Eloquent.
        $contenido = [];
        foreach ($this->getFillable() as $campo) {
            $valor = $this->getOriginal($campo);
            $contenido[$campo] = $valor instanceof \DateTimeInterface
                ? $valor->format('Y-m-d')
                : ($valor === null ? null : (string) $valor);
        }

        return hash('sha256', json_encode($contenido, JSON_THROW_ON_ERROR));
    }

    public function estaVerificado(): bool
    {
        return $this->verificado_at !== null
            && is_string($this->verificacion_huella)
            && hash_equals($this->verificacion_huella, $this->huellaVerificacion());
    }

    /**
     * Relación con CajaOperativa
     */
    public function cajaOperativa(): BelongsTo
    {
        return $this->belongsTo(CajaOperativa::class, 'caja_operativa_id');
    }

    /**
     * Relación con TipoCaja
     */
    public function tipoCaja(): BelongsTo
    {
        return $this->belongsTo(TipoCaja::class);
    }

    /**
     * Relación con Subrubro
     */
    public function subrubro(): BelongsTo
    {
        return $this->belongsTo(Subrubro::class);
    }

    /**
     * Relación con Usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con Alumno (nullable — solo en cobros de cuota)
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * Relación con Pago (nullable — solo en cobros de cuota registrados con el nuevo flujo)
     */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'ACTIVO');
    }
}
