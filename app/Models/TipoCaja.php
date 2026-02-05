<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCaja extends Model
{
    protected $table = 'tipos_caja';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con MovimientosOperativos
     */
    public function movimientosOperativos(): HasMany
    {
        return $this->hasMany(MovimientoOperativo::class);
    }

    /**
     * Relación con CashflowMovimientos
     */
    public function cashflowMovimientos(): HasMany
    {
        return $this->hasMany(CashflowMovimiento::class);
    }
}
