<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoOperativo extends Model
{
    protected $table = 'movimientos_operativos';

    protected $fillable = [
        'caja_operativa_id',
        'fecha',
        'tipo_caja_id',
        'subrubro_id',
        'monto',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

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
}
