<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeudaCuota extends Model
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PAGADA = 'PAGADA';
    const ESTADO_CONDONADA = 'CONDONADA';
    const ESTADO_AJUSTADA = 'AJUSTADA';

    protected $table = 'deuda_cuotas';

    protected $fillable = [
        'alumno_id',
        'periodo',
        'monto_original',
        'monto_pagado',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
    ];

    /**
     * Relación con Alumno
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * Relación con Pagos (a través de la tabla pivote)
     */
    public function pagos(): BelongsToMany
    {
        return $this->belongsToMany(Pago::class, 'pago_deuda_cuota')
            ->withPivot('monto_aplicado')
            ->withTimestamps();
    }

    /**
     * Relación directa con la tabla pivote
     */
    public function pagosDeuda(): HasMany
    {
        return $this->hasMany(PagoDeudaCuota::class);
    }

    /**
     * Obtener el saldo pendiente
     */
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, (float) $this->monto_original - (float) $this->monto_pagado);
    }

    /**
     * Verificar si la deuda está completamente pagada
     */
    public function estaPagada(): bool
    {
        return $this->estado === self::ESTADO_PAGADA ||
               (float) $this->monto_pagado >= (float) $this->monto_original;
    }
}
