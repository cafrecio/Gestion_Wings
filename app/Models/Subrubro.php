<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subrubro extends Model
{
    protected $fillable = [
        'rubro_id',
        'nombre',
        'permitido_para',
        'afecta_caja',
        'es_reservado_sistema',
    ];

    protected $casts = [
        'afecta_caja' => 'boolean',
        'es_reservado_sistema' => 'boolean',
    ];

    /**
     * Relación con Rubro
     */
    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

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
