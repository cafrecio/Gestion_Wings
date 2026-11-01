<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCaja extends Model
{
    use \App\Models\Concerns\GuardaHistorialAtomico;

    /** T96: la cuenta del cajón del mostrador. Carlos, 01/11/2026: no se elige ni se renombra. */
    public const NOMBRE_EFECTIVO = 'Efectivo';

    protected $table = 'tipos_caja';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'descripcion',
        'permite_descubierto',
        'saldo_inicial',
        'activo',
    ];

    protected $casts = [
        'activo'              => 'boolean',
        'permite_descubierto' => 'boolean',
        'saldo_inicial'       => 'decimal:2',
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

    /** Cualquier movimiento cuenta, incluso uno operativo cancelado. */
    public function tieneMovimientos(): bool
    {
        return $this->movimientosOperativos()->exists()
            || $this->cashflowMovimientos()->exists();
    }
}
