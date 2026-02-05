<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowMovimiento extends Model
{
    protected $table = 'cashflow_movimientos';

    protected $fillable = [
        'fecha',
        'subrubro_id',
        'tipo_caja_id',
        'monto',
        'observaciones',
        'usuario_admin_id',
        'referencia_tipo',
        'referencia_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    /**
     * Relación con Subrubro
     */
    public function subrubro(): BelongsTo
    {
        return $this->belongsTo(Subrubro::class);
    }

    /**
     * Relación con Usuario Admin
     */
    public function usuarioAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_admin_id');
    }

    /**
     * Relación con TipoCaja
     */
    public function tipoCaja(): BelongsTo
    {
        return $this->belongsTo(TipoCaja::class);
    }
}
