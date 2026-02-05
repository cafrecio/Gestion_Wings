<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoDeudaCuota extends Model
{
    protected $table = 'pago_deuda_cuota';

    protected $fillable = [
        'pago_id',
        'deuda_cuota_id',
        'monto_aplicado',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function deudaCuota(): BelongsTo
    {
        return $this->belongsTo(DeudaCuota::class);
    }
}
