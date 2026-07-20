<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowMovimiento extends Model
{
    protected $table = 'cashflow_movimientos';

    /**
     * Valores válidos de referencia_tipo (D2). Antes eran strings sueltos
     * y el seeder usaba 'MOVIMIENTO_OPERATIVO' para lo mismo que el
     * servicio marcaba 'CAJA_OPERATIVA', lo que causó movimientos
     * duplicados en el historial. Usar SIEMPRE estas constantes.
     */
    const REF_CAJA        = 'CAJA_OPERATIVA'; // reflejo de una caja validada
    const REF_PAGO_CUOTA  = 'PAGO_CUOTA';     // cobro de cuota directo por admin
    const REF_LIQUIDACION = 'LIQUIDACION';    // pago de liquidación a profesor

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
