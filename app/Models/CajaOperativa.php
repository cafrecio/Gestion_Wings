<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaOperativa extends Model
{
    protected $table = 'cajas_operativas';

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
    ];

    protected $casts = [
        'apertura_at' => 'datetime',
        'cierre_at' => 'datetime',
        'validada_at' => 'datetime',
        'cerrada_por_admin' => 'boolean',
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
     * Relación con Movimientos Operativos
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoOperativo::class, 'caja_operativa_id');
    }
}
