<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormaPago extends Model
{
    protected $table = 'formas_pago';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con Pagos
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'forma_pago_id');
    }
}
