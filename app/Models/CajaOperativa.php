<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaOperativa extends Model
{
    protected $table = 'cajas_operativas';

    // Estados válidos (coinciden con el ENUM de la columna estado). D6.
    const ESTADO_ABIERTA   = 'ABIERTA';
    const ESTADO_CERRADA   = 'CERRADA';
    const ESTADO_VALIDADA  = 'VALIDADA';
    const ESTADO_RECHAZADA = 'RECHAZADA';

    protected $fillable = [
        'usuario_operativo_id',
        'apertura_at',
        'cierre_at',
        'estado',
        'cerrada_por_admin',
        'usuario_admin_cierre_id',
        'usuario_admin_validacion_id',
        'validada_at',
        'motivo_rechazo',
        'tipo_caja_efectivo_id',
        'caja_origen_id',
        'efectivo_heredado',
        'efectivo_inicial',
        'motivo_apertura',
        'efectivo_esperado',
        'efectivo_contado',
        'diferencia_efectivo',
        'cambio_retenido',
        'efectivo_retirado',
        'usuario_cierre_id',
        'usuario_apertura_id',
    ];

    protected $casts = [
        'apertura_at' => 'datetime',
        'cierre_at' => 'datetime',
        'validada_at' => 'datetime',
        'cerrada_por_admin' => 'boolean',
        'efectivo_heredado' => 'decimal:2',
        'efectivo_inicial' => 'decimal:2',
        'efectivo_esperado' => 'decimal:2',
        'efectivo_contado' => 'decimal:2',
        'diferencia_efectivo' => 'decimal:2',
        'cambio_retenido' => 'decimal:2',
        'efectivo_retirado' => 'decimal:2',
    ];

    /**
     * Relación con Usuario Operativo
     */
    public function usuarioOperativo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_operativo_id');
    }

    /**
     * Relación con Usuario Admin que cerró la caja
     */
    public function usuarioAdminCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_admin_cierre_id');
    }

    /**
     * Relación con Usuario Admin que validó la caja
     */
    public function usuarioAdminValidacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_admin_validacion_id');
    }

    /**
     * Alias de usuarioOperativo para compatibilidad
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->usuarioOperativo();
    }

    /**
     * Relación con Movimientos Operativos
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoOperativo::class, 'caja_operativa_id');
    }
}
